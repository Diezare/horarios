<?php
// app/views/ano-letivo.php - VERSÃO PADRONIZADA (verificação id_instituicao aplicada)
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

/* ---------------- CONFIG ---------------- */
$PARAMS_ESPERADOS = ['id_ano', 'turma', 'prof_restricao'];
$LOG_PATH = __DIR__ . '/../../logs/seguranca.log';
$DEFAULT_BAD = 'Parâmetros inválidos';

/* ---------------- LOG e ABORT ---------------- */
function logSecurity(string $msg): void {
    global $LOG_PATH;
    $meta = [
        'ts'=>date('c'),
        'ip'=>$_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'ua'=>$_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'qs'=>$_SERVER['QUERY_STRING'] ?? '',
        'script'=>basename($_SERVER['SCRIPT_NAME'] ?? '')
    ];
    $entry = '['.date('d-M-Y H:i:s T').'] [SEGURANCA] '.$msg.' | META='.json_encode($meta, JSON_UNESCAPED_UNICODE).PHP_EOL;
    $dir = dirname($LOG_PATH);
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    @file_put_contents($LOG_PATH, $entry, FILE_APPEND | LOCK_EX);
}
function abortInvalid(): void { http_response_code(400); die('Parâmetros inválidos'); }
function abortServer(): void  { http_response_code(500); die('Parâmetros inválidos'); }
function abortNotFound(): void{ http_response_code(404); die('Parâmetros inválidos'); }

/* ---------------- HELPERS ---------------- */
function enc(string $s): string { return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$s); }
function fmtDate($d): string { $ts = strtotime($d); return $ts ? date('d/m/Y', $ts) : ''; }

/* ---------------- VALIDAÇÃO WHITELIST ---------------- */
// rejeita parâmetros extras
$extras = array_diff(array_keys($_GET), $PARAMS_ESPERADOS);
if (!empty($extras)) {
    logSecurity('Parâmetros inesperados: ' . implode(', ', $extras));
    abortInvalid();
}

// id_ano obrigatório e válido
if (!array_key_exists('id_ano', $_GET)) {
    logSecurity('id_ano ausente | QS: ' . ($_SERVER['QUERY_STRING'] ?? ''));
    abortInvalid();
}
$id_ano = filter_input(INPUT_GET, 'id_ano', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
if (!$id_ano) {
    logSecurity('id_ano inválido: ' . ($_GET['id_ano'] ?? ''));
    abortInvalid();
}

// turma se presente só aceita 1
$turma_filter = null;
if (array_key_exists('turma', $_GET)) {
    $turma_raw = $_GET['turma'];
    $turma_filter = filter_var($turma_raw, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    if ($turma_filter !== 1) {
        logSecurity('turma inválido: ' . $turma_raw);
        abortInvalid();
    }
}

// prof_restricao se presente aceita 'todas' ou id numérico >=1
$prof_filter = null;
if (array_key_exists('prof_restricao', $_GET)) {
    $prof_raw = (string)$_GET['prof_restricao'];
    if (mb_strtolower($prof_raw, 'UTF-8') === 'todas') {
        $prof_filter = 'todas';
    } else {
        $prof_id = filter_var($prof_raw, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if ($prof_id === false) {
            logSecurity('prof_restricao inválido: ' . $prof_raw);
            abortInvalid();
        }
        $prof_filter = (int)$prof_id;
    }
}

/* ---------------- CHECAGEM DE PROPRIEDADE (id_instituicao) ---------------- */
/*
 Se a sessão fornece id_instituicao, confirmamos que o ano pertence à essa instituição.
 Isso bloqueia leitura de dados entre instituições.
*/
$sessionInst = $_SESSION['id_instituicao'] ?? null;

/* ---------------- CONSULTAS SEGURAS ---------------- */
try {
    // busca do ano letivo solicitado com verificação de propriedade quando aplicável
    if ($sessionInst !== null) {
        $stmtAno = $pdo->prepare("SELECT id_ano_letivo, ano, data_inicio, data_fim, id_instituicao FROM ano_letivo WHERE id_ano_letivo = :id AND id_instituicao = :inst LIMIT 1");
        $stmtAno->execute([':id' => $id_ano, ':inst' => $sessionInst]);
    } else {
        $stmtAno = $pdo->prepare("SELECT id_ano_letivo, ano, data_inicio, data_fim, id_instituicao FROM ano_letivo WHERE id_ano_letivo = :id LIMIT 1");
        $stmtAno->execute([':id' => $id_ano]);
    }
    $ano = $stmtAno->fetch(PDO::FETCH_ASSOC);
    if (!$ano) {
        logSecurity("Ano letivo não encontrado ou sem permissão: id_ano={$id_ano} | sess_inst=" . ($sessionInst ?? 'null'));
        abortNotFound();
    }

    // dados da instituição para cabeçalho
    $stmtInst = $pdo->prepare("SELECT nome_instituicao, imagem_instituicao FROM instituicao WHERE id_instituicao = :inst LIMIT 1");
    if ($sessionInst !== null) {
        $stmtInst->execute([':inst' => $sessionInst]);
        $inst = $stmtInst->fetch(PDO::FETCH_ASSOC) ?: [];
    } else {
        // se não há sessão, pega a primeira instituição cadastrada (comportamento anterior)
        $stmtInst = $pdo->prepare("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
        $stmtInst->execute();
        $inst = $stmtInst->fetch(PDO::FETCH_ASSOC) ?: [];
    }

} catch (Exception $e) {
    logSecurity('Erro SQL ao buscar ano/inst: ' . $e->getMessage() . ' | QS: ' . ($_SERVER['QUERY_STRING'] ?? ''));
    abortServer();
}

/* ---------------- PREPARA PDF ---------------- */
class PDF extends FPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,enc('Página '.$this->PageNo()),0,0,'L');
        $this->Cell(0,10,enc('Impresso em: '.date('d/m/Y H:i:s')),0,0,'R');
    }
}

$pdf = new PDF('P','mm','A4');
$pdf->SetTitle(enc('Relatório de Ano Letivo'));
$pdf->AddPage();

/* ---------------- CABEÇALHO ---------------- */
$LOGO_SIZE_MM = 15; $LOGO_GAP_MM = 5;
$nomeInst = $inst['nome_instituicao'] ?? '';
$imgInst  = $inst['imagem_instituicao'] ?? '';
$logoPath = $imgInst ? LOGO_PATH . '/' . basename($imgInst) : null;

$topY = 12;
$pdf->SetFont('Arial','B',14);
$text = $nomeInst !== '' ? enc($nomeInst) : '';
$textW = $text !== '' ? $pdf->GetStringWidth($text) : 0;
$totalW = ($logoPath && file_exists($logoPath) ? ($LOGO_SIZE_MM + $LOGO_GAP_MM) : 0) + $textW;
$xStart = ($pdf->GetPageWidth() - $totalW) / 2;

if ($logoPath && file_exists($logoPath)) {
    $pdf->Image($logoPath, $xStart, $topY, $LOGO_SIZE_MM, $LOGO_SIZE_MM);
}
$textX = $xStart + ($logoPath && file_exists($logoPath) ? ($LOGO_SIZE_MM + $LOGO_GAP_MM) : 0);
$pdf->SetXY($textX, $topY + 2);
if ($text !== '') $pdf->Cell($textW + 2, 8, $text, 0, 1, 'L');

$pdf->SetY($topY + $LOGO_SIZE_MM + 6);

/* ---------------- TÍTULO E RESUMO DO ANO ---------------- */
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0, 8, enc('Relatório de Ano Letivo - ' . ($ano['ano'] ?? '')), 0, 1, 'L');
$pdf->Ln(1);

$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(220,220,220);
$pdf->Cell(40,8,enc('Ano Letivo'),1,0,'C',true);
$pdf->Cell(75,8,enc('Data Início'),1,0,'C',true);
$pdf->Cell(75,8,enc('Data Fim'),1,1,'C',true);

$pdf->SetFont('Arial','',11);
$pdf->Cell(40,8,enc($ano['ano'] ?? ''),1,0,'C');
$pdf->Cell(75,8,enc(fmtDate($ano['data_inicio'] ?? '')),1,0,'C');
$pdf->Cell(75,8,enc(fmtDate($ano['data_fim'] ?? '')),1,1,'C');

/* ---------------- TURMAS (opcional) ---------------- */
if ($turma_filter === 1) {
    try {
        // JOIN com ano_letivo para garantir instituição (caso não haja sessão esta JOIN não afeta)
        $sqlTurmas = "
            SELECT n.nome_nivel_ensino AS nivel,
                   s.nome_serie AS serie,
                   GROUP_CONCAT(t.nome_turma ORDER BY t.nome_turma SEPARATOR ', ') AS turmas
            FROM turma t
            JOIN serie s ON t.id_serie = s.id_serie
            JOIN nivel_ensino n ON s.id_nivel_ensino = n.id_nivel_ensino
            JOIN ano_letivo a ON t.id_ano_letivo = a.id_ano_letivo
            WHERE t.id_ano_letivo = ?
        ";
        $paramsT = [$id_ano];
        if ($sessionInst !== null) {
            $sqlTurmas .= " AND a.id_instituicao = ?";
            $paramsT[] = $sessionInst;
        }
        $sqlTurmas .= " GROUP BY n.id_nivel_ensino, s.id_serie
                        ORDER BY n.nome_nivel_ensino, s.nome_serie";
        $stmtT = $pdo->prepare($sqlTurmas);
        $stmtT->execute($paramsT);
        $turmas = $stmtT->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        logSecurity('Erro consulta turmas: ' . $e->getMessage());
        $turmas = [];
    }

    if (!empty($turmas)) {
        $pdf->Ln(6);
        $pdf->SetFont('Arial','B',13);
        $pdf->Cell(0,8,enc('Turmas'),0,1,'L');

        $pdf->SetFont('Arial','B',11);
        $pdf->SetFillColor(200,200,200);
        $pdf->Cell(60,8,enc('Nível de Ensino'),1,0,'C',true);
        $pdf->Cell(40,8,enc('Série'),1,0,'C',true);
        $pdf->Cell(90,8,enc('Turmas'),1,1,'C',true);

        $pdf->SetFont('Arial','',10);
        foreach ($turmas as $t) {
            $pdf->Cell(60,8,enc($t['nivel'] ?? ''),1,0,'C');
            $pdf->Cell(40,8,enc($t['serie'] ?? ''),1,0,'C');
            $pdf->Cell(90,8,enc($t['turmas'] ?? ''),1,1,'C');
        }
    }
}

/* ---------------- PROFESSOR RESTRIÇÃO (opcional) ---------------- */
if ($prof_filter !== null) {
    try {
        $sqlProf = "
            SELECT pr.id_professor, p.nome_completo AS nome_professor,
                   pr.dia_semana, pr.numero_aula
            FROM professor_restricoes pr
            JOIN professor p ON pr.id_professor = p.id_professor
            JOIN ano_letivo a ON pr.id_ano_letivo = a.id_ano_letivo
            WHERE pr.id_ano_letivo = ?
        ";
        $params = [$id_ano];
        if ($prof_filter !== 'todas') {
            $sqlProf .= " AND pr.id_professor = ?";
            $params[] = $prof_filter;
        }
        if ($sessionInst !== null) {
            $sqlProf .= " AND a.id_instituicao = ?";
            $params[] = $sessionInst;
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
            $diaKey = str_replace(['ç','á','é','í','ó','ú','ã','õ','â','ê','ô',' '], ['c','a','e','i','o','u','a','o','a','e','o',''], $dia);
            if ($diaKey === '') $diaKey = $dia;
            $num = (int)($r['numero_aula'] ?? 0);
            if (!isset($professores[$idProf])) $professores[$idProf] = ['nome'=>$nome,'restricoes'=>[]];
            if (!isset($professores[$idProf]['restricoes'][$diaKey])) $professores[$idProf]['restricoes'][$diaKey] = [];
            if ($num>0) $professores[$idProf]['restricoes'][$diaKey][] = $num;
        }

        // verifica se há algo válido
        $anyValid = false;
        foreach ($professores as $p) if (!empty($p['restricoes'])) { $anyValid = true; break; }

        if ($anyValid) {
            $diasOrdem = ['Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado'];
            $diaLabel = ['Domingo'=>'Domingo','Segunda'=>'Segunda','Terca'=>'Terça','Quarta'=>'Quarta','Quinta'=>'Quinta','Sexta'=>'Sexta','Sabado'=>'Sábado'];

            $pdf->Ln(6);
            $pdf->SetFont('Arial','B',11);
            $pdf->SetFillColor(200,200,200);
            $pdf->Cell(70,8,enc('Nome Professor'),1,0,'C',true);
            $pdf->Cell(60,8,enc('Dia da Semana'),1,0,'C',true);
            $pdf->Cell(60,8,enc('Aulas'),1,1,'C',true);
            $pdf->SetFont('Arial','',10);

            foreach ($professores as $p) {
                $nomeProf = $p['nome'] ?? '';
                $diasDoProfessor = [];
                foreach ($diasOrdem as $d) {
                    $key = str_replace(['ç','á','é','í','ó','ú','ã','õ','â','ê','ô',' '], ['c','a','e','i','o','u','a','o','a','e','o',''], $d);
                    if (!empty($p['restricoes'][$key])) $diasDoProfessor[] = $key;
                }
                if (empty($diasDoProfessor)) continue;

                $rowH = 8;
                $nameHeight = $rowH * count($diasDoProfessor);
                $x = $pdf->GetX();
                $y = $pdf->GetY();

                $pdf->Cell(70, $nameHeight, enc($nomeProf), 1, 0, 'C');

                foreach ($diasDoProfessor as $i => $diaKey) {
                    $lista = array_values(array_unique(array_map('intval', $p['restricoes'][$diaKey] ?? [])));
                    sort($lista, SORT_NUMERIC);
                    $aulasFormatadas = array_map(fn($n)=>$n.'ª', $lista);
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

/* ---------------- SAÍDA ---------------- */
$pdf->Output();
exit;
?>