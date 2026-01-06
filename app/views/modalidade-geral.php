<?php
// app/views/modalidade-geral.php - VERSÃO SEGURA
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

$PARAMS_ESPERADOS = ['modalidade','categoria'];
$LOG_PATH = __DIR__ . '/../../logs/seguranca.log';

function logSecurity(string $msg): void {
    global $LOG_PATH;
    $meta = [
        'ts'=>date('c'),
        'ip'=>$_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'ua'=>$_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'qs'=>$_SERVER['QUERY_STRING'] ?? '',
        'script'=>basename($_SERVER['SCRIPT_NAME'] ?? '')
    ];
    $entry = '['.date('d-M-Y H:i:s T').'] [SEGURANCA] '.$msg.' | META='.json_encode($meta, JSON_UNESCAPED_UNICODE).PHP_EOL;
    $dir = dirname($LOG_PATH);
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    @file_put_contents($LOG_PATH, $entry, FILE_APPEND | LOCK_EX);
}
function abortInvalid(): void { http_response_code(400); die('Parâmetros inválidos'); }
function abortServer(): void  { http_response_code(500); die('Parâmetros inválidos'); }
function abortNotFound(): void{ http_response_code(404); die('Parâmetros inválidos'); }

function enc($s){ return iconv('UTF-8','ISO-8859-1//TRANSLIT',(string)$s); }

/* WHITELIST */
$extras = array_diff(array_keys($_GET), $PARAMS_ESPERADOS);
if (!empty($extras)) {
    logSecurity('Parâmetros inesperados em modalidade-geral: '.implode(', ',$extras));
    abortInvalid();
}

/* lê e valida parâmetros */
$modalidadeRaw = array_key_exists('modalidade', $_GET) ? (string)$_GET['modalidade'] : 'todos';
$categoriaRaw  = array_key_exists('categoria', $_GET) ? (string)$_GET['categoria'] : null;

/* categoria, se presente, deve ser exatamente "1" (comportamento opcional) */
$categoriaOpt = false;
if ($categoriaRaw !== null) {
    if ($categoriaRaw !== '1') {
        logSecurity("categoria inválido (modalidade-geral): raw={$categoriaRaw}");
        abortInvalid();
    }
    $categoriaOpt = true;
}

/* modalidade: 'todos' ou id numérico */
$modalidadeFilter = 'todos';
if ($modalidadeRaw !== null && $modalidadeRaw !== '' && mb_strtolower($modalidadeRaw,'UTF-8') !== 'todos') {
    $mid = filter_var($modalidadeRaw, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    if ($mid === false) {
        logSecurity("modalidade inválido (modalidade-geral): raw={$modalidadeRaw}");
        abortInvalid();
    }
    /* verifica existência */
    try {
        $st = $pdo->prepare("SELECT 1 FROM modalidade WHERE id_modalidade = ? LIMIT 1");
        $st->execute([$mid]);
        if (!$st->fetchColumn()) {
            logSecurity("modalidade inexistente (modalidade-geral): id={$mid}");
            abortNotFound();
        }
    } catch (Exception $e) {
        logSecurity("Erro SQL verificação modalidade: ".$e->getMessage());
        abortServer();
    }
    $modalidadeFilter = $mid;
}

/* consulta modalidades (prepared) */
try {
    $sql = "SELECT id_modalidade, nome_modalidade, descricao FROM modalidade";
    $params = [];
    if ($modalidadeFilter !== 'todos') {
        $sql .= " WHERE id_modalidade = ?";
        $params[] = $modalidadeFilter;
    }
    $sql .= " ORDER BY nome_modalidade";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $modalidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logSecurity("Erro SQL modalidades: ".$e->getMessage());
    abortServer();
}

/* instituicao para cabeçalho */
try {
    $stmtInst = $pdo->prepare("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
    $stmtInst->execute();
    $inst = $stmtInst->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    logSecurity("Erro SQL instituicao (modalidade-geral): ".$e->getMessage());
    $inst = [];
}

/* PDF helpers */
class PDF_MG extends FPDF {
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
                if ($sep == -1) { if ($i == $j) $i++; }
                else $i = $sep + 1;
                $sep = -1; $j = $i; $l = 0; $nl++;
            } else $i++;
        }
        return $nl;
    }
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,enc('Página '.$this->PageNo()),0,0,'L');
        $this->Cell(0,10,enc('Impresso em: '.date('d/m/Y H:i:s')),0,0,'R');
    }
}
function enc($s){ return iconv('UTF-8','ISO-8859-1//TRANSLIT',(string)$s); }

$LOGO_SIZE_MM = 15; $LOGO_GAP_MM = 5;
$nomeInst = $inst['nome_instituicao'] ?? '';
$logoPath = (!empty($inst['imagem_instituicao'])) ? LOGO_PATH . '/' . basename($inst['imagem_instituicao']) : null;

/* gera PDF */
$pdf = new PDF_MG('P','mm','A4');
$pdf->SetTitle(enc('Relatório Geral de Modalidades'));
$pdf->AddPage();

/* header */
$pdf->SetY(12); $pdf->SetFont('Arial','B',14);
$txt = $nomeInst !== '' ? enc($nomeInst) : '';
$txtW = $txt !== '' ? $pdf->GetStringWidth($txt) : 0;
$hasLogo = ($logoPath && file_exists($logoPath));
$totalW = $hasLogo ? ($LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0) + $txtW) : $txtW;
$x = ($pdf->GetPageWidth() - $totalW) / 2;
$y = $pdf->GetY();
if ($hasLogo) { $pdf->Image($logoPath, $x, $y - 2, $LOGO_SIZE_MM, $LOGO_SIZE_MM); $x += $LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0); }
if ($txt !== '') { $pdf->SetXY($x, $y); $pdf->Cell($txtW, $LOGO_SIZE_MM, $txt, 0, 1, 'L'); }
$pdf->Ln(3);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,7,enc('Relatório Geral de Modalidades'),0,1,'L');
$pdf->Ln(1);

/* loop modalidades */
foreach ($modalidades as $mod) {
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,7,enc($mod['nome_modalidade']),0,1,'L');
    $pdf->Ln(1);

    if ($categoriaOpt) {
        try {
            $st = $pdo->prepare("SELECT nome_categoria FROM categoria WHERE id_modalidade = :idMod ORDER BY nome_categoria");
            $st->execute([':idMod' => $mod['id_modalidade']]);
            $cats = $st->fetchAll(PDO::FETCH_COLUMN) ?: [];
            $catsText = $cats ? implode(', ', $cats) : '-';
        } catch (Exception $e) {
            logSecurity("Erro SQL categorias (modalidade-geral): ".$e->getMessage());
            $catsText = '-';
        }

        $pdf->SetFont('Arial','B',11); $pdf->SetFillColor(220,220,220);
        $pdf->Cell(95,9,enc('Modalidade'),1,0,'C',true);
        $pdf->Cell(95,9,enc('Categorias'),1,1,'C',true);

        $lineH = 8; $wLeft = 95; $wRight = 95;
        $catsEnc = enc($catsText);
        $nb = $pdf->NbLines($wRight, $catsEnc);
        $h = $lineH * max(1, $nb);
        $x0 = $pdf->GetX(); $y0 = $pdf->GetY();
        $pdf->Cell($wLeft, $h, enc($mod['nome_modalidade']), 1, 0, 'C');
        $pdf->MultiCell($wRight, $lineH, $catsEnc, 1, 'C');
    } else {
        $pdf->SetFont('Arial','B',11); $pdf->SetFillColor(220,220,220);
        $pdf->Cell(190,9,enc('Modalidade'),1,1,'C',true);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(190,8,enc($mod['nome_modalidade']),1,1,'C');
    }

    if (!empty($mod['descricao'])) {
        $pdf->Ln(2);
        $pdf->SetFont('Arial','B',11);
        $pdf->Cell(190,9,enc('Descrição'),1,1,'C',true);
        $pdf->SetFont('Arial','',10);
        $pdf->MultiCell(190,7,enc($mod['descricao']),1,'L');
    }

    $pdf->Ln(6);
}

$pdf->Output();
exit;
?>