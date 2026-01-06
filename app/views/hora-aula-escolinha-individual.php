<?php
// app/views/hora-aula-escolinha-individual.php
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

// ---------------- Segurança e logging ----------------
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
 * Garante que nenhum PDF seja enviado.
 */
function abortClient($msg = 'Parâmetro "id_configuracao" obrigatório.') {
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit;
}

// ---------------- Whitelist e canonicalização da query ----------------
// Aceitamos apenas id_configuracao como parâmetro de query nesta página.
$rawQuery = $_SERVER['QUERY_STRING'] ?? '';
parse_str($rawQuery, $receivedParams);

$allowed = ['id_configuracao'];
$receivedKeys = array_keys($receivedParams);
$extra = array_diff($receivedKeys, $allowed);
if (!empty($extra)) {
    logSecurity('Parâmetros inesperados: '.implode(', ', $extra).' | raw_qs='.$rawQuery);
    abortClient('Parâmetros inválidos');
}

// Validar e normalizar id_configuracao
if (!array_key_exists('id_configuracao', $receivedParams)) {
    logSecurity('id_configuracao ausente | raw_qs='.$rawQuery);
    abortClient('Parâmetro "id_configuracao" obrigatório.');
}
$id_raw = $receivedParams['id_configuracao'];
// aceitar somente inteiros positivos
if (!is_scalar($id_raw) || !preg_match('/^\d+$/', (string)$id_raw) || (int)$id_raw <= 0) {
    logSecurity('id_configuracao inválido: raw=' . var_export($id_raw, true) . ' | raw_qs='.$rawQuery);
    abortClient('Parâmetros inválidos');
}
$id_canonical = ['id_configuracao' => (int)$id_raw];
$canonical_qs = http_build_query($id_canonical, '', '&', PHP_QUERY_RFC3986);

// Comparar canônico com raw (raw pode ter encoding; usamos parse_str -> receivedParams)
// Reconstituímos raw a partir de canonical e garantimos igualdade sem tolerância a ordem.
if ($canonical_qs !== http_build_query($receivedParams, '', '&', PHP_QUERY_RFC3986)) {
    // se diferente, considerar manipulação e rejeitar
    logSecurity("Query string não canônica: expected='{$canonical_qs}' got='{$rawQuery}'");
    abortClient('Parâmetros inválidos');
}

$id = (int)$id_canonical['id_configuracao'];

// ---------------- Consultas seguras (prepared statements) ----------------
// 1) Buscar configuração
$sql = "
    SELECT 
        chae.*, 
        al.ano AS ano_letivo, 
        m.nome_modalidade, 
        c.nome_categoria
    FROM configuracao_hora_aula_escolinha chae
    JOIN ano_letivo al ON chae.id_ano_letivo = al.id_ano_letivo
    JOIN modalidade m ON chae.id_modalidade = m.id_modalidade
    LEFT JOIN categoria c ON chae.id_categoria = c.id_categoria
    WHERE chae.id_configuracao = :id
    LIMIT 1
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$config = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$config) {
    logSecurity("Nenhuma configuração encontrada para id_configuracao={$id}");
    abortClient('Parâmetros inválidos');
}

// 2) Buscar professores vinculados via horario_escolinha
$professores = [];
$sqlProf = "
    SELECT DISTINCT COALESCE(p.nome_exibicao, p.nome_completo) AS nome_professor
    FROM horario_escolinha he
    JOIN professor p ON he.id_professor = p.id_professor
    WHERE he.id_ano_letivo = :ano AND he.id_modalidade = :mod
";
$paramsProf = [
    ':ano' => (int)$config['id_ano_letivo'],
    ':mod' => (int)$config['id_modalidade']
];
if (!empty($config['id_categoria'])) {
    $sqlProf .= " AND he.id_categoria = :cat";
    $paramsProf[':cat'] = (int)$config['id_categoria'];
}
$stmtProf = $pdo->prepare($sqlProf);
foreach ($paramsProf as $k => $v) {
    $stmtProf->bindValue($k, $v, PDO::PARAM_INT);
}
$stmtProf->execute();
while ($p = $stmtProf->fetch(PDO::FETCH_ASSOC)) {
    // sanitize names: keep string and strip control chars
    $nome = is_string($p['nome_professor']) ? preg_replace('/[[:cntrl:]]+/', ' ', trim($p['nome_professor'])) : '';
    if ($nome !== '') $professores[] = $nome;
}

// 3) Não é obrigatório haver professores; se precisar forçar erro, descomente
// if (empty($professores)) { logSecurity("Nenhum professor vinculado encontrado para config={$id}"); abortClient('Parâmetros inválidos'); }

// ---------------- Segurança no uso de arquivos (logo) ----------------
$logoPath = null;
$stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
$inst = $stmtInst->fetch(PDO::FETCH_ASSOC);
if ($inst && !empty($inst['imagem_instituicao'])) {
    // use basename para evitar traversal
    $logoCandidate = basename($inst['imagem_instituicao']);
    $fullLogoPath = LOGO_PATH . '/' . $logoCandidate;
    if (file_exists($fullLogoPath) && is_file($fullLogoPath) && strpos(realpath($fullLogoPath), realpath(LOGO_PATH)) === 0) {
        $logoPath = $fullLogoPath;
    } else {
        logSecurity("Logo inválido ou inacessível: " . $inst['imagem_instituicao']);
    }
}

// ---------------- Criar PDF apenas após todas as validações passarem ----------------
class PDF extends FPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $txt = 'Impresso em: ' . date('d/m/Y H:i:s');
        $this->Cell(0, 10, iconv('UTF-8','ISO-8859-1', $txt), 0, 0, 'R');
    }
}

$pdf = new PDF('P','mm','A4');
$pdf->SetTitle(iconv('UTF-8','ISO-8859-1','Relatório Individual - Hora/Aula de Escolinha'));
$pdf->AddPage();

// Logo (só se válido)
if ($logoPath) {
    // tamanho seguro
    $size = 90 * 25.4 / 96;
    $x = ($pdf->GetPageWidth() - $size) / 2;
    $pdf->Image($logoPath, $x, 10, $size, $size);
}

$pdf->Ln(25);
$pdf->SetFont('Arial','B',16);
if (!empty($inst['nome_instituicao'])) {
    $pdf->Cell(0, 10, iconv('UTF-8','ISO-8859-1', $inst['nome_instituicao']), 0, 1, 'C');
}

$pdf->Ln(5);
$pdf->SetFont('Arial','B',14);
// Título com nome do professor (usar primeiro ou 'N/A')
$nomeProfessor = !empty($professores) ? $professores[0] : 'N/A';
// garantir que nome não contenha caracteres perigosos
$nomeProfessor = preg_replace('/[[:cntrl:]]+/', ' ', trim($nomeProfessor));
$titulo = 'Relatório Individual - ' . $nomeProfessor . ' - Hora/Aula de Escolinha';
$pdf->Cell(0, 10, iconv('UTF-8','ISO-8859-1', $titulo), 0, 1, 'C');
$pdf->Ln(5);

// Cabeçalho da tabela
$pdf->SetFont('Arial','B',9);
$pdf->SetFillColor(230,230,230);
$pdf->Cell(40,8, iconv('UTF-8','ISO-8859-1','Ano'), 1, 0, 'C', true);
$pdf->Cell(120,8, iconv('UTF-8','ISO-8859-1','Modalidade - Categoria'), 1, 0, 'C', true);
$pdf->Cell(30,8, iconv('UTF-8','ISO-8859-1','Duração'), 1, 1, 'C', true);

$pdf->SetFont('Arial','',9);

// Preparar valores para exibição com limites e sanitização
$ano = isset($config['ano_letivo']) ? (string)$config['ano_letivo'] : 'N/A';
$mod = isset($config['nome_modalidade']) ? (string)$config['nome_modalidade'] : 'N/A';
$cat = isset($config['nome_categoria']) && $config['nome_categoria'] !== null ? (string)$config['nome_categoria'] : 'N/A';
$modCat = trim($mod . ' - ' . $cat);
if (mb_strlen($modCat) > 60) $modCat = mb_substr($modCat, 0, 57) . '...';
$duracao = isset($config['duracao_aula_minutos']) ? ((int)$config['duracao_aula_minutos']) . ' min' : 'N/A';

$pdf->Cell(40,8, iconv('UTF-8','ISO-8859-1', $ano), 1, 0, 'C');
$pdf->Cell(120,8, iconv('UTF-8','ISO-8859-1', $modCat), 1, 0, 'L');
$pdf->Cell(30,8, iconv('UTF-8','ISO-8859-1', $duracao), 1, 1, 'C');

$pdf->Output();
exit;
?>
