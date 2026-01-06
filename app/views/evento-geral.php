<?php
// app/views/evento-geral.php - VERSÃO ULTRASEGURA (LOG para logs/seguranca.log apenas)
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

/* ============================================================
   CONFIG E FUNÇÕES BÁSICAS
============================================================ */
$TIPOS_PERMITIDOS   = ['ferias', 'feriado', 'recesso', 'todos'];
$PARAMS_ESPERADOS   = ['id_ano_letivo', 'tipo_evento'];
$LOG_PATH           = __DIR__ . '/../../logs/seguranca.log';

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

function abortWithInvalid(): void {
    http_response_code(400);
    die('Parâmetros inválidos');
}

function abortWithServerError(): void {
    http_response_code(500);
    die('Parâmetros inválidos');
}

function abortWithNotFound(): void {
    http_response_code(404);
    die('Parâmetros inválidos');
}

function enc($s) { return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$s); }
function fmtDate($d) { $ts = strtotime($d); return $ts ? date('d/m/Y', $ts) : ''; }

/* ============================================================
   VALIDAÇÃO DE PARÂMETROS
============================================================ */
// Rejeita parâmetros extras
$extras = array_diff(array_keys($_GET), $PARAMS_ESPERADOS);
if (!empty($extras)) {
    logSecurity('Parâmetros inesperados: ' . implode(', ', $extras));
    abortWithInvalid();
}

// Exige ambos
if (!isset($_GET['id_ano_letivo'], $_GET['tipo_evento'])) {
    logSecurity('Parâmetros obrigatórios ausentes. QS: ' . ($_SERVER['QUERY_STRING'] ?? ''));
    abortWithInvalid();
}

// Valida valores
$idAnoLetivo = filter_input(INPUT_GET, 'id_ano_letivo', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$tipoEvento = mb_strtolower(trim((string)($_GET['tipo_evento'] ?? '')), 'UTF-8');

if (!$idAnoLetivo || !in_array($tipoEvento, $TIPOS_PERMITIDOS, true)) {
    logSecurity('Tentativa de acesso inválido: ' . ($_SERVER['QUERY_STRING'] ?? ''));
    abortWithInvalid();
}

/* ============================================================
   VERIFICA EXISTÊNCIA DO ANO LETIVO E PERMISSÃO
============================================================ */
try {
    $stmt = $pdo->prepare("SELECT id_ano_letivo FROM ano_letivo WHERE id_ano_letivo = :id LIMIT 1");
    $stmt->execute([':id' => $idAnoLetivo]);
    if (!$stmt->fetch()) {
        logSecurity("Ano letivo inexistente: id_ano_letivo={$idAnoLetivo}");
        abortWithInvalid();
    }
} catch (Exception $e) {
    logSecurity("Erro ao validar ano letivo: " . $e->getMessage());
    abortWithServerError();
}

/* ============================================================
   CONSULTA SEGURA
============================================================ */
try {
    $sql = "
        SELECT e.*, a.ano
        FROM eventos_calendario_escolar e
        JOIN ano_letivo a ON e.id_ano_letivo = a.id_ano_letivo
        WHERE e.id_ano_letivo = :ano
    ";
    $params = [':ano' => $idAnoLetivo];

    if ($tipoEvento !== 'todos') {
        $sql .= " AND e.tipo_evento = :tipo";
        $params[':tipo'] = $tipoEvento;
    }

    $sql .= " ORDER BY a.ano ASC, e.data_inicio ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logSecurity("Erro SQL evento-geral: " . $e->getMessage() . " | QS: " . ($_SERVER['QUERY_STRING'] ?? ''));
    abortWithServerError();
}

/* ============================================================
   PDF
============================================================ */
class PDF extends FPDF {
    function NbLines($w, $txt) {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', (string)$txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n") $nb--;
        $sep = -1; $i = 0; $j = 0; $l = 0; $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") { $i++; $sep = -1; $j = $i; $l = 0; $nl++; continue; }
            if ($c == ' ') $sep = $i;
            $l += $cw[$c] ?? 0;
            if ($l > $wmax) {
                if ($sep == -1) { if ($i == $j) $i++; } else { $i = $sep + 1; }
                $sep = -1; $j = $i; $l = 0; $nl++;
            } else { $i++; }
        }
        return $nl;
    }
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, enc('Página ' . $this->PageNo()), 0, 0, 'L');
        $this->Cell(0, 10, enc('Impresso em: ' . date('d/m/Y H:i:s')), 0, 0, 'R');
    }
}

$pdf = new PDF('P', 'mm', 'A4');
$pdf->SetTitle(enc('Relatório Geral de Eventos'));
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

/* Cabeçalho com logo */
$LOGO_SIZE_MM = 15; $LOGO_GAP_MM = 5;
try {
    $stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
    $inst = $stmtInst->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) { $inst = []; }

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
if ($hasLogo) { $pdf->Image($logoPath, $x, $y - 2, $LOGO_SIZE_MM, $LOGO_SIZE_MM); $x += $LOGO_SIZE_MM + $LOGO_GAP_MM; }
if ($nomeInst) { $pdf->SetXY($x, $y); $pdf->Cell($txtW, $LOGO_SIZE_MM, $txt, 0, 1, 'L'); }

$pdf->Ln(3);
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, enc('Relatório Geral de Eventos do Calendário Escolar'), 0, 1, 'L');
$pdf->Ln(1);

/* Tabela */
$wAno = 25; $wTipo = 30; $wNome = 75; $wIni = 30; $wFim = 30;
$lineH = 6;
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($wAno, 8, enc('Ano'), 1, 0, 'C', true);
$pdf->Cell($wTipo, 8, enc('Tipo'), 1, 0, 'C', true);
$pdf->Cell($wNome, 8, enc('Nome do Evento'), 1, 0, 'C', true);
$pdf->Cell($wIni, 8, enc('Início'), 1, 0, 'C', true);
$pdf->Cell($wFim, 8, enc('Fim'), 1, 1, 'C', true);
$pdf->SetFont('Arial', '', 9);

if (empty($rows)) {
    $pdf->Cell(190, 8, enc('Nenhum evento encontrado.'), 1, 1, 'C');
    $pdf->Output(); exit;
}

$grouped = [];
foreach ($rows as $r) { $grouped[$r['ano']][] = $r; }

foreach ($grouped as $ano => $items) {
    $blockHeight = count($items) * $lineH;
    $xLeft = $pdf->GetX(); $yTop = $pdf->GetY();
    foreach ($items as $ev) {
        $tipo = mb_convert_case($ev['tipo_evento'], MB_CASE_TITLE, 'UTF-8');
        $pdf->Cell($wAno, $lineH, enc($ano), 1, 0, 'C');
        $pdf->Cell($wTipo, $lineH, enc($tipo), 1, 0, 'C');
        $pdf->Cell($wNome, $lineH, enc($ev['nome_evento']), 1, 0, 'L');
        $pdf->Cell($wIni, $lineH, enc(fmtDate($ev['data_inicio'])), 1, 0, 'C');
        $pdf->Cell($wFim, $lineH, enc(fmtDate($ev['data_fim'])), 1, 1, 'C');
    }
}

$pdf->Output();
exit;
?>