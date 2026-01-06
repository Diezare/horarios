<?php
// app/views/modalidade.php - VERSÃO SEGURA
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

$EXPECTED = ['id_modalidade','categoria'];
$LOG_PATH = __DIR__ . '/../../logs/seguranca.log';

/* ---------- utilidades de segurança ---------- */
function logSecurity(string $msg): void {
    global $LOG_PATH;
    $meta = [
        'ts' => date('c'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'qs' => $_SERVER['QUERY_STRING'] ?? '',
        'script' => basename($_SERVER['SCRIPT_NAME'] ?? '')
    ];
    $entry = '['.date('d-M-Y H:i:s T').'] [SEGURANCA] '.$msg.' | META='.json_encode($meta, JSON_UNESCAPED_UNICODE).PHP_EOL;
    $dir = dirname($LOG_PATH);
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    @file_put_contents($LOG_PATH, $entry, FILE_APPEND | LOCK_EX);
}
function abortInvalid(): void { http_response_code(400); die('Parâmetros inválidos'); }
function enc($s){ return iconv('UTF-8','ISO-8859-1//TRANSLIT',(string)$s); }

/* ---------- whitelist de parâmetros ---------- */
$received = array_keys($_GET);
$extras = array_diff($received, $EXPECTED);
if (!empty($extras)) {
    logSecurity('Parâmetros inesperados em modalidade: '.implode(', ',$extras));
    abortInvalid();
}

/* ---------- ler e validar parâmetros ---------- */
/* id_modalidade é obrigatório */
if (!array_key_exists('id_modalidade', $_GET)) {
    logSecurity('id_modalidade ausente (modalidade)');
    abortInvalid();
}
$idModalidade = filter_input(INPUT_GET, 'id_modalidade', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
if (!$idModalidade) {
    logSecurity('id_modalidade inválido (modalidade): '.($_GET['id_modalidade'] ?? ''));
    abortInvalid();
}

/* categoria, se presente, deve ser exatamente "1" */
$categoriaOpt = false;
if (array_key_exists('categoria', $_GET)) {
    $raw = (string)$_GET['categoria'];
    if ($raw !== '1') {
        logSecurity('categoria inválido (modalidade): raw='.$raw);
        abortInvalid();
    }
    $categoriaOpt = true;
}

/* ---------- verifica existência da modalidade ---------- */
try {
    $st = $pdo->prepare("SELECT id_modalidade, nome_modalidade, descricao FROM modalidade WHERE id_modalidade = ? LIMIT 1");
    $st->execute([$idModalidade]);
    $modalidade = $st->fetch(PDO::FETCH_ASSOC);
    if (!$modalidade) {
        logSecurity("modalidade inexistente: id_modalidade={$idModalidade}");
        abortInvalid();
    }
} catch (Exception $e) {
    logSecurity("Erro SQL verificação modalidade: ".$e->getMessage());
    abortInvalid();
}

/* ---------- se categoriaOpt, carrega categorias vinculadas com prepared ---------- */
$cats = [];
if ($categoriaOpt) {
    try {
        $st = $pdo->prepare("SELECT nome_categoria FROM categoria WHERE id_modalidade = ? ORDER BY nome_categoria");
        $st->execute([$idModalidade]);
        $cats = $st->fetchAll(PDO::FETCH_COLUMN) ?: [];
    } catch (Exception $e) {
        logSecurity("Erro SQL categorias (modalidade): ".$e->getMessage());
        // não expõe o erro ao cliente; mantém lista vazia
        $cats = [];
    }
}

/* ---------- PDF helpers ---------- */
class PDF_M extends FPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10, enc('Página '.$this->PageNo()), 0,0,'L');
        $this->Cell(0,10, enc('Impresso em: '.date('d/m/Y H:i:s')), 0,0,'R');
    }
}
function headerInstitution(FPDF $pdf, string $nome, ?string $logoPath, int $logoSize = 15, int $gap = 5) {
    $top = 12;
    $pdf->SetFont('Arial','B',14);
    $txt = $nome !== '' ? enc($nome) : '';
    $txtW = $txt !== '' ? $pdf->GetStringWidth($txt) : 0;
    $hasLogo = ($logoPath && file_exists($logoPath));
    $totalW = $hasLogo ? ($logoSize + $gap + $txtW) : $txtW;
    $x = ($pdf->GetPageWidth() - $totalW) / 2;
    if ($hasLogo) { $pdf->Image($logoPath, $x, $top - 2, $logoSize, $logoSize); $x += $logoSize + $gap; }
    if ($txt !== '') { $pdf->SetXY($x, $top); $pdf->Cell($txtW, $logoSize, $txt, 0, 1, 'L'); }
    $pdf->SetY(max($top + ($hasLogo ? $logoSize : 0), $top + 8) + 6);
}

/* ---------- coleta dados da instituição (para cabeçalho) ---------- */
try {
    $st = $pdo->prepare("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
    $st->execute();
    $inst = $st->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    logSecurity("Erro SQL instituicao (modalidade): ".$e->getMessage());
    $inst = [];
}
$nomeInst = $inst['nome_instituicao'] ?? '';
$logoPath = (!empty($inst['imagem_instituicao'])) ? LOGO_PATH . '/' . basename($inst['imagem_instituicao']) : null;

/* ---------- gera PDF ---------- */
$pdf = new PDF_M('P','mm','A4');
$pdf->SetTitle(enc('Relatório de Modalidade'));
$pdf->AddPage();

/* cabeçalho visual */
headerInstitution($pdf, $nomeInst, $logoPath);

/* título */
$pdf->SetFont('Arial','B',13);
$pdf->Cell(0,8, enc('Relatório de Modalidade'), 0, 1, 'L');
$pdf->Ln(2);

/* conteúdo principal */
$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(220,220,220);
$pdf->Cell(95,10, enc('Modalidade'), 1, 0, 'C', true);
$pdf->Cell(95,10, enc('Descrição'), 1, 1, 'C', true);

$pdf->SetFont('Arial','',12);
$pdf->Cell(95,10, enc($modalidade['nome_modalidade'] ?? ''), 1, 0, 'C');
$pdf->Cell(95,10, enc($modalidade['descricao'] ?? ''), 1, 1, 'C');

if ($categoriaOpt) {
    $pdf->Ln(6);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,8, enc('Categorias vinculadas'), 0, 1, 'L');
    $pdf->Ln(2);

    $pdf->SetFont('Arial','B',11);
    $pdf->SetFillColor(220,220,220);
    $pdf->Cell(190,9, enc('Categoria'), 1, 1, 'C', true);

    $pdf->SetFont('Arial','',11);
    if (!empty($cats)) {
        foreach ($cats as $c) {
            $pdf->Cell(190,8, enc($c), 1, 1, 'L');
        }
    } else {
        $pdf->Cell(190,8, enc('Nenhuma categoria vinculada.'), 1, 1, 'L');
    }
}

$pdf->Output();
exit;
?>
