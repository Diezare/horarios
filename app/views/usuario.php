<?php
// app/views/usuario.php
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

// ---------------- WHITELIST de GET ----------------
$parametrosPermitidos = ['id_usuario'];
$recebidos = array_keys($_GET);
$naoPermitidos = array_diff($recebidos, $parametrosPermitidos);
if (!empty($naoPermitidos)) {
    logSecurityEvent("Tentativa de acesso com parâmetros não permitidos em usuario: " . implode(', ', $naoPermitidos));
    header('HTTP/1.1 400 Bad Request');
    die("Parâmetros inválidos");
}

// ---------------- Validação do id_usuario (obrigatório e inteiro positivo) ----------------
$idUsuario = filter_input(INPUT_GET, 'id_usuario', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$idUsuario) {
    logSecurityEvent("Tentativa de acesso com id_usuario inválido: " . ($_GET['id_usuario'] ?? ''));
    header('HTTP/1.1 400 Bad Request');
    die("Parâmetros inválidos");
}

// ---------------- Cabeçalho / PDF ----------------
$LOGO_SIZE_MM = 15; // largura = altura
$LOGO_GAP_MM  = 5;

class PDF extends FPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10, iconv('UTF-8','ISO-8859-1','Página '.$this->PageNo()), 0, 0, 'L');
        $this->Cell(0,10, iconv('UTF-8','ISO-8859-1','Impresso em: '.date('d/m/Y H:i:s')), 0, 0, 'R');
    }
}

/* Consulta segura com verificação de existência */
try {
    $sql = "
      SELECT u.id_usuario, u.nome_usuario, u.email_usuario, u.situacao_usuario,
             (SELECT GROUP_CONCAT(n.nome_nivel_ensino SEPARATOR ', ')
                FROM usuario_niveis un
                JOIN nivel_ensino n ON un.id_nivel_ensino = n.id_nivel_ensino
               WHERE un.id_usuario = u.id_usuario) AS niveis_acesso
        FROM usuario u
       WHERE u.id_usuario = :id
       LIMIT 1
    ";
    $st = $pdo->prepare($sql);
    $st->execute([':id'=>$idUsuario]);
    $u = $st->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logSecurityEvent("Erro na consulta de usuario (usuario.php): " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    die("Erro interno do sistema");
}

$pdf = new PDF('P','mm','A4');
$pdf->SetTitle(iconv('UTF-8','ISO-8859-1','Relatório de Usuário'));
$pdf->AddPage();

/* Cabeçalho padrão (como turma.php) */
$inst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$nomeInst = $inst['nome_instituicao'] ?? '';
$logoPath = (!empty($inst['imagem_instituicao'])) ? LOGO_PATH.'/'.basename($inst['imagem_instituicao']) : null;

$pdf->SetY(12);
$pdf->SetFont('Arial','B',14);
$txt  = iconv('UTF-8','ISO-8859-1',$nomeInst);
$txtW = $pdf->GetStringWidth($txt);
$hasLogo = ($logoPath && file_exists($logoPath));
$totalW  = $hasLogo ? ($LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0) + $txtW) : $txtW;
$pageW   = $pdf->GetPageWidth();
$x       = ($pageW - $totalW)/2;
$y       = $pdf->GetY();
if ($hasLogo) {
    $pdf->Image($logoPath, $x, $y-2, $LOGO_SIZE_MM, $LOGO_SIZE_MM);
    $x += $LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0);
}
if ($nomeInst) {
    $pdf->SetXY($x,$y);
    $pdf->Cell($txtW, $LOGO_SIZE_MM, $txt, 0, 1, 'L');
}
$pdf->Ln(3);
$pdf->SetFont('Arial','B',13);
$pdf->Cell(0,7, iconv('UTF-8','ISO-8859-1','Relatório de Usuário'), 0,1,'L');
$pdf->Ln(1);

if (!$u) {
    logSecurityEvent("Tentativa de acesso a id_usuario inexistente: id_usuario=" . $idUsuario);
    $pdf->SetFont('Arial','I',12);
    $pdf->Cell(190,10, iconv('UTF-8','ISO-8859-1','Usuário não encontrado.'),1,1,'C');
    $pdf->Output(); exit;
}

/* Tabela */
$pdf->SetFont('Arial','B',11);
$pdf->SetFillColor(220,220,220);
$pdf->Cell(70,9, iconv('UTF-8','ISO-8859-1','Nome'),    1,0,'C',true);
$pdf->Cell(90,9, iconv('UTF-8','ISO-8859-1','E-mail'),  1,0,'C',true);
$pdf->Cell(30,9, iconv('UTF-8','ISO-8859-1','Situação'),1,1,'C',true);

$pdf->SetFont('Arial','',11);
$pdf->Cell(70,9, iconv('UTF-8','ISO-8859-1',$u['nome_usuario']),    1,0,'C');
$pdf->Cell(90,9, iconv('UTF-8','ISO-8859-1',$u['email_usuario']),   1,0,'C');
$pdf->Cell(30,9, iconv('UTF-8','ISO-8859-1',$u['situacao_usuario']),1,1,'C');

$pdf->SetFont('Arial','I',10);
$niveis = !empty($u['niveis_acesso']) ? $u['niveis_acesso'] : 'Nenhum vínculo com nível de ensino';
$pdf->MultiCell(190,8, iconv('UTF-8','ISO-8859-1','Níveis: '.$niveis), 1, 'L');

$pdf->Output();
exit;
?>
