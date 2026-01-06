<?php
// app/views/ano-letivo-geral.php - VERSÃO REESCRITA (validação rígida + logs em logs/seguranca.log)
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

/* ---------------- CONFIGURAÇÃO ---------------- */
$PARAMS_ESPERADOS = ['turma', 'prof_restricao'];
$LOG_PATH = __DIR__ . '/../../logs/seguranca.log';
$DEFAULT_BAD_MSG = 'Parâmetros inválidos';

/* ---------------- LOG + ABORT ---------------- */
function logSecurity(string $msg): void {
    global $LOG_PATH;
    $meta = [
        'ts' => date('c'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'qs' => $_SERVER['QUERY_STRING'] ?? '',
        'script' => basename($_SERVER['SCRIPT_NAME'] ?? '')
    ];
    $entry = '[' . date('d-M-Y H:i:s T') . '] [SEGURANCA] ' . $msg
           . ' | META=' . json_encode($meta, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    $dir = dirname($LOG_PATH);
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    @file_put_contents($LOG_PATH, $entry, FILE_APPEND | LOCK_EX);
}
function abortInvalid(): void {
    http_response_code(400);
    die('Parâmetros inválidos');
}
function abortServer(): void {
    http_response_code(500);
    die('Parâmetros inválidos');
}
function abortNotFound(): void {
    http_response_code(404);
    die('Parâmetros inválidos');
}

/* ---------------- HELPERS ---------------- */
function enc(string $s): string {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$s);
}
function fmtDate($d): string {
    $ts = strtotime($d);
    return $ts ? date('d/m/Y', $ts) : '';
}

/* ---------------- VALIDAÇÃO DE PARÂMETROS (WHITELIST) ---------------- */
/// Rejeita parâmetros extras
$extras = array_diff(array_keys($_GET), $PARAMS_ESPERADOS);
if (!empty($extras)) {
    logSecurity('Parâmetros inesperados: ' . implode(', ', $extras));
    abortInvalid();
}

// Lê e valida parâmetros opcionais
$turma_raw = array_key_exists('turma', $_GET) ? $_GET['turma'] : null;
$prof_raw  = array_key_exists('prof_restricao', $_GET) ? $_GET['prof_restricao'] : null;

$turma_filter = null;
$prof_filter  = null;

// turma: se presente aceita apenas '1' (representando verdadeiro) ou aborta
if ($turma_raw !== null) {
    // aceitar '1' ou '0' numericamente; aqui exige 1
    $turma_filter = filter_var($turma_raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($turma_filter !== 1) {
        logSecurity('turma inválido: ' . $turma_raw);
        abortInvalid();
    }
}

// prof_restricao: se presente aceita 'todas' ou ID numérico >=1
if ($prof_raw !== null) {
    $prof_raw_str = (string) $prof_raw;
    if (mb_strtolower($prof_raw_str, 'UTF-8') === 'todas') {
        $prof_filter = 'todas';
    } else {
        $prof_filter_id = filter_var($prof_raw_str, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($prof_filter_id === false) {
            logSecurity('prof_restricao inválido: ' . $prof_raw_str);
            abortInvalid();
        }
        $prof_filter = (int) $prof_filter_id;
    }
}

/* ---------------- CONSULTAS (seguras) ---------------- */
try {
    // lista de anos letivos
    $stmtAnos = $pdo->prepare("SELECT id_ano_letivo, ano, data_inicio, data_fim FROM ano_letivo ORDER BY ano");
    $stmtAnos->execute();
    $listaAnos = $stmtAnos->fetchAll(PDO::FETCH_ASSOC);

    // dados da instituição (para cabeçalho)
    $stmtInst = $pdo->prepare("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
    $stmtInst->execute();
    $inst = $stmtInst->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    logSecurity('Erro SQL ao buscar anos/inst: ' . $e->getMessage() . ' | QS: ' . ($_SERVER['QUERY_STRING'] ?? ''));
    abortServer();
}

/* ---------------- PREPARE PDF ---------------- */
class PDF extends FPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, enc('Página ' . $this->PageNo()), 0, 0, 'L');
        $this->Cell(0, 10, enc('Impresso em: ' . date('d/m/Y H:i:s')), 0, 0, 'R');
    }
}

$pdf = new PDF('P','mm','A4');
$pdf->SetTitle(enc('Relatório Geral de Anos Letivos'));

/* ---------------- se não há anos, gera página simples ---------------- */
if (empty($listaAnos)) {
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,10, enc('Não há anos letivos cadastrados'), 0,1,'C');
    $pdf->Output();
    exit;
}

/* ---------------- dados da instituição ---------------- */
$nomeInstituicao = $inst['nome_instituicao'] ?? '';
$imgInstituicao  = $inst['imagem_instituicao'] ?? '';
$logoPath = $imgInstituicao ? LOGO_PATH . '/' . basename($imgInstituicao) : null;

/* ---------------- layout constants ---------------- */
$LOGO_SIZE_MM = 15;
$LOGO_GAP_MM  = 5;

/* ---------------- percorre anos e gera páginas ---------------- */
foreach ($listaAnos as $anoItem) {
    $id_ano  = $anoItem['id_ano_letivo'];
    $ano     = $anoItem['ano'];
    $dataIni = $anoItem['data_inicio'];
    $dataFim = $anoItem['data_fim'];

    $pdf->AddPage();

    // cabeçalho (logo + nome)
    $topY = 12;
    $pdf->SetFont('Arial','B',14);
    $text = enc($nomeInstituicao);
    $textW = $pdf->GetStringWidth($text);
    $totalW = ($logoPath && file_exists($logoPath) ? ($LOGO_SIZE_MM + $LOGO_GAP_MM) : 0) + $textW;
    $pageW = $pdf->GetPageWidth();
    $xStart = ($pageW - $totalW) / 2;

    if ($logoPath && file_exists($logoPath)) {
        $pdf->Image($logoPath, $xStart, $topY, $LOGO_SIZE_MM, $LOGO_SIZE_MM);
    }
    $textX = $xStart + ($logoPath && file_exists($logoPath) ? ($LOGO_SIZE_MM + $LOGO_GAP_MM) : 0);
    $pdf->SetXY($textX, $topY + 2);
    if ($nomeInstituicao !== '') {
        $pdf->Cell($textW + 2, 8, $text, 0, 1, 'L');
    }

    $pdf->SetY($topY + $LOGO_SIZE_MM + 6);

    // título do ano
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0, 8, enc('Relatório de Ano Letivo - ' . $ano), 0, 1, 'L');
    $pdf->Ln(1);

    // tabela resumo do ano
    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(220,220,220);
    $pdf->Cell(40, 8, enc('Ano Letivo'), 1, 0, 'C', true);
    $pdf->Cell(75, 8, enc('Data Início'), 1, 0, 'C', true);
    $pdf->Cell(75, 8, enc('Data Fim'), 1, 1, 'C', true);

    $pdf->SetFont('Arial','',11);
    $pdf->Cell(40, 8, enc($ano), 1, 0, 'C');
    $pdf->Cell(75, 8, enc(fmtDate($dataIni)), 1, 0, 'C');
    $pdf->Cell(75, 8, enc(fmtDate($dataFim)), 1, 1, 'C');

    /* ---------------- seção Turmas (apenas se solicitada) ---------------- */
    if ($turma_filter === 1) {
        try {
            $stmtT = $pdo->prepare("
                SELECT n.nome_nivel_ensino AS nivel,
                       s.nome_serie AS serie,
                       GROUP_CONCAT(t.nome_turma ORDER BY t.nome_turma SEPARATOR ', ') AS turmas
                FROM turma t
                JOIN serie s ON t.id_serie = s.id_serie
                JOIN nivel_ensino n ON s.id_nivel_ensino = n.id_nivel_ensino
                WHERE t.id_ano_letivo = ?
                GROUP BY n.id_nivel_ensino, s.id_serie
                ORDER BY n.nome_nivel_ensino, s.nome_serie
            ");
            $stmtT->execute([$id_ano]);
            $turmas = $stmtT->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            logSecurity('Erro consulta turmas: ' . $e->getMessage());
            $turmas = [];
        }

        if (!empty($turmas)) {
            $pdf->Ln(6);
            $pdf->SetFont('Arial','B',13);
            $pdf->Cell(0,8, enc('Turmas'), 0,1,'L');

            $pdf->SetFont('Arial','B',11);
            $pdf->SetFillColor(200,200,200);
            $pdf->Cell(60, 8, enc('Nível de Ensino'), 1, 0, 'C', true);
            $pdf->Cell(40, 8, enc('Série'), 1, 0, 'C', true);
            $pdf->Cell(90, 8, enc('Turmas'), 1, 1, 'C', true);

            $pdf->SetFont('Arial','',10);
            foreach ($turmas as $t) {
                $pdf->Cell(60, 8, enc($t['nivel'] ?? ''), 1, 0, 'C');
                $pdf->Cell(40, 8, enc($t['serie'] ?? ''), 1, 0, 'C');
                $pdf->Cell(90, 8, enc($t['turmas'] ?? ''), 1, 1, 'C');
            }
        }
    }

    /* ---------------- seção Professor Restrição (apenas se solicitada) ---------------- */
    if ($prof_filter !== null) {
        try {
            $pdf->Ln(6);
            $pdf->SetFont('Arial','B',13);
            $pdf->Cell(0,8, enc('Professor Restrição'), 0,1,'L');

            $sqlProf = "
                SELECT pr.id_professor,
                       p.nome_completo AS nome_professor,
                       pr.dia_semana,
                       pr.numero_aula
                FROM professor_restricoes pr
                JOIN professor p ON pr.id_professor = p.id_professor
                WHERE pr.id_ano_letivo = ?
            ";
            $params = [$id_ano];
            if ($prof_filter !== 'todas') {
                $sqlProf .= " AND pr.id_professor = ?";
                $params[] = $prof_filter;
            }
            $sqlProf .= "
                ORDER BY p.nome_completo,
                         FIELD(pr.dia_semana,'Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado'),
                         pr.numero_aula
            ";
            $stmtP = $pdo->prepare($sqlProf);
            $stmtP->execute($params);
            $rows = $stmtP->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            logSecurity('Erro consulta restricoes professor: ' . $e->getMessage());
            $rows = [];
        }

        if (!empty($rows)) {
            // agrupa por professor
            $professores = [];
            foreach ($rows as $r) {
                $idProf = (int)($r['id_professor'] ?? 0);
                if ($idProf === 0) continue;
                $nome = $r['nome_professor'] ?? '';
                $dia = $r['dia_semana'] ?? '';
                // normalizar dias para chave
                $diaKey = str_replace(['ç','á','é','í','ó','ú','ã','õ','â','ê','ô',' '], ['c','a','e','i','o','u','a','o','a','e','o',''], $dia);
                if ($diaKey === '') $diaKey = $dia;
                $num = (int)($r['numero_aula'] ?? 0);
                if (!isset($professores[$idProf])) $professores[$idProf] = ['nome' => $nome, 'restricoes' => []];
                if (!isset($professores[$idProf]['restricoes'][$diaKey])) $professores[$idProf]['restricoes'][$diaKey] = [];
                if ($num > 0) $professores[$idProf]['restricoes'][$diaKey][] = $num;
            }

            // imprime tabela somente se houver dados válidos
            $anyValid = false;
            foreach ($professores as $p) if (!empty($p['restricoes'])) { $anyValid = true; break; }

            if ($anyValid) {
                $diasOrdem = ['Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado'];
                $diaLabel = [
                    'Domingo'=>'Domingo','Segunda'=>'Segunda','Terca'=>'Terça','Quarta'=>'Quarta',
                    'Quinta'=>'Quinta','Sexta'=>'Sexta','Sabado'=>'Sábado'
                ];

                $pdf->SetFont('Arial','B',11);
                $pdf->SetFillColor(200,200,200);
                $pdf->Cell(70, 8, enc('Nome Professor'), 1, 0, 'C', true);
                $pdf->Cell(60, 8, enc('Dia da Semana'), 1, 0, 'C', true);
                $pdf->Cell(60, 8, enc('Aulas'), 1, 1, 'C', true);
                $pdf->SetFont('Arial','',10);

                foreach ($professores as $p) {
                    $nomeProf = $p['nome'] ?? '';
                    // quais dias esse professor tem
                    $diasDoProfessor = [];
                    foreach ($diasOrdem as $d) {
                        $key = str_replace(['ç','á','é','í','ó','ú','ã','õ','â','ê','ô',' '], ['c','a','e','i','o','u','a','o','a','e','o',''], $d);
                        if (!empty($p['restricoes'][$key])) $diasDoProfessor[] = $key;
                    }
                    if (empty($diasDoProfessor)) continue;

                    $linhas = count($diasDoProfessor);
                    $rowH = 8;
                    $nameHeight = $rowH * $linhas;
                    $x = $pdf->GetX();
                    $y = $pdf->GetY();

                    // nome mesclado
                    $pdf->Cell(70, $nameHeight, enc($nomeProf), 1, 0, 'C');

                    foreach ($diasDoProfessor as $i => $diaKey) {
                        $lista = array_values(array_unique(array_map('intval', $p['restricoes'][$diaKey] ?? [])));
                        sort($lista, SORT_NUMERIC);
                        $aulasFormatadas = array_map(fn($n) => $n . 'ª', $lista);
                        $aulasStr = implode(', ', $aulasFormatadas);
                        $label = $diaLabel[$diaKey] ?? $diaKey;

                        $pdf->SetXY($x + 70, $y + ($i * $rowH));
                        $pdf->Cell(60, $rowH, enc($label), 1, 0, 'C');
                        $pdf->Cell(60, $rowH, enc($aulasStr), 1, 0, 'C');
                    }

                    $pdf->SetXY($x, $y + $nameHeight);
                    $pdf->Ln(3);
                }
            }
        }
    }
}

/* ---------------- SAÍDA ---------------- */
$pdf->Output();
exit;
?>
