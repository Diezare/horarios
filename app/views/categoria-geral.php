<?php
// app/views/categoria-geral.php - VERSÃO SEGURA (logs/seguranca.log, validação rígida)
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

$PARAMS_ESPERADOS = ['categoria', 'modalidade', 'professores'];
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

function enc($s) { return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$s); }

/* ---------------- VALIDATION (WHITELIST) ---------------- */
$extras = array_diff(array_keys($_GET), $PARAMS_ESPERADOS);
if (!empty($extras)) {
    logSecurity('Parâmetros inesperados em categoria-geral: '.implode(', ',$extras));
    abortInvalid();
}

/* leitura segura dos parâmetros opcionais */
$categoriaRaw = array_key_exists('categoria', $_GET) ? (string)$_GET['categoria'] : null;
$modalidadeRaw = array_key_exists('modalidade', $_GET) ? (string)$_GET['modalidade'] : null;
$professoresRaw = array_key_exists('professores', $_GET) ? (string)$_GET['professores'] : null;

/* modalidade e professores, se presentes, devem ser exatamente "1" */
$modalidadeOpt = null;
$professoresOpt = null;

if ($modalidadeRaw !== null) {
    if ($modalidadeRaw !== '1') {
        logSecurity('modalidade inválido: '.$modalidadeRaw);
        abortInvalid();
    }
    $modalidadeOpt = true;
}

if ($professoresRaw !== null) {
    if ($professoresRaw !== '1') {
        logSecurity('professores inválido: '.$professoresRaw);
        abortInvalid();
    }
    $professoresOpt = true;
}

/* categoria: 'todas' ou id numérico válido e existente */
$categoriaParam = 'todas';
if ($categoriaRaw !== null && $categoriaRaw !== '') {
    if (mb_strtolower($categoriaRaw, 'UTF-8') === 'todas') {
        $categoriaParam = 'todas';
    } else {
        $catId = filter_var($categoriaRaw, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if ($catId === false) {
            logSecurity('categoria inválido: '.$categoriaRaw);
            abortInvalid();
        }
        // verifica existência
        try {
            $stmtCheck = $pdo->prepare("SELECT 1 FROM categoria WHERE id_categoria = ? LIMIT 1");
            $stmtCheck->execute([$catId]);
            if (!$stmtCheck->fetchColumn()) {
                logSecurity('categoria inexistente: id_categoria='.$catId);
                abortNotFound();
            }
            $categoriaParam = (int)$catId;
        } catch (Exception $e) {
            logSecurity('Erro SQL verificação categoria: '.$e->getMessage());
            abortServer();
        }
    }
}

/* ---------------- CONSULTA PRINCIPAL ---------------- */
try {
    $sql = "
        SELECT c.id_categoria, c.nome_categoria, c.descricao, c.id_modalidade, m.nome_modalidade
        FROM categoria c
        JOIN modalidade m ON c.id_modalidade = m.id_modalidade
    ";
    $params = [];
    if ($categoriaParam !== 'todas') {
        $sql .= " WHERE c.id_categoria = ?";
        $params[] = $categoriaParam;
    }
    $sql .= " ORDER BY c.nome_categoria";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logSecurity('Erro SQL categorias: '.$e->getMessage().' | QS: '.($_SERVER['QUERY_STRING'] ?? ''));
    abortServer();
}

if (empty($categorias)) {
    // nenhuma categoria => PDF informando ausência
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,10,enc('Não há categorias cadastradas'),0,1,'C');
    $pdf->Output();
    exit;
}

/* busca instituicao para cabeçalho */
try {
    $stmtInst = $pdo->prepare("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
    $stmtInst->execute();
    $inst = $stmtInst->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    logSecurity('Erro SQL instituicao: '.$e->getMessage());
    $inst = [];
}

/* ---------------- GERA PDF ---------------- */
class PDF_CG extends FPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,enc('Página '.$this->PageNo()),0,0,'L');
        $this->Cell(0,10,enc('Impresso em: '.date('d/m/Y H:i:s')),0,0,'R');
    }
}

$pdf = new PDF_CG('P','mm','A4');
$pdf->SetTitle(enc('Relatório Geral de Categorias'));
$pdf->AddPage();

$LOGO_SIZE_MM = 15; $LOGO_GAP_MM = 5; $SECTION_GAP_MM = 12;
$nomeInst = $inst['nome_instituicao'] ?? '';
$logoPath = !empty($inst['imagem_instituicao']) ? LOGO_PATH . '/' . basename($inst['imagem_instituicao']) : null;
$hasLogo = ($logoPath && file_exists($logoPath));

$pdf->SetY(12);
$pdf->SetFont('Arial','B',14);
$txt = $nomeInst !== '' ? enc($nomeInst) : '';
$txtW = $txt !== '' ? $pdf->GetStringWidth($txt) : 0;
$totalW = $hasLogo ? ($LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0) + $txtW) : $txtW;
$x = ($pdf->GetPageWidth() - $totalW) / 2;
$y = $pdf->GetY();
if ($hasLogo) { $pdf->Image($logoPath, $x, $y-2, $LOGO_SIZE_MM, $LOGO_SIZE_MM); $x += $LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0); }
if ($txt !== '') { $pdf->SetXY($x, $y); $pdf->Cell($txtW, $LOGO_SIZE_MM, $txt, 0, 1, 'L'); }
$pdf->Ln(3);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,7,enc('Relatório de Categorias'),0,1,'L');
$pdf->Ln(1);

/* Loop categorias */
foreach ($categorias as $cat) {
    if ($modalidadeOpt) {
        $pdf->SetFont('Arial','B',11); $pdf->SetFillColor(220,220,220);
        $pdf->Cell(95,9,enc('Categoria'),1,0,'C',true); $pdf->Cell(95,9,enc('Modalidade'),1,1,'C',true);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(95,9,enc($cat['nome_categoria'] ?? ''),1,0,'C');
        $pdf->Cell(95,9,enc($cat['nome_modalidade'] ?? ''),1,1,'C');
    } else {
        $pdf->SetFont('Arial','B',11); $pdf->SetFillColor(220,220,220);
        $pdf->Cell(190,9,enc('Categoria'),1,1,'C',true);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(190,9,enc($cat['nome_categoria'] ?? ''),1,1,'C');
    }

    if (!empty($cat['descricao'])) {
        $pdf->Ln(3);
        $pdf->SetFont('Arial','B',11); $pdf->SetFillColor(220,220,220);
        $pdf->Cell(190,9,enc('Descrição'),1,1,'C',true);
        $pdf->SetFont('Arial','',10);
        $pdf->MultiCell(190,7,enc($cat['descricao']),1,'L');
    }

    if ($professoresOpt) {
        try {
            $stmtP = $pdo->prepare("SELECT p.nome_completo FROM professor_categoria pc JOIN professor p ON p.id_professor = pc.id_professor WHERE pc.id_categoria = ? ORDER BY p.nome_completo");
            $stmtP->execute([$cat['id_categoria']]);
            $profRows = $stmtP->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            logSecurity('Erro SQL professores categoria-geral: '.$e->getMessage());
            $profRows = [];
        }

        $pdf->Ln(3);
        $pdf->SetFont('Arial','B',11); $pdf->SetFillColor(220,220,220);
        $pdf->Cell(190,9,enc('Professores'),1,1,'C',true);
        $pdf->SetFont('Arial','',10);
        if ($profRows) {
            foreach ($profRows as $p) $pdf->Cell(190,8,enc($p),1,1,'L');
        } else {
            $pdf->Cell(190,8,enc('Nenhum professor vinculado.'),1,1,'L');
        }
    }

    $pdf->Ln($SECTION_GAP_MM);
}

$pdf->Output();
exit;
?>