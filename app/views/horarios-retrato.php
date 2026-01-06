<?php
// app/views/horarios-retrato.php
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

// ---------------- Segurança / logging (cabeçalho) ----------------
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

// ---------------- Validação rígida da query string (somente cabeçalho) ----------------
// Permitimos somente estes parâmetros
$allowed = ['id_turma','id_ano_letivo','id_nivel_ensino','id_turno'];
$rawQuery = $_SERVER['QUERY_STRING'] ?? '';
parse_str($rawQuery, $receivedParams);

// Rejeita parâmetros inesperados
$receivedKeys = array_keys($receivedParams);
$extra = array_diff($receivedKeys, $allowed);
if (!empty($extra)) {
    logSecurity('Parâmetros inesperados em horarios-retrato: ' . implode(', ', $extra) . ' | raw_qs=' . $rawQuery);
    abortClient();
}

// Normalização e validação: aceitar somente inteiros não-negativos (>=0) quando presentes.
$canonical = [];
foreach ($receivedParams as $k => $v) {
    if (!is_scalar($v)) {
        logSecurity("Parâmetro não escalar em horarios-retrato: {$k}");
        abortClient();
    }
    if (!preg_match('/^\d+$/', (string)$v)) {
        logSecurity("Parâmetro não numérico permitido em horarios-retrato: {$k} raw=" . var_export($v, true) . " | raw_qs={$rawQuery}");
        abortClient();
    }
    $ival = (int)$v;
    if ($ival < 0) {
        logSecurity("Parâmetro negativo rejeitado em horarios-retrato: {$k} raw=" . var_export($v, true) . " | raw_qs={$rawQuery}");
        abortClient();
    }
    $canonical[$k] = $ival;
}

// Ordenar para comparação canônica
ksort($canonical);
$normalized_received_array = [];
foreach ($receivedParams as $k => $v) $normalized_received_array[$k] = (int)$v;
ksort($normalized_received_array);

if ($canonical !== $normalized_received_array) {
    logSecurity("Query string não canônica em horarios-retrato: expected_array=" . json_encode($canonical) . " got_array=" . json_encode($normalized_received_array) . " | full_raw={$rawQuery}");
    abortClient();
}

// ---------------- Validações de existência / consistência no banco ----------------
try {
    // id_ano_letivo existe?
    if (isset($canonical['id_ano_letivo']) && $canonical['id_ano_letivo'] > 0) {
        $st = $pdo->prepare("SELECT 1 FROM ano_letivo WHERE id_ano_letivo = :id LIMIT 1");
        $st->execute([':id' => $canonical['id_ano_letivo']]);
        if (!$st->fetchColumn()) {
            logSecurity("id_ano_letivo inexistente em horarios-retrato: id=" . $canonical['id_ano_letivo'] . " | raw_qs={$rawQuery}");
            abortClient();
        }
    }

    // id_nivel_ensino existe?
    if (isset($canonical['id_nivel_ensino']) && $canonical['id_nivel_ensino'] > 0) {
        $st = $pdo->prepare("SELECT 1 FROM nivel_ensino WHERE id_nivel_ensino = :id LIMIT 1");
        $st->execute([':id' => $canonical['id_nivel_ensino']]);
        if (!$st->fetchColumn()) {
            logSecurity("id_nivel_ensino inexistente em horarios-retrato: id=" . $canonical['id_nivel_ensino'] . " | raw_qs={$rawQuery}");
            abortClient();
        }
    }

    // id_turno existe?
    if (isset($canonical['id_turno']) && $canonical['id_turno'] > 0) {
        $st = $pdo->prepare("SELECT 1 FROM turno WHERE id_turno = :id LIMIT 1");
        $st->execute([':id' => $canonical['id_turno']]);
        if (!$st->fetchColumn()) {
            logSecurity("id_turno inexistente em horarios-retrato: id=" . $canonical['id_turno'] . " | raw_qs={$rawQuery}");
            abortClient();
        }
    }

    // id_turma existe? e se id_ano_letivo foi passado, validar associação
    if (isset($canonical['id_turma']) && $canonical['id_turma'] > 0) {
        $st = $pdo->prepare("SELECT id_turma, id_ano_letivo, id_turno, id_serie FROM turma WHERE id_turma = :id LIMIT 1");
        $st->execute([':id' => $canonical['id_turma']]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            logSecurity("id_turma inexistente em horarios-retrato: id=" . $canonical['id_turma'] . " | raw_qs={$rawQuery}");
            abortClient();
        }
        // se passou id_ano_letivo, validar pertence à turma
        if (isset($canonical['id_ano_letivo']) && $canonical['id_ano_letivo'] > 0 && (int)$row['id_ano_letivo'] !== $canonical['id_ano_letivo']) {
            logSecurity("id_turma/id_ano_letivo inconsistentes em horarios-retrato: turma_id=" . $canonical['id_turma'] . " turma_ano=" . $row['id_ano_letivo'] . " vs qs_ano=" . $canonical['id_ano_letivo'] . " | raw_qs={$rawQuery}");
            abortClient();
        }
        // se passou id_turno, validar corresponde
        if (isset($canonical['id_turno']) && $canonical['id_turno'] > 0 && (int)$row['id_turno'] !== $canonical['id_turno']) {
            logSecurity("id_turma/id_turno inconsistentes em horarios-retrato: turma_id=" . $canonical['id_turma'] . " turma_turno=" . $row['id_turno'] . " vs qs_turno=" . $canonical['id_turno'] . " | raw_qs={$rawQuery}");
            abortClient();
        }
        // se passou id_nivel_ensino, validar via serie -> nivel
        if (isset($canonical['id_nivel_ensino']) && $canonical['id_nivel_ensino'] > 0) {
            $st2 = $pdo->prepare("SELECT s.id_nivel_ensino FROM serie s WHERE s.id_serie = :idserie LIMIT 1");
            $st2->execute([':idserie' => $row['id_serie']]);
            $serieRow = $st2->fetch(PDO::FETCH_ASSOC);
            if (!$serieRow || (int)$serieRow['id_nivel_ensino'] !== $canonical['id_nivel_ensino']) {
                logSecurity("id_turma/id_nivel_ensino inconsistentes em horarios-retrato: turma_id=" . $canonical['id_turma'] . " serie_nivel=" . ($serieRow['id_nivel_ensino'] ?? 'null') . " vs qs_nivel=" . $canonical['id_nivel_ensino'] . " | raw_qs={$rawQuery}");
                abortClient();
            }
        }
    }
} catch (Throwable $e) {
    logSecurity("Erro ao validar IDs em horarios-retrato: " . $e->getMessage() . " | raw_qs={$rawQuery}");
    abortClient();
}

// ---------------- FIM da camada de cabeçalho/segurança ----------------

// Agora definimos as variáveis do relatório a partir do canonical (mantendo compatibilidade)
$id_turma        = $canonical['id_turma'] ?? 0;
$id_ano_letivo   = $canonical['id_ano_letivo'] ?? 0;
$id_nivel_ensino = $canonical['id_nivel_ensino'] ?? 0;
$id_turno        = $canonical['id_turno'] ?? 0;

// Mapeamento do dia para imprimir com acentos corretos
$diaMap = [
    'Domingo' => 'Domingo',
    'Segunda' => 'Segunda',
    'Terca'   => 'Terça',
    'Quarta'  => 'Quarta',
    'Quinta'  => 'Quinta',
    'Sexta'   => 'Sexta',
    'Sabado'  => 'Sábado'
];

// Orientação Retrato
$orientation = 'P';

// Parâmetros visuais do cabeçalho (iguais aos demais relatórios)
$LOGO_SIZE_MM = 15;
$LOGO_GAP_MM  = 5;

// Proteção do logo: carregamos $inst e $logoPath agora (apenas leitura segura)
$inst = null;
$logoPath = null;
try {
    $stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
    $inst = $stmtInst->fetch(PDO::FETCH_ASSOC);
    if ($inst && !empty($inst['imagem_instituicao'])) {
        $candidate = basename($inst['imagem_instituicao']);
        $fullLogo = LOGO_PATH . '/' . $candidate;
        if (file_exists($fullLogo) && is_file($fullLogo) && strpos(realpath($fullLogo), realpath(LOGO_PATH)) === 0) {
            $logoPath = $fullLogo;
        } else {
            logSecurity("Logo inválido/inacessível em horarios-retrato: " . $inst['imagem_instituicao']);
            $logoPath = null;
        }
    }
} catch (Throwable $e) {
    logSecurity("Erro SQL buscando instituicao em horarios-retrato: " . $e->getMessage());
    $inst = null;
    $logoPath = null;
}

class PDFHorariosTurmaRetrato extends FPDF
{
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);

        // Esquerda: Página X (sem "/N")
        $this->Cell(0, 10, iconv('UTF-8','ISO-8859-1','Página ' . $this->PageNo()), 0, 0, 'L');

        // Direita: data/hora
        $this->Cell(0, 10, iconv('UTF-8','ISO-8859-1','Impresso em: ' . date('d/m/Y H:i:s')), 0, 0, 'R');
    }
}

/**
 * Cabeçalho da tabela (Aula / Dia + dias)
 */
function imprimirCabecalhoTabela(
    PDFHorariosTurmaRetrato $pdf,
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
        // sanitize labels
        $displayDia = preg_replace('/[[:cntrl:]]+/', ' ', (string)$displayDia);
        $pdf->Cell($colDiaW, 12, iconv('UTF-8','ISO-8859-1',$displayDia), 1, 0, 'C', true);
    }
    $pdf->Ln();
}

/**
 * Gera o relatório de UMA turma (Retrato)
 * agora aceita $LOGO_SIZE_MM, $LOGO_GAP_MM e $logoPath e $inst para evitar variáveis indefinidas
 */
function gerarRelatorioTurmaRetrato(PDO $pdo, PDFHorariosTurmaRetrato $pdf, int $idTurma, array $diaMap, int $LOGO_SIZE_MM, int $LOGO_GAP_MM, ?string $logoPath = null, ?array $inst = null)
{
    if ($idTurma <= 0) {
        logSecurity("id_turma inválido em gerarRelatorioTurmaRetrato: idTurma={$idTurma}");
        return;
    }

    // Adiciona página
    $pdf->AddPage();

    // ===== 1) Cabeçalho padronizado: logo + nome da instituição na mesma linha =====
    $nomeInst = $inst ? ($inst['nome_instituicao'] ?? '') : '';
    $pdf->SetY(12);
    $pdf->SetFont('Arial','B',14);
    $txt  = iconv('UTF-8','ISO-8859-1', $nomeInst);
    $txtW = $pdf->GetStringWidth($txt);
    $hasLogo = ($logoPath && file_exists($logoPath));
    $totalW  = $hasLogo ? ($LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0) + $txtW) : $txtW;
    $pageW   = $pdf->GetPageWidth();
    $x       = ($pageW - $totalW) / 2;
    $y       = $pdf->GetY();

    if ($hasLogo) {
        // proteção adicional: imagem apenas do diretório já verificado
        $pdf->Image($logoPath, $x, $y-2, $LOGO_SIZE_MM, $LOGO_SIZE_MM);
        $x += $LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0);
    }
    if ($nomeInst) {
        $pdf->SetXY($x, $y);
        $pdf->Cell($txtW, $LOGO_SIZE_MM, $txt, 0, 1, 'L');
    }
    $pdf->Ln(3);

    // ===== 2) Dados da turma =====
    $sqlT = "
        SELECT t.*,
               s.nome_serie,
               n.nome_nivel_ensino,
               a.ano,
               tur.nome_turno,
               t.intervalos_positions
          FROM turma t
          JOIN serie s         ON t.id_serie        = s.id_serie
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

    // Linha abaixo do cabeçalho no formato solicitado
    $linhaInfo = sprintf('Ano Letivo %s | Horário da Turma %s %s',
        $turma['ano'],
        $turma['nome_serie'],
        $turma['nome_turma']
    );
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0, 7, iconv('UTF-8','ISO-8859-1', preg_replace('/[[:cntrl:]]+/', ' ', $linhaInfo)), 0, 1, 'C');
    $pdf->Ln(8); // <<< Espaçamento antes da tabela (ajustável)

    // ===== 3) Dias do turno =====
    $sqlTD = "
        SELECT dia_semana, aulas_no_dia
          FROM turno_dias
         WHERE id_turno = :turno
           AND aulas_no_dia > 0
         ORDER BY FIELD(dia_semana, 'Segunda','Terca','Quarta','Quinta','Sexta','Sabado','Domingo')
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
    $maxPorDia     = [];
    foreach ($turnoDias as $td) {
        $dia = $td['dia_semana'];
        $diasRelatorio[]   = $dia;
        $maxPorDia[$dia]   = (int)$td['aulas_no_dia'];
    }

    // ===== 4) Carregar horários =====
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

    // Monta [dia][aula] => "Disciplina\nProfessor" (sanitizado)
    $matrix = [];
    foreach ($diasRelatorio as $dia) {
        $matrix[$dia] = [];
    }
    foreach ($horarios as $hr) {
        $dia = $hr['dia_semana'];
        $na  = (int)$hr['numero_aula'];
        $disc = is_string($hr['nome_disciplina']) ? preg_replace('/[[:cntrl:]]+/', ' ', trim($hr['nome_disciplina'])) : '';
        $prof = is_string($hr['professor']) ? preg_replace('/[[:cntrl:]]+/', ' ', trim($hr['professor'])) : '';
        $txt = $disc;
        if ($prof !== '') $txt .= "\n".$prof;
        $matrix[$dia][$na] = $txt;
    }

    $maxAulasGlobal = max($maxPorDia);

    // ===== 5) Intervalos =====
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

    // ===== 6) Montar tabela =====
    $margin   = 10;
    $pageW    = $pdf->GetPageWidth();
    $usableW  = $pageW - (2 * $margin);

    $colAulaW = 30;
    $daysCnt  = count($diasRelatorio) ?: 1;
    $colDiaW  = ($usableW - $colAulaW) / $daysCnt;

    // Cabeçalho da tabela
    imprimirCabecalhoTabela($pdf, $colAulaW, $colDiaW, $diasRelatorio, $diaMap);

    // Linhas (aulas)
    for ($a = 1; $a <= $maxAulasGlobal; $a++) {
        // calcula nº de linhas necessárias
        $diaTexts = [];
        $maxLines = 1;
        foreach ($diasRelatorio as $dia) {
            $txt = ($a <= ($maxPorDia[$dia] ?? 0)) ? ($matrix[$dia][$a] ?? '') : '';
            $diaTexts[$dia] = $txt;
            if ($txt) {
                $n = count(explode("\n", $txt));
                if ($n > $maxLines) $maxLines = $n;
            }
        }

        $lineHeight = 7;
        $rowH = max(14, $maxLines * $lineHeight);

        $needsInterval   = in_array($a, $intervals, true);
        $intervalHeight  = 10;
        $totalHeight     = $rowH + ($needsInterval ? $intervalHeight : 0);

        // Quebra de página se necessário (15mm margem inferior para rodapé)
        if ($pdf->GetY() + $totalHeight > ($pdf->GetPageHeight() - 15)) {
            $pdf->AddPage();
            // reimprime SÓ o cabeçalho da tabela
            imprimirCabecalhoTabela($pdf, $colAulaW, $colDiaW, $diasRelatorio, $diaMap);
        }

        // Coluna "Xª Aula"
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell($colAulaW, $rowH, iconv('UTF-8','ISO-8859-1',$a.'ª Aula'), 1, 0, 'C', true);

        // Colunas dos dias
        $pdf->SetFont('Arial','',11);
        foreach ($diasRelatorio as $dia) {
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->Cell($colDiaW, $rowH, '', 1, 0, 'C'); // borda

            $texto = $diaTexts[$dia];
            if ($texto) {
                $lines = explode("\n", $texto);
                $yC = $y + ($rowH / 2) - ((count($lines) * 4) / 2); // centraliza verticalmente
                $pdf->SetXY($x, $yC);
                foreach ($lines as $ln) {
                    $clean = preg_replace('/[[:cntrl:]]+/', ' ', trim($ln));
                    if (mb_strlen($clean) > 80) $clean = mb_substr($clean, 0, 77) . '...';
                    $pdf->Cell($colDiaW, 4, iconv('UTF-8','ISO-8859-1',$clean), 0, 2, 'C');
                }
                $pdf->SetXY($x + $colDiaW, $y);
            }
        }
        $pdf->Ln($rowH);

        // Linha de "Intervalo", se for o caso
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

// ------------------------------------------------------------
// Execução principal (mantive exatamente a lógica original)
// ------------------------------------------------------------
$pdf = new PDFHorariosTurmaRetrato($orientation, 'mm', 'A4');
$pdf->SetTitle('Relatório de Horários - Retrato', true);

if ($id_turma > 0) {
    // Uma turma
    gerarRelatorioTurmaRetrato($pdo, $pdf, $id_turma, $diaMap, (int)$LOGO_SIZE_MM, (int)$LOGO_GAP_MM, $logoPath, $inst ?? null);
} else {
    // Várias turmas (pelo menos ano letivo precisa vir)
    if ($id_ano_letivo <= 0) {
        abortClient('Nenhum ID de turma e nenhum Ano Letivo informado. Selecione pelo menos o Ano Letivo.');
    }

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
            gerarRelatorioTurmaRetrato($pdo, $pdf, (int)$tid, $diaMap, (int)$LOGO_SIZE_MM, (int)$LOGO_GAP_MM, $logoPath, $inst ?? null);
        }
    }
}

// Saída
$pdf->Output('I', 'HorariosRetrato.pdf');
exit;
?>
