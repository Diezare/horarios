<?php
// app/views/turma.php
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

// ---------------- Segurança e logging ----------------
$SEG_LOG = __DIR__ . '/../../logs/seguranca.log';
function logSecurity($msg) {
    global $SEG_LOG;
    $meta = [
        'ts' => date('c'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'qs' => $_SERVER['QUERY_STRING'] ?? '',
        'script' => basename($_SERVER['SCRIPT_NAME'] ?? '')
    ];
    $entry = '['.date('d-M-Y H:i:s T').'] [SEGURANCA] '.$msg.' | META='.json_encode($meta, JSON_UNESCAPED_UNICODE).PHP_EOL;
    @file_put_contents($SEG_LOG, $entry, FILE_APPEND | LOCK_EX);
}
function abortClient() { http_response_code(400); die('Parâmetros inválidos'); }

// whitelist de parâmetros: id_turma obrigatório, prof opcional
$allowed = ['id_turma','prof'];
$received = array_keys($_GET);
$extra = array_diff($received, $allowed);
if (!empty($extra)) {
    logSecurity('Parâmetros inesperados em turma: '.implode(', ',$extra));
    abortClient();
}

// valida id_turma
$idTurma = filter_input(INPUT_GET, 'id_turma', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
if (!$idTurma) {
    logSecurity('id_turma inválido ou ausente em turma: raw=' . ($_GET['id_turma'] ?? 'null'));
    abortClient();
}

// valida prof se presente (só "1" aceito)
$profOpt = false;
if (array_key_exists('prof', $_GET)) {
    if ((string)$_GET['prof'] !== '1') {
        logSecurity("Valor inválido para 'prof' em turma: raw=" . ($_GET['prof'] ?? ''));
        abortClient();
    }
    $profOpt = true;
}

// ---------------- Parâmetros do cabeçalho (mantidos) ----------------
$LOGO_SIZE_MM = 15;
$LOGO_GAP_MM  = 5;

class PDF extends FPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10, iconv('UTF-8','ISO-8859-1','Página ' . $this->PageNo()), 0, 0, 'L');
        $this->Cell(0,10, iconv('UTF-8','ISO-8859-1','Impresso em: ' . date('d/m/Y H:i:s')), 0, 0, 'R');
    }
}

// ---------------- Inicia PDF ----------------
$pdf = new PDF('P','mm','A4');
$pdf->SetTitle(iconv('UTF-8','ISO-8859-1','Relatório de Turma'));
$pdf->AddPage();

// ---------------- Cabeçalho visual (mantido) ----------------
$stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
$inst = $stmtInst->fetch(PDO::FETCH_ASSOC);

$nomeInst = $inst ? ($inst['nome_instituicao'] ?? '') : '';
$logoPath = ($inst && !empty($inst['imagem_instituicao'])) ? LOGO_PATH . '/' . basename($inst['imagem_instituicao']) : null;

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
    $pdf->Image($logoPath, $x, $y - 2, $LOGO_SIZE_MM, $LOGO_SIZE_MM);
    $x += $LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0);
}
if ($nomeInst) {
    $pdf->SetXY($x, $y);
    $pdf->Cell($txtW, $LOGO_SIZE_MM, $txt, 0, 1, 'L');
}
$pdf->Ln(3);
$pdf->SetFont('Arial','B',13);
$pdf->Cell(0,7, iconv('UTF-8','ISO-8859-1','Relatório de Turma'), 0,1,'L');
$pdf->Ln(1);

// ---------------- Consulta turma com prepared statement ----------------
try {
    $sql = "
        SELECT
            t.id_turma,
            t.nome_turma,
            a.ano AS ano_letivo,
            s.nome_serie,
            n.nome_nivel_ensino,
            tr.nome_turno,
            t.intervalos_por_dia
        FROM turma t
        JOIN serie s ON t.id_serie = s.id_serie
        JOIN nivel_ensino n ON s.id_nivel_ensino = n.id_nivel_ensino
        JOIN ano_letivo a ON t.id_ano_letivo = a.id_ano_letivo
        JOIN turno tr ON t.id_turno = tr.id_turno
        WHERE t.id_turma = :idT
        LIMIT 1
    ";
    $stmtTurma = $pdo->prepare($sql);
    $stmtTurma->execute([':idT' => $idTurma]);
    $turma = $stmtTurma->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logSecurity("Erro SQL buscar turma (id_turma={$idTurma}): ".$e->getMessage());
    $turma = false;
}

if (!$turma) {
    logSecurity("Turma não encontrada: id_turma={$idTurma}");
    $pdf->SetFont('Arial','I',12);
    $pdf->Cell(0, 8, iconv('UTF-8','ISO-8859-1','Turma não encontrada.'), 0,1,'C');
    $pdf->Output();
    exit;
}

// ---------------- Exibe dados (mantido) ----------------
$pdf->SetFont('Arial','B',11);
$pdf->SetFillColor(200,200,200);
$pdf->Cell(30,9, iconv('UTF-8','ISO-8859-1','Ano Letivo'),      1, 0, 'C', true);
$pdf->Cell(20,9, iconv('UTF-8','ISO-8859-1','Série'),           1, 0, 'C', true);
$pdf->Cell(20,9, iconv('UTF-8','ISO-8859-1','Turma'),           1, 0, 'C', true);
$pdf->Cell(55,9, iconv('UTF-8','ISO-8859-1','Nível de Ensino'), 1, 0, 'C', true);
$pdf->Cell(35,9, iconv('UTF-8','ISO-8859-1','Turno'),           1, 0, 'C', true);
$pdf->Cell(30,9, iconv('UTF-8','ISO-8859-1','Intervalos'),      1, 1, 'C', true);

$pdf->SetFont('Arial','',11);
$pdf->Cell(30,9, iconv('UTF-8','ISO-8859-1', $turma['ano_letivo']),        1, 0, 'C');
$pdf->Cell(20,9, iconv('UTF-8','ISO-8859-1', $turma['nome_serie']),        1, 0, 'C');
$pdf->Cell(20,9, iconv('UTF-8','ISO-8859-1', $turma['nome_turma']),        1, 0, 'C');
$pdf->Cell(55,9, iconv('UTF-8','ISO-8859-1', $turma['nome_nivel_ensino']), 1, 0, 'C');
$pdf->Cell(35,9, iconv('UTF-8','ISO-8859-1', $turma['nome_turno']),        1, 0, 'C');
$pdf->Cell(30,9, iconv('UTF-8','ISO-8859-1', $turma['intervalos_por_dia']),1, 1, 'C');

// ---------------- Professores (opcional via ?prof=1) ----------------
if ($profOpt) {
    $pdf->Ln(8);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(190,8, iconv('UTF-8','ISO-8859-1','Professores'), 0,1,'L');

    try {
        $sqlProf = "
            SELECT DISTINCT p.nome_completo AS nome_prof
            FROM professor_disciplinas_turmas pdt
            JOIN professor p ON pdt.id_professor = p.id_professor
            WHERE pdt.id_turma = :idT
            ORDER BY p.nome_completo
        ";
        $stmtProf = $pdo->prepare($sqlProf);
        $stmtProf->execute([':idT' => $turma['id_turma']]);
        $profRows = $stmtProf->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        logSecurity("Erro SQL buscar professores turma (id_turma={$idTurma}): ".$e->getMessage());
        $profRows = [];
    }

    $pdf->SetFont('Arial','',11);
    if ($profRows) {
        $profList = implode(", ", $profRows);
        $pdf->MultiCell(190,8, iconv('UTF-8','ISO-8859-1', $profList), 1, 'L');
    } else {
        $pdf->Cell(190,8, iconv('UTF-8','ISO-8859-1','Nenhum professor encontrado.'), 1,1,'C');
    }
}

$pdf->Output();
exit;
?>
