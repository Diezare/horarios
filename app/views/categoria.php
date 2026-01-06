<?php
// app/views/categoria.php - VERSÃO COMPLETAMENTE SEGURA
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

/* ---------------- VALIDAÇÃO RIGOROSA DE TODOS OS PARÂMETROS ---------------- */

// Lista branca de parâmetros permitidos
$parametrosPermitidos = ['id_categoria', 'modalidade', 'professores'];

// Verifica se há parâmetros não permitidos
$parametrosRecebidos = array_keys($_GET);
$parametrosNaoPermitidos = array_diff($parametrosRecebidos, $parametrosPermitidos);

if (!empty($parametrosNaoPermitidos)) {
    logSecurityEvent("Tentativa de acesso com parâmetros não permitidos em categoria: " . implode(', ', $parametrosNaoPermitidos));
    header('HTTP/1.1 400 Bad Request');
    die("Parâmetros inválidos");
}

// Validação individual dos parâmetros permitidos
$idCategoria = filter_input(INPUT_GET, 'id_categoria', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

$modalidadeOpt = filter_input(INPUT_GET, 'modalidade', FILTER_VALIDATE_BOOLEAN);
$profOpt = filter_input(INPUT_GET, 'professores', FILTER_VALIDATE_BOOLEAN);

// VALIDAÇÃO: id_categoria é obrigatório
if (!$idCategoria) {
    logSecurityEvent("Tentativa de acesso com id_categoria inválido: " . ($_GET['id_categoria'] ?? ''));
    header('HTTP/1.1 400 Bad Request');
    die("Parâmetros inválidos");
}

/* ---------------- CONSULTAS SEGURAS COM VERIFICAÇÃO DE EXISTÊNCIA ---------------- */
try {
    // Dados da instituição
    $stmtInst = $pdo->prepare("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
    $stmtInst->execute();
    $inst = $stmtInst->fetch(PDO::FETCH_ASSOC);

    // VERIFICAR SE A CATEGORIA EXISTE ANTES DE BUSCAR OS DADOS
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM categoria WHERE id_categoria = ?");
    $stmtCheck->execute([$idCategoria]);
    $categoriaExiste = $stmtCheck->fetchColumn();

    if (!$categoriaExiste) {
        logSecurityEvent("Tentativa de acesso a categoria inexistente: id_categoria=" . $idCategoria);
        header('HTTP/1.1 404 Not Found');
        die("Parámetros inválidos");
    }

    // Buscar dados da Categoria (agora sabemos que existe)
    $sql = "
        SELECT c.id_categoria,
               c.nome_categoria,
               c.descricao,
               c.id_modalidade,
               m.nome_modalidade
        FROM categoria c
        JOIN modalidade m ON c.id_modalidade = m.id_modalidade
        WHERE c.id_categoria = ?
        LIMIT 1
    ";
    $stmtCat = $pdo->prepare($sql);
    $stmtCat->execute([$idCategoria]);
    $categoria = $stmtCat->fetch(PDO::FETCH_ASSOC);

    if (!$categoria) {
        // Isso não deveria acontecer, mas por segurança
        logSecurityEvent("Erro inesperado: categoria não encontrada após verificação: id_categoria=" . $idCategoria);
        header('HTTP/1.1 404 Not Found');
        die("Categoria não encontrada");
    }

} catch (Exception $e) {
    logSecurityEvent("Erro na consulta de categoria: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    die("Erro interno do sistema");
}

/* ---------------- HELPERS ---------------- */
function enc($s) { 
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$s); 
}

/* ---------------- PDF ---------------- */
class PDF extends FPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, enc('Página ' . $this->PageNo()), 0, 0, 'L');
        $this->Cell(0, 10, enc('Impresso em: ' . date('d/m/Y H:i:s')), 0, 0, 'R');
    }
}

// ---------------- Parâmetros do cabeçalho ----------------
$LOGO_SIZE_MM = 15;
$LOGO_GAP_MM  = 5;

// ----------------------------------------------------------------
// Inicia PDF
// ----------------------------------------------------------------
$pdf = new PDF('P', 'mm', 'A4');
$pdf->SetTitle(enc('Relatório de Categoria'));
$pdf->AddPage();

// ----------------------------------------------------------------
// Cabeçalho (logo + nome em linha única, centralizado) + título
// ----------------------------------------------------------------
$nomeInst = $inst['nome_instituicao'] ?? '';
$logoPath = (!empty($inst['imagem_instituicao'])) ? LOGO_PATH . '/' . basename($inst['imagem_instituicao']) : null;

$pdf->SetY(12);
$pdf->SetFont('Arial', 'B', 14);
$txt  = enc($nomeInst);
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
$pdf->SetFont('Arial', 'B', 13);
$pdf->Cell(0, 7, enc('Relatório de Categoria'), 0, 1, 'L');
$pdf->Ln(1);

// ----------------------------------------------------------------
// Exibir dados
// ----------------------------------------------------------------
if ($modalidadeOpt) {
    // Categoria + Modalidade (duas colunas)
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(220, 220, 220);
    $pdf->Cell(95, 10, enc('Categoria'), 1, 0, 'C', true);
    $pdf->Cell(95, 10, enc('Modalidade'), 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(95, 10, enc($categoria['nome_categoria']), 1, 0, 'C');
    $pdf->Cell(95, 10, enc($categoria['nome_modalidade']), 1, 1, 'C');
} else {
    // Só Categoria (coluna cheia)
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(220, 220, 220);
    $pdf->Cell(190, 10, enc('Categoria'), 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(190, 10, enc($categoria['nome_categoria']), 1, 1, 'C');
}

// Descrição (se houver)
if (!empty($categoria['descricao'])) {
    $pdf->Ln(6);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(220, 220, 220);
    $pdf->Cell(190, 9, enc('Descrição'), 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 12);
    $pdf->MultiCell(190, 8, enc($categoria['descricao']), 1, 'L');
}

// Professores (opcional)
if ($profOpt) {
    try {
        $pdf->Ln(6);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(220, 220, 220);
        $pdf->Cell(190, 9, enc('Professores'), 1, 1, 'C', true);

        $sqlProf = "
            SELECT p.nome_completo
            FROM professor_categoria pc
            JOIN professor p ON p.id_professor = pc.id_professor
            WHERE pc.id_categoria = ?
            ORDER BY p.nome_completo
        ";
        $stmtProf = $pdo->prepare($sqlProf);
        $stmtProf->execute([$categoria['id_categoria']]);
        $profRows = $stmtProf->fetchAll(PDO::FETCH_COLUMN);

        $pdf->SetFont('Arial', '', 12);
        if ($profRows) {
            foreach ($profRows as $nome) {
                $pdf->Cell(190, 8, enc($nome), 1, 1, 'L');
            }
        } else {
            $pdf->Cell(190, 8, enc('Nenhum professor vinculado.'), 1, 1, 'L');
        }
    } catch (Exception $e) {
        logSecurityEvent("Erro na consulta de professores da categoria: " . $e->getMessage());
        $pdf->Cell(190, 8, enc('Erro ao carregar professores.'), 1, 1, 'L');
    }
}

// ----------------------------------------------------------------
// Finaliza PDF
// ----------------------------------------------------------------
$pdf->Output();
exit;
?>