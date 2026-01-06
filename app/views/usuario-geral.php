<?php
// app/views/usuario-geral.php
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

// ---------------- WHITELIST de GET (nenhum parâmetro esperado neste relatório) ----------------
$parametrosPermitidos = []; // se no futuro quiser filtros coloque-os aqui (ex: 'nivel')
$recebidos = array_keys($_GET);
$naoPermitidos = array_diff($recebidos, $parametrosPermitidos);
if (!empty($naoPermitidos)) {
    logSecurityEvent("Tentativa de acesso com parâmetros não permitidos em usuario-geral: " . implode(', ', $naoPermitidos));
    header('HTTP/1.1 400 Bad Request');
    die("Parâmetros inválidos");
}

// ---------------- Cabeçalho / PDF (mantive igual ao original) ----------------
$LOGO_SIZE_MM = 15;
$LOGO_GAP_MM  = 5;

class PDF extends FPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10, iconv('UTF-8','ISO-8859-1','Página '.$this->PageNo()), 0, 0, 'L');
        $this->Cell(0,10, iconv('UTF-8','ISO-8859-1','Impresso em: '.date('d/m/Y H:i:s')), 0, 0, 'R');
    }
}

$pdf = new PDF('P','mm','A4');
$pdf->SetTitle(iconv('UTF-8','ISO-8859-1','Relatório Geral de Usuários'));
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
$pdf->Cell(0,7, iconv('UTF-8','ISO-8859-1','Relatório Geral de Usuários'), 0,1,'L');
$pdf->Ln(1);

/* Cabeçalho da tabela */
$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(220,220,220);
$pdf->Cell(70, 9, iconv('UTF-8','ISO-8859-1','Nome'),    1, 0, 'C', true);
$pdf->Cell(90, 9, iconv('UTF-8','ISO-8859-1','E-mail'),  1, 0, 'C', true);
$pdf->Cell(30, 9, iconv('UTF-8','ISO-8859-1','Situação'),1, 1, 'C', true);

/* Dados com consulta segura */
$pdf->SetFont('Arial','',11);

try {
    $sql = "
        SELECT u.id_usuario, u.nome_usuario, u.email_usuario, u.situacao_usuario,
               (SELECT GROUP_CONCAT(n.nome_nivel_ensino SEPARATOR ', ')
                  FROM usuario_niveis un
                  JOIN nivel_ensino n ON un.id_nivel_ensino = n.id_nivel_ensino
                 WHERE un.id_usuario = u.id_usuario) AS niveis_acesso
          FROM usuario u
      ORDER BY u.nome_usuario ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logSecurityEvent("Erro na consulta de usuarios (usuario-geral): " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    die("Erro interno do sistema");
}

if ($rows) {
    foreach ($rows as $user) {
        // Linha principal
        $pdf->Cell(70, 9, iconv('UTF-8','ISO-8859-1',$user['nome_usuario']),    1, 0, 'C');
        $pdf->Cell(90, 9, iconv('UTF-8','ISO-8859-1',$user['email_usuario']),   1, 0, 'C');
        $pdf->Cell(30, 9, iconv('UTF-8','ISO-8859-1',$user['situacao_usuario']),1, 1, 'C');

        // Linha extra com quebra automática
        $pdf->SetFont('Arial','I',10);
        $niveis = !empty($user['niveis_acesso']) ? $user['niveis_acesso'] : 'Nenhum vínculo com nível de ensino';
        $pdf->MultiCell(190, 8, iconv('UTF-8','ISO-8859-1','Níveis: '.$niveis), 1, 'L');
        $pdf->SetFont('Arial','',11);

        // Espaço entre usuários
        $pdf->Ln(2);
    }
} else {
    $pdf->Cell(190, 10, iconv('UTF-8','ISO-8859-1','Nenhum usuário cadastrado.'), 1, 1, 'C');
}

$pdf->Output();
exit;
?>
