<?php
// app/views/horarios-unidos-paisagem.php
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

/* -----------------------------
   LOG SIMPLE PARA SEGURANÇA (APENAS CABEÇALHO)
------------------------------*/
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

/* -----------------------------
   Aborta com resposta padronizada ao cliente.
------------------------------*/
function abortClient($msg = 'Parâmetros inválidos') {
    while (ob_get_level() > 0) @ob_end_clean();
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit;
}

/* -----------------------------
   Conversão segura UTF-8 -> ISO-8859-1 (tolerante)
------------------------------*/
function safeToIso(string $s): string {
    $s = (string)$s;
    if (function_exists('mb_check_encoding') && mb_check_encoding($s, 'UTF-8')) {
        $out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $s);
        if ($out !== false) return $out;
    }
    $clean = @mb_convert_encoding($s, 'UTF-8', 'UTF-8');
    $out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $clean);
    if ($out !== false) return $out;
    $try1252 = @mb_convert_encoding($s, 'UTF-8', 'Windows-1252');
    $out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $try1252);
    if ($out !== false) return $out;
    $out = @utf8_decode($s);
    if ($out !== false && $out !== null) return $out;
    $plain = preg_replace('/[^\x09\x0A\x0D\x20-\x7E\xA0-\xFF]/', '', $s);
    logSecurity('safeToIso: conversão falhou, raw_sample=' . substr($s,0,200));
    return $plain !== null ? $plain : '';
}

/* -----------------------------
   VALIDAÇÃO DE PARÂMETROS (REJEITA EXTRAS)
------------------------------*/
$allowed = ['id_ano_letivo', 'id_nivel_ensino', 'id_turno'];
parse_str($_SERVER['QUERY_STRING'] ?? '', $receivedParams);

// rejeita parâmetros inesperados
$extra = array_diff(array_keys($receivedParams), $allowed);
if (!empty($extra)) {
    logSecurity('Parâmetros inesperados em horarios-unidos-paisagem.php: ' . implode(', ', $extra));
    abortClient();
}

// normaliza e valida escalaridade (apenas inteiros não negativos)
$canonical = [];
foreach ($allowed as $k) {
    if (isset($receivedParams[$k])) {
        if (!is_scalar($receivedParams[$k])) {
            logSecurity("Parâmetro não escalar {$k} em horarios-unidos-paisagem.php");
            abortClient();
        }
        $s = (string)$receivedParams[$k];
        if ($s === '') {
            $canonical[$k] = 0;
        } else {
            if (!preg_match('/^\d+$/', $s)) {
                logSecurity("Valor inválido para {$k} em horarios-unidos-paisagem.php raw=" . var_export($receivedParams[$k], true));
                abortClient();
            }
            $canonical[$k] = (int)$s;
        }
    } else {
        $canonical[$k] = 0;
    }
}

$id_ano_letivo   = $canonical['id_ano_letivo'];
$id_nivel_ensino = $canonical['id_nivel_ensino'];
$id_turno        = $canonical['id_turno'];

// valida existência de ano letivo (obrigatório)
if ($id_ano_letivo <= 0) {
    logSecurity("Ano letivo não informado em horarios-unidos-paisagem.php");
    abortClient('Parâmetros inválidos');
}
$stAno = $pdo->prepare("SELECT ano FROM ano_letivo WHERE id_ano_letivo = :id LIMIT 1");
$stAno->execute([':id' => $id_ano_letivo]);
$anoLegivel = $stAno->fetchColumn();
if (!$anoLegivel) {
    logSecurity("Ano letivo inexistente: id={$id_ano_letivo}");
    abortClient('Parâmetros inválidos');
}

// valida opcional nível de ensino
if ($id_nivel_ensino > 0) {
    $stN = $pdo->prepare("SELECT 1 FROM nivel_ensino WHERE id_nivel_ensino = :id LIMIT 1");
    $stN->execute([':id' => $id_nivel_ensino]);
    if ($stN->fetchColumn() === false) {
        logSecurity("Nivel de ensino inexistente: id={$id_nivel_ensino}");
        abortClient('Parâmetros inválidos');
    }
}

// valida opcional turno
if ($id_turno > 0) {
    $stT = $pdo->prepare("SELECT 1 FROM turno WHERE id_turno = :id LIMIT 1");
    $stT->execute([':id' => $id_turno]);
    if ($stT->fetchColumn() === false) {
        logSecurity("Turno inexistente: id={$id_turno}");
        abortClient('Parâmetros inválidos');
    }
}

/* -----------------------------
   Cabeçalho padrão (mesma linha)
------------------------------*/
$LOGO_SIZE_MM = 15;
$LOGO_GAP_MM  = 5;

class PDFHorariosUnidos extends FPDF
{
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0, 10, safeToIso('Página ' . $this->PageNo()), 0, 0, 'L');
        $this->Cell(0, 10, safeToIso('Impresso em: ' . date('d/m/Y H:i:s')), 0, 0, 'R');
    }
}

/* -----------------------------
   Mapeamentos dias
------------------------------*/
$diaParaSigla = [
    'Domingo' => 'DOM',
    'Segunda' => 'SEG',
    'Terca'   => 'TER',
    'Quarta'  => 'QUA',
    'Quinta'  => 'QUI',
    'Sexta'   => 'SEX',
    'Sabado'  => 'SÁB'
];

// Dias da semana na horizontal (substituindo as turmas)
$diasSemana = [
    'DOM' => "Domingo",
    'SEG' => "Segunda",
    'TER' => "Terça",
    'QUA' => "Quarta",
    'QUI' => "Quinta",
    'SEX' => "Sexta",
    'SÁB' => "Sábado"
];

/* -----------------------------
   Funções de render (usam safeToIso)
------------------------------*/
// Substitua pela nova função renderCabecalhoPadrao
function renderCabecalhoPadrao(
    FPDF $pdf,
    string $nomeInst,
    ?string $logoPath,
    string $anoLetivo,
    string $nomeNivel,
    string $nomeTurno,
    int $LOGO_SIZE_MM,
    int $LOGO_GAP_MM,
    ?string $logoRaw = null
) {
    $pdf->SetY(12);
    $pdf->SetFont('Arial','B',14);
    $txt  = mb_substr($nomeInst, 0, 200);
    $txtEnc = safeToIso($txt);
    $txtW = $pdf->GetStringWidth($txtEnc);

    $hasLogo = ($logoPath && file_exists($logoPath));
    $totalW  = $hasLogo ? ($LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0) + $txtW) : $txtW;
    $pageW   = $pdf->GetPageWidth();
    $x       = ($pageW - $totalW) / 2;
    $y       = $pdf->GetY();

    if ($hasLogo) {
        // proteção path-traversal
        $candidate = basename($logoPath);
        $fullLogo = LOGO_PATH . '/' . $candidate;
        if (file_exists($fullLogo) && is_file($fullLogo)) {
            $realLogo = realpath($fullLogo);
            $realLogoDir = realpath(LOGO_PATH);
            if ($realLogo !== false && $realLogoDir !== false && strpos($realLogo, $realLogoDir) === 0) {
                $pdf->Image($realLogo, $x, $y - 2, $LOGO_SIZE_MM, $LOGO_SIZE_MM);
                $x += $LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0);
            } else {
                logSecurity("Tentativa de usar logo fora do diretório permitido: " . ($logoRaw ?? ''));
            }
        } else {
            logSecurity("Logo informado não encontrado: " . ($logoRaw ?? ''));
        }
    }

    if (!empty($nomeInst)) {
        $pdf->SetXY($x, $y);
        $pdf->Cell($txtW, $LOGO_SIZE_MM, $txtEnc, 0, 1, 'L');
    }

    $pdf->Ln(3);
    $pdf->SetFont('Arial','B',10);

    // monta partes dinamicamente (remove '—' quando vazio)
    $parts = [];
    if ($anoLetivo !== '') $parts[] = 'Ano Letivo ' . $anoLetivo;
    if ($nomeNivel !== '') $parts[] = $nomeNivel;
    if ($nomeTurno !== '') $parts[] = 'Turno ' . $nomeTurno;

    $linhaInfo = implode(' | ', $parts);
    if ($linhaInfo === '') $linhaInfo = 'Ano Letivo —';

    $pdf->Cell(0, 7, safeToIso($linhaInfo), 0, 1, 'C');
    $pdf->Ln(2);
}


function desenharTabela(
    FPDF $pdf,
    array $turmas,
    array $dias,
    array $horariosMap,
    int $maxAulas,
    array $turmaVertical,
    array $diasSemana
) {
    // Layout
    $leftMargin   = 10;
    $rightMargin  = 10;
    $usableWidth  = $pdf->GetPageWidth() - ($leftMargin + $rightMargin);

    $colTurmaW    = 10;  // Turma vertical
    $colAulaW     = 20;  // "1ª Aula"

    $numDias      = max(1, count($dias));
    $rest         = $usableWidth - ($colTurmaW + $colAulaW);
    $colDiaW      = $rest / $numDias;

    // Cabeçalho da tabela
    $pdf->SetFont('Arial','B',9);
    $pdf->SetFillColor(220,220,220);

    $pdf->Cell($colTurmaW, 8, safeToIso('Turma'), 1,0,'C',true);
    $pdf->Cell($colAulaW,  8, safeToIso('Aula'), 1,0,'C',true);
    foreach ($dias as $diaSigla) {
        $nomeDia = $diasSemana[$diaSigla] ?? $diaSigla;
        $pdf->Cell($colDiaW, 8, safeToIso($nomeDia), 1,0,'C',true);
    }
    $pdf->Ln();
    $pdf->Ln(2);

    // Agora imprime as turmas
    $pdf->SetFont('Arial','',8);
    $lineH = 9;

    foreach ($turmas as $tm) {
        $tID = (int)$tm['id_turma'];
        $verticalText = $turmaVertical[$tID] ?? ($tm['nome_serie'] . ' ' . $tm['nome_turma']);
        $heightTurma = $maxAulas * $lineH;
        $xTurma = $pdf->GetX();
        $yTurma = $pdf->GetY();

        // Borda mesclada
        $pdf->Rect($xTurma, $yTurma, $colTurmaW, $heightTurma);

        // Centraliza vertical
        $numLines  = count(explode("\n", $verticalText));
        $textTotal = $numLines * 3.5;
        $startY    = $yTurma + ($heightTurma/2) - ($textTotal/2);
        $pdf->SetXY($xTurma, $startY);
        $pdf->MultiCell($colTurmaW, 3.5, safeToIso($verticalText), 0,'C');

        $pdf->SetXY($xTurma + $colTurmaW, $yTurma);

        for ($a=1; $a<=$maxAulas; $a++) {
            $labelAulaISO = safeToIso($a.'ª Aula');
            $pdf->Cell($colAulaW, $lineH, $labelAulaISO, 1,0,'C');

            foreach ($dias as $diaSigla) {
                $txt  = $horariosMap[$tID][$diaSigla][$a] ?? '';
                $xCel = $pdf->GetX();
                $yCel = $pdf->GetY();
                $pdf->Cell($colDiaW, $lineH, '', 1,0);

                if (!empty($txt)) {
                    // limpa espaços/linhas extras
                    $txt = preg_replace('/[ \t]+\n/', "\n", trim($txt));
                    $txt = preg_replace("/\n{2,}/", "\n", $txt);
                    $pdf->SetXY($xCel + 1, $yCel + 0.8);
                    $pdf->MultiCell($colDiaW - 2, ($lineH/2) - 1.0, safeToIso($txt), 0,'C');
                    $pdf->SetXY($xCel + $colDiaW, $yCel);
                }
            }
            $pdf->Ln($lineH);
            $pdf->SetX($xTurma + $colTurmaW);
        }
        $pdf->Ln(2);
    }
}

/* -----------------------------
   Função principal: carregar dados e gerar PDF
------------------------------*/
// Substitua pela nova função gerarHorariosUnidos
function gerarHorariosUnidos(PDO $pdo, PDFHorariosUnidos $pdf, int $idAno, int $idNiv, int $idTurno, int $LOGO_SIZE_MM, int $LOGO_GAP_MM)
{
    global $diaParaSigla, $diasSemana;

    // instituição
    $stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
    $inst = $stmtInst->fetch(PDO::FETCH_ASSOC);
    $nomeInst = $inst['nome_instituicao'] ?? '';
    $logoPath = (!empty($inst['imagem_instituicao'])) ? LOGO_PATH . '/' . basename($inst['imagem_instituicao']) : null;
    $logoRaw  = $inst['imagem_instituicao'] ?? null;

    // nomes
    $nomeNivel = '';
    if ($idNiv > 0) {
        // CORREÇÃO: usa $idNiv (antes estava $idN)
        $stN = $pdo->prepare("SELECT nome_nivel_ensino FROM nivel_ensino WHERE id_nivel_ensino = :id LIMIT 1");
        $stN->execute([':id' => $idNiv]);
        $nomeNivel = $stN->fetchColumn() ?: '';
    }
    $nomeTurno = '';
    if ($idTurno > 0) {
        $stT = $pdo->prepare("SELECT nome_turno FROM turno WHERE id_turno = :id LIMIT 1");
        $stT->execute([':id' => $idTurno]);
        $nomeTurno = $stT->fetchColumn() ?: '';
    }
    $stA = $pdo->prepare("SELECT ano FROM ano_letivo WHERE id_ano_letivo = :id LIMIT 1");
    $stA->execute([':id' => $idAno]);
    $anoLetivo = $stA->fetchColumn() ?: '';

    // turmas
    $sqlTur = "
        SELECT t.id_turma, s.nome_serie, t.nome_turma
          FROM turma t
          JOIN serie s ON t.id_serie = s.id_serie
         WHERE t.id_ano_letivo = ?
    ";
    $params = [$idAno];
    if ($idNiv > 0) {
        $sqlTur .= " AND s.id_nivel_ensino = ? ";
        $params[] = $idNiv;
    }
    if ($idTurno > 0) {
        $sqlTur .= " AND t.id_turno = ? ";
        $params[] = $idTurno;
    }
    $sqlTur .= " ORDER BY s.nome_serie, t.nome_turma";
    $stTur = $pdo->prepare($sqlTur);
    $stTur->execute($params);
    $turmas = $stTur->fetchAll(PDO::FETCH_ASSOC);

    if (!$turmas) {
        logSecurity(sprintf('Nenhuma turma encontrada (paisagem). ano=%d nivel=%d turno=%d', $idAno, $idNiv, $idTurno));
        abortClient('Parâmetros inválidos');
    }

    // busca horários em bloco (ids inteiros validados)
    $arrIds = array_map('intval', array_column($turmas, 'id_turma'));
    $strIds = implode(',', $arrIds);

    $sqlHor = "
        SELECT h.id_turma,
               h.dia_semana,
               h.numero_aula,
               d.nome_disciplina,
               COALESCE(p.nome_exibicao, p.nome_completo) AS prof
          FROM horario h
          JOIN disciplina d ON h.id_disciplina = d.id_disciplina
          JOIN professor  p ON h.id_professor  = p.id_professor
         WHERE h.id_turma IN ($strIds)
         ORDER BY 
           FIELD(h.dia_semana,'Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado'),
           h.numero_aula
    ";
    $rowsHor = $pdo->query($sqlHor)->fetchAll(PDO::FETCH_ASSOC);

    // mapeia [id_turma][SIGLA][numero_aula] => texto
    $horariosMap = [];
    foreach ($rowsHor as $r) {
        $tid = (int)$r['id_turma'];
        $diaCompleto = $r['dia_semana'];
        if (!isset($diaParaSigla[$diaCompleto])) continue;
        $diaSig = $diaParaSigla[$diaCompleto];
        $numAula = (int)$r['numero_aula'];
        $texto   = $r['nome_disciplina'];
        if (!empty($r['prof'])) $texto .= "\n" . $r['prof'];
        $horariosMap[$tid][$diaSig][$numAula] = $texto;
    }

    $maxAulasDia = 6;
    $diasExibir = ['SEG','TER','QUA','QUI','SEX'];

    // vertical names per turma
    $turmaVertical = [];
    foreach ($turmas as $tm) {
        $nomeTurma = $tm['nome_serie'] . ' ' . $tm['nome_turma'];
        $verticalText = '';
        for ($i = 0; $i < mb_strlen($nomeTurma, 'UTF-8'); $i++) {
            $char = mb_substr($nomeTurma, $i, 1, 'UTF-8');
            $verticalText .= $char . "\n";
        }
        $turmaVertical[$tm['id_turma']] = trim($verticalText);
    }

    // groups of 2 turmas per page (mantido)
    $gruposTurmas = array_chunk($turmas, 2);

    foreach ($gruposTurmas as $grupoTurma) {
        $pdf->AddPage('L');
        renderCabecalhoPadrao($pdf, $nomeInst, $logoPath, $anoLetivo, $nomeNivel, $nomeTurno, $LOGO_SIZE_MM, $LOGO_GAP_MM, $logoRaw);
        desenharTabela($pdf, $grupoTurma, $diasExibir, $horariosMap, $maxAulasDia, $turmaVertical, $diasSemana);
    }
}


/* -----------------------------
   Execução final
------------------------------*/
$pdf = new PDFHorariosUnidos('L','mm','A4');
$pdf->SetTitle('Horários Unificados - Paisagem', true);

gerarHorariosUnidos($pdo, $pdf, $id_ano_letivo, $id_nivel_ensino, $id_turno, $LOGO_SIZE_MM, $LOGO_GAP_MM);

$pdf->Output('I','horarios-unidos-paisagem.pdf');
exit;
?>
