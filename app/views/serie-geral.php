<?php
// app/views/serie-geral.php
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
$allowed = ['nivel','turmas','disc'];
$rawQuery = $_SERVER['QUERY_STRING'] ?? '';
parse_str($rawQuery, $receivedParams);

// rejeita parâmetros inesperados
$receivedKeys = array_keys($receivedParams);
$extra = array_diff($receivedKeys, $allowed);
if (!empty($extra)) {
    logSecurity('Parâmetros inesperados em serie-geral.php: ' . implode(', ', $extra) . ' | raw_qs=' . $rawQuery);
    abortClient();
}

// normalize & validate
$canonical = [];
foreach ($receivedParams as $k => $v) {
    if (!is_scalar($v)) {
        logSecurity("Parâmetro não escalar em serie-geral.php: {$k}");
        abortClient();
    }

    if (in_array($k, ['turmas','disc'], true)) {
        $val = (string)$v;
        $okValues = ['', '1', 'on', 'true'];
        if ($val !== '' && !in_array(strtolower($val), $okValues, true)) {
            logSecurity("Valor inválido para flag {$k} em serie-geral.php raw=" . var_export($v, true));
            abortClient();
        }
        $canonical[$k] = in_array(strtolower($val), ['1','on','true'], true) ? 1 : 0;
        continue;
    }

    // 'nivel' accepts 'todos' or non-negative integer
    if ($k === 'nivel') {
        $s = (string)$v;
        if (strtolower($s) === 'todos') {
            $canonical[$k] = 'todos';
            continue;
        }
        if (!preg_match('/^\d+$/', $s)) {
            logSecurity("Valor inválido para nivel em serie-geral.php raw=" . var_export($v, true));
            abortClient();
        }
        $canonical[$k] = (int)$s;
        continue;
    }

    // any other param (shouldn't happen)
    logSecurity("Parâmetro inesperado no loop em serie-geral.php: {$k}");
    abortClient();
}

// Canonical ordering check
$normalized_received_array = [];
foreach ($receivedParams as $k => $v) {
    if (in_array($k, ['turmas','disc'], true)) {
        $normalized_received_array[$k] = in_array(strtolower((string)$v), ['1','on','true'], true) ? 1 : 0;
    } elseif ($k === 'nivel') {
        $s = (string)$v;
        $normalized_received_array[$k] = (strtolower($s) === 'todos') ? 'todos' : (int)$s;
    } else {
        $normalized_received_array[$k] = $v;
    }
}
ksort($canonical);
ksort($normalized_received_array);
if ($canonical !== $normalized_received_array) {
    logSecurity("Query string não canônica em serie-geral.php: expected=" . json_encode($canonical) . " got=" . json_encode($normalized_received_array) . " | raw_qs={$rawQuery}");
    abortClient();
}

/* -----------------------------
   Checa sessão e autorização básica
------------------------------*/
if (empty($_SESSION['id_usuario'])) {
    logSecurity("Acesso sem sessão em serie-geral.php raw_qs={$rawQuery}");
    abortClient();
}
$idUsuario = (int)$_SESSION['id_usuario'];

/* -----------------------------
   Normaliza flags e valida 'nivel' se fornecido
------------------------------*/
$turmas = !empty($canonical['turmas']);
$discOpt = !empty($canonical['disc']);
$nivelOpt = $canonical['nivel'] ?? 'todos';

if ($nivelOpt !== 'todos') {
    $nivelOpt = (int)$nivelOpt;
    // verifica existência do nivel e se o usuario tem acesso a ele (usuario_niveis)
    $stN = $pdo->prepare("SELECT 1 FROM nivel_ensino n JOIN usuario_niveis un ON n.id_nivel_ensino = un.id_nivel_ensino WHERE n.id_nivel_ensino = :niv AND un.id_usuario = :idu LIMIT 1");
    $stN->execute([':niv' => $nivelOpt, ':idu' => $idUsuario]);
    if ($stN->fetchColumn() === false) {
        logSecurity("Nivel inexistente ou não autorizado em serie-geral.php: nivel={$nivelOpt} user={$idUsuario}");
        abortClient();
    }
}

/* -----------------------------
   Parâmetros visuais
------------------------------*/
$LOGO_SIZE_MM = 15; // tamanho (largura=altura) da logo em mm
$LOGO_GAP_MM  = 5;  // espaço entre a logo e o nome em mm

class PDF extends FPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,iconv('UTF-8','ISO-8859-1','Página ' . $this->PageNo()),0,0,'L');
        $this->Cell(0,10,iconv('UTF-8','ISO-8859-1','Impresso em: ' . date('d/m/Y H:i:s')),0,0,'R');
    }
}

/* -----------------------------
   Inicia PDF e carrega instituicao
------------------------------*/
$pdf = new PDF('P','mm','A4');
$pdf->SetTitle(iconv('UTF-8','ISO-8859-1','Relatório Geral de Séries'));

// Dados da instituição uma vez
$stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
$inst = $stmtInst->fetch(PDO::FETCH_ASSOC);
$NOME_INST = $inst ? ($inst['nome_instituicao'] ?? '') : '';
$logoRaw   = ($inst && !empty($inst['imagem_instituicao'])) ? $inst['imagem_instituicao'] : null;
$LOGO_PATH = $logoRaw ? LOGO_PATH . '/' . basename($logoRaw) : null;

/* Função: cabeçalho inline (logo + nome), com proteção de caminho */
function printHeaderInline(FPDF $pdf, $nomeInst, $logoPath, $LOGO_SIZE_MM, $LOGO_GAP_MM, $logoRaw = null) {
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
    $pdf->Cell(0,7, iconv('UTF-8','ISO-8859-1','Relatório Geral de Séries'), 0, 1, 'L');
    $pdf->Ln(1);
}

/* -----------------------------
   Query: séries que o usuário pode ver
------------------------------*/
$sql = "
    SELECT s.id_serie, s.nome_serie, s.total_aulas_semana, n.nome_nivel_ensino
    FROM serie s
    JOIN nivel_ensino n ON s.id_nivel_ensino = n.id_nivel_ensino
    JOIN usuario_niveis un ON n.id_nivel_ensino = un.id_nivel_ensino
    WHERE un.id_usuario = :idUsu
";
$params = [':idUsu' => $idUsuario];
if ($nivelOpt !== 'todos') {
    $sql .= " AND n.id_nivel_ensino = :nivel ";
    $params[':nivel'] = $nivelOpt;
}
$sql .= " ORDER BY s.nome_serie";

$stmtSeries = $pdo->prepare($sql);
$stmtSeries->execute($params);
$series = $stmtSeries->fetchAll(PDO::FETCH_ASSOC);

if (!$series) {
    $pdf->AddPage();
    printHeaderInline($pdf, $NOME_INST, $LOGO_PATH, $LOGO_SIZE_MM, $LOGO_GAP_MM, $logoRaw);
    $pdf->SetFont('Arial','I',12);
    $pdf->Cell(0,8, iconv('UTF-8','ISO-8859-1','Nenhuma série encontrada para este usuário.'), 0,1,'C');
    $pdf->Output(); exit;
}

/* -----------------------------
   Preloads opcionais
------------------------------*/
$disciplinasPorSerie = [];
if ($discOpt) {
    $sqlDisc = "
        SELECT sd.id_serie, d.nome_disciplina, sd.aulas_semana AS qtd_aulas
        FROM serie_disciplinas sd
        JOIN disciplina d ON sd.id_disciplina = d.id_disciplina
        ORDER BY d.nome_disciplina
    ";
    $rowsD = $pdo->query($sqlDisc)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rowsD as $row) {
        $idS = (int)$row['id_serie'];
        if (!isset($disciplinasPorSerie[$idS])) $disciplinasPorSerie[$idS] = [];
        $disciplinasPorSerie[$idS][] = $row;
    }
}

$turmasPorSerie = [];
if ($turmas) {
    $sqlT = "SELECT id_serie, nome_turma FROM turma ORDER BY nome_turma";
    $rowsT = $pdo->query($sqlT)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rowsT as $row) {
        $idS = (int)$row['id_serie'];
        if (!isset($turmasPorSerie[$idS])) $turmasPorSerie[$idS] = [];
        $turmasPorSerie[$idS][] = $row['nome_turma'];
    }
}

/* -----------------------------
   Imprime cada série em página própria
------------------------------*/
foreach ($series as $serie) {
    $pdf->AddPage();
    printHeaderInline($pdf, $NOME_INST, $LOGO_PATH, $LOGO_SIZE_MM, $LOGO_GAP_MM, $logoRaw);

    // Dados da Série
    $pdf->SetFont('Arial','B',11);
    $pdf->SetFillColor(200,200,200);
    $pdf->Cell(130,9, iconv('UTF-8','ISO-8859-1','Série e Total de Aulas'), 1, 0, 'C', true);
    $pdf->Cell(60, 9, iconv('UTF-8','ISO-8859-1','Nível de Ensino'),    1, 1, 'C', true);

    $pdf->SetFont('Arial','',11);
    $pdf->Cell(130,9, iconv('UTF-8','ISO-8859-1', $serie['nome_serie'].' - '.$serie['total_aulas_semana']), 1, 0, 'C');
    $pdf->Cell(60, 9, iconv('UTF-8','ISO-8859-1', $serie['nome_nivel_ensino']), 1, 1, 'C');

    // Turmas
    if ($turmas) {
        $pdf->Ln(8);
        $pdf->SetFont('Arial','B',12);
        $pdf->SetFillColor(220,220,220);
        $pdf->Cell(190,8, iconv('UTF-8','ISO-8859-1','Turmas desta Série'), 1,1,'C', true);

        $rowsTurma = $turmasPorSerie[(int)$serie['id_serie']] ?? [];
        if (!empty($rowsTurma)) {
            $totalAulas  = (int)$serie['total_aulas_semana'];
            $numT        = count($rowsTurma);
            $div         = $numT ? intdiv($totalAulas, $numT) : 0;
            $rest        = $numT ? ($totalAulas % $numT) : 0;

            $pdf->SetFont('Arial','B',11);
            $pdf->SetFillColor(200,200,200);
            $pdf->Cell(60,8, iconv('UTF-8','ISO-8859-1','Série'),          1,0,'C',true);
            $pdf->Cell(70,8, iconv('UTF-8','ISO-8859-1','Turma'),          1,0,'C',true);
            $pdf->Cell(60,8, iconv('UTF-8','ISO-8859-1','Aulas Parciais'), 1,1,'C',true);

            $pdf->SetFont('Arial','',11);
            $rowH   = 8;
            $totalH = $rowH * $numT;

            $x0 = $pdf->GetX();
            $y0 = $pdf->GetY();

            $pdf->Cell(60, $totalH, iconv('UTF-8','ISO-8859-1', $serie['nome_serie']), 1, 0, 'C');

            $pdf->SetXY($x0 + 60, $y0);
            for ($i = 0; $i < $numT; $i++) {
                $parcial = $div + (($i == $numT - 1) ? $rest : 0);
                $pdf->Cell(70, $rowH, iconv('UTF-8','ISO-8859-1', $rowsTurma[$i]), 1, 0, 'C');
                $pdf->Cell(60, $rowH, $parcial, 1, 1, 'C');
                if ($i < $numT - 1) $pdf->SetX($x0 + 60);
            }
        } else {
            $pdf->SetFont('Arial','I',11);
            $pdf->Cell(190,8, iconv('UTF-8','ISO-8859-1','Nenhuma turma cadastrada para esta série.'), 1,1,'C');
        }
    }

    // Disciplinas
    if ($discOpt) {
        $pdf->Ln(8);
        $pdf->SetFont('Arial','B',12);
        $pdf->SetFillColor(220,220,220);
        $pdf->Cell(190,8, iconv('UTF-8','ISO-8859-1','Disciplinas desta Série'), 1,1,'C', true);

        if (!isset($disciplinasPorSerie[(int)$serie['id_serie']])) {
            $stmtD = $pdo->prepare("
                SELECT d.nome_disciplina, sd.aulas_semana AS qtd_aulas
                FROM serie_disciplinas sd
                JOIN disciplina d ON sd.id_disciplina = d.id_disciplina
                WHERE sd.id_serie = :id
                ORDER BY d.nome_disciplina
            ");
            $stmtD->execute([':id'=>$serie['id_serie']]);
            $disciplinasPorSerie[(int)$serie['id_serie']] = $stmtD->fetchAll(PDO::FETCH_ASSOC);
        }
        $rowsDisc = $disciplinasPorSerie[(int)$serie['id_serie']];

        if (!$rowsDisc) {
            $pdf->SetFont('Arial','I',11);
            $pdf->Cell(190,8, iconv('UTF-8','ISO-8859-1','Nenhuma disciplina vinculada a esta série.'), 1,1,'C');
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
            $firstLine = true;
            foreach ($rowsDisc as $disc) {
                $qtdAulas = (int)$disc['qtd_aulas'];

                if ($firstLine) {
                    $pdf->Cell(40,8, iconv('UTF-8','ISO-8859-1',$serie['nome_serie']), 1,0,'C');
                    $firstLine = false;
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
}

/* -----------------------------
   Finaliza
------------------------------*/
$pdf->Output();
exit;
?>
