<?php
// app/views/evento.php - RELATÓRIO DE EVENTO INDIVIDUAL (VERSÃO PADRONIZADA PARA logs/seguranca.log)
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

/* ---------------- CONFIG ---------------- */
$PARAMS_ESPERADOS = ['id_evento'];
$LOG_PATH = __DIR__ . '/../../logs/seguranca.log';

/* ---------------- LOG + ABORT ---------------- */
function logSecurity(string $msg): void {
    global $LOG_PATH;
    $meta = [
        'ts'     => date('c'),
        'ip'     => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'ua'     => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'qs'     => $_SERVER['QUERY_STRING'] ?? '',
        'script' => basename($_SERVER['SCRIPT_NAME'] ?? '')
    ];
    $entry = '[' . date('d-M-Y H:i:s T') . '] [SEGURANCA] ' . $msg
           . ' | META=' . json_encode($meta, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    $dir = dirname($LOG_PATH);
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    @file_put_contents($LOG_PATH, $entry, FILE_APPEND | LOCK_EX);
}
function abortInvalid(): void {
    http_response_code(400);
    die('Parâmetros inválidos');
}
function abortNotFound(): void {
    http_response_code(404);
    die('Parâmetros inválidos');
}
function abortForbidden(): void {
    http_response_code(403);
    die('Parâmetros inválidos');
}
function abortServer(): void {
    http_response_code(500);
    die('Parâmetros inválidos');
}

/* ---------------- HELPERS ---------------- */
function enc($s) { return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$s); }
function fmtDate($d) { $ts = strtotime($d); return $ts ? date('d/m/Y', $ts) : ''; }

/* ---------------- VALIDAÇÃO DE PARÂMETROS (WHITELIST) ---------------- */
// Rejeita parâmetros extras
$extras = array_diff(array_keys($_GET), $PARAMS_ESPERADOS);
if (!empty($extras)) {
    logSecurity('Parâmetros inesperados: ' . implode(', ', $extras));
    abortInvalid();
}

// Exige presença explícita
if (!array_key_exists('id_evento', $_GET)) {
    logSecurity('Parâmetro ausente: id_evento | QS: ' . ($_SERVER['QUERY_STRING'] ?? ''));
    abortInvalid();
}

// Valida valor
$id = filter_input(INPUT_GET, 'id_evento', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    logSecurity('id_evento inválido: ' . ($_GET['id_evento'] ?? ''));
    abortInvalid();
}

/* ---------------- BUSCA E AUTORIZA ---------------- */
try {
    // sempre seleciona id_instituicao do ano para checagem de propriedade
    $sql = "
        SELECT e.*, a.ano, a.id_instituicao
        FROM eventos_calendario_escolar e
        JOIN ano_letivo a ON e.id_ano_letivo = a.id_ano_letivo
        WHERE e.id_evento = :id
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $evento = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$evento) {
        logSecurity("Evento não encontrado: id_evento={$id}");
        abortNotFound();
    }

    if (isset($_SESSION['id_instituicao']) && isset($evento['id_instituicao'])) {
        if ((int)$_SESSION['id_instituicao'] !== (int)$evento['id_instituicao']) {
            logSecurity("Tentativa de acessar evento de outra instituição: id_evento={$id} | sess_inst=" . ($_SESSION['id_instituicao'] ?? 'null'));
            abortForbidden();
        }
    }
} catch (Exception $e) {
    logSecurity("Erro buscando evento: " . $e->getMessage() . " | QS: " . ($_SERVER['QUERY_STRING'] ?? ''));
    abortServer();
}

/* ---------------- GERA PDF ---------------- */
class PDF extends FPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, enc('Página ' . $this->PageNo()), 0, 0, 'L');
        $this->Cell(0, 10, enc('Impresso em: ' . date('d/m/Y H:i:s')), 0, 0, 'R');
    }
}

$pdf = new PDF('P', 'mm', 'A4');
$pdf->SetTitle(enc('Relatório de Evento Individual'));
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

/* ---------------- CABEÇALHO (instituição) ---------------- */
$LOGO_SIZE_MM = 15; $LOGO_GAP_MM = 5;
try {
    $stmtInst = $pdo->prepare("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
    $stmtInst->execute();
    $inst = $stmtInst->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    logSecurity("Erro ao buscar instituicao: " . $e->getMessage());
    $inst = [];
}

$nomeInst = $inst['nome_instituicao'] ?? '';
$logoPath = (!empty($inst['imagem_instituicao'])) ? LOGO_PATH . '/' . basename($inst['imagem_instituicao']) : null;
$hasLogo = ($logoPath && file_exists($logoPath));

$pdf->SetY(12);
$pdf->SetFont('Arial', 'B', 12);
$txt = enc($nomeInst);
$txtW = $pdf->GetStringWidth($txt);
$totalW = $hasLogo ? ($LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0) + $txtW) : $txtW;
$x = ($pdf->GetPageWidth() - $totalW) / 2;
$y = $pdf->GetY();
if ($hasLogo) { $pdf->Image($logoPath, $x, $y - 2, $LOGO_SIZE_MM, $LOGO_SIZE_MM); $x += $LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0); }
if ($nomeInst) { $pdf->SetXY($x, $y); $pdf->Cell($txtW, $LOGO_SIZE_MM, $txt, 0, 1, 'L'); }

$pdf->Ln(3);
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, enc('Relatório de Evento Individual'), 0, 1, 'L');
$pdf->Ln(1);

/* ---------------- TABELA PRINCIPAL ---------------- */
$wAno = 20; $wTipo = 30; $wNome = 70; $wIni = 35; $wFim = 35;
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($wAno, 8, enc('Ano'), 1, 0, 'C', true);
$pdf->Cell($wTipo, 8, enc('Tipo'), 1, 0, 'C', true);
$pdf->Cell($wNome, 8, enc('Nome do Evento'), 1, 0, 'C', true);
$pdf->Cell($wIni, 8, enc('Início'), 1, 0, 'C', true);
$pdf->Cell($wFim, 8, enc('Fim'), 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 11);
$tipoFmt = $evento['tipo_evento'] ?? '';
if ($tipoFmt !== '') { $tipoFmt = mb_convert_case($tipoFmt, MB_CASE_TITLE, 'UTF-8'); }

$pdf->Cell($wAno, 8, enc($evento['ano'] ?? ''), 1, 0, 'C');
$pdf->Cell($wTipo, 8, enc($tipoFmt), 1, 0, 'C');
$pdf->Cell($wNome, 8, enc($evento['nome_evento'] ?? ''), 1, 0, 'L');
$pdf->Cell($wIni, 8, enc(fmtDate($evento['data_inicio'] ?? '')), 1, 0, 'C');
$pdf->Cell($wFim, 8, enc(fmtDate($evento['data_fim'] ?? '')), 1, 1, 'C');

/* ---------------- OBSERVAÇÕES ---------------- */
$obs = trim((string)($evento['observacoes'] ?? ''));
if ($obs !== '') {
    $pdf->Ln(6);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, enc('Observações'), 0, 1, 'L');
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetFillColor(245, 245, 245);
    $pdf->MultiCell(0, 8, enc($obs), 1, 'L', true);
}

$pdf->Output();
exit;
?>
