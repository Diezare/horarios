<?php
// app/views/sala.php
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
function enc($s){ return iconv('UTF-8','ISO-8859-1//TRANSLIT',(string)$s); }

// whitelist de parâmetros: id_sala obrigatório e opcional turmas
$allowed = ['id_sala','turmas'];
$received = array_keys($_GET);
$extra = array_diff($received, $allowed);
if (!empty($extra)) {
    logSecurity('Parâmetros inesperados em sala: '.implode(', ',$extra));
    abortClient();
}

// Valida id_sala
$id_sala = filter_input(INPUT_GET, 'id_sala', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
if (!$id_sala) {
    logSecurity('id_sala inválido ou ausente em sala: raw=' . ($_GET['id_sala'] ?? 'null'));
    abortClient();
}

// Valida turmas se presente
$mostrarTurmas = false;
if (array_key_exists('turmas', $_GET)) {
    if ((string)$_GET['turmas'] !== '1') {
        logSecurity("Valor inválido para 'turmas' em sala: raw=" . ($_GET['turmas'] ?? ''));
        abortClient();
    }
    $mostrarTurmas = true;
}

// ---------------- Parâmetros do cabeçalho (mantidos) ----------------
$LOGO_SIZE_MM = 15; // tamanho (largura=altura) da logo em mm
$LOGO_GAP_MM  = 5;  // espaço entre a logo e o nome em mm

class PDF extends FPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10, iconv('UTF-8','ISO-8859-1','Página ' . $this->PageNo()), 0, 0, 'L');
        $this->Cell(0,10, iconv('UTF-8','ISO-8859-1','Impresso em: ' . date('d/m/Y H:i:s')), 0, 0, 'R');
    }
}

// ----------------------------------------------------------------
// Inicia PDF e cabeçalho visual (mantido)
// ----------------------------------------------------------------
$pdf = new PDF('P','mm','A4');
$pdf->SetTitle(iconv('UTF-8','ISO-8859-1','Relatório de Sala'));
$pdf->AddPage();

$stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
$inst = $stmtInst->fetch(PDO::FETCH_ASSOC);
$nomeInst = $inst ? ($inst['nome_instituicao'] ?? '') : '';
$logoPath = ($inst && !empty($inst['imagem_instituicao']))
    ? LOGO_PATH . '/' . basename($inst['imagem_instituicao'])
    : null;

$topY = 12;
$pdf->SetY($topY);
$pdf->SetFont('Arial','B',14);
$nomeTxt  = iconv('UTF-8','ISO-8859-1', $nomeInst);
$nomeW    = $pdf->GetStringWidth($nomeTxt);

// largura total do bloco (logo + gap + texto)
if ($logoPath && file_exists($logoPath)) {
    $totalW = $LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0) + $nomeW;
} else {
    $totalW = $nomeW;
}
$pageW  = $pdf->GetPageWidth();
$startX = ($pageW - $totalW) / 2;
$y      = $pdf->GetY();

// logo
if ($logoPath && file_exists($logoPath)) {
    $pdf->Image($logoPath, $startX, $y - 2, $LOGO_SIZE_MM, $LOGO_SIZE_MM);
    $startX += $LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0);
}

// nome da instituição
if ($nomeInst) {
    $pdf->SetXY($startX, $y);
    $pdf->Cell($nomeW, $LOGO_SIZE_MM, $nomeTxt, 0, 1, 'L');
}

// Título
$pdf->Ln(3);
$pdf->SetFont('Arial','B',13);
$pdf->Cell(0, 7, iconv('UTF-8','ISO-8859-1','Relatório de Sala'), 0, 1, 'L');
$pdf->Ln(1);

// ----------------------------------------------------------------
// Consulta e exibição dos dados da Sala (com verificação de existência)
// ----------------------------------------------------------------
try {
    $stmtSala = $pdo->prepare("SELECT * FROM sala WHERE id_sala = :id LIMIT 1");
    $stmtSala->execute([':id' => $id_sala]);
    $sala = $stmtSala->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logSecurity("Erro SQL buscar sala (id_sala={$id_sala}): ".$e->getMessage());
    $sala = false;
}

if (!$sala) {
    logSecurity("Sala não encontrada: id_sala={$id_sala}");
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10, iconv('UTF-8','ISO-8859-1','Sala não encontrada.'), 0,1, 'C');
    $pdf->Output();
    exit;
}

// 4.2) Tabela: Nome da Sala, Carteiras, Cadeiras, Capacidade
$pdf->SetFont('Arial','B',11);
$pdf->SetFillColor(220,220,220);
// Largura total 190: 60 + 40 + 40 + 50
$pdf->Cell(60, 9, iconv('UTF-8','ISO-8859-1','Nome da Sala'), 1, 0, 'C', true);
$pdf->Cell(40, 9, iconv('UTF-8','ISO-8859-1','Carteiras'),    1, 0, 'C', true);
$pdf->Cell(40, 9, iconv('UTF-8','ISO-8859-1','Cadeiras'),     1, 0, 'C', true);
$pdf->Cell(50, 9, iconv('UTF-8','ISO-8859-1','Capacidade'),   1, 1, 'C', true);

$pdf->SetFont('Arial','',11);
$pdf->Cell(60, 9, iconv('UTF-8','ISO-8859-1', $sala['nome_sala']), 1, 0, 'C');
$pdf->Cell(40, 9, $sala['max_carteiras'], 1, 0, 'C');
$pdf->Cell(40, 9, $sala['max_cadeiras'],  1, 0, 'C');
$pdf->Cell(50, 9, $sala['capacidade_alunos'], 1, 1, 'C');

// 4.3) Localização
$pdf->Ln(2);
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0, 8, iconv('UTF-8','ISO-8859-1','Localização'), 1, 1, 'C', true);

$pdf->SetFont('Arial','',11);
$pdf->MultiCell(0, 8, iconv('UTF-8','ISO-8859-1', $sala['localizacao']), 1, 'L');

// 4.4) Recursos
$pdf->Ln(2);
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0, 8, iconv('UTF-8','ISO-8859-1','Recursos'), 1, 1, 'C', true);

$pdf->SetFont('Arial','',11);
$pdf->MultiCell(0, 8, iconv('UTF-8','ISO-8859-1', $sala['recursos']), 1, 'L');

// ----------------------------------------------------------------
// Turmas Vinculadas (se ?turmas=1)
// ----------------------------------------------------------------
if ($mostrarTurmas) {
    try {
        $stmtVinc = $pdo->prepare("
            SELECT st.*, t.nome_turno, tr.nome_turma, s.nome_serie, n.nome_nivel_ensino
              FROM sala_turno st
              JOIN turno t        ON st.id_turno = t.id_turno
              JOIN turma tr       ON st.id_turma = tr.id_turma
              JOIN serie s        ON tr.id_serie = s.id_serie
              JOIN nivel_ensino n ON s.id_nivel_ensino = n.id_nivel_ensino
             WHERE st.id_sala = :id
             ORDER BY t.nome_turno, s.nome_serie, tr.nome_turma
        ");
        $stmtVinc->execute([':id' => $id_sala]);
        $vinculacoes = $stmtVinc->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        logSecurity("Erro SQL vinculacoes sala (id_sala={$id_sala}): ".$e->getMessage());
        $vinculacoes = [];
    }

    if ($vinculacoes) {
        // Agrupa por turno
        $turmasPorTurno = [];
        foreach ($vinculacoes as $v) {
            $infoTurma = $v['nome_nivel_ensino'] . " - " . $v['nome_serie'] . " - " . $v['nome_turma'];
            $turno = $v['nome_turno'];
            $turmasPorTurno[$turno][] = $infoTurma;
        }

        // Cabeçalho da seção
        $pdf->Ln(5);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0, 7, iconv('UTF-8','ISO-8859-1','Turmas Vinculadas'), 0, 1, 'L');
        $pdf->Ln(1);

        // Cabeçalho da tabela: Turno (40) | Turmas (150)
        $pdf->SetFont('Arial','B',11);
        $pdf->SetFillColor(230,230,230);
        $pdf->Cell(40, 8, iconv('UTF-8','ISO-8859-1','Turno'),  1, 0, 'C', true);
        $pdf->Cell(150,8, iconv('UTF-8','ISO-8859-1','Turmas'), 1, 1, 'C', true);

        // Linhas
        $pdf->SetFont('Arial','',11);
        foreach ($turmasPorTurno as $turno => $lista) {
            $turmasStr = implode(', ', $lista);
            $pdf->Cell(40, 8, iconv('UTF-8','ISO-8859-1', $turno), 1, 0, 'C');
            $pdf->MultiCell(150, 8, iconv('UTF-8','ISO-8859-1', $turmasStr), 1, 'L');
        }
    } else {
        $pdf->Ln(5);
        $pdf->SetFont('Arial','B',11);
        $pdf->Cell(0, 7, iconv('UTF-8','ISO-8859-1','Não há turmas vinculadas.'), 0, 1, 'L');
    }
}

// Finaliza
$pdf->Ln(4);
$pdf->Output();
exit;
?>
