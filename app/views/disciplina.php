<?php
// app/views/disciplina.php - AJUSTADO (sem turmas duplicadas + professores na mesma folha)
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

$PARAMS_ESPERADOS = ['id_disc','nivel','profdt'];
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
function abortNotFound(): void{ http_response_code(404); die('Parâmetros inválidos'); }

function enc($s){ return iconv('UTF-8','ISO-8859-1//TRANSLIT',(string)$s); }

/* -------------- WHITELIST --------------- */
$extras = array_diff(array_keys($_GET), $PARAMS_ESPERADOS);
if (!empty($extras)) {
    logSecurity('Parâmetros inesperados em disciplina: '.implode(', ',$extras));
    abortInvalid();
}

/* -------------- LER E VALIDAR ------------- */
$id_disc = filter_input(INPUT_GET,'id_disc',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if (!$id_disc) {
    logSecurity('id_disc inválido/ausente (disciplina)');
    abortInvalid();
}

$nivelRaw = array_key_exists('nivel', $_GET) ? (string)$_GET['nivel'] : null;
$profRaw  = array_key_exists('profdt', $_GET) ? (string)$_GET['profdt'] : null;

$nivel = null;   // null | 'todas' | numeric id | string nome
$profdt = null;  // null | 'todas' | numeric id

if ($nivelRaw !== null && $nivelRaw !== '') {
    if (mb_strtolower($nivelRaw,'UTF-8') === 'todas') {
        $nivel = 'todas';
    } elseif (ctype_digit($nivelRaw)) {
        $nivel = (int)$nivelRaw;
    } else {
        $nivel = trim(filter_var($nivelRaw, FILTER_SANITIZE_SPECIAL_CHARS));
        if ($nivel === '') { logSecurity("nivel inválido (disciplina): raw={$nivelRaw}"); abortInvalid(); }
    }
}

if ($profRaw !== null && $profRaw !== '') {
    if (mb_strtolower($profRaw,'UTF-8') === 'todas') {
        $profdt = 'todas';
    } else {
        $profId = filter_var($profRaw, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if ($profId === false) { logSecurity("profdt inválido (disciplina): raw={$profRaw}"); abortInvalid(); }
        try {
            $st = $pdo->prepare("SELECT 1 FROM professor WHERE id_professor = ? LIMIT 1");
            $st->execute([$profId]);
            if (!$st->fetchColumn()) { logSecurity("professor inexistente (disciplina): id={$profId}"); abortInvalid(); }
        } catch (Exception $e) { logSecurity("Erro SQL valida professor (disciplina): ".$e->getMessage()); abortServer(); }
        $profdt = $profId;
    }
}

/* -------------- valida combinação nivel x prof (se ambos presentes e não 'todas') ------------- */
if ($nivel !== null && $nivel !== 'todas' && $profdt !== null && $profdt !== 'todas') {
    try {
        $params = [$profdt];
        if (is_int($nivel)) {
            $sqlCheck = "
                SELECT 1 FROM professor_disciplinas_turmas pdt
                JOIN turma t ON t.id_turma = pdt.id_turma
                JOIN serie s ON s.id_serie = t.id_serie
                WHERE pdt.id_professor = ? AND s.id_nivel_ensino = ? LIMIT 1
            ";
            $params[] = $nivel;
        } else {
            $sqlCheck = "
                SELECT 1 FROM professor_disciplinas_turmas pdt
                JOIN turma t ON t.id_turma = pdt.id_turma
                JOIN serie s ON s.id_serie = t.id_serie
                JOIN nivel_ensino n ON n.id_nivel_ensino = s.id_nivel_ensino
                WHERE pdt.id_professor = ? AND n.nome_nivel_ensino = ? LIMIT 1
            ";
            $params[] = $nivel;
        }
        $st = $pdo->prepare($sqlCheck);
        $st->execute($params);
        if (!$st->fetchColumn()) {
            logSecurity("Combinação professor x nível inválida (disciplina): prof={$profdt} nivel={$nivelRaw}");
            $nivel = null;
            $profdt = null;
        }
    } catch (Exception $e) {
        logSecurity("Erro valida combinação prof x nivel (disciplina): ".$e->getMessage());
        abortServer();
    }
}

/* -------------- CONSULTAS E CHECAGEM DA DISCIPLINA -------------- */
try {
    $stmtInst = $pdo->prepare("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
    $stmtInst->execute();
    $inst = $stmtInst->fetch(PDO::FETCH_ASSOC) ?: [];

    $stmtCheck = $pdo->prepare("SELECT nome_disciplina, sigla_disciplina FROM disciplina WHERE id_disciplina = ? LIMIT 1");
    $stmtCheck->execute([$id_disc]);
    $disc = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    if (!$disc) {
        logSecurity("Disciplina não encontrada: id_disc={$id_disc}");
        abortNotFound();
    }
} catch (Exception $e) {
    logSecurity("Erro SQL disciplina: ".$e->getMessage());
    abortServer();
}

/* -------------- PDF HELPERS -------------- */
class PDF_D extends FPDF {
    public function Footer(){
        $this->SetY(-15); $this->SetFont('Arial','I',8);
        $this->Cell(0,10,enc('Página '.$this->PageNo()),0,0,'L');
        $this->Cell(0,10,enc('Impresso em: '.date('d/m/Y H:i:s')),0,0,'R');
    }
}
function headerRender(PDF_D $pdf, string $nome, ?string $logo, float $size=15, float $gap=5){
    $topY=12; $pdf->SetFont('Arial','B',14);
    $txt=enc($nome); $txtW=$pdf->GetStringWidth($txt); $hasLogo = ($logo && file_exists($logo));
    $totalW = $hasLogo ? ($size+$gap+$txtW) : $txtW; $xStart = ($pdf->GetPageWidth()-$totalW)/2;
    if ($hasLogo){ $pdf->Image($logo,$xStart,$topY,$size,$size); $pdf->SetXY($xStart+$size+$gap,$topY+3); } else { $pdf->SetXY($xStart,$topY); }
    if ($txt !== '') $pdf->Cell($txtW+1,8,$txt,0,1,'L');
    $pdf->SetY(max($topY+($hasLogo?$size:0),$topY+8)+6);
}
function ensureSpace(PDF_D $pdf, float $neededMm): bool {
    $bottom = 18; // margem do rodapé
    $limit = $pdf->GetPageHeight() - $bottom;
    return ($pdf->GetY() + $neededMm) <= $limit;
}

/* -------------- GERA PDF -------------- */
$pdf = new PDF_D('P','mm','A4');
$pdf->SetTitle(enc('Relatório de Disciplina'));

$nomeInstituicao = $inst['nome_instituicao'] ?? '';
$logoPath = !empty($inst['imagem_instituicao']) ? LOGO_PATH.'/'.basename($inst['imagem_instituicao']) : null;

$pdf->AddPage();
headerRender($pdf, $nomeInstituicao, $logoPath);

// título único (sem duplicar com "Relatório de Disciplina: X")
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,8,enc('Relatório de Disciplina'),0,1,'L'); 
$pdf->Ln(2);

// bloco disciplina
$pdf->SetFont('Arial','B',12); $pdf->SetFillColor(220,220,220);
$pdf->Cell(100,10,enc('Disciplina'),1,0,'C',true);
$pdf->Cell(90,10,enc('Sigla'),1,1,'C',true);

$pdf->SetFont('Arial','',12);
$pdf->Cell(100,10,enc($disc['nome_disciplina']),1,0,'C');
$pdf->Cell(90,10,enc($disc['sigla_disciplina']),1,1,'C');

/* ----------- NÍVEL/SÉRIE/TURMAS (opcional) ----------- */
if ($nivel !== null) {
    try {
        $params = [$id_disc];
        // DISTINCT para não duplicar turma/linha
        $sql = "
            SELECT DISTINCT n.nome_nivel_ensino, s.nome_serie, t.nome_turma
            FROM serie_disciplinas sd
            JOIN serie s ON s.id_serie = sd.id_serie
            JOIN nivel_ensino n ON n.id_nivel_ensino = s.id_nivel_ensino
            LEFT JOIN turma t ON t.id_serie = s.id_serie
            WHERE sd.id_disciplina = ?
        ";
        if ($nivel !== 'todas') {
            if (is_int($nivel)) { $sql .= " AND s.id_nivel_ensino = ? "; $params[] = $nivel; }
            else { $sql .= " AND n.nome_nivel_ensino = ? "; $params[] = $nivel; }
        }
        $sql .= " ORDER BY n.nome_nivel_ensino, s.nome_serie, t.nome_turma";
        $st = $pdo->prepare($sql); $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        logSecurity("Erro SQL niveis (disciplina): ".$e->getMessage());
        $rows = [];
    }

    $pdf->Ln(8); $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,8,enc('Nível de Ensino, Série e Turmas'),0,1,'L');

    if (empty($rows)) {
        $pdf->SetFont('Arial','I',12);
        $pdf->Cell(0,8,enc('Nenhum dado encontrado.'),0,1,'L');
    } else {
        $agg = [];
        foreach ($rows as $r) {
            $n = $r['nome_nivel_ensino'] ?? '';
            $s = $r['nome_serie'] ?? '';
            $t = $r['nome_turma'] ?? '';
            $agg[$n] = $agg[$n] ?? [];
            $agg[$n][$s] = $agg[$n][$s] ?? [];

            if ($t !== '' && $t !== null) $agg[$n][$s][] = $t;
        }
        // dedup turmas no PHP (segurança extra)
        foreach ($agg as $n => $series) {
            foreach ($series as $s => $turmas) {
                $agg[$n][$s] = array_values(array_unique($turmas));
                sort($agg[$n][$s], SORT_NATURAL);
            }
        }

        $pdf->SetFont('Arial','B',12); $pdf->SetFillColor(200,200,200);
        $pdf->Cell(60,8,enc('Nível de Ensino'),1,0,'C',true);
        $pdf->Cell(40,8,enc('Série'),1,0,'C',true);
        $pdf->Cell(90,8,enc('Turmas'),1,1,'C',true);
        $pdf->SetFont('Arial','',11);

        foreach ($agg as $n=>$series) {
            foreach ($series as $s=>$turmas) {
                $turStr = !empty($turmas) ? implode(', ', $turmas) : '-';
                $pdf->Cell(60,8,enc($n),1,0,'C');
                $pdf->Cell(40,8,enc($s),1,0,'C');
                $pdf->Cell(90,8,enc($turStr),1,1,'C');
            }
        }
    }
}

/* ----------- PROFESSORES (opcional) ----------- */
if ($profdt !== null) {
    try {
        $params = [$id_disc];
        $sql = "
            SELECT DISTINCT
                   p.id_professor,
                   p.nome_completo AS nome_prof,
                   n.nome_nivel_ensino,
                   s.nome_serie,
                   t.nome_turma
            FROM professor_disciplinas_turmas pdt
            JOIN professor p ON p.id_professor = pdt.id_professor
            JOIN turma t ON t.id_turma = pdt.id_turma
            JOIN serie s ON s.id_serie = t.id_serie
            JOIN nivel_ensino n ON n.id_nivel_ensino = s.id_nivel_ensino
            WHERE pdt.id_disciplina = ?
        ";
        if ($profdt !== 'todas') { $sql .= " AND pdt.id_professor = ?"; $params[] = (int)$profdt; }
        if ($nivel !== null && $nivel !== 'todas') {
            if (is_int($nivel)) { $sql .= " AND s.id_nivel_ensino = ?"; $params[] = $nivel; }
            else { $sql .= " AND n.nome_nivel_ensino = ?"; $params[] = $nivel; }
        }
        $sql .= " ORDER BY p.nome_completo, n.nome_nivel_ensino, s.nome_serie, t.nome_turma";
        $st = $pdo->prepare($sql); $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        logSecurity("Erro SQL professores (disciplina): ".$e->getMessage());
        $rows = [];
    }

    $pdf->Ln(8);
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,8,enc('Professores, Nível de Ensino, Série e Turmas'),0,1,'L');
    $pdf->Ln(2);

    if (empty($rows)) {
        $pdf->SetFont('Arial','I',12);
        $pdf->Cell(0,8,enc('Nenhum professor encontrado.'),0,1,'L');
    } else {
        // agrega
        $agg=[];
        foreach ($rows as $r) {
            $pid=(int)($r['id_professor'] ?? 0);
            if ($pid <= 0) continue;

            $agg[$pid] = $agg[$pid] ?? ['nome'=>$r['nome_prof'] ?? '', 'niveis'=>[]];
            $n=$r['nome_nivel_ensino'] ?? '';
            $s=$r['nome_serie'] ?? '';
            $t=$r['nome_turma'] ?? '';

            $agg[$pid]['niveis'][$n] = $agg[$pid]['niveis'][$n] ?? [];
            $agg[$pid]['niveis'][$n][$s] = $agg[$pid]['niveis'][$n][$s] ?? [];
            if ($t !== null && $t !== '') $agg[$pid]['niveis'][$n][$s][] = $t;
        }
        // dedup e sort
        foreach ($agg as $pid => &$p) {
            foreach ($p['niveis'] as $n => &$series) {
                foreach ($series as $s => &$turmas) {
                    $turmas = array_values(array_unique($turmas));
                    sort($turmas, SORT_NATURAL);
                }
                unset($turmas);
            }
            unset($series);
        }
        unset($p);

        // tenta manter na mesma folha: se estiver apertado, reduz um pouco fonte/altura
        $rowH = 8;
        $fontBody = 11;

        // estimativa de linhas
        $lines = 1; // header
        foreach ($agg as $p) {
            foreach ($p['niveis'] as $n=>$series) {
                foreach ($series as $s=>$turmas) $lines++;
            }
            $lines++; // espacinho
        }
        $needed = ($lines * $rowH) + 10;

        if (!ensureSpace($pdf, $needed)) {
            // comprime para tentar caber
            $rowH = 6.5;
            $fontBody = 9.5;
        }

        // se ainda assim não couber, não força AddPage (mantém seu pedido); apenas imprime até onde der.
        $pdf->SetFont('Arial','B',12); $pdf->SetFillColor(200,200,200);
        $pdf->Cell(60,8,enc('Nome do Professor'),1,0,'C',true);
        $pdf->Cell(50,8,enc('Nível de Ensino'),1,0,'C',true);
        $pdf->Cell(30,8,enc('Série'),1,0,'C',true);
        $pdf->Cell(50,8,enc('Turmas'),1,1,'C',true);

        $pdf->SetFont('Arial','', $fontBody);

        foreach ($agg as $p) {
            $first=true;
            foreach ($p['niveis'] as $n=>$series) {
                foreach ($series as $s=>$turmas) {
                    $turStr = $turmas ? implode(', ',$turmas) : '-';

                    if (!ensureSpace($pdf, $rowH + 2)) {
                        // não quebra página (pedido do usuário). Para não ficar “cortado”, para aqui.
                        $pdf->SetFont('Arial','I',10);
                        $pdf->Cell(0,8,enc('(*) Conteúdo excede uma página para este filtro.'),0,1,'L');
                        break 3;
                    }

                    if ($first){ $pdf->Cell(60,$rowH,enc($p['nome']),1,0,'C'); $first=false; }
                    else { $pdf->Cell(60,$rowH,'',1,0,'C'); }
                    $pdf->Cell(50,$rowH,enc($n),1,0,'C');
                    $pdf->Cell(30,$rowH,enc($s),1,0,'C');
                    $pdf->Cell(50,$rowH,enc($turStr),1,1,'C');
                }
            }
            $pdf->Ln(2);
        }
    }
}

$pdf->Output();
exit;
?>
