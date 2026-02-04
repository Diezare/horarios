<?php
// app/views/nivel-ensino-geral.php - VERSÃO REFORÇADA DE SEGURANÇA
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
function abortInvalid(): void { http_response_code(400); die('Parâmetros inválidos'); }
function abortServer(): void  { http_response_code(500); die('Parâmetros inválidos'); }
function abortNotFound(): void{ http_response_code(404); die('Parâmetros inválidos'); }

function enc($s){ return iconv('UTF-8','ISO-8859-1//TRANSLIT',(string)$s); }

/* ---------------- WHITELIST E VALIDAÇÃO DE GET ---------------- */
$allowed = ['nivel','series','usuarios'];
$received = array_keys($_GET);
$extras = array_diff($received, $allowed);
if (!empty($extras)) {
    logSecurity('Parâmetros inesperados em nivel-ensino-geral: '.implode(', ',$extras));
    abortInvalid();
}

/* flags opcionais: se presentes devem ser exatamente "1" */
$seriesOptRaw   = array_key_exists('series', $_GET)   ? (string)$_GET['series']   : null;
$usuariosOptRaw = array_key_exists('usuarios', $_GET) ? (string)$_GET['usuarios'] : null;

if ($seriesOptRaw !== null && $seriesOptRaw !== '1') {
    logSecurity("Valor inválido para 'series': raw={$seriesOptRaw}");
    abortInvalid();
}
if ($usuariosOptRaw !== null && $usuariosOptRaw !== '1') {
    logSecurity("Valor inválido para 'usuarios': raw={$usuariosOptRaw}");
    abortInvalid();
}

$seriesOpt   = ($seriesOptRaw === '1');
$usuariosOpt = ($usuariosOptRaw === '1');

/* nivel pode ser 'todos' ou id válido */
$nivelRaw = array_key_exists('nivel', $_GET) ? (string)$_GET['nivel'] : 'todos';
$nivelFilter = 'todos';
if ($nivelRaw !== 'todos') {
    $nid = filter_var($nivelRaw, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    if ($nid === false) {
        logSecurity("nivel inválido recebido: raw={$nivelRaw}");
        abortInvalid();
    }
    $nivelFilter = $nid;
}

/* ---------------- Verifica sessão / usuário ---------------- */
$idUsuario = $_SESSION['id_usuario'] ?? null;
if (!$idUsuario) {
    logSecurity("Acesso sem sessão: tentativa em nivel-ensino-geral");
    abortInvalid();
}

/* ---------------- Busca níveis autorizados para o usuário ---------------- */
try {
    $sql = "
        SELECT n.id_nivel_ensino, n.nome_nivel_ensino
          FROM nivel_ensino n
          JOIN usuario_niveis un ON n.id_nivel_ensino = un.id_nivel_ensino
         WHERE un.id_usuario = :idUsu
    ";
    $params = [':idUsu' => (int)$idUsuario];
    if ($nivelFilter !== 'todos') {
        $sql .= " AND n.id_nivel_ensino = :nivel";
        $params[':nivel'] = (int)$nivelFilter;
    }
    $sql .= " ORDER BY n.nome_nivel_ensino";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $niveis = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logSecurity("Erro SQL buscando niveis para usuario {$idUsuario}: ".$e->getMessage());
    abortServer();
}

if (empty($niveis)) {
    // Produz PDF com mensagem padrão — não expõe detalhes ao cliente
    $pdf = new FPDF('P','mm','A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial','I',12);
    $pdf->Cell(0,10,enc('Parâmetros inválidos'),0,1,'C');
    $pdf->Output();
    exit;
}

/* ---------------- Dados da instituição (para cabeçalho) ---------------- */
try {
    $stmtInst = $pdo->prepare("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
    $stmtInst->execute();
    $inst = $stmtInst->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    logSecurity("Erro SQL instituicao (nivel-ensino-geral): ".$e->getMessage());
    $inst = [];
}
$NOME_INST = $inst['nome_instituicao'] ?? '';
$LOGO_FILE = (!empty($inst['imagem_instituicao'])) ? LOGO_PATH . '/' . basename($inst['imagem_instituicao']) : '';

/* ---------------- Classe PDF (mantida) ---------------- */
class PDF extends FPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0, 10, iconv('UTF-8','ISO-8859-1','Página ' . $this->PageNo()), 0, 0, 'L');
        $this->Cell(0, 10, iconv('UTF-8','ISO-8859-1','Impresso em: ' . date('d/m/Y H:i:s')), 0, 0, 'R');
    }
}

/* ---------------- renderHeader corrigido (aceita FPDF) ---------------- */
function renderHeader(\FPDF $pdf, string $nomeInstituicao, ?string $logoPath, float $logoSizeMm = 15, float $gapMm = 5): void
{
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

/* ---------------- Gera PDF (por nível autorizado) ---------------- */
$pdf = new PDF('P','mm','A4');
$pdf->AliasNbPages();
$pdf->SetTitle(enc('Relatório Geral de Nível de Ensino'));

foreach ($niveis as $nivel) {
    // sanity: garantir tipos corretos
    $idNivel = (int)$nivel['id_nivel_ensino'];
    $nomeNivel = (string)$nivel['nome_nivel_ensino'];

    $pdf->AddPage();
    renderHeader($pdf, $NOME_INST, $LOGO_FILE, 15, 5);

    // Título da página
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0, 8, enc('Relatório Geral de Nível de Ensino'), 0, 1, 'L');
    $pdf->Ln(2);

    // Dados do nível
    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(220,220,220);
    $pdf->Cell(190, 8, enc('Nível de Ensino'), 1, 1, 'C', true);

    $pdf->SetFont('Arial','',11);
    $pdf->Cell(190, 8, enc($nomeNivel), 1, 1, 'C');

    /* ---------- Séries e Turmas (opcional) ---------- */
    if ($seriesOpt) {
        $pdf->Ln(6);
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(0, 8, enc('Séries e Turmas'), 0, 1, 'L');

        try {
            // Lista por linha: Ano letivo + Série + Total aulas + Turno + Turma (DISTINCT evita duplicidade)
            $sqlSeries = "
                SELECT DISTINCT
                    COALESCE(a.ano, '-') AS ano_letivo,
                    s.nome_serie,
                    s.total_aulas_semana,
                    COALESCE(tr.nome_turno, '-') AS nome_turno,
                    COALESCE(t.nome_turma, '-') AS nome_turma
                FROM serie s
                LEFT JOIN turma t      ON t.id_serie = s.id_serie
                LEFT JOIN ano_letivo a ON a.id_ano_letivo = t.id_ano_letivo
                LEFT JOIN turno tr     ON tr.id_turno = t.id_turno
                WHERE s.id_nivel_ensino = :idNivel
                ORDER BY a.ano, s.nome_serie, tr.nome_turno, t.nome_turma
            ";
            $stSeries = $pdo->prepare($sqlSeries);
            $stSeries->execute([':idNivel' => $idNivel]);
            $rows = $stSeries->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            logSecurity("Erro SQL series/turmas para nivel {$idNivel}: ".$e->getMessage());
            $rows = [];
        }

        if ($rows) {
            // Cabeçalho da tabela
            $pdf->SetFont('Arial','B',12);
            $pdf->SetFillColor(200,200,200);

            // Larguras (total = 190mm)
            $wAno   = 25;
            $wSerie = 60;
            $wAulas = 30;
            $wTurno = 45;
            $wTurma = 30;

            $pdf->Cell($wAno,  8, enc('Ano'),        1, 0, 'C', true);
            $pdf->Cell($wSerie,8, enc('Série'),      1, 0, 'C', true);
            $pdf->Cell($wAulas,8, enc('Aulas/Sem'),  1, 0, 'C', true);
            $pdf->Cell($wTurno,8, enc('Turno'),      1, 0, 'C', true);
            $pdf->Cell($wTurma,8, enc('Turma'),      1, 1, 'C', true);

            // Linhas
            $pdf->SetFont('Arial','',11);

            foreach ($rows as $r) {
                $ano   = (string)($r['ano_letivo'] ?? '-');
                $serie = (string)($r['nome_serie'] ?? '-');
                $aulas = (string)($r['total_aulas_semana'] ?? '-');
                $turno = (string)($r['nome_turno'] ?? '-');
                $turma = (string)($r['nome_turma'] ?? '-');

                $pdf->Cell($wAno,  8, enc($ano),   1, 0, 'C');
                $pdf->Cell($wSerie,8, enc($serie), 1, 0, 'C');
                $pdf->Cell($wAulas,8, enc($aulas), 1, 0, 'C');
                $pdf->Cell($wTurno,8, enc($turno), 1, 0, 'C');
                $pdf->Cell($wTurma,8, enc($turma), 1, 1, 'C');
            }

        } else {
            $pdf->SetFont('Arial','I',11);
            $pdf->Cell(190, 8, enc('Nenhuma série cadastrada para este nível.'), 1, 1, 'C');
        }
    }


    /* ---------- Usuários (opcional) ---------- */
    if ($usuariosOpt) {
        try {
            $sqlUsers = "
                SELECT u.nome_usuario, u.email_usuario, u.situacao_usuario
                FROM usuario u
                JOIN usuario_niveis un ON u.id_usuario = un.id_usuario
                WHERE un.id_nivel_ensino = :idNivel
                ORDER BY u.nome_usuario
            ";
            $stUsers = $pdo->prepare($sqlUsers);
            $stUsers->execute([':idNivel' => $idNivel]);
            $users = $stUsers->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            logSecurity("Erro SQL usuarios para nivel {$idNivel}: ".$e->getMessage());
            $users = [];
        }

        $pdf->Ln(6);
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(0, 8, enc('Usuários com acesso a este nível'), 0, 1, 'L');
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
            $pdf->Cell(190, 8, enc('Nenhum usuário vinculado a este nível.'), 1, 1, 'C');
        }
    }
}

$pdf->Output();
exit;
?>
