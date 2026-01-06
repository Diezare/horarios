<?php
// app/views/horarios-paisagem.php
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

// ---------------- Segurança / logging ----------------
$SEG_LOG = __DIR__ . '/../../logs/seguranca.log';
if (!function_exists('logSecurity')) {
    function logSecurity($msg) {
        global $SEG_LOG;
        $meta = [
            'ts'    => date('c'),
            'ip'    => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'ua'    => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'qs'    => $_SERVER['QUERY_STRING'] ?? '',
            'script'=> basename($_SERVER['SCRIPT_NAME'] ?? '')
        ];
        $entry = '['.date('d-M-Y H:i:s T').'] [SEGURANCA] '.$msg.' | META='.json_encode($meta, JSON_UNESCAPED_UNICODE).PHP_EOL;
        @file_put_contents($SEG_LOG, $entry, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Encerra a requisição emitindo texto puro. Evita qualquer saída de PDF.
 */
function abortClient($msg = 'Parâmetros inválidos') {
    while (ob_get_level() > 0) @ob_end_clean();
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit;
}

// ---------------- Validação rígida da query string ----------------
// Permitimos somente estes parâmetros
$allowed = ['id_turma','id_ano_letivo','id_nivel_ensino','id_turno'];
$rawQuery = $_SERVER['QUERY_STRING'] ?? '';
parse_str($rawQuery, $receivedParams);

// Rejeita parâmetros inesperados
$receivedKeys = array_keys($receivedParams);
$extra = array_diff($receivedKeys, $allowed);
if (!empty($extra)) {
    logSecurity('Parâmetros inesperados em horarios-paisagem: ' . implode(', ', $extra) . ' | raw_qs=' . $rawQuery);
    abortClient();
}

// Normalização e validação: aceitar somente inteiros não-negativos (>=0) quando presentes.
// id_turma e id_ano_letivo e id_nivel_ensino e id_turno — 0 ou inteiro positivo.
// id_turma >0 significa relatório de uma turma específica.
// id_ano_letivo >0 geralmente obrigatório quando não há id_turma.
$canonical = [];

foreach ($receivedParams as $k => $v) {
    if (!is_scalar($v)) {
        logSecurity("Parâmetro não escalar em horarios-paisagem: {$k}");
        abortClient();
    }
    // aceitar apenas dígitos (0 permitido)
    if (!preg_match('/^\d+$/', (string)$v)) {
        logSecurity("Parâmetro não numérico permitido em horarios-paisagem: {$k} raw=" . var_export($v, true) . " | raw_qs={$rawQuery}");
        abortClient();
    }
    $ival = (int)$v;
    // não aceitar negativos (defensivo)
    if ($ival < 0) {
        logSecurity("Parâmetro negativo rejeitado em horarios-paisagem: {$k} raw=" . var_export($v, true) . " | raw_qs={$rawQuery}");
        abortClient();
    }
    $canonical[$k] = $ival;
}

// Ordenar para comparar independentemente da ordem
ksort($canonical);

// Normalizar receivedParams em tipos inteiros para comparar
$normalized_received_array = [];
foreach ($receivedParams as $k => $v) {
    $normalized_received_array[$k] = (int)$v;
}
ksort($normalized_received_array);

if ($canonical !== $normalized_received_array) {
    logSecurity("Query string não canônica em horarios-paisagem: expected_array=" . json_encode($canonical) . " got_array=" . json_encode($normalized_received_array) . " | full_raw={$rawQuery}");
    abortClient();
}

// Atribuir variáveis seguras com defaults
$id_turma        = $canonical['id_turma'] ?? 0;
$id_ano_letivo   = $canonical['id_ano_letivo'] ?? 0;
$id_nivel_ensino = $canonical['id_nivel_ensino'] ?? 0;
$id_turno        = $canonical['id_turno'] ?? 0;

// ---------------- Existence checks (novos) ----------------
// 1) Se id_turma informado, verificar existência e consistência
$turmaRow = null;
if ($id_turma > 0) {
    try {
        $st = $pdo->prepare("SELECT id_turma, id_ano_letivo, id_serie, id_turno, intervalos_positions, nome_turma FROM turma WHERE id_turma = :tid LIMIT 1");
        $st->execute([':tid' => $id_turma]);
        $turmaRow = $st->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        logSecurity("Erro SQL buscando turma em horarios-paisagem: " . $e->getMessage() . " | tid={$id_turma}");
        abortClient();
    }

    if (!$turmaRow) {
        logSecurity("id_turma inexistente em horarios-paisagem: id_turma={$id_turma} | raw_qs={$rawQuery}");
        abortClient();
    }

    // Se id_ano_letivo foi enviado, verificar consistência com a turma
    if ($id_ano_letivo > 0 && (int)$turmaRow['id_ano_letivo'] !== $id_ano_letivo) {
        logSecurity("Inconsistência id_turma vs id_ano_letivo em horarios-paisagem: id_turma={$id_turma} turma_ano={$turmaRow['id_ano_letivo']} request_ano={$id_ano_letivo} | raw_qs={$rawQuery}");
        abortClient();
    }

    // Se id_nivel_ensino foi enviado, verificar que a série da turma pertence ao nivel
    if ($id_nivel_ensino > 0) {
        try {
            $st = $pdo->prepare("SELECT s.id_serie FROM serie s WHERE s.id_serie = :sid AND s.id_nivel_ensino = :nid LIMIT 1");
            $st->execute([':sid' => $turmaRow['id_serie'], ':nid' => $id_nivel_ensino]);
            $ok = $st->fetch(PDO::FETCH_ASSOC);
            if (!$ok) {
                logSecurity("Inconsistência id_turma vs id_nivel_ensino em horarios-paisagem: id_turma={$id_turma} serie={$turmaRow['id_serie']} request_nivel={$id_nivel_ensino} | raw_qs={$rawQuery}");
                abortClient();
            }
        } catch (Throwable $e) {
            logSecurity("Erro SQL verificando nivel da serie em horarios-paisagem: " . $e->getMessage());
            abortClient();
        }
    }

    // Se id_turno informado, verificar consistência com a turma
    if ($id_turno > 0 && (int)$turmaRow['id_turno'] !== $id_turno) {
        logSecurity("Inconsistência id_turma vs id_turno em horarios-paisagem: id_turma={$id_turma} turma_turno={$turmaRow['id_turno']} request_turno={$id_turno} | raw_qs={$rawQuery}");
        abortClient();
    }
}

// 2) Se id_turma não informado, mas id_ano_letivo informado, verificar existência do ano_letivo
if ($id_turma <= 0) {
    if ($id_ano_letivo <= 0) {
        logSecurity("Nenhum id_turma e nenhum id_ano_letivo adequado em horarios-paisagem | raw_qs={$rawQuery}");
        abortClient('Nenhum ID de turma e nenhum Ano Letivo informado. Selecione pelo menos o Ano Letivo.');
    }
    try {
        $st = $pdo->prepare("SELECT id_ano_letivo FROM ano_letivo WHERE id_ano_letivo = :aid LIMIT 1");
        $st->execute([':aid' => $id_ano_letivo]);
        if (!$st->fetch(PDO::FETCH_ASSOC)) {
            logSecurity("id_ano_letivo inexistente em horarios-paisagem: id_ano_letivo={$id_ano_letivo} | raw_qs={$rawQuery}");
            abortClient();
        }
    } catch (Throwable $e) {
        logSecurity("Erro SQL buscando ano_letivo em horarios-paisagem: " . $e->getMessage());
        abortClient();
    }

    // Se id_nivel_ensino informado, verificar existência
    if ($id_nivel_ensino > 0) {
        try {
            $st = $pdo->prepare("SELECT id_nivel_ensino FROM nivel_ensino WHERE id_nivel_ensino = :nid LIMIT 1");
            $st->execute([':nid' => $id_nivel_ensino]);
            if (!$st->fetch(PDO::FETCH_ASSOC)) {
                logSecurity("id_nivel_ensino inexistente em horarios-paisagem: id_nivel_ensino={$id_nivel_ensino} | raw_qs={$rawQuery}");
                abortClient();
            }
        } catch (Throwable $e) {
            logSecurity("Erro SQL buscando nivel_ensino em horarios-paisagem: " . $e->getMessage());
            abortClient();
        }
    }

    // Se id_turno informado, verificar existência
    if ($id_turno > 0) {
        try {
            $st = $pdo->prepare("SELECT id_turno FROM turno WHERE id_turno = :tid LIMIT 1");
            $st->execute([':tid' => $id_turno]);
            if (!$st->fetch(PDO::FETCH_ASSOC)) {
                logSecurity("id_turno inexistente em horarios-paisagem: id_turno={$id_turno} | raw_qs={$rawQuery}");
                abortClient();
            }
        } catch (Throwable $e) {
            logSecurity("Erro SQL buscando turno em horarios-paisagem: " . $e->getMessage());
            abortClient();
        }
    }
}

// ---------------- Proteção do logo ----------------
$LOGO_SIZE_MM = 15; // tamanho da logo (largura=altura) em mm
$LOGO_GAP_MM  = 5;  // espaço entre logo e nome da instituição em mm

$logoPath = null;
try {
    if (!isset($inst)) {
        $stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
        $inst = $stmtInst->fetch(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    logSecurity("Erro SQL buscando instituicao em horarios-paisagem: " . $e->getMessage());
    $inst = null;
}
if ($inst && !empty($inst['imagem_instituicao'])) {
    $candidate = basename($inst['imagem_instituicao']);
    $fullLogo = LOGO_PATH . '/' . $candidate;
    if (file_exists($fullLogo) && is_file($fullLogo) && strpos(realpath($fullLogo), realpath(LOGO_PATH)) === 0) {
        $logoPath = $fullLogo;
    } else {
        logSecurity("Logo inválido/inacessível em horarios-paisagem: " . $inst['imagem_instituicao']);
        $logoPath = null;
    }
}

// ---------------- Mapas e orientação ----------------
$diaMap = [
    'Domingo' => 'Domingo',
    'Segunda' => 'Segunda',
    'Terca'   => 'Terça',
    'Quarta'  => 'Quarta',
    'Quinta'  => 'Quinta',
    'Sexta'   => 'Sexta',
    'Sabado'  => 'Sábado'
];
$orientation = 'L';

// ---------------- Classe PDF ----------------
class PDFHorariosTurmaPaisagem extends FPDF
{
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0, 10, iconv('UTF-8','ISO-8859-1','Página ' . $this->PageNo()), 0, 0, 'L');
        $this->Cell(0, 10, iconv('UTF-8','ISO-8859-1','Impresso em: ' . date('d/m/Y H:i:s')), 0, 0, 'R');
    }
}

/**
 * Cabeçalho da tabela (Aula / Dia + colunas dos dias).
 */
function imprimirCabecalhoTabela(
    PDFHorariosTurmaPaisagem $pdf,
    float $colAulaW,
    float $colDiaW,
    array $diasRelatorio,
    array $diaMap
) {
    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(200,200,200);
    $pdf->Cell($colAulaW, 12, iconv('UTF-8','ISO-8859-1','Aula / Dia'), 1, 0, 'C', true);
    foreach ($diasRelatorio as $dia) {
        $displayDia = isset($diaMap[$dia]) ? $diaMap[$dia] : $dia;
        // sanitizar label do dia
        $displayDia = preg_replace('/[[:cntrl:]]+/', ' ', (string)$displayDia);
        $pdf->Cell($colDiaW, 12, iconv('UTF-8','ISO-8859-1',$displayDia), 1, 0, 'C', true);
    }
    $pdf->Ln();
}

/**
 * Gera relatório de UMA turma (paisagem).
 * Note: mantém prepared statements e sanitização.
 */
function gerarRelatorioTurmaPaisagem(PDO $pdo, PDFHorariosTurmaPaisagem $pdf, int $idTurma, array $diaMap, int $LOGO_SIZE_MM, int $LOGO_GAP_MM, ?string $logoPath, ?array $inst)
{
    // Valida idTurma
    if ($idTurma <= 0) {
        logSecurity("id_turma inválido em gerarRelatorioTurmaPaisagem: idTurma={$idTurma}");
        return;
    }

    $pdf->AddPage();

    // Cabeçalho: logo + nome instituição (seguro)
    $nomeInst = $inst ? ($inst['nome_instituicao'] ?? '') : '';
    $hasLogo = ($logoPath && file_exists($logoPath));
    $pdf->SetY(12);
    $pdf->SetFont('Arial','B',14);
    $txt  = iconv('UTF-8','ISO-8859-1', $nomeInst);
    $txtW = $pdf->GetStringWidth($txt);
    $totalW  = $hasLogo ? ($LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0) + $txtW) : $txtW;
    $pageW   = $pdf->GetPageWidth();
    $x       = ($pageW - $totalW) / 2;
    $y       = $pdf->GetY();
    if ($hasLogo) {
        // proteção adicional: image só se dentro do dir já verificado
        $pdf->Image($logoPath, $x, $y-2, $LOGO_SIZE_MM, $LOGO_SIZE_MM);
        $x += $LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0);
    }
    if ($nomeInst) {
        $pdf->SetXY($x, $y);
        $pdf->Cell($txtW, $LOGO_SIZE_MM, $txt, 0, 1, 'L');
    }
    $pdf->Ln(3);

    // Buscar dados da turma com prepared statement
    $sqlT = "
        SELECT t.*,
               s.nome_serie,
               n.nome_nivel_ensino,
               a.ano,
               tur.nome_turno,
               t.intervalos_positions
          FROM turma t
          JOIN serie s         ON t.id_serie = s.id_serie
          JOIN nivel_ensino n  ON s.id_nivel_ensino = n.id_nivel_ensino
          JOIN ano_letivo a    ON t.id_ano_letivo   = a.id_ano_letivo
          JOIN turno tur       ON t.id_turno        = tur.id_turno
         WHERE t.id_turma = :tid
         LIMIT 1
    ";
    $stTur = $pdo->prepare($sqlT);
    $stTur->bindValue(':tid', $idTurma, PDO::PARAM_INT);
    $stTur->execute();
    $turma = $stTur->fetch(PDO::FETCH_ASSOC);

    if (!$turma) {
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0, 10, iconv('UTF-8','ISO-8859-1','Turma não encontrada.'), 0, 1, 'C');
        return;
    }

    // Linha informativa
    $pdf->SetFont('Arial','',12);
    $linhaInfo = sprintf(
        'Ano Letivo %s | Horário da Turma: %s %s',
        $turma['ano'],
        $turma['nome_serie'],
        $turma['nome_turma']
    );
    $pdf->Cell(0, 7, iconv('UTF-8','ISO-8859-1', preg_replace('/[[:cntrl:]]+/', ' ', $linhaInfo) ), 0, 1, 'C');
    $pdf->Ln(8);

    // Dias do turno
    $sqlTD = "
        SELECT dia_semana, aulas_no_dia
          FROM turno_dias
         WHERE id_turno = :turno
           AND aulas_no_dia > 0
         ORDER BY FIELD(dia_semana,'Segunda','Terca','Quarta','Quinta','Sexta','Sabado','Domingo')
    ";
    $stTD = $pdo->prepare($sqlTD);
    $stTD->bindValue(':turno', $turma['id_turno'], PDO::PARAM_INT);
    $stTD->execute();
    $turnoDias = $stTD->fetchAll(PDO::FETCH_ASSOC);

    if (!$turnoDias) {
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0, 10, iconv('UTF-8','ISO-8859-1','Nenhum dia configurado neste turno.'), 0, 1, 'C');
        return;
    }

    $diasRelatorio = [];
    $maxPorDia = [];
    foreach ($turnoDias as $td) {
        $dia = $td['dia_semana'];
        $diasRelatorio[] = $dia;
        $maxPorDia[$dia] = (int)$td['aulas_no_dia'];
    }

    // Buscar horários da turma (prepared)
    $sqlH = "
        SELECT h.dia_semana, h.numero_aula,
               d.nome_disciplina,
               p.nome_exibicao AS professor
          FROM horario h
          JOIN disciplina d ON h.id_disciplina = d.id_disciplina
          JOIN professor  p ON h.id_professor  = p.id_professor
         WHERE h.id_turma = :tid
         ORDER BY FIELD(h.dia_semana,'Segunda','Terca','Quarta','Quinta','Sexta','Sabado','Domingo'),
                  h.numero_aula
    ";
    $stH = $pdo->prepare($sqlH);
    $stH->bindValue(':tid', $idTurma, PDO::PARAM_INT);
    $stH->execute();
    $horarios = $stH->fetchAll(PDO::FETCH_ASSOC);

    // Montar matriz segura [dia][aula] => texto (sanitize)
    $matrix = [];
    foreach ($diasRelatorio as $dia) $matrix[$dia] = [];
    foreach ($horarios as $hr) {
        $dia = $hr['dia_semana'];
        $na = (int)$hr['numero_aula'];
        $disc = is_string($hr['nome_disciplina']) ? preg_replace('/[[:cntrl:]]+/', ' ', trim($hr['nome_disciplina'])) : '';
        $prof = is_string($hr['professor']) ? preg_replace('/[[:cntrl:]]+/', ' ', trim($hr['professor'])) : '';
        $txt = $disc;
        if ($prof !== '') $txt .= "\n".$prof;
        $matrix[$dia][$na] = $txt;
    }

    $maxAulasGlobal = max($maxPorDia);

    // Intervalos seguros
    $intervals = [];
    if (!empty($turma['intervalos_positions'])) {
        $ex = explode(',', $turma['intervalos_positions']);
        foreach ($ex as $val) {
            if (!preg_match('/^\s*\d+\s*$/', $val)) continue;
            $v = (int)trim($val);
            if ($v > 0) $intervals[] = $v;
        }
        sort($intervals);
    }

    // Montagem da tabela
    $margin   = 10;
    $pageW    = $pdf->GetPageWidth();
    $usableW  = $pageW - (2 * $margin);
    $colAulaW = 30;
    $daysCnt  = count($diasRelatorio) ?: 1;
    $colDiaW  = ($usableW - $colAulaW) / $daysCnt;

    imprimirCabecalhoTabela($pdf, $colAulaW, $colDiaW, $diasRelatorio, $diaMap);

    for ($a = 1; $a <= $maxAulasGlobal; $a++) {
        $diaTexts = [];
        $maxLines = 1;
        foreach ($diasRelatorio as $dia) {
            $txt = ($a <= ($maxPorDia[$dia] ?? 0)) ? ($matrix[$dia][$a] ?? '') : '';
            $diaTexts[$dia] = $txt;
            if ($txt !== '') {
                $n = count(explode("\n", $txt));
                if ($n > $maxLines) $maxLines = $n;
            }
        }

        $lineHeight = 7;
        $rowH = max(14, $maxLines * $lineHeight);
        $needsInterval = in_array($a, $intervals, true);
        $intervalHeight = 10;
        $totalHeight = $rowH + ($needsInterval ? $intervalHeight : 0);

        if ($pdf->GetY() + $totalHeight > ($pdf->GetPageHeight() - 15)) {
            $pdf->AddPage();
            imprimirCabecalhoTabela($pdf, $colAulaW, $colDiaW, $diasRelatorio, $diaMap);
        }

        $pdf->SetFont('Arial','B',12);
        $pdf->Cell($colAulaW, $rowH, iconv('UTF-8','ISO-8859-1', $a.'ª Aula'), 1, 0, 'C', true);
        $pdf->SetFont('Arial','',11);

        foreach ($diasRelatorio as $dia) {
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->Cell($colDiaW, $rowH, '', 1, 0, 'C');
            $texto = $diaTexts[$dia];
            if ($texto !== '') {
                $lines = explode("\n", $texto);
                $yC = $y + ($rowH / 2) - ((count($lines) * 4) / 2);
                $pdf->SetXY($x, $yC);
                foreach ($lines as $ln) {
                    $clean = preg_replace('/[[:cntrl:]]+/', ' ', trim($ln));
                    // limitar comprimento da linha
                    if (mb_strlen($clean) > 80) $clean = mb_substr($clean, 0, 77) . '...';
                    $pdf->Cell($colDiaW, 4, iconv('UTF-8','ISO-8859-1', $clean), 0, 2, 'C');
                }
                $pdf->SetXY($x + $colDiaW, $y);
            }
        }
        $pdf->Ln($rowH);

        if ($needsInterval) {
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell($colAulaW, $intervalHeight, iconv('UTF-8','ISO-8859-1','Intervalo'), 1, 0, 'C');
            foreach ($diasRelatorio as $dia) {
                $pdf->Cell($colDiaW, $intervalHeight, iconv('UTF-8','ISO-8859-1','Intervalo'), 1, 0, 'C');
            }
            $pdf->Ln($intervalHeight);
        }
    }
}

// ---------------- Execução principal ----------------
$pdf = new PDFHorariosTurmaPaisagem($orientation, 'mm', 'A4');
$pdf->SetTitle('Relatório de Horários - Paisagem', true);

if ($id_turma > 0) {
    gerarRelatorioTurmaPaisagem($pdo, $pdf, $id_turma, $diaMap, $LOGO_SIZE_MM, $LOGO_GAP_MM, $logoPath, $inst ?? null);
} else {
    // Se não há id_turma, já garantimos que id_ano_letivo existe acima
    $sql = "SELECT t.id_turma FROM turma t WHERE t.id_ano_letivo = :ano";
    $params = [':ano' => $id_ano_letivo];
    if ($id_nivel_ensino > 0) {
        $sql .= " AND t.id_serie IN (SELECT s.id_serie FROM serie s WHERE s.id_nivel_ensino = :niv)";
        $params[':niv'] = $id_nivel_ensino;
    }
    if ($id_turno > 0) {
        $sql .= " AND t.id_turno = :turno";
        $params[':turno'] = $id_turno;
    }
    $sql .= " ORDER BY t.id_turma";

    $st = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $st->bindValue($k, (int)$v, PDO::PARAM_INT);
    }
    $st->execute();
    $listaTurmas = $st->fetchAll(PDO::FETCH_COLUMN);

    if (!$listaTurmas) {
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0, 10, iconv('UTF-8','ISO-8859-1','Nenhuma turma encontrada.'), 0, 1, 'C');
    } else {
        foreach ($listaTurmas as $tid) {
            gerarRelatorioTurmaPaisagem($pdo, $pdf, (int)$tid, $diaMap, $LOGO_SIZE_MM, $LOGO_GAP_MM, $logoPath, $inst ?? null);
        }
    }
}

$pdf->Output('I', 'HorariosPaisagem.pdf');
exit;
?>
