<?php
// app/views/disciplina-geral.php - VERSÃO SEGURA (logs/seguranca.log, whitelist, prepared stmts)
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

$PARAMS_ESPERADOS = ['nivel','profdt'];
$LOG_PATH = __DIR__ . '/../../logs/seguranca.log';

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

function enc($s){ return iconv('UTF-8','ISO-8859-1//TRANSLIT',(string)$s); }

/* ------------ WHITELIST ------------- */
$extras = array_diff(array_keys($_GET), $PARAMS_ESPERADOS);
if (!empty($extras)) {
    logSecurity('Parâmetros inesperados em disciplina-geral: ' . implode(', ', $extras));
    abortInvalid();
}

/* ------------ LER E VALIDAR PARAMS ------------- */
$nivelRaw = array_key_exists('nivel', $_GET) ? (string)$_GET['nivel'] : null;
$profRaw  = array_key_exists('profdt', $_GET) ? (string)$_GET['profdt'] : null;

$nivelId = null;
$nivelNome = null;
$profId = null;

try {
    // nivel: 'todas' or id or name
    if ($nivelRaw !== null && $nivelRaw !== '' && mb_strtolower($nivelRaw,'UTF-8') !== 'todas') {
        if (ctype_digit($nivelRaw)) {
            $stmt = $pdo->prepare("SELECT id_nivel_ensino, nome_nivel_ensino FROM nivel_ensino WHERE id_nivel_ensino = ? LIMIT 1");
            $stmt->execute([(int)$nivelRaw]);
        } else {
            $stmt = $pdo->prepare("SELECT id_nivel_ensino, nome_nivel_ensino FROM nivel_ensino WHERE nome_nivel_ensino = ? LIMIT 1");
            $stmt->execute([$nivelRaw]);
        }
        $niv = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$niv) {
            logSecurity("nivel inválido (disciplina-geral): raw={$nivelRaw}");
            abortInvalid();
        }
        $nivelId = (int)$niv['id_nivel_ensino'];
        $nivelNome = $niv['nome_nivel_ensino'];
    }

    // professor: 'todas' or numeric id
    if ($profRaw !== null && $profRaw !== '' && mb_strtolower($profRaw,'UTF-8') !== 'todas') {
        $profIdVal = filter_var($profRaw, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if ($profIdVal === false) {
            logSecurity("profdt inválido (disciplina-geral): raw={$profRaw}");
            abortInvalid();
        }
        $stmt = $pdo->prepare("SELECT id_professor FROM professor WHERE id_professor = ? LIMIT 1");
        $stmt->execute([$profIdVal]);
        if (!$stmt->fetchColumn()) {
            logSecurity("professor inexistente (disciplina-geral): id={$profIdVal}");
            abortInvalid();
        }
        $profId = $profIdVal;
    }
} catch (Exception $e) {
    logSecurity("Erro validação params (disciplina-geral): ".$e->getMessage());
    abortServer();
}

/* ------------ CONSULTE DISCIPLINAS (PREPARED) ------------- */
try {
    $params = [];
    $sql = "
        SELECT DISTINCT d.id_disciplina, d.nome_disciplina, d.sigla_disciplina
        FROM disciplina d
        JOIN serie_disciplinas sd ON sd.id_disciplina = d.id_disciplina
        JOIN serie s ON s.id_serie = sd.id_serie
    ";
    if ($profId !== null) {
        $sql .= " JOIN professor_disciplinas_turmas pdt ON pdt.id_disciplina = d.id_disciplina
                  JOIN turma t ON t.id_turma = pdt.id_turma AND t.id_serie = s.id_serie
                  JOIN professor p ON p.id_professor = pdt.id_professor ";
    }
    $sql .= " WHERE 1=1 ";
    if ($nivelId !== null) {
        $sql .= " AND s.id_nivel_ensino = ? ";
        $params[] = $nivelId;
    }
    if ($profId !== null) {
        $sql .= " AND p.id_professor = ? ";
        $params[] = $profId;
    }
    $sql .= " ORDER BY d.nome_disciplina ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $disciplinas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logSecurity("Erro SQL disciplina-geral: ".$e->getMessage());
    abortServer();
}

/* ------------ INSTITUIÇÃO PARA CABEÇALHO ------------- */
try {
    $stmtInst = $pdo->prepare("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
    $stmtInst->execute();
    $inst = $stmtInst->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    logSecurity("Erro SQL instituicao (disciplina-geral): ".$e->getMessage());
    $inst = [];
}

/* ------------ PDF HELPERS ------------- */
class PDF_DG extends FPDF {
    public function Footer(){
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,enc('Página '.$this->PageNo()),0,0,'L');
        $this->Cell(0,10,enc('Impresso em: '.date('d/m/Y H:i:s')),0,0,'R');
    }
}
function enc($s){ return iconv('UTF-8','ISO-8859-1//TRANSLIT',(string)$s); }
function headerInst(PDF_DG $pdf, $nome, $logo){
    $top=12; $pdf->SetFont('Arial','B',14); $txt=enc($nome);
    $w=$pdf->GetStringWidth($txt); $has=($logo && file_exists($logo));
    $total = $has ? (15+5+$w) : $w; $x=($pdf->GetPageWidth()-$total)/2;
    if ($has){ $pdf->Image($logo,$x,$top,15,15); $pdf->SetXY($x+20,$top+3); } else { $pdf->SetXY($x,$top); }
    $pdf->Cell($w+1,8,$txt,0,1,'L'); $pdf->SetY(max($top+15,$top+8)+6);
}

/* ------------ GERA PDF ------------- */
$pdf = new PDF_DG('P','mm','A4');
$pdf->SetTitle(enc('Relatório Geral de Disciplinas'));

$NOME_INST = $inst['nome_instituicao'] ?? '';
$LOGO_PATH = (!empty($inst['imagem_instituicao'])) ? LOGO_PATH.'/'.basename($inst['imagem_instituicao']) : null;

if (empty($disciplinas)) {
    $pdf->AddPage();
    headerInst($pdf,$NOME_INST,$LOGO_PATH);
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,8,enc('Nenhuma disciplina encontrada para o filtro aplicado.'),0,1,'L');
    $pdf->Output();
    exit;
}

foreach ($disciplinas as $disc) {
    $pdf->AddPage(); headerInst($pdf,$NOME_INST,$LOGO_PATH);
    $pdf->SetFont('Arial','B',14);
    $titulo = 'Relatório de Disciplina: '.$disc['nome_disciplina'];
    if ($nivelNome) $titulo .= ' — '.$nivelNome;
    $pdf->Cell(0,8,enc($titulo),0,1,'L'); $pdf->Ln(2);

    // quadro da disciplina
    $pdf->SetFont('Arial','B',12); $pdf->SetFillColor(220,220,220);
    $pdf->Cell(100,10,enc('Disciplina'),1,0,'C',true);
    $pdf->Cell(90,10,enc('Sigla'),1,1,'C',true);
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(100,10,enc($disc['nome_disciplina']),1,0,'C');
    $pdf->Cell(90,10,enc($disc['sigla_disciplina']),1,1,'C');

    // bloco 1: detalhes (nivel/serie/turmas)
    $paramsDet = [$disc['id_disciplina']];
    $sqlDet = "
        SELECT n.nome_nivel_ensino, s.nome_serie, t.nome_turma
        FROM serie_disciplinas sd
        JOIN serie s ON s.id_serie = sd.id_serie
        JOIN nivel_ensino n ON n.id_nivel_ensino = s.id_nivel_ensino
        LEFT JOIN turma t ON t.id_serie = s.id_serie
        WHERE sd.id_disciplina = ?
    ";
    if ($nivelId !== null) { $sqlDet .= " AND s.id_nivel_ensino = ? "; $paramsDet[] = $nivelId; }
    if ($profId !== null) {
        $sqlDet .= "
            AND EXISTS (
                SELECT 1
                FROM professor_disciplinas_turmas pdt2
                WHERE pdt2.id_disciplina = sd.id_disciplina
                  AND pdt2.id_professor  = ?
                  AND pdt2.id_turma IN (SELECT id_turma FROM turma WHERE id_serie = s.id_serie)
            )
        ";
        $paramsDet[] = $profId;
    }
    $sqlDet .= " ORDER BY n.nome_nivel_ensino, s.nome_serie, t.nome_turma";
    $stDet = $pdo->prepare($sqlDet); $stDet->execute($paramsDet);
    $rowsDet = $stDet->fetchAll(PDO::FETCH_ASSOC);

    $pdf->Ln(8); $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,8,enc('Nível de Ensino, Série e Turmas'),0,1,'L');

    if (!$rowsDet) {
        $pdf->SetFont('Arial','I',12);
        $pdf->Cell(0,8,enc('Nenhum dado encontrado.'),0,1,'L');
    } else {
        $agg=[];
        foreach ($rowsDet as $r){
            $n=$r['nome_nivel_ensino'] ?? ''; $s=$r['nome_serie'] ?? ''; $t=$r['nome_turma'] ?? '';
            $agg[$n] = $agg[$n] ?? []; $agg[$n][$s] = $agg[$n][$s] ?? [];
            if (!empty($t)) $agg[$n][$s][] = $t;
        }
        $pdf->SetFont('Arial','B',12); $pdf->SetFillColor(200,200,200);
        $pdf->Cell(60,8,enc('Nível de Ensino'),1,0,'C',true);
        $pdf->Cell(40,8,enc('Série'),1,0,'C',true);
        $pdf->Cell(90,8,enc('Turmas'),1,1,'C',true);
        $pdf->SetFont('Arial','',11);
        foreach ($agg as $n=>$series){
            foreach($series as $s=>$turmas){
                $turStr = $turmas ? implode(', ', $turmas) : '-';
                $pdf->Cell(60,8,enc($n),1,0,'C');
                $pdf->Cell(40,8,enc($s),1,0,'C');
                $pdf->Cell(90,8,enc($turStr),1,1,'C');
            }
        }
    }

    // bloco 2: professores
    $pdf->Ln(8); $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,8,enc('Professores, Nível de Ensino, Série e Turmas'),0,1,'L');

    $paramsP = [$disc['id_disciplina']];
    $sqlP = "
        SELECT p.id_professor, p.nome_completo AS nome_prof,
               n.nome_nivel_ensino, s.nome_serie, t.nome_turma
        FROM professor_disciplinas_turmas pdt
        JOIN professor p ON p.id_professor = pdt.id_professor
        JOIN turma t ON t.id_turma = pdt.id_turma
        JOIN serie s ON s.id_serie = t.id_serie
        JOIN nivel_ensino n ON n.id_nivel_ensino = s.id_nivel_ensino
        WHERE pdt.id_disciplina = ?
    ";
    if ($nivelId !== null) { $sqlP .= " AND s.id_nivel_ensino = ? "; $paramsP[] = $nivelId; }
    if ($profId !== null)  { $sqlP .= " AND p.id_professor = ? ";     $paramsP[] = $profId; }
    $sqlP .= " ORDER BY p.nome_completo, n.nome_nivel_ensino, s.nome_serie, t.nome_turma";
    $stP = $pdo->prepare($sqlP); $stP->execute($paramsP);
    $rowsP = $stP->fetchAll(PDO::FETCH_ASSOC);

    if (!$rowsP) {
        $pdf->SetFont('Arial','I',12);
        $pdf->Cell(0,8,enc('Nenhum dado encontrado.'),0,1,'L');
    } else {
        $pdf->SetFont('Arial','B',12); $pdf->SetFillColor(200,200,200);
        $pdf->Cell(60,10,enc('Nome do Professor'),1,0,'C',true);
        $pdf->Cell(50,10,enc('Nível de Ensino'),1,0,'C',true);
        $pdf->Cell(30,10,enc('Série'),1,0,'C',true);
        $pdf->Cell(50,10,enc('Turmas'),1,1,'C',true);
        $pdf->SetFont('Arial','',12);
        $agg=[];
        foreach ($rowsP as $r){
            $pid=(int)($r['id_professor'] ?? 0);
            $agg[$pid] = $agg[$pid] ?? ['nome'=>$r['nome_prof'],'niveis'=>[]];
            $n = $r['nome_nivel_ensino'] ?? ''; $s = $r['nome_serie'] ?? ''; $t = $r['nome_turma'] ?? '';
            $agg[$pid]['niveis'][$n] = $agg[$pid]['niveis'][$n] ?? [];
            $agg[$pid]['niveis'][$n][$s] = $agg[$pid]['niveis'][$n][$s] ?? [];
            if ($t !== null) $agg[$pid]['niveis'][$n][$s][] = $t;
        }
        foreach ($agg as $p){
            $first=true;
            foreach ($p['niveis'] as $n=>$series){
                foreach ($series as $s=>$turmas){
                    $turStr = $turmas ? implode(', ',$turmas) : '-';
                    if ($first){ $pdf->Cell(60,10,enc($p['nome']),1,0,'C'); $first=false; }
                    else { $pdf->Cell(60,10,'',1,0,'C'); }
                    $pdf->Cell(50,10,enc($n),1,0,'C');
                    $pdf->Cell(30,10,enc($s),1,0,'C');
                    $pdf->Cell(50,10,enc($turStr),1,1,'C');
                }
            }
            $pdf->Ln(2);
        }
    }
}

$pdf->Output();
exit;
?>
