<?php
// app/views/nivel-ensino.php - VERSÃO INDIVIDUAL COM VALIDAÇÕES REFORÇADAS
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

/* ---------------- CONFIG / LOG ---------------- */
$LOG_PATH = __DIR__ . '/../../logs/seguranca.log';
function logSecurity(string $msg): void {
    global $LOG_PATH;
    $meta = [
        'ts'     => date('c'),
        'ip'     => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'ua'     => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'qs'     => $_SERVER['QUERY_STRING'] ?? '',
        'script' => basename($_SERVER['SCRIPT_NAME'] ?? '')
    ];
    $entry = '['.date('d-M-Y H:i:s T').'] [SEGURANCA] '.$msg.' | META='.json_encode($meta, JSON_UNESCAPED_UNICODE).PHP_EOL;
    $dir = dirname($LOG_PATH);
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    @file_put_contents($LOG_PATH, $entry, FILE_APPEND | LOCK_EX);
}
function abortClient(): void { http_response_code(400); die('Parâmetros inválidos'); }
function enc($s){ return iconv('UTF-8','ISO-8859-1//TRANSLIT',(string)$s); }

/* ---------------- WHITELIST E VALIDAÇÃO DE GET ---------------- */
$allowed = ['id_nivel','series','usuarios'];
$received = array_keys($_GET);
$extras = array_diff($received, $allowed);
if (!empty($extras)) {
    logSecurity('Parâmetros inesperados em nivel-ensino: '.implode(', ',$extras));
    abortClient();
}

/* flags opcionais: se presentes devem ser exatamente "1" */
$seriesRaw   = array_key_exists('series', $_GET)   ? (string)$_GET['series']   : null;
$usuariosRaw = array_key_exists('usuarios', $_GET) ? (string)$_GET['usuarios'] : null;

if ($seriesRaw !== null && $seriesRaw !== '1') {
    logSecurity("Valor inválido para 'series': raw={$seriesRaw}");
    abortClient();
}
if ($usuariosRaw !== null && $usuariosRaw !== '1') {
    logSecurity("Valor inválido para 'usuarios': raw={$usuariosRaw}");
    abortClient();
}

$seriesOpt   = ($seriesRaw === '1');
$usuariosOpt = ($usuariosRaw === '1');

/* id_nivel obrigatório e inteiro positivo */
$idNivel = filter_input(INPUT_GET, 'id_nivel', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
if (!$idNivel) {
    logSecurity('id_nivel inválido ou ausente: raw=' . ($_GET['id_nivel'] ?? 'null'));
    abortClient();
}

/* ---------------- Sessão / autorização ---------------- */
$idUsuario = $_SESSION['id_usuario'] ?? null;
if (!$idUsuario) {
    logSecurity('Acesso sem sessão ao nivel-ensino: id_nivel=' . $idNivel);
    abortClient();
}

/* Verifica se o usuário tem vínculo com o nível solicitado */
try {
    $stmtAuth = $pdo->prepare("SELECT 1 FROM usuario_niveis WHERE id_usuario = :usu AND id_nivel_ensino = :niv LIMIT 1");
    $stmtAuth->execute([':usu' => (int)$idUsuario, ':niv' => (int)$idNivel]);
    if (!$stmtAuth->fetchColumn()) {
        logSecurity("Usuário {$idUsuario} sem permissão para nivel {$idNivel}");
        abortClient();
    }
} catch (Exception $e) {
    logSecurity("Erro SQL autorizacao nivel (usuario={$idUsuario}, nivel={$idNivel}): " . $e->getMessage());
    abortClient();
}

/* ---------------- Busca do nível (existência) ---------------- */
try {
    $stmtNivel = $pdo->prepare("SELECT id_nivel_ensino, nome_nivel_ensino FROM nivel_ensino WHERE id_nivel_ensino = :id LIMIT 1");
    $stmtNivel->execute([':id' => (int)$idNivel]);
    $nivelRow = $stmtNivel->fetch(PDO::FETCH_ASSOC);
    if (!$nivelRow) {
        logSecurity("Nível inexistente solicitado: id_nivel={$idNivel} por usuario={$idUsuario}");
        abortClient();
    }
} catch (Exception $e) {
    logSecurity("Erro SQL fetch nivel (id_nivel={$idNivel}): " . $e->getMessage());
    abortClient();
}

/* ---------------- Dados da instituição (cabeçalho) ---------------- */
try {
    $stmtInst = $pdo->prepare("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
    $stmtInst->execute();
    $inst = $stmtInst->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    logSecurity("Erro SQL instituicao (nivel-ensino): ".$e->getMessage());
    $inst = [];
}
$NOME_INST = $inst['nome_instituicao'] ?? '';
$LOGO_FILE = (!empty($inst['imagem_instituicao'])) ? LOGO_PATH . '/' . basename($inst['imagem_instituicao']) : '';

/* ---------------- Classe PDF e renderHeader (aceita FPDF) ---------------- */
class PDF_NEG extends FPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,iconv('UTF-8','ISO-8859-1','Página '.$this->PageNo()),0,0,'L');
        $this->Cell(0,10,iconv('UTF-8','ISO-8859-1','Impresso em: '.date('d/m/Y H:i:s')),0,0,'R');
    }
}
function renderHeader(\FPDF $pdf, string $nomeInstituicao, ?string $logoPath, float $logoSizeMm = 15, float $gapMm = 5): void {
    $topY = 12;
    $pdf->SetFont('Arial','B',14);
    $text = $nomeInstituicao ? enc($nomeInstituicao) : '';
    $textW = $text ? $pdf->GetStringWidth($text) : 0;
    $hasLogo = ($logoPath && file_exists($logoPath));
    $totalW  = $hasLogo ? ($logoSizeMm + $gapMm + $textW) : $textW;
    $pageW = $pdf->GetPageWidth();
    $xStart = ($pageW - $totalW) / 2;
    if ($hasLogo) {
        $pdf->Image($logoPath, $xStart, $topY, $logoSizeMm, $logoSizeMm);
        $xText = $xStart + $logoSizeMm + $gapMm;
        $textY = $topY + ($logoSizeMm / 2) - 5;
        if ($textY < $topY) $textY = $topY;
    } else {
        $xText = $xStart;
        $textY = $topY;
    }
    if ($text !== '') {
        $pdf->SetXY($xText, $textY);
        $pdf->Cell($textW + 1, $logoSizeMm, $text, 0, 1, 'L');
    }
    $pdf->SetY(max($topY + ($hasLogo ? $logoSizeMm : 0), $textY + 8) + 6);
}

/* ---------------- GERA PDF ---------------- */
$pdf = new PDF_NEG('P','mm','A4');
$pdf->SetTitle(enc('Relatório de Nível de Ensino'));
$pdf->AddPage();
renderHeader($pdf, $NOME_INST, $LOGO_FILE, 15, 5);

/* Título do relatório */
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0, 8, enc('Relatório de Nível de Ensino'), 0, 1, 'L');
$pdf->Ln(2);

/* Cabeçalho da tabela do Nível */
$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(220,220,220);
$pdf->Cell(190, 8, enc('Nível de Ensino'), 1, 1, 'C', true);

/* Linha do Nível */
$pdf->SetFont('Arial','',11);
$pdf->Cell(190, 8, enc($nivelRow['nome_nivel_ensino']), 1, 1, 'C');

/* ---------- Séries e Turmas (opcional) ---------- */
if ($seriesOpt) {
    $pdf->Ln(6);
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0, 8, enc('Séries e Turmas'), 0, 1, 'L');

    try {
        $sql = "
            SELECT s.id_serie, s.nome_serie, t.nome_turma
            FROM serie s
            LEFT JOIN turma t ON t.id_serie = s.id_serie
            WHERE s.id_nivel_ensino = :idNivel
            ORDER BY s.nome_serie, t.nome_turma
        ";
        $st = $pdo->prepare($sql);
        $st->execute([':idNivel' => (int)$idNivel]);
        $rowsSeries = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        logSecurity("Erro SQL series (id_nivel={$idNivel}): ".$e->getMessage());
        $rowsSeries = [];
    }

    if ($rowsSeries) {
        $seriesAgrupadas = [];
        foreach ($rowsSeries as $row) {
            $idSer = (int)$row['id_serie'];
            $seriesAgrupadas[$idSer] = $seriesAgrupadas[$idSer] ?? [
                'nome_serie' => $row['nome_serie'],
                'turmas' => []
            ];
            if (!empty($row['nome_turma'])) $seriesAgrupadas[$idSer]['turmas'][] = $row['nome_turma'];
        }

        $pdf->SetFont('Arial','B',12);
        $pdf->SetFillColor(200,200,200);
        $pdf->Cell(70, 8, enc('Série'), 1, 0, 'C', true);
        $pdf->Cell(120, 8, enc('Turmas'), 1, 1, 'C', true);

        $pdf->SetFont('Arial','',11);
        foreach ($seriesAgrupadas as $sd) {
            $nomeSerie = $sd['nome_serie'];
            $turmasStr = $sd['turmas'] ? implode(', ', $sd['turmas']) : '-';
            $pdf->Cell(70, 8, enc($nomeSerie), 1, 0, 'C');
            $pdf->Cell(120, 8, enc($turmasStr), 1, 1, 'C');
        }
    } else {
        $pdf->SetFont('Arial','I',11);
        $pdf->Cell(0, 8, enc('Nenhuma série cadastrada para este Nível de Ensino.'), 0, 1, 'L');
    }
}

/* ---------- Usuários vinculados (opcional) ---------- */
if ($usuariosOpt) {
    try {
        $sqlU = "
            SELECT u.nome_usuario, u.email_usuario, u.situacao_usuario
            FROM usuario u
            JOIN usuario_niveis un ON un.id_usuario = u.id_usuario
            WHERE un.id_nivel_ensino = :idNivel
            ORDER BY u.nome_usuario
        ";
        $stU = $pdo->prepare($sqlU);
        $stU->execute([':idNivel' => (int)$idNivel]);
        $users = $stU->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        logSecurity("Erro SQL usuarios (id_nivel={$idNivel}): ".$e->getMessage());
        $users = [];
    }

    $pdf->Ln(6);
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0, 8, enc('Usuários com acesso a este Nível de Ensino'), 0, 1, 'L');
    $pdf->Ln(1);

    if ($users) {
        $pdf->SetFont('Arial','B',12);
        $pdf->SetFillColor(220,220,220);
        $pdf->Cell(70, 8, enc('Nome'), 1, 0, 'C', true);
        $pdf->Cell(90, 8, enc('E-mail'), 1, 0, 'C', true);
        $pdf->Cell(30, 8, enc('Situação'), 1, 1, 'C', true);

        $pdf->SetFont('Arial','',11);
        foreach ($users as $u) {
            $pdf->Cell(70, 8, enc($u['nome_usuario']), 1, 0, 'C');
            $pdf->Cell(90, 8, enc($u['email_usuario']), 1, 0, 'C');
            $pdf->Cell(30, 8, enc($u['situacao_usuario']), 1, 1, 'C');
        }
    } else {
        $pdf->SetFont('Arial','I',11);
        $pdf->Cell(190, 8, enc('Nenhum usuário vinculado a este Nível de Ensino.'), 1, 1, 'C');
    }
}

$pdf->Output();
exit;
?>
