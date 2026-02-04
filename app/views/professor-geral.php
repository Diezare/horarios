<?php
// app/views/professor-geral.php
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

/* -----------------------------
   LOG SIMPLE PARA SEGURANÇA (APENAS CABEÇALHO)
------------------------------*/
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
 * Emite resposta de erro padrão e encerra.
 */
function abortClient($msg = 'Parâmetros inválidos') {
    while (ob_get_level() > 0) @ob_end_clean();
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit;
}

/* -----------------------------
   Helpers
------------------------------*/
function enc($s) { return iconv('UTF-8','ISO-8859-1//TRANSLIT',(string)$s); }

/**
 * Normaliza dia_semana vindo do banco (Terca/Terça, Sabado/Sábado etc.)
 * Retorna SEMPRE com acento para bater com o layout do relatório.
 */
function normalizeDia(string $dia): string {
    $d = trim($dia);

    // remove duplicidade de espaços
    $d = preg_replace('/\s+/', ' ', $d);

    // normalização de variantes comuns sem acento
    $map = [
        'Terca'  => 'Terça',
        'Sabado' => 'Sábado',
        'Domingo'=> 'Domingo',
        'Segunda'=> 'Segunda',
        'Terça'  => 'Terça',
        'Quarta' => 'Quarta',
        'Quinta' => 'Quinta',
        'Sexta'  => 'Sexta',
        'Sábado' => 'Sábado',
    ];

    // tenta match direto
    if (isset($map[$d])) return $map[$d];

    // tenta match case-insensitive
    foreach ($map as $k => $v) {
        if (mb_strtolower($d, 'UTF-8') === mb_strtolower($k, 'UTF-8')) return $v;
    }

    // fallback: devolve como veio
    return $d;
}

/**
 * Faz o cabeçalho (logo + instituição) centralizado e o título do relatório.
 */
function renderHeader(\FPDF $pdf, string $nomeInst, ?string $logoPath, int $LOGO_SIZE_MM, int $LOGO_GAP_MM, string $titulo): void {
    $topY = 12;
    $pdf->SetY($topY);
    $pdf->SetFont('Arial','B',14);

    $text  = enc($nomeInst);
    $textW = $nomeInst ? $pdf->GetStringWidth($text) : 0;

    $hasLogo = ($logoPath && file_exists($logoPath));
    $totalW = $hasLogo ? ($LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0) + $textW) : $textW;

    $pageW  = $pdf->GetPageWidth();
    $startX = ($pageW - $totalW) / 2;
    $y      = $pdf->GetY();

    if ($hasLogo) {
        // segurança: basename + realpath dentro LOGO_PATH
        $candidate   = basename($logoPath);
        $fullLogo    = LOGO_PATH . '/' . $candidate;
        $realLogo    = (file_exists($fullLogo) && is_file($fullLogo)) ? realpath($fullLogo) : false;
        $realLogoDir = realpath(LOGO_PATH);

        if ($realLogo && $realLogoDir && strpos($realLogo, $realLogoDir) === 0) {
            $pdf->Image($realLogo, $startX, $y - 2, $LOGO_SIZE_MM, $LOGO_SIZE_MM);
            $startX += $LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0);
        } else {
            logSecurity("Logo inválido/falso caminho: " . ($logoPath ?? ''));
        }
    }

    if ($nomeInst) {
        $pdf->SetXY($startX, $y);
        $pdf->Cell($textW, $LOGO_SIZE_MM, $text, 0, 1, 'L');
    }

    $pdf->Ln(3);
    $pdf->SetFont('Arial','B',13);
    $pdf->Cell(0, 7, enc($titulo), 0, 1, 'L');
    $pdf->Ln(1);
}

/**
 * Se necessário, força quebra de página antes de uma seção.
 */
function ensureSpaceOrNewPage(\FPDF $pdf, float $minSpaceMm, callable $onNewPage = null): void {
    $bottomMargin = 15; // mesmo do footer
    $pageH = $pdf->GetPageHeight();
    $y = $pdf->GetY();
    $spaceLeft = $pageH - $bottomMargin - $y;
    if ($spaceLeft < $minSpaceMm) {
        $pdf->AddPage();
        if ($onNewPage) $onNewPage();
    }
}

/* -----------------------------
   VALIDAÇÃO RÍGIDA DA QUERY STRING (CABEÇALHO)
------------------------------*/
$allowed = ['id_ano','disciplina','restricoes','turnos','turmas','horarios'];
$rawQuery = $_SERVER['QUERY_STRING'] ?? '';
parse_str($rawQuery, $receivedParams);

$receivedKeys = array_keys($receivedParams);
$extra = array_diff($receivedKeys, $allowed);
if (!empty($extra)) {
    logSecurity('Parâmetros inesperados em professor-geral.php: ' . implode(', ', $extra) . ' | raw_qs=' . $rawQuery);
    abortClient();
}

$canonical = [];
foreach ($receivedParams as $k => $v) {
    if (!is_scalar($v)) {
        logSecurity("Parâmetro não escalar em professor-geral.php: {$k}");
        abortClient();
    }

    if (in_array($k, ['disciplina','restricoes','turnos','turmas','horarios'], true)) {
        $val = (string)$v;
        $okValues = ['', '1', 'on', 'true'];
        if ($val !== '' && !in_array(strtolower($val), $okValues, true)) {
            logSecurity("Valor inválido para flag {$k} em professor-geral.php raw=" . var_export($v, true));
            abortClient();
        }
        $canonical[$k] = in_array(strtolower($val), ['1','on','true'], true) ? 1 : 0;
        continue;
    }

    if (!preg_match('/^\d+$/', (string)$v)) {
        logSecurity("Parâmetro não numérico permitido em professor-geral.php: {$k} raw=" . var_export($v, true) . " | raw_qs={$rawQuery}");
        abortClient();
    }
    $ival = (int)$v;
    if ($ival < 0) {
        logSecurity("Parâmetro negativo rejeitado em professor-geral.php: {$k} raw=" . var_export($v, true) . " | raw_qs={$rawQuery}");
        abortClient();
    }
    $canonical[$k] = $ival;
}

ksort($canonical);
$normalized_received_array = [];
foreach ($receivedParams as $k => $v) {
    if (in_array($k, ['disciplina','restricoes','turnos','turmas','horarios'], true)) {
        $normalized_received_array[$k] = in_array(strtolower((string)$v), ['1','on','true'], true) ? 1 : 0;
    } else {
        $normalized_received_array[$k] = (int)$v;
    }
}
ksort($normalized_received_array);
if ($canonical !== $normalized_received_array) {
    logSecurity("Query string não canônica em professor-geral.php: expected=" . json_encode($canonical) . " got=" . json_encode($normalized_received_array) . " | raw_qs={$rawQuery}");
    abortClient();
}

/* -----------------------------
   Semântica: se id_ano informado, deve existir no BD
------------------------------*/
$id_ano = $canonical['id_ano'] ?? 0;
if ($id_ano > 0) {
    $stA = $pdo->prepare("SELECT 1 FROM ano_letivo WHERE id_ano_letivo = :id LIMIT 1");
    $stA->execute([':id' => $id_ano]);
    if ($stA->fetchColumn() === false) {
        logSecurity("id_ano inexistente em professor-geral.php: {$id_ano}");
        abortClient();
    }
}

// flags
$exibeDisc     = !empty($canonical['disciplina']);
$exibeRestr    = !empty($canonical['restricoes']);
$exibeTurnos   = !empty($canonical['turnos']);
$exibeTurmas   = !empty($canonical['turmas']);
$exibeHorarios = !empty($canonical['horarios']);

/* -----------------------------
   Constantes visuais
------------------------------*/
$LOGO_SIZE_MM = 15;
$LOGO_GAP_MM  = 5;

// ----------------------------------------------------------------
// Classe PDF
// ----------------------------------------------------------------
class PDFGeral extends FPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,enc('Página ' . $this->PageNo()),0,0,'L');
        $this->Cell(0,10,enc('Impresso em: ' . date('d/m/Y H:i:s')),0,0,'R');
    }
}

// ----------------------------------------------------------------
// Carrega instituição
// ----------------------------------------------------------------
$stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
$inst = $stmtInst->fetch(PDO::FETCH_ASSOC);
$nomeInst = $inst ? ($inst['nome_instituicao'] ?? '') : '';
$logoPath = ($inst && !empty($inst['imagem_instituicao']))
    ? (LOGO_PATH . '/' . basename($inst['imagem_instituicao']))
    : null;

// ----------------------------------------------------------------
// Carrega professores
// ----------------------------------------------------------------
$stmt = $pdo->query("SELECT * FROM professor ORDER BY nome_completo");
$professores = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pdf = new PDFGeral('P','mm','A4');
$pdf->SetTitle(enc('Relatório Geral de Professores'));

// sem professores
if (!$professores) {
    $pdf->AddPage();
    renderHeader($pdf, $nomeInst, $logoPath, $LOGO_SIZE_MM, $LOGO_GAP_MM, 'Relatório Geral de Professores');
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10, enc('Nenhum professor cadastrado.'), 0,1,'C');
    $pdf->Output();
    exit;
}

// dias do relatório (padrão)
$diasUteis = ['Segunda','Terça','Quarta','Quinta','Sexta'];

foreach ($professores as $prof) {
    $id_professor = (int)($prof['id_professor'] ?? 0);
    if ($id_professor <= 0) continue;

    // Página base do professor
    $pdf->AddPage();
    renderHeader($pdf, $nomeInst, $logoPath, $LOGO_SIZE_MM, $LOGO_GAP_MM, 'Relatório Geral de Professores');

    // ----------------------------------------------------------------
    // Dados do Professor
    // ----------------------------------------------------------------
    $pdf->SetFont('Arial','B',10);
    $pdf->SetFillColor(200,200,200);
    $pdf->Cell(80, 8, enc('Nome Completo'), 1, 0, 'C', true);
    $pdf->Cell(35, 8, enc('Nome Exibição'), 1, 0, 'C', true);
    $pdf->Cell(45, 8, enc('Telefone'),      1, 0, 'C', true);
    $pdf->Cell(30, 8, enc('Sexo'),          1, 1, 'C', true);

    $pdf->SetFont('Arial','',10);
    $pdf->Cell(80, 8, enc($prof['nome_completo'] ?? ''), 1, 0, 'C');
    $pdf->Cell(35, 8, enc($prof['nome_exibicao'] ?? ''), 1, 0, 'C');
    $pdf->Cell(45, 8, enc($prof['telefone'] ?? ''),      1, 0, 'C');
    $pdf->Cell(30, 8, enc($prof['sexo'] ?? ''),          1, 1, 'C');

    $gapSection = 6;

    // ----------------------------------------------------------------
    // Restrições (normalizando dia para não "sumir" terça/terca etc.)
    // ----------------------------------------------------------------
    if ($exibeRestr && $id_ano) {
        ensureSpaceOrNewPage($pdf, 60, function() use ($pdf,$nomeInst,$logoPath,$LOGO_SIZE_MM,$LOGO_GAP_MM){
            renderHeader($pdf, $nomeInst, $logoPath, $LOGO_SIZE_MM, $LOGO_GAP_MM, 'Relatório Geral de Professores');
        });

        $stmtRes = $pdo->prepare("
            SELECT dia_semana, numero_aula
              FROM professor_restricoes
             WHERE id_professor = :idprof
               AND id_ano_letivo = :idano
        ");
        $stmtRes->execute([':idprof'=>$id_professor, ':idano'=>$id_ano]);
        $restricoesData = $stmtRes->fetchAll(PDO::FETCH_ASSOC);

        $restricoesMap = [];
        foreach ($restricoesData as $r) {
            $dia = normalizeDia((string)$r['dia_semana']);
            if (!isset($restricoesMap[$dia])) $restricoesMap[$dia] = [];
            $restricoesMap[$dia][] = (int)$r['numero_aula'];
        }
        // remove duplicadas
        foreach ($restricoesMap as $d => $arr) {
            $restricoesMap[$d] = array_values(array_unique($arr));
            sort($restricoesMap[$d]);
        }

        $pdf->Ln($gapSection);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,7, enc('Restrições (X = restrito)'),0,1,'L');

        $pdf->SetFont('Arial','B',9);
        $pdf->SetFillColor(200,200,200);
        $pdf->Cell(30, 7, enc('Aula/Dia'), 1, 0, 'C', true);
        foreach ($diasUteis as $dia) {
            $pdf->Cell(32,7, enc($dia), 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('Arial','',9);
        $maxAulas = 6;
        for ($aula = 1; $aula <= $maxAulas; $aula++) {
            $pdf->Cell(30, 7, $aula.enc('ª Aula'), 1, 0, 'C');
            foreach ($diasUteis as $dia) {
                if (isset($restricoesMap[$dia]) && in_array($aula, $restricoesMap[$dia], true)) {
                    $pdf->SetFillColor(255,0,0);
                    $pdf->SetTextColor(255,255,255);
                    $pdf->Cell(32,7,'X',1,0,'C',true);
                    $pdf->SetTextColor(0,0,0);
                    $pdf->SetFillColor(255,255,255);
                } else {
                    $pdf->SetTextColor(0,128,0);
                    $pdf->Cell(32,7,'V',1,0,'C');
                    $pdf->SetTextColor(0,0,0);
                }
            }
            $pdf->Ln();
        }
    }

    // ----------------------------------------------------------------
    // Turmas (corrigido: remove duplicadas com DISTINCT e evita duplicação por múltiplas disciplinas)
    // ----------------------------------------------------------------
    if ($exibeTurmas && $id_ano) {
        ensureSpaceOrNewPage($pdf, 40, function() use ($pdf,$nomeInst,$logoPath,$LOGO_SIZE_MM,$LOGO_GAP_MM){
            renderHeader($pdf, $nomeInst, $logoPath, $LOGO_SIZE_MM, $LOGO_GAP_MM, 'Relatório Geral de Professores');
        });

        $pdf->Ln($gapSection);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,7, enc('Turmas'), 0, 1, 'L');

        $stmtTurmasInfo = $pdo->prepare("
            SELECT a.ano,
                   n.nome_nivel_ensino,
                   s.nome_serie,
                   GROUP_CONCAT(DISTINCT t.nome_turma ORDER BY t.nome_turma SEPARATOR ', ') AS turmas
              FROM professor_disciplinas_turmas pdt
              JOIN turma t        ON pdt.id_turma = t.id_turma
              JOIN ano_letivo a   ON t.id_ano_letivo = a.id_ano_letivo
              JOIN serie s        ON t.id_serie = s.id_serie
              JOIN nivel_ensino n ON s.id_nivel_ensino = n.id_nivel_ensino
             WHERE pdt.id_professor = :id
               AND a.id_ano_letivo  = :id_ano
             GROUP BY a.ano, n.nome_nivel_ensino, s.id_serie, s.nome_serie
             ORDER BY a.ano, n.nome_nivel_ensino, s.nome_serie
        ");
        $stmtTurmasInfo->execute([':id'=>$id_professor, ':id_ano'=>$id_ano]);
        $rows = $stmtTurmasInfo->fetchAll(PDO::FETCH_ASSOC);

        if ($rows) {
            $agrupado = [];
            foreach ($rows as $r) {
                $key = $r['ano'].'__'.$r['nome_nivel_ensino'];
                if (!isset($agrupado[$key])) {
                    $agrupado[$key] = [
                        'ano'   => $r['ano'],
                        'nivel' => $r['nome_nivel_ensino'],
                        'series'=> []
                    ];
                }
                $agrupado[$key]['series'][] = [
                    'nome_serie' => $r['nome_serie'],
                    'turmas'     => $r['turmas'] ?: '-'
                ];
            }

            $pdf->SetFont('Arial','B',9);
            $pdf->SetFillColor(200,200,200);
            $pdf->Cell(30,7,enc('Ano'),1,0,'C',true);
            $pdf->Cell(60,7,enc('Nível de Ensino'),1,0,'C',true);
            $pdf->Cell(100,7,enc('Séries e Turmas'),1,1,'C',true);

            $pdf->SetFont('Arial','',9);
            foreach ($agrupado as $group) {
                $ano   = $group['ano'];
                $nivel = $group['nivel'];
                $series= $group['series'];

                $stText = '';
                foreach ($series as $serieData) {
                    $stText .= enc($serieData['nome_serie']).' - '.enc($serieData['turmas'])."\n";
                }
                $stText = rtrim($stText,"\n");

                $lineCount = substr_count($stText,"\n") + 1;
                $rowHeight = 6 * $lineCount;

                $pdf->Cell(30,$rowHeight,enc($ano),1,0,'C');
                $pdf->Cell(60,$rowHeight,enc($nivel),1,0,'C');
                $pdf->MultiCell(100,6,$stText,1,'C');
            }
        } else {
            $pdf->SetFont('Arial','I',10);
            $pdf->Cell(0,8, enc('Nenhuma turma encontrada para o ano selecionado.'),0,1,'C');
        }
    }

    // ----------------------------------------------------------------
    // Disciplinas (corrigido: DISTINCT + dedupe de turmas por série)
    // ----------------------------------------------------------------
    if ($exibeDisc && $id_ano) {
        ensureSpaceOrNewPage($pdf, 40, function() use ($pdf,$nomeInst,$logoPath,$LOGO_SIZE_MM,$LOGO_GAP_MM){
            renderHeader($pdf, $nomeInst, $logoPath, $LOGO_SIZE_MM, $LOGO_GAP_MM, 'Relatório Geral de Professores');
        });

        $pdf->Ln($gapSection);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,7,enc('Disciplinas'),0,1,'L');

        // OBS: não precisa do join professor_disciplinas para listar; pdt já tem vínculo
        $stmtDisciplina = $pdo->prepare("
            SELECT DISTINCT
                   a.ano,
                   n.nome_nivel_ensino,
                   s.nome_serie,
                   t.nome_turma,
                   d.nome_disciplina
              FROM professor_disciplinas_turmas pdt
              JOIN turma t        ON pdt.id_turma = t.id_turma
              JOIN serie s        ON t.id_serie = s.id_serie
              JOIN ano_letivo a   ON t.id_ano_letivo = a.id_ano_letivo
              JOIN nivel_ensino n ON s.id_nivel_ensino = n.id_nivel_ensino
              JOIN disciplina d   ON pdt.id_disciplina = d.id_disciplina
             WHERE pdt.id_professor = :id
               AND a.id_ano_letivo  = :id_ano
             ORDER BY a.ano, n.nome_nivel_ensino, d.nome_disciplina, s.nome_serie, t.nome_turma
        ");
        $stmtDisciplina->execute([':id'=>$id_professor, ':id_ano'=>$id_ano]);
        $discRows = $stmtDisciplina->fetchAll(PDO::FETCH_ASSOC);

        $agrupado = [];
        foreach ($discRows as $row) {
            $key = $row['ano'].'__'.$row['nome_nivel_ensino'].'__'.$row['nome_disciplina'];
            if (!isset($agrupado[$key])) {
                $agrupado[$key] = [
                    'ano'        => $row['ano'],
                    'nivel'      => $row['nome_nivel_ensino'],
                    'disciplina' => $row['nome_disciplina'],
                    'seriesTurmas' => []
                ];
            }
            $agrupado[$key]['seriesTurmas'][] = [
                'serie' => $row['nome_serie'],
                'turma' => $row['nome_turma']
            ];
        }

        if ($agrupado) {
            $pdf->SetFont('Arial','B',9);
            $pdf->SetFillColor(200,200,200);
            $pdf->Cell(30,7,enc('Ano'),1,0,'C',true);
            $pdf->Cell(50,7,enc('Nível de Ensino'),1,0,'C',true);
            $pdf->Cell(60,7,enc('Série e Turmas'),1,0,'C',true);
            $pdf->Cell(50,7,enc('Disciplina'),1,1,'C',true);

            $pdf->SetFont('Arial','',9);
            foreach ($agrupado as $grp) {
                $ano        = $grp['ano'];
                $nivel      = $grp['nivel'];
                $disciplina = $grp['disciplina'];
                $listaST    = $grp['seriesTurmas'];

                $serieTurmaMap = [];
                foreach ($listaST as $st) {
                    $serie = (string)$st['serie'];
                    if (!isset($serieTurmaMap[$serie])) $serieTurmaMap[$serie] = [];
                    $serieTurmaMap[$serie][] = (string)$st['turma'];
                }
                // remove duplicadas e ordena
                foreach ($serieTurmaMap as $serie => $arrT) {
                    $arrT = array_values(array_unique($arrT));
                    sort($arrT, SORT_NATURAL);
                    $serieTurmaMap[$serie] = $arrT;
                }

                $stText = '';
                foreach ($serieTurmaMap as $serie => $arrT) {
                    $stText .= enc($serie).' '.enc(implode(', ', $arrT))."\n";
                }
                $stText = rtrim($stText,"\n");
                $lineCount  = substr_count($stText,"\n") + 1;
                $cellHeight = 6 * $lineCount;

                $x = $pdf->GetX();
                $y = $pdf->GetY();

                $pdf->Cell(30,$cellHeight, enc($ano),   1, 0, 'C');
                $pdf->Cell(50,$cellHeight, enc($nivel), 1, 0, 'C');

                $pdf->SetXY($x+30+50, $y);
                $pdf->MultiCell(60,6,$stText,1,'C');

                $pdf->SetXY($x+30+50+60, $y);
                $pdf->Cell(50,$cellHeight, enc($disciplina), 1, 1, 'C');
            }
        } else {
            $pdf->SetFont('Arial','I',10);
            $pdf->Cell(0,8, enc('Nenhuma disciplina encontrada.'),0,1,'C');
        }
    }

    // ----------------------------------------------------------------
    // Turnos
    // ----------------------------------------------------------------
    if ($exibeTurnos) {
        ensureSpaceOrNewPage($pdf, 25, function() use ($pdf,$nomeInst,$logoPath,$LOGO_SIZE_MM,$LOGO_GAP_MM){
            renderHeader($pdf, $nomeInst, $logoPath, $LOGO_SIZE_MM, $LOGO_GAP_MM, 'Relatório Geral de Professores');
        });

        $pdf->Ln($gapSection);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,7,enc('Turnos'),0,1,'L');

        $stmtTurnos = $pdo->prepare("
            SELECT DISTINCT t.nome_turno
              FROM professor_turnos pt
              JOIN turno t ON pt.id_turno = t.id_turno
             WHERE pt.id_professor = :id
             ORDER BY t.nome_turno
        ");
        $stmtTurnos->execute([':id'=>$id_professor]);
        $turnosList = $stmtTurnos->fetchAll(PDO::FETCH_ASSOC);

        if ($turnosList) {
            $totalWidth  = 190;
            $countTurnos = count($turnosList);
            $cellWidth   = ($countTurnos > 0) ? floor($totalWidth / $countTurnos) : 0;
            $cellHeight  = 8;
            $pdf->SetFont('Arial','',10);

            foreach ($turnosList as $t) {
                $pdf->Cell($cellWidth, $cellHeight, enc($t['nome_turno'] ?? ''), 1, 0, 'C');
            }
            $pdf->Ln();
        } else {
            $pdf->SetFont('Arial','I',10);
            $pdf->Cell(0,8, enc('Nenhum turno vinculado.'),1,1,'C');
        }
    }

    // ----------------------------------------------------------------
    // Horários
    //   CORREÇÃO 1: Sempre começa em NOVA PÁGINA (para não ficar “quebrado” como no print).
    //   CORREÇÃO 2: Normaliza dia_semana para preencher corretamente a coluna Terça.
    // ----------------------------------------------------------------
    if ($exibeHorarios && $id_ano) {
        // SEMPRE nova página para a grade do ano letivo (conforme seu pedido)
        $pdf->AddPage();
        renderHeader($pdf, $nomeInst, $logoPath, $LOGO_SIZE_MM, $LOGO_GAP_MM, 'Relatório Geral de Professores');

        // Ano letivo
        $stmtAno = $pdo->prepare("SELECT ano FROM ano_letivo WHERE id_ano_letivo = :id_ano LIMIT 1");
        $stmtAno->execute([':id_ano'=>$id_ano]);
        $anoLetivoRow = $stmtAno->fetch(PDO::FETCH_ASSOC);
        $anoLetivo = $anoLetivoRow ? ($anoLetivoRow['ano'] ?? '') : '';

        // Horários
        $stmtHorarios = $pdo->prepare("
            SELECT DISTINCT
                h.dia_semana,
                h.numero_aula,
                s.nome_serie,
                t.nome_turma,
                d.nome_disciplina
            FROM horario h
            JOIN turma t        ON h.id_turma = t.id_turma
            JOIN serie s        ON t.id_serie = s.id_serie
            JOIN disciplina d   ON h.id_disciplina = d.id_disciplina
            JOIN ano_letivo a   ON t.id_ano_letivo = a.id_ano_letivo
            WHERE h.id_professor = :idprof
              AND a.id_ano_letivo = :idano
            ORDER BY
              h.numero_aula,
              FIELD(h.dia_semana,'Segunda','Terca','Terça','Quarta','Quinta','Sexta','Sabado','Sábado','Domingo')
        ");
        $stmtHorarios->execute([':idprof'=>$id_professor, ':idano'=>$id_ano]);
        $horariosData = $stmtHorarios->fetchAll(PDO::FETCH_ASSOC);

        if (!$horariosData) {
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(190, 8, enc('Nenhuma aula foi encontrada'), 1, 1, 'C');
        } else {
            $dias = $diasUteis; // Segunda..Sexta
            $maxAulas = 6;

            $matrix = [];
            for ($aula = 1; $aula <= $maxAulas; $aula++) {
                foreach ($dias as $dia) $matrix[$aula][$dia] = "";
            }

            foreach ($horariosData as $rowH) {
                $dia  = normalizeDia((string)$rowH['dia_semana']);
                $aula = (int)$rowH['numero_aula'];

                if (!isset($matrix[$aula][$dia])) continue;

                $serieTurma = (string)$rowH['nome_serie'].' '.(string)$rowH['nome_turma'];
                $disciplina = (string)$rowH['nome_disciplina'];

                $text = enc($serieTurma."\n".$disciplina);

                // evita duplicar o mesmo bloco no mesmo slot
                if ($matrix[$aula][$dia] !== "" && strpos($matrix[$aula][$dia], $text) !== false) {
                    continue;
                }

                $matrix[$aula][$dia] = $matrix[$aula][$dia]
                    ? ($matrix[$aula][$dia]."\n-----------\n".$text)
                    : $text;
            }

            $pdf->Ln(2);
            $pdf->SetFont('Arial','B',12);
            $tituloAno = $anoLetivo ? ("Ano Letivo de ".$anoLetivo) : "Horários";
            $pdf->Cell(190,8,enc($tituloAno),1,1,'C');

            // Cabeçalho
            $pdf->SetFont('Arial','B',9);
            $pdf->SetFillColor(200,200,200);
            $pdf->Cell(30,6,enc('Aula/Dia'),1,0,'C',true);
            foreach ($dias as $dia) {
                $pdf->Cell(32,6,enc($dia),1,0,'C',true);
            }
            $pdf->Ln();

            // Conteúdo compacto
            $pdf->SetFont('Arial','',8);
            for ($aula = 1; $aula <= $maxAulas; $aula++) {
                $maxRowHeight = 6;
                foreach ($dias as $dia) {
                    $cellTxt = $matrix[$aula][$dia];
                    $lines  = ($cellTxt === "") ? 1 : (substr_count($cellTxt, "\n") + 1);
                    $height = 4.5 * $lines;
                    if ($height > $maxRowHeight) $maxRowHeight = $height;
                }

                $pdf->Cell(30, $maxRowHeight, $aula.enc('ª Aula'),1,0,'C');

                foreach ($dias as $dia) {
                    $conteudo = $matrix[$aula][$dia];
                    if ($conteudo === "") {
                        $pdf->SetFillColor(0,0,0);
                        $pdf->Cell(32, $maxRowHeight, '', 1, 0, 'C', true);
                        $pdf->SetFillColor(255,255,255);
                    } else {
                        $x = $pdf->GetX();
                        $y = $pdf->GetY();
                        $pdf->MultiCell(32, 4.5, $conteudo, 1, 'C');
                        $pdf->SetXY($x + 32, $y);
                    }
                }
                $pdf->Ln($maxRowHeight);
                $pdf->Ln(2);
            }
        }
    }
}

// ----------------------------------------------------------------
// Finaliza e envia ao browser
// ----------------------------------------------------------------
$pdf->Output();
exit;
?>
