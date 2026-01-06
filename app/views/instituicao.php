<?php
// app/views/instituicao.php — versão segura e validada
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

/* ============================================================
   CONFIGURAÇÃO
============================================================ */
$PARAMS_ESPERADOS = ['id_inst'];
$DEFAULT_BAD_MSG = 'Parâmetros inválidos';
$LOG_PATH = __DIR__ . '/../../logs/seguranca.log';

/* ============================================================
   FUNÇÕES AUXILIARES
============================================================ */
function logSecurity(string $msg): void {
    global $LOG_PATH;
    $meta = [
        'ts'   => date('c'),
        'ip'   => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'ua'   => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'qs'   => $_SERVER['QUERY_STRING'] ?? '',
        'script' => basename($_SERVER['SCRIPT_NAME'] ?? '')
    ];
    $entry = '[' . date('d-M-Y H:i:s T') . '] [SEGURANCA] ' . $msg .
             ' | META=' . json_encode($meta, JSON_UNESCAPED_UNICODE) . PHP_EOL;

    $dir = dirname($LOG_PATH);
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    @file_put_contents($LOG_PATH, $entry, FILE_APPEND | LOCK_EX);
}

function abortBad(): void {
    http_response_code(400);
    die('Parâmetros inválidos');
}
function abortNotFound(): void {
    http_response_code(404);
    die('Parâmetros inválidos');
}
function abortServer(): void {
    http_response_code(500);
    die('Parâmetros inválidos');
}
function enc(string $s): string {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$s);
}

/* ============================================================
   VALIDAÇÃO DE PARÂMETROS
============================================================ */
$extras = array_diff(array_keys($_GET), $PARAMS_ESPERADOS);
if (!empty($extras)) {
    logSecurity('Parâmetros inesperados: ' . implode(', ', $extras));
    abortBad();
}

if (!array_key_exists('id_inst', $_GET)) {
    logSecurity('Ausência de parâmetro id_inst');
    abortBad();
}

$id_inst = filter_input(INPUT_GET, 'id_inst', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id_inst) {
    logSecurity('id_inst inválido: ' . ($_GET['id_inst'] ?? ''));
    abortBad();
}

/* ============================================================
   BUSCA INSTITUIÇÃO
============================================================ */
try {
    $stmtInst = $pdo->prepare("
        SELECT nome_instituicao, imagem_instituicao,
               cnpj_instituicao, endereco_instituicao,
               telefone_instituicao, email_instituicao
        FROM instituicao
        WHERE id_instituicao = :id
        LIMIT 1
    ");
    $stmtInst->execute([':id' => $id_inst]);
    $inst = $stmtInst->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logSecurity('Erro SQL ao buscar instituição: ' . $e->getMessage());
    abortServer();
}

/* ============================================================
   PDF
============================================================ */
class PDF extends FPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,iconv('UTF-8','ISO-8859-1','Página '.$this->PageNo()),0,0,'L');
        $this->Cell(0,10,iconv('UTF-8','ISO-8859-1','Impresso em: '.date('d/m/Y H:i:s')),0,0,'R');
    }
}

$pdf = new PDF('P', 'mm', 'A4');
$pdf->SetTitle(iconv('UTF-8','ISO-8859-1','Relatório de Instituição'));
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

/* ============================================================
   CABEÇALHO
============================================================ */
$LOGO_SIZE_MM = 15;
$LOGO_GAP_MM  = 5;

$pdf->SetY(12);
$pdf->SetFont('Arial','B',12);

$nomeInst = $inst['nome_instituicao'] ?? '';
$logoPath = (!empty($inst['imagem_instituicao'])) ? LOGO_PATH . '/' . basename($inst['imagem_instituicao']) : null;
$hasLogo  = ($logoPath && file_exists($logoPath));

$txt = iconv('UTF-8','ISO-8859-1//TRANSLIT', $nomeInst);
$txtW = $pdf->GetStringWidth($txt);
$totalW = $hasLogo ? ($LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0) + $txtW) : $txtW;
$pageW = $pdf->GetPageWidth();
$x = ($pageW - $totalW) / 2;
$y = $pdf->GetY();

if ($hasLogo) {
    $pdf->Image($logoPath, $x, $y - 2, $LOGO_SIZE_MM, $LOGO_SIZE_MM);
    $x += $LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0);
}
if ($nomeInst) {
    $pdf->SetXY($x, $y);
    $pdf->Cell($txtW, $LOGO_SIZE_MM, $txt, 0, 1, 'L');
}

$pdf->Ln(3);
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0, 8, iconv('UTF-8','ISO-8859-1//TRANSLIT','Relatório de Instituição'), 0, 1, 'L');
$pdf->Ln(2);

/* ============================================================
   DADOS DA INSTITUIÇÃO
============================================================ */
$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(220,220,220);
$labelWidth = 40;
$valueWidth = 150;

$pdf->SetFont('Arial','',12);

if ($inst) {
    $campos = [
        'Nome:'      => $inst['nome_instituicao'] ?? '',
        'CNPJ:'      => $inst['cnpj_instituicao'] ?? '',
        'Endereço:'  => $inst['endereco_instituicao'] ?? '',
        'Telefone:'  => $inst['telefone_instituicao'] ?? '',
        'Email:'     => $inst['email_instituicao'] ?? ''
    ];
    foreach ($campos as $rotulo => $valor) {
        $pdf->Cell($labelWidth, 12, iconv('UTF-8','ISO-8859-1',$rotulo), 1, 0, 'L', true);
        $pdf->Cell($valueWidth, 12, iconv('UTF-8','ISO-8859-1',$valor), 1, 1, 'L');
    }
} else {
    logSecurity("Instituição não encontrada: id_instituicao={$id_inst}");
    $pdf->Cell(190, 10, iconv('UTF-8','ISO-8859-1','Instituição não encontrada.'), 1, 1, 'C');
}

/* ============================================================
   SAÍDA FINAL
============================================================ */
$pdf->Output();
exit;
?>