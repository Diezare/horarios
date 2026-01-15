<?php
// app/views/ano-letivo-geral.php 

require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

/* ---------------- CONFIG ---------------- */
$LOG_PATH = __DIR__ . '/../../logs/seguranca.log';

/* ---------------- LOG ---------------- */
function logSecurity(string $msg): void {
    global $LOG_PATH;
    $meta = [
        'ts'     => date('c'),
        'ip'     => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'ua'     => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'qs'     => $_SERVER['QUERY_STRING'] ?? '',
        'script' => basename($_SERVER['SCRIPT_NAME'] ?? '')
    ];
    $entry = '[' . date('d-M-Y H:i:s T') . '] [SEGURANCA] ' . $msg
           . ' | META=' . json_encode($meta, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    $dir = dirname($LOG_PATH);
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    @file_put_contents($LOG_PATH, $entry, FILE_APPEND | LOCK_EX);
}

/* ---------------- HELPERS ---------------- */
function enc($s){ return iconv('UTF-8','ISO-8859-1//TRANSLIT',(string)$s); }

function fmtDateBR($d): string {
    $ts = strtotime((string)$d);
    return $ts ? date('d/m/Y', $ts) : '';
}

// checkbox flag: aceita 1/on/true/"" como habilitado; 0/false como desabilitado
function parseFlag($raw): bool {
    if ($raw === null) return false;
    $s = trim((string)$raw);
    if ($s === '') return true; // checkbox sem value
    $low = mb_strtolower($s,'UTF-8');
    if ($low === 'on' || $low === 'true' || $s === '1') return true;
    if ($low === 'off' || $low === 'false' || $s === '0') return false;
    // qualquer outro valor: considera habilitado (não quebra relatório)
    return true;
}

function parseProfFilter($raw) {
    if ($raw === null) return null;
    $s = trim((string)$raw);
    $low = mb_strtolower($s,'UTF-8');

    // checkbox / UI: on/1/true/vazio => todas
    if ($s === '' || $low === 'on' || $s === '1' || $low === 'true') return 'todas';
    if ($low === 'todas') return 'todas';

    if (ctype_digit($s) && (int)$s >= 1) return (int)$s;

    // inválido: não aborta, apenas assume todas
    logSecurity("prof_restricao inválido no geral (assumindo 'todas'): raw={$s}");
    return 'todas';
}

function normalizeDiaKey($diaRaw): string {
    $d = trim((string)$diaRaw);
    if ($d === '') return '';

    // numérico (1..7) se vier assim
    if (ctype_digit($d)) {
        $n = (int)$d;
        $map = [1=>'Domingo',2=>'Segunda',3=>'Terca',4=>'Quarta',5=>'Quinta',6=>'Sexta',7=>'Sabado'];
        return $map[$n] ?? '';
    }

    $x = mb_strtolower($d,'UTF-8');
    $x = strtr($x, [
        'á'=>'a','à'=>'a','â'=>'a','ã'=>'a',
        'é'=>'e','ê'=>'e',
        'í'=>'i',
        'ó'=>'o','ô'=>'o','õ'=>'o',
        'ú'=>'u',
        'ç'=>'c'
    ]);
    $x = preg_replace('/[^a-z]/', '', $x);

    if ($x === 'domingo') return 'Domingo';
    if ($x === 'segunda' || $x === 'segundafeira') return 'Segunda';
    if ($x === 'terca'   || $x === 'tercafeira')   return 'Terca';
    if ($x === 'quarta'  || $x === 'quartafeira')  return 'Quarta';
    if ($x === 'quinta'  || $x === 'quintafeira')  return 'Quinta';
    if ($x === 'sexta'   || $x === 'sextafeira')   return 'Sexta';
    if ($x === 'sabado') return 'Sabado';

    return '';
}

function diaLabel($key): string {
    $labels = [
        'Domingo'=>'Domingo',
        'Segunda'=>'Segunda',
        'Terca'=>'Terça',
        'Quarta'=>'Quarta',
        'Quinta'=>'Quinta',
        'Sexta'=>'Sexta',
        'Sabado'=>'Sábado'
    ];
    return $labels[$key] ?? $key;
}

/* ---------------- PDF ---------------- */
class PDF extends FPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,enc('Página '.$this->PageNo()),0,0,'L');
        $this->Cell(0,10,enc('Impresso em: '.date('d/m/Y H:i:s')),0,0,'R');
    }
}

/* ---------------- CABEÇALHO (MESMO MODELO DO ORIGINAL) ---------------- */
function renderHeaderAnoGeral(PDF $pdf, array $inst, int $LOGO_SIZE_MM = 15, int $LOGO_GAP_MM = 5): void {
    $topY = 12;
    $pdf->SetY($topY);

    $nomeInst = $inst['nome_instituicao'] ?? '';
    $pdf->SetFont('Arial','B',14);
    $text = iconv('UTF-8','ISO-8859-1',$nomeInst);
    $textW = $pdf->GetStringWidth($text);

    $hasLogo = false;
    $logoPath = null;
    if (!empty($inst['imagem_instituicao'])) {
        $logoPath = LOGO_PATH . '/' . basename($inst['imagem_instituicao']);
        if ($logoPath && file_exists($logoPath)) $hasLogo = true;
    }

    $totalW = ($hasLogo ? ($LOGO_SIZE_MM + $LOGO_GAP_MM) : 0) + $textW;
    $pageW  = $pdf->GetPageWidth();
    $xStart = ($pageW - $totalW) / 2;

    if ($hasLogo) {
        $pdf->Image($logoPath, $xStart, $topY, $LOGO_SIZE_MM, $LOGO_SIZE_MM);
    }

    // Texto ao lado da logo (centralizado verticalmente)
    $textY = $topY + ($LOGO_SIZE_MM / 2) - 5; // ajuste fino igual ao original
    if ($textY < $topY) $textY = $topY;

    $textX = $xStart + ($hasLogo ? ($LOGO_SIZE_MM + $LOGO_GAP_MM) : 0);
    $pdf->SetXY($textX, $textY);
    if ($nomeInst !== '') {
        $pdf->Cell($textW + 1, 10, $text, 0, 1, 'L');
    }

    // Cursor abaixo do cabeçalho
    $pdf->SetY($topY + $LOGO_SIZE_MM + 6);
}

/* ---------------- PARÂMETROS (SEM "PARÂMETROS INVÁLIDOS") ---------------- */
$turma_enabled = parseFlag($_GET['turma'] ?? null);
$prof_enabled  = array_key_exists('prof_restricao', $_GET); // só mostra seção se usuário pediu
$prof_filter   = $prof_enabled ? parseProfFilter($_GET['prof_restricao'] ?? null) : null;

/* ---------------- CONSULTAS BASE ---------------- */
try {
    $stmtAnos = $pdo->prepare("SELECT id_ano_letivo, ano, data_inicio, data_fim FROM ano_letivo ORDER BY ano");
    $stmtAnos->execute();
    $listaAnos = $stmtAnos->fetchAll(PDO::FETCH_ASSOC);

    $stmtInst = $pdo->prepare("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
    $stmtInst->execute();
    $inst = $stmtInst->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    logSecurity('Erro SQL ao buscar anos/inst (ano-letivo-geral): '.$e->getMessage());
    http_response_code(500);
    die('Parâmetros inválidos');
}

/* ---------------- GERA PDF ---------------- */
$pdf = new PDF('P','mm','A4');
$pdf->SetTitle(enc('Relatório Geral de Anos Letivos'));

if (empty($listaAnos)) {
    $pdf->AddPage();
    renderHeaderAnoGeral($pdf, $inst);
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,8,enc('Não há anos letivos cadastrados.'),0,1,'L');
    $pdf->Output();
    exit;
}

$LOGO_SIZE_MM = 15;
$LOGO_GAP_MM  = 5;

// controle de espaço (evitar “quebrar bloco” no fim da página)
$ensureSpace = function(PDF $pdf, float $needed, callable $onNewPage) {
    $bottomSafe = 20;
    $limit = $pdf->GetPageHeight() - $bottomSafe;
    if (($pdf->GetY() + $needed) > $limit) {
        $pdf->AddPage();
        $onNewPage();
    }
};

foreach ($listaAnos as $anoItem) {
    $id_ano  = (int)$anoItem['id_ano_letivo'];
    $anoLbl  = (string)$anoItem['ano'];
    $dataIni = (string)($anoItem['data_inicio'] ?? '');
    $dataFim = (string)($anoItem['data_fim'] ?? '');

    $pdf->AddPage();
    renderHeaderAnoGeral($pdf, $inst, $LOGO_SIZE_MM, $LOGO_GAP_MM);

    // título do ano
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0, 8, enc('Relatório de Ano Letivo - ' . $anoLbl), 0, 1, 'L');
    $pdf->Ln(1);

    // tabela resumo do ano
    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(220,220,220);
    $pdf->Cell(40, 8, enc('Ano Letivo'),   1, 0, 'C', true);
    $pdf->Cell(75, 8, enc('Data Início'),  1, 0, 'C', true);
    $pdf->Cell(75, 8, enc('Data Fim'),     1, 1, 'C', true);

    $pdf->SetFont('Arial','',11);
    $pdf->Cell(40, 8, enc($anoLbl),           1, 0, 'C');
    $pdf->Cell(75, 8, enc(fmtDateBR($dataIni)),1, 0, 'C');
    $pdf->Cell(75, 8, enc(fmtDateBR($dataFim)),1, 1, 'C');

    /* ---------------- TURMAS (se solicitado) ---------------- */
    if ($turma_enabled) {
        try {
            $stmtT = $pdo->prepare("
                SELECT
                    n.nome_nivel_ensino AS nivel,
                    s.nome_serie AS serie,
                    GROUP_CONCAT(DISTINCT t.nome_turma ORDER BY t.nome_turma SEPARATOR ', ') AS turmas
                FROM turma t
                JOIN serie s ON t.id_serie = s.id_serie
                JOIN nivel_ensino n ON s.id_nivel_ensino = n.id_nivel_ensino
                WHERE t.id_ano_letivo = ?
                GROUP BY n.id_nivel_ensino, s.id_serie
                ORDER BY n.nome_nivel_ensino, s.nome_serie
            ");
            $stmtT->execute([$id_ano]);
            $turmas = $stmtT->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            logSecurity('Erro consulta turmas (ano-letivo-geral): '.$e->getMessage()." | id_ano={$id_ano}");
            $turmas = [];
        }

        if (!empty($turmas)) {
            $pdf->Ln(6);
            $pdf->SetFont('Arial','B',13);
            $pdf->Cell(0,8, enc('Turmas'), 0,1,'L');

            $printTurmaHeader = function() use ($pdf) {
                $pdf->SetFont('Arial','B',11);
                $pdf->SetFillColor(200,200,200);
                $pdf->Cell(60, 8, enc('Nível de Ensino'), 1, 0, 'C', true);
                $pdf->Cell(40, 8, enc('Série'),          1, 0, 'C', true);
                $pdf->Cell(90, 8, enc('Turmas'),         1, 1, 'C', true);
                $pdf->SetFont('Arial','',10);
            };

            $printTurmaHeader();

            foreach ($turmas as $t) {
                $ensureSpace($pdf, 10, function() use ($pdf, $inst, $LOGO_SIZE_MM, $LOGO_GAP_MM, $anoLbl, $printTurmaHeader) {
                    renderHeaderAnoGeral($pdf, $inst, $LOGO_SIZE_MM, $LOGO_GAP_MM);
                    $pdf->SetFont('Arial','B',14);
                    $pdf->Cell(0, 8, enc('Relatório de Ano Letivo - ' . $anoLbl . ' (continuação)'), 0, 1, 'L');
                    $pdf->Ln(2);
                    $pdf->SetFont('Arial','B',13);
                    $pdf->Cell(0,8, enc('Turmas (continuação)'), 0,1,'L');
                    $printTurmaHeader();
                });

                $pdf->Cell(60, 8, enc($t['nivel'] ?? ''),  1, 0, 'C');
                $pdf->Cell(40, 8, enc($t['serie'] ?? ''),  1, 0, 'C');
                $pdf->Cell(90, 8, enc($t['turmas'] ?? ''), 1, 1, 'C');
            }
        }
    }

    /* ---------------- PROFESSOR RESTRIÇÃO (se solicitado) ---------------- */
    if ($prof_enabled) {
        try {
            // se vier ID, valida existência; se não existir, trata como "todas" (não quebra)
            if (is_int($prof_filter)) {
                $stChk = $pdo->prepare("SELECT 1 FROM professor WHERE id_professor = ? LIMIT 1");
                $stChk->execute([$prof_filter]);
                if (!$stChk->fetchColumn()) {
                    logSecurity("prof_restricao id não existe no geral (assumindo 'todas'): id={$prof_filter}");
                    $prof_filter = 'todas';
                }
            }

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
            if ($prof_filter !== null && $prof_filter !== 'todas') {
                $sqlProf .= " AND pr.id_professor = ?";
                $params[] = (int)$prof_filter;
            }
            $sqlProf .= "
                ORDER BY p.nome_completo,
                         FIELD(pr.dia_semana,'Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado'),
                         pr.numero_aula
            ";

            $stmtP = $pdo->prepare($sqlProf);
            $stmtP->execute($params);
            $rows = $stmtP->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            logSecurity('Erro consulta restrições professor (ano-letivo-geral): '.$e->getMessage()." | id_ano={$id_ano}");
            $rows = [];
        }

        $pdf->Ln(6);
        $pdf->SetFont('Arial','B',13);
        $pdf->Cell(0,8, enc('Professor Restrição'), 0,1,'L');

        if (empty($rows)) {
            $pdf->SetFont('Arial','I',11);
            $pdf->Cell(0,7, enc('Nenhuma restrição encontrada para este ano.'), 0, 1, 'L');
        } else {
            // agrupa por professor
            $professores = [];
            foreach ($rows as $r) {
                $idProf = (int)($r['id_professor'] ?? 0);
                if ($idProf <= 0) continue;

                $diaKey = normalizeDiaKey($r['dia_semana'] ?? '');
                if ($diaKey === '') continue;

                $num = (int)($r['numero_aula'] ?? 0);
                if ($num <= 0) continue;

                if (!isset($professores[$idProf])) {
                    $professores[$idProf] = ['nome' => ($r['nome_professor'] ?? ''), 'restricoes' => []];
                }
                if (!isset($professores[$idProf]['restricoes'][$diaKey])) {
                    $professores[$idProf]['restricoes'][$diaKey] = [];
                }
                $professores[$idProf]['restricoes'][$diaKey][] = $num;
            }

            // normaliza (unique+sort)
            foreach ($professores as &$p) {
                foreach ($p['restricoes'] as &$arr) {
                    $arr = array_values(array_unique(array_map('intval', $arr)));
                    sort($arr, SORT_NUMERIC);
                }
                unset($arr);
            }
            unset($p);

            $printRestrHeader = function() use ($pdf) {
                $pdf->SetFont('Arial','B',11);
                $pdf->SetFillColor(200,200,200);
                $pdf->Cell(70, 8, enc('Nome Professor'), 1, 0, 'C', true);
                $pdf->Cell(60, 8, enc('Dia da Semana'),  1, 0, 'C', true);
                $pdf->Cell(60, 8, enc('Aulas'),          1, 1, 'C', true);
                $pdf->SetFont('Arial','',10);
            };

            $printRestrHeader();

            $diasOrdem = ['Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado'];
            $rowH = 8;

            foreach ($professores as $p) {
                $nomeProf = $p['nome'] ?? '';

                // dias que realmente têm restrição (Domingo/Sábado só entram se esse professor tiver)
                $diasDoProfessor = [];
                foreach ($diasOrdem as $d) {
                    if (!empty($p['restricoes'][$d])) $diasDoProfessor[] = $d;
                }
                if (empty($diasDoProfessor)) continue;

                $linhas = count($diasDoProfessor);
                $nameHeight = $rowH * $linhas;

                // evita quebrar bloco
                $ensureSpace($pdf, $nameHeight + 3, function() use ($pdf, $inst, $LOGO_SIZE_MM, $LOGO_GAP_MM, $anoLbl, $printRestrHeader) {
                    renderHeaderAnoGeral($pdf, $inst, $LOGO_SIZE_MM, $LOGO_GAP_MM);
                    $pdf->SetFont('Arial','B',14);
                    $pdf->Cell(0, 8, enc('Relatório de Ano Letivo - ' . $anoLbl . ' (continuação)'), 0, 1, 'L');
                    $pdf->Ln(2);
                    $pdf->SetFont('Arial','B',13);
                    $pdf->Cell(0,8, enc('Professor Restrição (continuação)'), 0,1,'L');
                    $printRestrHeader();
                });

                $x = $pdf->GetX();
                $y = $pdf->GetY();

                // nome mesclado
                $pdf->Cell(70, $nameHeight, enc($nomeProf), 1, 0, 'C');

                foreach ($diasDoProfessor as $i => $diaKey) {
                    $lista = $p['restricoes'][$diaKey] ?? [];
                    $aulasFormatadas = array_map(fn($n) => $n . 'ª', $lista);
                    $aulasStr = implode(', ', $aulasFormatadas);

                    $pdf->SetXY($x + 70, $y + ($i * $rowH));
                    $pdf->Cell(60, $rowH, enc(diaLabel($diaKey)), 1, 0, 'C');
                    $pdf->Cell(60, $rowH, enc($aulasStr),         1, 0, 'C');
                }

                $pdf->SetXY($x, $y + $nameHeight);
                $pdf->Ln(3);
            }
        }
    }
}

$pdf->Output();
exit;
?>
