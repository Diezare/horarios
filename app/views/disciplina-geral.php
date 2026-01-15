<?php
// app/views/disciplina-geral.php

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

/* ---------------- WHITELIST ---------------- */
$extras = array_diff(array_keys($_GET), $PARAMS_ESPERADOS);
if (!empty($extras)) {
    logSecurity('Parâmetros inesperados em disciplina-geral: '.implode(', ',$extras));
    abortInvalid();
}

/* ---------------- LER / VALIDAR PARAMS (mesma ideia do disciplina.php) ---------------- */
$nivelRaw = array_key_exists('nivel', $_GET) ? (string)$_GET['nivel'] : null;
$profRaw  = array_key_exists('profdt', $_GET) ? (string)$_GET['profdt'] : null;

$nivel = null;   // null | 'todas' | int (id_nivel) | string (nome)
$profdt = null;  // null | 'todas' | int (id_prof)

if ($nivelRaw !== null && $nivelRaw !== '') {
    if (mb_strtolower($nivelRaw,'UTF-8') === 'todas') {
        $nivel = 'todas';
    } elseif (ctype_digit($nivelRaw)) {
        $nivel = (int)$nivelRaw;
    } else {
        $nivel = trim(filter_var($nivelRaw, FILTER_SANITIZE_SPECIAL_CHARS));
        if ($nivel === '') { logSecurity("nivel inválido (disciplina-geral): raw={$nivelRaw}"); abortInvalid(); }
    }
}

if ($profRaw !== null && $profRaw !== '') {
    if (mb_strtolower($profRaw,'UTF-8') === 'todas') {
        $profdt = 'todas';
    } else {
        $profId = filter_var($profRaw, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if ($profId === false) { logSecurity("profdt inválido (disciplina-geral): raw={$profRaw}"); abortInvalid(); }
        try {
            $st = $pdo->prepare("SELECT 1 FROM professor WHERE id_professor = ? LIMIT 1");
            $st->execute([$profId]);
            if (!$st->fetchColumn()) { logSecurity("professor inexistente (disciplina-geral): id={$profId}"); abortInvalid(); }
        } catch (Exception $e) {
            logSecurity("Erro SQL valida professor (disciplina-geral): ".$e->getMessage());
            abortServer();
        }
        $profdt = $profId;
    }
}

/* valida combinação nivel x prof (igual disciplina.php: se conflitante, zera ambos por segurança) */
if ($nivel !== null && $nivel !== 'todas' && $profdt !== null && $profdt !== 'todas') {
    try {
        $params = [$profdt];
        if (is_int($nivel)) {
            $sqlCheck = "
                SELECT 1
                FROM professor_disciplinas_turmas pdt
                JOIN turma t ON t.id_turma = pdt.id_turma
                JOIN serie s ON s.id_serie = t.id_serie
                WHERE pdt.id_professor = ? AND s.id_nivel_ensino = ? LIMIT 1
            ";
            $params[] = $nivel;
        } else {
            $sqlCheck = "
                SELECT 1
                FROM professor_disciplinas_turmas pdt
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
            logSecurity("Combinação professor x nível inválida (disciplina-geral): prof={$profdt} nivel={$nivelRaw}");
            $nivel = null;
            $profdt = null;
        }
    } catch (Exception $e) {
        logSecurity("Erro valida combinação prof x nivel (disciplina-geral): ".$e->getMessage());
        abortServer();
    }
}

/* ---------------- INSTITUIÇÃO (para cabeçalho) ---------------- */
try {
    $stmtInst = $pdo->prepare("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
    $stmtInst->execute();
    $inst = $stmtInst->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    logSecurity("Erro SQL instituicao (disciplina-geral): ".$e->getMessage());
    $inst = [];
}

/* ---------------- LISTA DE DISCIPLINAS (com filtros opcionais) ---------------- */
try {
    $params = [];
    $sql = "
        SELECT DISTINCT d.id_disciplina, d.nome_disciplina, d.sigla_disciplina
        FROM disciplina d
        JOIN serie_disciplinas sd ON sd.id_disciplina = d.id_disciplina
        JOIN serie s ON s.id_serie = sd.id_serie
        JOIN nivel_ensino n ON n.id_nivel_ensino = s.id_nivel_ensino
    ";

    if ($profdt !== null) {
        $sql .= "
            JOIN professor_disciplinas_turmas pdt ON pdt.id_disciplina = d.id_disciplina
            JOIN turma t ON t.id_turma = pdt.id_turma AND t.id_serie = s.id_serie
        ";
    }

    $sql .= " WHERE 1=1 ";

    if ($nivel !== null && $nivel !== 'todas') {
        if (is_int($nivel)) { $sql .= " AND s.id_nivel_ensino = ? "; $params[] = $nivel; }
        else { $sql .= " AND n.nome_nivel_ensino = ? "; $params[] = $nivel; }
    }

    if ($profdt !== null && $profdt !== 'todas') {
        $sql .= " AND pdt.id_professor = ? ";
        $params[] = (int)$profdt;
    }

    $sql .= " ORDER BY d.nome_disciplina ";

    $st = $pdo->prepare($sql);
    $st->execute($params);
    $disciplinas = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logSecurity("Erro SQL disciplinas (disciplina-geral): ".$e->getMessage());
    abortServer();
}

/* ---------------- PDF HELPERS ---------------- */
class PDF_DG extends FPDF {
    public function Footer(){
        $this->SetY(-15); $this->SetFont('Arial','I',8);
        $this->Cell(0,10,enc('Página '.$this->PageNo()),0,0,'L');
        $this->Cell(0,10,enc('Impresso em: '.date('d/m/Y H:i:s')),0,0,'R');
    }
}

/* Cabeçalho PADRÃO (IGUAL ao modalidade-geral.php) */
function headerPadrao(PDF_DG $pdf, array $inst, string $tituloRelatorio): void {
    $LOGO_SIZE_MM = 15;
    $LOGO_GAP_MM  = 5;

    $nomeInst = $inst['nome_instituicao'] ?? '';
    $logoPath = (!empty($inst['imagem_instituicao']) && defined('LOGO_PATH'))
        ? (LOGO_PATH . '/' . basename($inst['imagem_instituicao']))
        : null;

    $pdf->SetY(12);
    $pdf->SetFont('Arial','B',14);

    $txt = $nomeInst !== '' ? enc($nomeInst) : '';
    $txtW = $txt !== '' ? $pdf->GetStringWidth($txt) : 0;
    $hasLogo = ($logoPath && file_exists($logoPath));

    $totalW = $hasLogo
        ? ($LOGO_SIZE_MM + ($txt ? $LOGO_GAP_MM : 0) + $txtW)
        : $txtW;

    $x = ($pdf->GetPageWidth() - $totalW) / 2;
    $y = $pdf->GetY();

    if ($hasLogo) {
        $pdf->Image($logoPath, $x, $y - 2, $LOGO_SIZE_MM, $LOGO_SIZE_MM);
        $x += $LOGO_SIZE_MM + ($txt ? $LOGO_GAP_MM : 0);
    }

    if ($txt !== '') {
        $pdf->SetXY($x, $y);
        $pdf->Cell($txtW, $LOGO_SIZE_MM, $txt, 0, 1, 'L');
    }

    $pdf->Ln(3);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,7,enc($tituloRelatorio),0,1,'L');
    $pdf->Ln(1);
}

/* Page-break simples (se estourar, cria outra página e mantém cabeçalho padrão) */
function ensureSpace(PDF_DG $pdf, array $inst, float $neededMm, string $tituloRelatorio): void {
    $bottom = 280;
    if ($pdf->GetY() + $neededMm > $bottom) {
        $pdf->AddPage();
        headerPadrao($pdf, $inst, $tituloRelatorio);
    }
}

/* ---------------- GERA PDF ---------------- */
$pdf = new PDF_DG('P','mm','A4');
$pdf->SetTitle(enc('Relatório Geral de Disciplinas'));

if (empty($disciplinas)) {
    $pdf->AddPage();
    headerPadrao($pdf, $inst, 'Relatório Geral de Disciplinas');
    $pdf->SetFont('Arial','I',12);
    $pdf->Cell(0,8,enc('Nenhuma disciplina encontrada para o filtro aplicado.'),0,1,'L');
    $pdf->Output();
    exit;
}

/* 1 disciplina por página (como está hoje) */
foreach ($disciplinas as $disc) {

    $pdf->AddPage();
    headerPadrao($pdf, $inst, 'Relatório de Disciplina');

    /* ----- BLOCO: Disciplina + Sigla ----- */
    ensureSpace($pdf, $inst, 35, 'Relatório de Disciplina');
    $pdf->SetFont('Arial','B',12); $pdf->SetFillColor(220,220,220);
    $pdf->Cell(100,10,enc('Disciplina'),1,0,'C',true);
    $pdf->Cell(90,10,enc('Sigla'),1,1,'C',true);

    $pdf->SetFont('Arial','',12);
    $pdf->Cell(100,10,enc($disc['nome_disciplina']),1,0,'C');
    $pdf->Cell(90,10,enc($disc['sigla_disciplina']),1,1,'C');

    /* ----- BLOCO: Nível/Série/Turmas (opcional) + ANO LETIVO + DEDUPE ----- */
    if ($nivel !== null) {
        try {
            $paramsDet = [$disc['id_disciplina']];

            // Se houver filtro de professor, puxa APENAS as turmas vinculadas ao professor (evita duplicar por séries sem turma)
            if ($profdt !== null && $profdt !== 'todas') {
                $sqlDet = "
                    SELECT
                        n.nome_nivel_ensino,
                        s.nome_serie,
                        a.ano AS ano_letivo,
                        t.nome_turma
                    FROM serie_disciplinas sd
                    JOIN serie s ON s.id_serie = sd.id_serie
                    JOIN nivel_ensino n ON n.id_nivel_ensino = s.id_nivel_ensino
                    JOIN professor_disciplinas_turmas pdt2
                        ON pdt2.id_disciplina = sd.id_disciplina
                       AND pdt2.id_professor  = ?
                    JOIN turma t ON t.id_turma = pdt2.id_turma
                    LEFT JOIN ano_letivo a ON a.id_ano_letivo = t.id_ano_letivo
                    WHERE sd.id_disciplina = ?
                ";
                // ordem dos params: prof, disc
                $paramsDet = [(int)$profdt, (int)$disc['id_disciplina']];

                if ($nivel !== 'todas') {
                    if (is_int($nivel)) { $sqlDet .= " AND s.id_nivel_ensino = ? "; $paramsDet[] = $nivel; }
                    else { $sqlDet .= " AND n.nome_nivel_ensino = ? "; $paramsDet[] = $nivel; }
                }

                $sqlDet .= " ORDER BY a.ano, n.nome_nivel_ensino, s.nome_serie, t.nome_turma";
            } else {
                $sqlDet = "
                    SELECT
                        n.nome_nivel_ensino,
                        s.nome_serie,
                        a.ano AS ano_letivo,
                        t.nome_turma
                    FROM serie_disciplinas sd
                    JOIN serie s ON s.id_serie = sd.id_serie
                    JOIN nivel_ensino n ON n.id_nivel_ensino = s.id_nivel_ensino
                    LEFT JOIN turma t ON t.id_serie = s.id_serie
                    LEFT JOIN ano_letivo a ON a.id_ano_letivo = t.id_ano_letivo
                    WHERE sd.id_disciplina = ?
                ";

                if ($nivel !== 'todas') {
                    if (is_int($nivel)) { $sqlDet .= " AND s.id_nivel_ensino = ? "; $paramsDet[] = $nivel; }
                    else { $sqlDet .= " AND n.nome_nivel_ensino = ? "; $paramsDet[] = $nivel; }
                }

                $sqlDet .= " ORDER BY a.ano, n.nome_nivel_ensino, s.nome_serie, t.nome_turma";
            }

            $stDet = $pdo->prepare($sqlDet);
            $stDet->execute($paramsDet);
            $rowsDet = $stDet->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            logSecurity("Erro SQL niveis/turmas (disciplina-geral): ".$e->getMessage());
            $rowsDet = [];
        }

        ensureSpace($pdf, $inst, 20, 'Relatório de Disciplina');
        $pdf->Ln(8);
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(0,8,enc('Nível de Ensino, Série, Ano Letivo e Turmas'),0,1,'L');

        if (empty($rowsDet)) {
            $pdf->SetFont('Arial','I',12);
            $pdf->Cell(0,8,enc('Nenhum dado encontrado.'),0,1,'L');
        } else {
            // agg[nivel][ano][serie] => set(turma)
            $agg = [];
            foreach ($rowsDet as $r) {
                $n = $r['nome_nivel_ensino'] ?? '';
                $a = $r['ano_letivo'] ?? '';
                $s = $r['nome_serie'] ?? '';
                $t = $r['nome_turma'] ?? '';

                $keyAno = ($a !== null && $a !== '') ? (string)$a : '-';

                $agg[$n] = $agg[$n] ?? [];
                $agg[$n][$keyAno] = $agg[$n][$keyAno] ?? [];
                $agg[$n][$keyAno][$s] = $agg[$n][$keyAno][$s] ?? [];

                // DEDUPE: usa set por nome da turma
                if ($t !== null && $t !== '') {
                    $agg[$n][$keyAno][$s][$t] = true;
                }
            }

            ensureSpace($pdf, $inst, 30, 'Relatório de Disciplina');
            $pdf->SetFont('Arial','B',12);
            $pdf->SetFillColor(200,200,200);
            $pdf->Cell(50,8,enc('Nível de Ensino'),1,0,'C',true);
            $pdf->Cell(25,8,enc('Ano'),1,0,'C',true);
            $pdf->Cell(35,8,enc('Série'),1,0,'C',true);
            $pdf->Cell(80,8,enc('Turmas'),1,1,'C',true);

            $pdf->SetFont('Arial','',11);
            foreach ($agg as $nivelNome => $anos) {
                foreach ($anos as $anoLetivo => $series) {
                    foreach ($series as $serieNome => $setTurmas) {
                        ensureSpace($pdf, $inst, 10, 'Relatório de Disciplina');

                        $listaTurmas = array_keys($setTurmas);
                        sort($listaTurmas, SORT_NATURAL);

                        $turStr = !empty($listaTurmas) ? implode(', ', $listaTurmas) : '-';

                        $pdf->Cell(50,8,enc($nivelNome),1,0,'C');
                        $pdf->Cell(25,8,enc($anoLetivo),1,0,'C');
                        $pdf->Cell(35,8,enc($serieNome),1,0,'C');
                        $pdf->Cell(80,8,enc($turStr),1,1,'C');
                    }
                }
            }
        }
    }

    /* ----- BLOCO: Professores (opcional) - NA MESMA FOLHA + ANO LETIVO + DEDUPE ----- */
    if ($profdt !== null) {
        try {
            $paramsP = [$disc['id_disciplina']];
            $sqlP = "
                SELECT
                    p.id_professor,
                    p.nome_completo AS nome_prof,
                    a.ano AS ano_letivo,
                    n.nome_nivel_ensino,
                    s.nome_serie,
                    t.nome_turma
                FROM professor_disciplinas_turmas pdt
                JOIN professor p ON p.id_professor = pdt.id_professor
                JOIN turma t ON t.id_turma = pdt.id_turma
                LEFT JOIN ano_letivo a ON a.id_ano_letivo = t.id_ano_letivo
                JOIN serie s ON s.id_serie = t.id_serie
                JOIN nivel_ensino n ON n.id_nivel_ensino = s.id_nivel_ensino
                WHERE pdt.id_disciplina = ?
            ";

            if ($profdt !== 'todas') { $sqlP .= " AND pdt.id_professor = ?"; $paramsP[] = (int)$profdt; }
            if ($nivel !== null && $nivel !== 'todas') {
                if (is_int($nivel)) { $sqlP .= " AND s.id_nivel_ensino = ?"; $paramsP[] = $nivel; }
                else { $sqlP .= " AND n.nome_nivel_ensino = ?"; $paramsP[] = $nivel; }
            }

            $sqlP .= " ORDER BY p.nome_completo, a.ano, n.nome_nivel_ensino, s.nome_serie, t.nome_turma";

            $stP = $pdo->prepare($sqlP);
            $stP->execute($paramsP);
            $rowsP = $stP->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            logSecurity("Erro SQL professores (disciplina-geral): ".$e->getMessage());
            $rowsP = [];
        }

        ensureSpace($pdf, $inst, 22, 'Relatório de Disciplina');
        $pdf->Ln(8);
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(0,8,enc('Professores, Ano Letivo, Nível de Ensino, Série e Turmas'),0,1,'L');
        $pdf->Ln(2);

        if (empty($rowsP)) {
            $pdf->SetFont('Arial','I',12);
            $pdf->Cell(0,8,enc('Nenhum professor encontrado.'),0,1,'L');
        } else {
            $pdf->SetFont('Arial','B',12);
            $pdf->SetFillColor(200,200,200);

            // Colunas com Ano Letivo
            $pdf->Cell(55,8,enc('Nome do Professor'),1,0,'C',true);
            $pdf->Cell(20,8,enc('Ano'),1,0,'C',true);
            $pdf->Cell(45,8,enc('Nível'),1,0,'C',true);
            $pdf->Cell(25,8,enc('Série'),1,0,'C',true);
            $pdf->Cell(45,8,enc('Turmas'),1,1,'C',true);

            $pdf->SetFont('Arial','',11);

            // agg[prof][ano][nivel][serie] => set(turma)
            $agg = [];
            foreach ($rowsP as $r) {
                $pid = (int)($r['id_professor'] ?? 0);
                if ($pid <= 0) continue;

                $nome = $r['nome_prof'] ?? '';
                $anoL = $r['ano_letivo'] ?? '';
                $anoKey = ($anoL !== null && $anoL !== '') ? (string)$anoL : '-';

                $niv = $r['nome_nivel_ensino'] ?? '';
                $ser = $r['nome_serie'] ?? '';
                $tur = $r['nome_turma'] ?? '';

                $agg[$pid] = $agg[$pid] ?? ['nome'=>$nome,'anos'=>[]];
                $agg[$pid]['anos'][$anoKey] = $agg[$pid]['anos'][$anoKey] ?? [];
                $agg[$pid]['anos'][$anoKey][$niv] = $agg[$pid]['anos'][$anoKey][$niv] ?? [];
                $agg[$pid]['anos'][$anoKey][$niv][$ser] = $agg[$pid]['anos'][$anoKey][$niv][$ser] ?? [];

                // DEDUPE por nome da turma
                if ($tur !== null && $tur !== '') {
                    $agg[$pid]['anos'][$anoKey][$niv][$ser][$tur] = true;
                }
            }

            foreach ($agg as $p) {
                $firstRowForProfessor = true;

                foreach ($p['anos'] as $anoKey => $niveis) {
                    foreach ($niveis as $niv => $series) {
                        foreach ($series as $ser => $setTurmas) {

                            ensureSpace($pdf, $inst, 10, 'Relatório de Disciplina');

                            $listaTurmas = array_keys($setTurmas);
                            sort($listaTurmas, SORT_NATURAL);
                            $turStr = !empty($listaTurmas) ? implode(', ', $listaTurmas) : '-';

                            if ($firstRowForProfessor) {
                                $pdf->Cell(55,8,enc($p['nome']),1,0,'C');
                                $firstRowForProfessor = false;
                            } else {
                                $pdf->Cell(55,8,'',1,0,'C');
                            }

                            $pdf->Cell(20,8,enc($anoKey),1,0,'C');
                            $pdf->Cell(45,8,enc($niv),1,0,'C');
                            $pdf->Cell(25,8,enc($ser),1,0,'C');
                            $pdf->Cell(45,8,enc($turStr),1,1,'C');
                        }
                    }
                }

                $pdf->Ln(2);
            }
        }
    }
}

$pdf->Output();
exit;
?>
