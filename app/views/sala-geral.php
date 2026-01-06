<?php
// app/views/sala-geral.php
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

// ---------------- Segurança e logging (não altera cabeçalho visual) ----------------
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

// whitelist de parâmetros permitidos: apenas "turmas" com valor "1" se presente
$allowed = ['turmas'];
$received = array_keys($_GET);
$extra = array_diff($received, $allowed);
if (!empty($extra)) {
    logSecurity('Parâmetros inesperados em sala-geral: '.implode(', ',$extra));
    abortClient();
}
$mostrarTurmas = false;
if (array_key_exists('turmas', $_GET)) {
    if ((string)$_GET['turmas'] !== '1') {
        logSecurity("Valor inválido para 'turmas' em sala-geral: raw=" . ($_GET['turmas'] ?? ''));
        abortClient();
    }
    $mostrarTurmas = true;
}

// ---------------- Parâmetros do cabeçalho (ajuste aqui) ----------------
$LOGO_SIZE_MM = 15; // tamanho (largura=altura) da logo em mm
$LOGO_GAP_MM  = 5;  // espaço entre a logo e o nome em mm
 
class PDF extends FPDF {
    // ----------------------------------------------------------------
    // Footer: rodapé do relatório
    // ----------------------------------------------------------------
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        // Esquerda: Página X (sem "/N")
        $this->Cell(0, 10, iconv('UTF-8','ISO-8859-1','Página ' . $this->PageNo()), 0, 0, 'L');
        // Direita: Data/hora de impressão
        $this->Cell(0, 10, iconv('UTF-8','ISO-8859-1','Impresso em: ' . date('d/m/Y H:i:s')), 0, 0, 'R');
    }
}

// ----------------------------------------------------------------
// 1) Parâmetros GET
// ----------------------------------------------------------------
// $mostrarTurmas já definido e validado acima

// ----------------------------------------------------------------
// 2) Inicia PDF
// ----------------------------------------------------------------
$pdf = new PDF('P','mm','A4');
$pdf->SetTitle(iconv('UTF-8','ISO-8859-1','Relatório Geral de Salas'));

// ----------------------------------------------------------------
// 3) Cabeçalho (logo + nome na mesma linha, centralizados)
// ----------------------------------------------------------------
$stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
$inst = $stmtInst->fetch(PDO::FETCH_ASSOC);
$nomeInst = $inst ? ($inst['nome_instituicao'] ?? '') : '';
$logoPathBase = ($inst && !empty($inst['imagem_instituicao']))
    ? LOGO_PATH . '/' . basename($inst['imagem_instituicao'])
    : null;

function printHeaderInline(PDF $pdf, $nomeInst, $logoPathBase, $LOGO_SIZE_MM, $LOGO_GAP_MM) {
    $pdf->SetY(12);
    $pdf->SetFont('Arial','B',14);

    $nomeTxt = iconv('UTF-8','ISO-8859-1', $nomeInst);
    $nomeW   = $pdf->GetStringWidth($nomeTxt);

    $hasLogo = ($logoPathBase && file_exists($logoPathBase));
    $totalW  = $hasLogo ? ($LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0) + $nomeW) : $nomeW;

    $pageW   = $pdf->GetPageWidth();
    $startX  = ($pageW - $totalW) / 2;
    $y       = $pdf->GetY();

    // Logo
    if ($hasLogo) {
        $pdf->Image($logoPathBase, $startX, $y - 2, $LOGO_SIZE_MM, $LOGO_SIZE_MM);
        $startX += $LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0);
    }

    // Nome da instituição
    if ($nomeInst) {
        $pdf->SetXY($startX, $y);
        $pdf->Cell($nomeW, $LOGO_SIZE_MM, $nomeTxt, 0, 1, 'L');
    }

    // Título
    $pdf->Ln(3);
    $pdf->SetFont('Arial','B',13);
    $pdf->Cell(0, 7, iconv('UTF-8','ISO-8859-1','Relatório de Sala'), 0, 1, 'L');
    $pdf->Ln(1);
}

// ----------------------------------------------------------------
// 4) Consulta de todas as salas (uso de try/catch e prepared statements)
// ----------------------------------------------------------------
try {
    $stmtSalas = $pdo->prepare("SELECT * FROM sala ORDER BY nome_sala");
    $stmtSalas->execute();
    $salas = $stmtSalas->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logSecurity("Erro SQL listar salas: ".$e->getMessage());
    // mostra mensagem simples no PDF
    $pdf->AddPage();
    printHeaderInline($pdf, $nomeInst, $logoPathBase, $LOGO_SIZE_MM, $LOGO_GAP_MM);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10, iconv('UTF-8','ISO-8859-1','Erro interno ao carregar salas.'), 0,1,'C');
    $pdf->Output();
    exit;
}

if (!$salas) {
    $pdf->AddPage();
    printHeaderInline($pdf, $nomeInst, $logoPathBase, $LOGO_SIZE_MM, $LOGO_GAP_MM);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10, iconv('UTF-8','ISO-8859-1','Nenhuma sala encontrada.'), 0,1,'C');
    $pdf->Output();
    exit;
}

// ----------------------------------------------------------------
// 5) Geração do PDF para cada sala
// ----------------------------------------------------------------
foreach ($salas as $sala) {
    $pdf->AddPage();
    printHeaderInline($pdf, $nomeInst, $logoPathBase, $LOGO_SIZE_MM, $LOGO_GAP_MM);

    // 5.1) Dados gerais da sala (fontes um pouco menores para padronizar)
    $pdf->SetFont('Arial','B',11);
    $pdf->SetFillColor(220,220,220);
    // Cabeçalho com largura total de 190 mm: 60 + 40 + 40 + 50
    $pdf->Cell(60, 9, iconv('UTF-8','ISO-8859-1','Nome da Sala'), 1, 0, 'C', true);
    $pdf->Cell(40, 9, iconv('UTF-8','ISO-8859-1','Carteiras'),    1, 0, 'C', true);
    $pdf->Cell(40, 9, iconv('UTF-8','ISO-8859-1','Cadeiras'),     1, 0, 'C', true);
    $pdf->Cell(50, 9, iconv('UTF-8','ISO-8859-1','Capacidade'),   1, 1, 'C', true);

    $pdf->SetFont('Arial','',11);
    $pdf->Cell(60, 9, iconv('UTF-8','ISO-8859-1', $sala['nome_sala']), 1, 0, 'C');
    $pdf->Cell(40, 9, $sala['max_carteiras'], 1, 0, 'C');
    $pdf->Cell(40, 9, $sala['max_cadeiras'],  1, 0, 'C');
    $pdf->Cell(50, 9, $sala['capacidade_alunos'], 1, 1, 'C');

    // 5.2) Linha de Localização
    $pdf->Ln(2);
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0, 8, iconv('UTF-8','ISO-8859-1','Localização'), 1, 1, 'C', true);

    $pdf->SetFont('Arial','',11);
    $pdf->MultiCell(0, 8, iconv('UTF-8','ISO-8859-1', $sala['localizacao']), 1, 'L');

    // 5.3) Linha de Recursos
    $pdf->Ln(2);
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0, 8, iconv('UTF-8','ISO-8859-1','Recursos'), 1, 1, 'C', true);

    $pdf->SetFont('Arial','',11);
    $pdf->MultiCell(0, 8, iconv('UTF-8','ISO-8859-1', $sala['recursos']), 1, 'L');

    // 5.4) Turmas Vinculadas (se o parâmetro turmas=1 estiver marcado)
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
            $stmtVinc->execute([':id' => $sala['id_sala']]);
            $vinculacoes = $stmtVinc->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            logSecurity("Erro SQL vinculacoes sala (id_sala={$sala['id_sala']}): ".$e->getMessage());
            $vinculacoes = [];
        }

        if ($vinculacoes) {
            // Agrupa os dados por turno
            $turmasPorTurno = [];
            foreach ($vinculacoes as $v) {
                $infoTurma = $v['nome_nivel_ensino'] . " - " . $v['nome_serie'] . " - " . $v['nome_turma'];
                $turno = $v['nome_turno'];
                if (!isset($turmasPorTurno[$turno])) {
                    $turmasPorTurno[$turno] = [];
                }
                $turmasPorTurno[$turno][] = $infoTurma;
            }

            // Título da seção
            $pdf->Ln(5);
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(0, 7, iconv('UTF-8','ISO-8859-1','Turmas Vinculadas'), 0, 1, 'L');
            $pdf->Ln(1);

            // Cabeçalho da tabela: Turno (40 mm) e Turmas (150 mm)
            $pdf->SetFont('Arial','B',11);
            $pdf->SetFillColor(230,230,230);
            $pdf->Cell(40, 8, iconv('UTF-8','ISO-8859-1','Turno'),  1, 0, 'C', true);
            $pdf->Cell(150,8, iconv('UTF-8','ISO-8859-1','Turmas'), 1, 1, 'C', true);

            // Conteúdo
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
}

$pdf->Ln(4);
$pdf->Output();
exit;
?>
