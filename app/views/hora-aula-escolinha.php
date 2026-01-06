<?php
// app/views/hora-aula-escolinha.php

require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

// ---------------- Segurança / logging ----------------
$SEG_LOG = __DIR__ . '/../../logs/seguranca.log';
if (!function_exists('logSecurity')) {
    function logSecurity($msg) {
        global $SEG_LOG;
        $meta = [
            'ts'    => date('c'),
            'ip'    => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'ua'    => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'qs'    => $_SERVER['QUERY_STRING'] ?? '',
            'script'=> basename($_SERVER['SCRIPT_NAME'] ?? '')
        ];
        $entry = '['.date('d-M-Y H:i:s T').'] [SEGURANCA] '.$msg.' | META='.json_encode($meta, JSON_UNESCAPED_UNICODE).PHP_EOL;
        @file_put_contents($SEG_LOG, $entry, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Aborta a requisição e retorna mensagem de erro em texto puro.
 * Limpa buffers para evitar PDF misturado com texto.
 */
function abortClient($msg = 'Parâmetros inválidos') {
    while (ob_get_level() > 0) @ob_end_clean();
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit;
}

// ---------------- Validação rígida da query string ----------------
// Aceitamos apenas estes parâmetros via GET
$allowed = ['id_ano_letivo', 'apenas_ativas'];
$rawQuery = $_SERVER['QUERY_STRING'] ?? '';
parse_str($rawQuery, $receivedParams);

// Detecta parâmetros inesperados
$receivedKeys = array_keys($receivedParams);
$extra = array_diff($receivedKeys, $allowed);
if (!empty($extra)) {
    logSecurity('Parâmetros inesperados em hora-aula-escolinha: ' . implode(', ', $extra) . ' | raw_qs=' . $rawQuery);
    abortClient();
}

// Normalizadores e validadores
$canonical = [];

// id_ano_letivo: opcional, inteiro positivo (>0) quando presente
if (array_key_exists('id_ano_letivo', $receivedParams)) {
    $raw = $receivedParams['id_ano_letivo'];
    // rejeita qualquer coisa que não seja apenas dígitos e >0
    if (!is_scalar($raw) || !preg_match('/^[1-9]\d*$/', (string)$raw)) {
        logSecurity('id_ano_letivo inválido em hora-aula-escolinha: raw=' . var_export($raw, true) . ' | raw_qs=' . $rawQuery);
        abortClient();
    }
    $canonical['id_ano_letivo'] = (int)$raw;
}

// apenas_ativas: aceitar apenas -1 (todos), 0, 1
if (array_key_exists('apenas_ativas', $receivedParams)) {
    $raw = $receivedParams['apenas_ativas'];
    if (!is_scalar($raw) || !preg_match('/^-?\d+$/', (string)$raw)) {
        logSecurity('apenas_ativas inválido (não-numérico): raw=' . var_export($raw, true) . ' | raw_qs=' . $rawQuery);
        abortClient();
    }
    $ival = (int)$raw;
    if (!in_array($ival, [-1,0,1], true)) {
        logSecurity('apenas_ativas fora do conjunto permitido: raw=' . var_export($raw, true) . ' | raw_qs=' . $rawQuery);
        abortClient();
    }
    $canonical['apenas_ativas'] = $ival;
}

// Comparar arrays normalizados (independente da ordem)
// Normalizar receivedParams convertendo valores esperados para tipos canônicos
$normalized_received_array = [];
foreach ($receivedParams as $k => $v) {
    if ($k === 'id_ano_letivo') {
        $normalized_received_array[$k] = (int)$v;
    } elseif ($k === 'apenas_ativas') {
        $normalized_received_array[$k] = (int)$v;
    } else {
        // Embora extras tenham sido rejeitados, defendo contra casos estranhos
        $normalized_received_array[$k] = $v;
    }
}

// Ordenar por chave para comparação
ksort($canonical);
ksort($normalized_received_array);

// Comparar arrays (tipos normalizados)
if ($canonical !== $normalized_received_array) {
    logSecurity("Query string não corresponde ao formato canônico em hora-aula-escolinha: expected_array=" . json_encode($canonical) . " got_array=" . json_encode($normalized_received_array) . " | full_raw={$rawQuery}");
    abortClient();
}

// Atribuir valores seguros (usar defaults caso ausentes)
$idAnoLetivo = isset($canonical['id_ano_letivo']) ? (int)$canonical['id_ano_letivo'] : 0;
$apenasAtivas = isset($canonical['apenas_ativas']) ? (int)$canonical['apenas_ativas'] : -1;

// ---------------- Proteção de arquivos (logo) ----------------
$logoPath = null;
$stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
$inst = $stmtInst->fetch(PDO::FETCH_ASSOC);
if ($inst && !empty($inst['imagem_instituicao'])) {
    $logoCandidate = basename($inst['imagem_instituicao']);
    $fullLogoPath = LOGO_PATH . '/' . $logoCandidate;
    if (file_exists($fullLogoPath) && is_file($fullLogoPath) && strpos(realpath($fullLogoPath), realpath(LOGO_PATH)) === 0) {
        $logoPath = $fullLogoPath;
    } else {
        logSecurity("Logo inválido ou inacessível em hora-aula-escolinha: " . $inst['imagem_instituicao']);
        $logoPath = null;
    }
}

// ---------------- PDF (apenas após todas validações) ----------------
class PDF extends FPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $txt = 'Impresso em: ' . date('d/m/Y H:i:s');
        $this->Cell(0, 10, iconv('UTF-8','ISO-8859-1', $txt), 0, 0, 'R');
    }
}

$pdf = new PDF('P','mm','A4');
$pdf->SetTitle(iconv('UTF-8','ISO-8859-1','Relatório de Configurações - Hora/Aula Escolinha'));
$pdf->AddPage();

// Logo da instituição (somente se validado)
if ($logoPath) {
    $size = 90 * 25.4 / 96;
    $x = ($pdf->GetPageWidth() - $size) / 2;
    $pdf->Image($logoPath, $x, 10, $size, $size);
}

$pdf->Ln(25);
$pdf->SetFont('Arial','B',16);
if ($inst && !empty($inst['nome_instituicao'])) {
    $pdf->Cell(0,10, iconv('UTF-8','ISO-8859-1',$inst['nome_instituicao']), 0,1,'C');
}

$pdf->Ln(5);
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10, iconv('UTF-8','ISO-8859-1','Relatório de Configurações - Hora/Aula Escolinha'), 0,1,'C');
$pdf->Ln(5);

// ---------------- Query base (seguindo sua lógica original) ----------------
$sql = "
    SELECT
        chae.id_configuracao,
        al.ano as ano_letivo,
        m.nome_modalidade,
        c.nome_categoria,
        CONCAT(m.nome_modalidade, ' - ', c.nome_categoria) as nome_completo,
        chae.duracao_aula_minutos,
        chae.tolerancia_quebra
    FROM configuracao_hora_aula_escolinha chae
    JOIN ano_letivo al ON chae.id_ano_letivo = al.id_ano_letivo
    JOIN modalidade m ON chae.id_modalidade = m.id_modalidade
    LEFT JOIN categoria c ON chae.id_categoria = c.id_categoria
    WHERE chae.ativo = 1
";

$params = [];

if ($idAnoLetivo > 0) {
    $sql .= " AND chae.id_ano_letivo = :id_ano_letivo";
    $params[':id_ano_letivo'] = $idAnoLetivo;
}
if ($apenasAtivas === 1) {
    $sql .= " AND chae.tolerancia_quebra = 1";
} elseif ($apenasAtivas === 0) {
    $sql .= " AND chae.tolerancia_quebra = 0";
}

$sql .= " ORDER BY al.ano DESC, m.nome_modalidade ASC, c.nome_categoria ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$configuracoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$configuracoes) {
    // manter resposta em texto para evitar PDF vazio corrompido
    logSecurity("Nenhuma configuração encontrada com filtros: id_ano_letivo={$idAnoLetivo} apenas_ativas={$apenasAtivas}");
    while (ob_get_level() > 0) @ob_end_clean();
    http_response_code(200);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Nenhuma configuração encontrada com os filtros aplicados.';
    exit;
}

// Cabeçalho da tabela
$pdf->SetFont('Arial','B',9);
$pdf->SetFillColor(230,230,230);
$pdf->Cell(25,8, iconv('UTF-8','ISO-8859-1','Ano'), 1, 0, 'C', true);
$pdf->Cell(90,8, iconv('UTF-8','ISO-8859-1','Modalidade - Categoria'), 1, 0, 'C', true);
$pdf->Cell(35,8, iconv('UTF-8','ISO-8859-1','Duração'), 1, 0, 'C', true);
$pdf->Cell(40,8, iconv('UTF-8','ISO-8859-1','Tolerância'), 1, 1, 'C', true);

$pdf->SetFont('Arial','',9);

$totalConfiguracoes = 0;
$totalAtivas = 0;
$totalInativas = 0;

foreach ($configuracoes as $config) {
    if ($pdf->GetY() > 250) {
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',9);
        $pdf->SetFillColor(230,230,230);
        $pdf->Cell(25,8, iconv('UTF-8','ISO-8859-1','Ano'), 1, 0, 'C', true);
        $pdf->Cell(90,8, iconv('UTF-8','ISO-8859-1','Modalidade - Categoria'), 1, 0, 'C', true);
        $pdf->Cell(35,8, iconv('UTF-8','ISO-8859-1','Duração'), 1, 0, 'C', true);
        $pdf->Cell(40,8, iconv('UTF-8','ISO-8859-1','Tolerância'), 1, 1, 'C', true);
        $pdf->SetFont('Arial','',9);
    }

    $totalConfiguracoes++;
    $config['tolerancia_quebra'] == 1 ? $totalAtivas++ : $totalInativas++;

    $nomeCompleto = $config['nome_completo'];
    if (mb_strlen($nomeCompleto) > 45) {
        $nomeCompleto = mb_substr($nomeCompleto, 0, 42) . '...';
    }

    $pdf->Cell(25,8, iconv('UTF-8','ISO-8859-1', $config['ano_letivo']), 1, 0, 'C');
    $pdf->Cell(90,8, iconv('UTF-8','ISO-8859-1', $nomeCompleto), 1, 0, 'L');
    $pdf->Cell(35,8, iconv('UTF-8','ISO-8859-1', $config['duracao_aula_minutos'] . ' min'), 1, 0, 'C');
    $pdf->Cell(40,8, iconv('UTF-8','ISO-8859-1', $config['tolerancia_quebra'] ? 'Sim' : 'Não'), 1, 1, 'C');
}

// Resumo Estatístico
$pdf->Ln(10);
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,8, iconv('UTF-8','ISO-8859-1','Resumo Estatístico'), 0,1,'L');

$pdf->SetFont('Arial','B',9);
$pdf->SetFillColor(240,240,240);

if ($apenasAtivas === 1) {
    $pdf->Cell(95,8, iconv('UTF-8','ISO-8859-1','Total de Configurações'), 1, 0, 'C', true);
    $pdf->Cell(95,8, iconv('UTF-8','ISO-8859-1','Tolerância Ativa'), 1, 1, 'C', true);
    $pdf->SetFont('Arial','',9);
    $pdf->Cell(95,8, iconv('UTF-8','ISO-8859-1', $totalConfiguracoes), 1, 0, 'C');
    $pdf->Cell(95,8, iconv('UTF-8','ISO-8859-1', $totalAtivas), 1, 1, 'C');
} elseif ($apenasAtivas === 0) {
    $pdf->Cell(95,8, iconv('UTF-8','ISO-8859-1','Total de Configurações'), 1, 0, 'C', true);
    $pdf->Cell(95,8, iconv('UTF-8','ISO-8859-1','Tolerância Inativa'), 1, 1, 'C', true);
    $pdf->SetFont('Arial','',9);
    $pdf->Cell(95,8, iconv('UTF-8','ISO-8859-1', $totalConfiguracoes), 1, 0, 'C');
    $pdf->Cell(95,8, iconv('UTF-8','ISO-8859-1', $totalInativas), 1, 1, 'C');
} else {
    $pdf->Cell(63.33,8, iconv('UTF-8','ISO-8859-1','Total de Configurações'), 1, 0, 'C', true);
    $pdf->Cell(63.33,8, iconv('UTF-8','ISO-8859-1','Tolerância Ativa'), 1, 0, 'C', true);
    $pdf->Cell(63.33,8, iconv('UTF-8','ISO-8859-1','Tolerância Inativa'), 1, 1, 'C', true);
    $pdf->SetFont('Arial','',9);
    $pdf->Cell(63.33,8, iconv('UTF-8','ISO-8859-1', $totalConfiguracoes), 1, 0, 'C');
    $pdf->Cell(63.33,8, iconv('UTF-8','ISO-8859-1', $totalAtivas), 1, 0, 'C');
    $pdf->Cell(63.33,8, iconv('UTF-8','ISO-8859-1', $totalInativas), 1, 1, 'C');
}

// Durações Mais Utilizadas
$pdf->Ln(10);
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,8, iconv('UTF-8','ISO-8859-1','Durações Mais Utilizadas'), 0,1,'L');

$sqlDuracoes = "
    SELECT duracao_aula_minutos, COUNT(*) as quantidade
    FROM configuracao_hora_aula_escolinha
    WHERE ativo = 1
";

if ($idAnoLetivo > 0) {
    $sqlDuracoes .= " AND id_ano_letivo = :id_ano_letivo";
}
if ($apenasAtivas === 1) {
    $sqlDuracoes .= " AND tolerancia_quebra = 1";
} elseif ($apenasAtivas === 0) {
    $sqlDuracoes .= " AND tolerancia_quebra = 0";
}

$sqlDuracoes .= " GROUP BY duracao_aula_minutos ORDER BY quantidade DESC, duracao_aula_minutos ASC";
$stmtDuracoes = $pdo->prepare($sqlDuracoes);
$stmtDuracoes->execute($params);
$duracoes = $stmtDuracoes->fetchAll(PDO::FETCH_ASSOC);

if (!empty($duracoes)) {
    $pdf->SetFont('Arial','B',9);
    $pdf->SetFillColor(240,240,240);
    $pdf->Cell(95,8, iconv('UTF-8','ISO-8859-1','Duração (minutos)'), 1, 0, 'C', true);
    $pdf->Cell(95,8, iconv('UTF-8','ISO-8859-1','Quantidade de Configurações'), 1, 1, 'C', true);

    $pdf->SetFont('Arial','',9);
    foreach ($duracoes as $d) {
        $pdf->Cell(95,8, iconv('UTF-8','ISO-8859-1', $d['duracao_aula_minutos'] . ' minutos'), 1, 0, 'C');
        $pdf->Cell(95,8, iconv('UTF-8','ISO-8859-1', $d['quantidade']), 1, 1, 'C');
    }
}

$pdf->Output();
exit;
?>
