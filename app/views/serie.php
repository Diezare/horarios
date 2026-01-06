<?php
// app/views/serie.php
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

/**
 * Emite resposta de erro padrão e encerra.
 */
function abortClient($msg = 'Parâmetros inválidos') {
    while (ob_get_level() > 0) @ob_end_clean();
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit;
}

/* -----------------------------
   VALIDAÇÃO RÍGIDA DA QUERY STRING (CABEÇALHO)
   Permitimos só os parâmetros conhecidos e validamos tipos/valores.
------------------------------*/
$allowed = [
    'id_serie',
    'turmas',
    'disc'
];
$rawQuery = $_SERVER['QUERY_STRING'] ?? '';
parse_str($rawQuery, $receivedParams);

// rejeita parâmetros inesperados
$receivedKeys = array_keys($receivedParams);
$extra = array_diff($receivedKeys, $allowed);
if (!empty($extra)) {
    logSecurity('Parâmetros inesperados em serie.php: ' . implode(', ', $extra) . ' | raw_qs=' . $rawQuery);
    abortClient();
}

// normalize & validate
$canonical = [];
foreach ($receivedParams as $k => $v) {
    if (!is_scalar($v)) {
        logSecurity("Parâmetro não escalar em serie.php: {$k}");
        abortClient();
    }

    // flags (checkboxes) must be either empty (present without value) or accepted truthy strings
    if (in_array($k, ['turmas','disc'], true)) {
        $val = (string)$v;
        // Acceptable values for checkbox-like params: empty, '1', 'on', 'true'
        $okValues = ['', '1', 'on', 'true'];
        if ($val !== '' && !in_array(strtolower($val), $okValues, true)) {
            logSecurity("Valor inválido para flag {$k} em serie.php raw=" . var_export($v, true));
            abortClient();
        }
        $canonical[$k] = in_array(strtolower($val), ['1','on','true'], true) ? 1 : 0;
        continue;
    }

    // numeric params must be non-negative integers
    if (!preg_match('/^\d+$/', (string)$v)) {
        logSecurity("Parâmetro não numérico permitido em serie.php: {$k} raw=" . var_export($v, true) . " | raw_qs={$rawQuery}");
        abortClient();
    }
    $ival = (int)$v;
    if ($ival < 0) {
        logSecurity("Parâmetro negativo rejeitado em serie.php: {$k} raw=" . var_export($v, true) . " | raw_qs={$rawQuery}");
        abortClient();
    }
    $canonical[$k] = $ival;
}

// Defensive canonical ordering check
ksort($canonical);
$normalized_received_array = [];
foreach ($receivedParams as $k => $v) {
    if (in_array($k, ['turmas','disc'], true)) {
        $normalized_received_array[$k] = in_array(strtolower((string)$v), ['1','on','true'], true) ? 1 : 0;
    } else {
        $normalized_received_array[$k] = (int)$v;
    }
}
ksort($normalized_received_array);
if ($canonical !== $normalized_received_array) {
    logSecurity("Query string não canônica em serie.php: expected=" . json_encode($canonical) . " got=" . json_encode($normalized_received_array) . " | raw_qs={$rawQuery}");
    abortClient();
}

/* -----------------------------
   Semântica: id_serie obrigatório e deve existir no BD
------------------------------*/
$idSerie = $canonical['id_serie'] ?? 0;
if ($idSerie <= 0) {
    logSecurity("id_serie ausente ou zero em serie.php raw_qs={$rawQuery}");
    abortClient();
}
$stExist = $pdo->prepare("SELECT 1 FROM serie WHERE id_serie = :id LIMIT 1");
$stExist->execute([':id' => $idSerie]);
if ($stExist->fetchColumn() === false) {
    logSecurity("id_serie inexistente em serie.php: {$idSerie}");
    abortClient();
}

// flags normalizadas
$turmas  = !empty($canonical['turmas']);
$discOpt = !empty($canonical['disc']);

/* -----------------------------
   Parâmetros visuais
------------------------------*/
$LOGO_SIZE_MM = 15; // tamanho (largura=altura) da logo em mm
$LOGO_GAP_MM  = 5;  // espaço entre a logo e o nome em mm

class PDF extends FPDF
{
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        // Esquerda: Página X (sem "/N")
        $this->Cell(0,10,iconv('UTF-8','ISO-8859-1','Página ' . $this->PageNo()),0,0,'L');
        // Direita: data/hora
        $this->Cell(0,10,iconv('UTF-8','ISO-8859-1//TRANSLIT','Impresso em: ' . date('d/m/Y H:i:s')),0,0,'R');
    }
}

// ----------------------------------------------------------------
// 2) Inicia PDF
// ----------------------------------------------------------------
$pdf = new PDF('P','mm','A4');
$pdf->SetTitle(iconv('UTF-8','ISO-8859-1','Relatório de Série'));
$pdf->AddPage();

// ----------------------------------------------------------------
// 3) Cabeçalho (logo + nome na mesma linha, centralizados)
// ----------------------------------------------------------------
$stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
$inst = $stmtInst->fetch(PDO::FETCH_ASSOC);
$nomeInst = $inst ? ($inst['nome_instituicao'] ?? '') : '';
$logoRaw  = ($inst && !empty($inst['imagem_instituicao'])) ? $inst['imagem_instituicao'] : null;
$logoPath = $logoRaw ? LOGO_PATH . '/' . basename($logoRaw) : null;

function printHeaderInlineSerie(FPDF $pdf, $nomeInst, $logoPath, $LOGO_SIZE_MM, $LOGO_GAP_MM, $logoRaw = null)
{
    $pdf->SetY(12);
    $pdf->SetFont('Arial','B',14);

    $txt  = iconv('UTF-8','ISO-8859-1', $nomeInst);
    $txtW = $pdf->GetStringWidth($txt);

    $hasLogo = ($logoPath && file_exists($logoPath));
    $totalW  = $hasLogo ? ($LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0) + $txtW) : $txtW;

    $pageW = $pdf->GetPageWidth();
    $x     = ($pageW - $totalW) / 2;
    $y     = $pdf->GetY();

    if ($hasLogo) {
        // segurança: basename + realpath dentro LOGO_PATH
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
    if ($nomeInst) {
        $pdf->SetXY($x, $y);
        $pdf->Cell($txtW, $LOGO_SIZE_MM, $txt, 0, 1, 'L');
    }

    $pdf->Ln(3);
    $pdf->SetFont('Arial','B',13);
    $pdf->Cell(0,7, iconv('UTF-8','ISO-8859-1','Relatório de Série'), 0, 1, 'L');
    $pdf->Ln(1);
}
printHeaderInlineSerie($pdf, $nomeInst, $logoPath, $LOGO_SIZE_MM, $LOGO_GAP_MM, $logoRaw);

// ----------------------------------------------------------------
// 4) Buscar dados da Série
// ----------------------------------------------------------------
$sqlSerie = "
    SELECT s.id_serie,
           s.nome_serie,
           s.total_aulas_semana,
           n.nome_nivel_ensino
      FROM serie s
      JOIN nivel_ensino n ON s.id_nivel_ensino = n.id_nivel_ensino
     WHERE s.id_serie = :id
     LIMIT 1
";
$stmtS = $pdo->prepare($sqlSerie);
$stmtS->execute([':id'=>$idSerie]);
$serie = $stmtS->fetch(PDO::FETCH_ASSOC);

if (!$serie) {
    // caso extremo: embora já checamos existência, mantém mensagem amigável
    $pdf->SetFont('Arial','I',12);
    $pdf->Cell(0,8, iconv('UTF-8','ISO-8859-1','Série não encontrada.'), 0, 1, 'C');
    $pdf->Output(); exit;
}

// Quadro principal
$pdf->SetFont('Arial','B',11);
$pdf->SetFillColor(200,200,200);
$pdf->Cell(130,9, iconv('UTF-8','ISO-8859-1','Série e Total Aulas'), 1, 0, 'C', true);
$pdf->Cell(60, 9, iconv('UTF-8','ISO-8859-1','Nível de Ensino'),    1, 1, 'C', true);

$pdf->SetFont('Arial','',11);
$pdf->Cell(130,9, iconv('UTF-8','ISO-8859-1', $serie['nome_serie'].' - '.$serie['total_aulas_semana']), 1, 0, 'C');
$pdf->Cell(60, 9, iconv('UTF-8','ISO-8859-1', $serie['nome_nivel_ensino']), 1, 1, 'C');

// ----------------------------------------------------------------
// 5) Turmas (com "Série" mesclado/rowspan)
// ----------------------------------------------------------------
if ($turmas) {
    $pdf->Ln(8);
    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(220,220,220);
    $pdf->Cell(190,8, iconv('UTF-8','ISO-8859-1','Turmas desta Série'), 1, 1, 'C', true);

    $sqlT = "
        SELECT id_turma, nome_turma
          FROM turma
         WHERE id_serie = :id
         ORDER BY nome_turma
    ";
    $stmtT = $pdo->prepare($sqlT);
    $stmtT->execute([':id'=>$idSerie]);
    $rowsTurma = $stmtT->fetchAll(PDO::FETCH_ASSOC);

    if (!$rowsTurma) {
        $pdf->SetFont('Arial','I',11);
        $pdf->Cell(0,7, iconv('UTF-8','ISO-8859-1','Nenhuma turma cadastrada para esta série.'), 0, 1, 'L');
    } else {
        // Cabeçalho da grade
        $pdf->SetFont('Arial','B',11);
        $pdf->SetFillColor(200,200,200);
        $pdf->Cell(60,8, iconv('UTF-8','ISO-8859-1','Série'),          1, 0, 'C', true);
        $pdf->Cell(70,8, iconv('UTF-8','ISO-8859-1','Turma'),          1, 0, 'C', true);
        $pdf->Cell(60,8, iconv('UTF-8','ISO-8859-1','Aulas Parciais'), 1, 1, 'C', true);

        // Cálculo das parciais
        $pdf->SetFont('Arial','',11);
        $totalAulas = (int)$serie['total_aulas_semana'];
        $numT       = count($rowsTurma);
        $div        = $numT ? intdiv($totalAulas, $numT) : 0;
        $rest       = $numT ? ($totalAulas % $numT) : 0;

        // Altura por linha e altura total para a célula "mesclada"
        $rowH       = 8;
        $totalH     = $rowH * $numT;

        // Posições para construir o "rowspan"
        $x0 = $pdf->GetX();
        $y0 = $pdf->GetY();

        // 1) Célula única da coluna "Série" (mesclada)
        $pdf->Cell(60, $totalH, iconv('UTF-8','ISO-8859-1', $serie['nome_serie']), 1, 0, 'C');

        // 2) Colunas à direita, linha a linha
        $pdf->SetXY($x0 + 60, $y0);
        for ($i = 0; $i < $numT; $i++) {
            $parcial = $div + (($i == $numT - 1) ? $rest : 0);
            $pdf->Cell(70, $rowH, iconv('UTF-8','ISO-8859-1', $rowsTurma[$i]['nome_turma']), 1, 0, 'C');
            $pdf->Cell(60, $rowH, $parcial, 1, 1, 'C');
            // volta o X para a coluna das turmas
            if ($i < $numT - 1) {
                $pdf->SetX($x0 + 60);
            }
        }
    }
}

// ----------------------------------------------------------------
// 6) Disciplinas (mantido)
// ----------------------------------------------------------------
if ($discOpt) {
    $pdf->Ln(8);
    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(220,220,220);
    $pdf->Cell(190,8, iconv('UTF-8','ISO-8859-1','Disciplinas desta Série'), 1, 1, 'C', true);

    $sqlD = "
        SELECT sd.id_disciplina,
               d.nome_disciplina,
               sd.aulas_semana AS qtd_aulas_disciplina
          FROM serie_disciplinas sd
          JOIN disciplina d ON sd.id_disciplina = d.id_disciplina
         WHERE sd.id_serie = :id
         ORDER BY d.nome_disciplina
    ";
    $stmtD = $pdo->prepare($sqlD);
    $stmtD->execute([':id'=>$idSerie]);
    $rowsDisc = $stmtD->fetchAll(PDO::FETCH_ASSOC);

    if (!$rowsDisc) {
        $pdf->SetFont('Arial','I',11);
        $pdf->Cell(0,7, iconv('UTF-8','ISO-8859-1','Nenhuma disciplina vinculada a esta série.'), 0, 1, 'L');
    } else {
        $pdf->SetFont('Arial','B',11);
        $pdf->SetFillColor(200,200,200);
        $pdf->Cell(40,8, iconv('UTF-8','ISO-8859-1','Série'),      1,0,'C',true);
        $pdf->Cell(50,8, iconv('UTF-8','ISO-8859-1','Disciplina'), 1,0,'C',true);
        $pdf->Cell(20,8, iconv('UTF-8','ISO-8859-1','Aulas'),      1,0,'C',true);
        $pdf->SetFillColor(173,216,230);
        $pdf->Cell(40,8, iconv('UTF-8','ISO-8859-1','Parcial Turma A'), 1,0,'C',true);
        $pdf->SetFillColor(255,255,224);
        $pdf->Cell(40,8, iconv('UTF-8','ISO-8859-1','Parcial Turma B'), 1,1,'C',true);

        $pdf->SetFont('Arial','',11);
        $firstDiscLine = true;
        foreach ($rowsDisc as $disc) {
            $qtdAulas = (int)$disc['qtd_aulas_disciplina'];

            if ($firstDiscLine) {
                $pdf->Cell(40,8, iconv('UTF-8','ISO-8859-1',$serie['nome_serie']), 1,0,'C');
                $firstDiscLine = false;
            } else {
                $pdf->Cell(40,8, '', 1,0,'C');
            }

            $pdf->Cell(50,8, iconv('UTF-8','ISO-8859-1',$disc['nome_disciplina']), 1,0,'C');
            $pdf->Cell(20,8, $qtdAulas, 1,0,'C');

            $div  = intdiv($qtdAulas, 2);
            $rest = $qtdAulas % 2;
            $aVal = $div;
            $bVal = $div + ($rest ? 1 : 0);

            $pdf->SetFillColor(173,216,230);
            $pdf->Cell(40,8, $aVal, 1,0,'C',true);
            $pdf->SetFillColor(255,255,224);
            $pdf->Cell(40,8, $bVal, 1,1,'C',true);
        }
    }
}

// ----------------------------------------------------------------
// 7) Finaliza PDF
// ----------------------------------------------------------------
$pdf->Output();
exit;
?>
