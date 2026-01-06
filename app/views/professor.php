<?php
// app/views/professor.php
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
   VALIDAÇÃO RÍGIDA DA QUERY STRING (CABEÇALHO)
   Permitimos só os parâmetros conhecidos e validamos tipos/valores.
------------------------------*/
$allowed = [
    'id_professor','id_ano',
    'disciplinas','restricoes','turnos','turmas','disciplina','horarios'
];
$rawQuery = $_SERVER['QUERY_STRING'] ?? '';
parse_str($rawQuery, $receivedParams);

// rejeita parâmetros inesperados
$receivedKeys = array_keys($receivedParams);
$extra = array_diff($receivedKeys, $allowed);
if (!empty($extra)) {
    logSecurity('Parâmetros inesperados em professor.php: ' . implode(', ', $extra) . ' | raw_qs=' . $rawQuery);
    abortClient();
}

// normalize & validate
$canonical = [];
foreach ($receivedParams as $k => $v) {
    if (!is_scalar($v)) {
        logSecurity("Parâmetro não escalar em professor.php: {$k}");
        abortClient();
    }
    // flags (checkboxes) must be either empty (present without value) or accepted truthy strings
    if (in_array($k, ['disciplinas','restricoes','turnos','turmas','disciplina','horarios'], true)) {
        $val = (string)$v;
        // Acceptable values for checkbox-like params: empty, '1', 'on', 'true'
        $okValues = ['', '1', 'on', 'true'];
        if ($val !== '' && !in_array(strtolower($val), $okValues, true)) {
            logSecurity("Valor inválido para flag {$k} em professor.php raw=" . var_export($v, true));
            abortClient();
        }
        $canonical[$k] = in_array(strtolower($val), ['1','on','true'], true) ? 1 : 0;
        continue;
    }
    // numeric params must be non-negative integers
    if (!preg_match('/^\d+$/', (string)$v)) {
        logSecurity("Parâmetro não numérico permitido em professor.php: {$k} raw=" . var_export($v, true) . " | raw_qs={$rawQuery}");
        abortClient();
    }
    $ival = (int)$v;
    if ($ival < 0) {
        logSecurity("Parâmetro negativo rejeitado em professor.php: {$k} raw=" . var_export($v, true) . " | raw_qs={$rawQuery}");
        abortClient();
    }
    $canonical[$k] = $ival;
}

// Defensive canonical ordering check
ksort($canonical);
$normalized_received_array = [];
foreach ($receivedParams as $k => $v) {
    if (in_array($k, ['disciplinas','restricoes','turnos','turmas','disciplina','horarios'], true)) {
        $normalized_received_array[$k] = in_array(strtolower((string)$v), ['1','on','true'], true) ? 1 : 0;
    } else {
        $normalized_received_array[$k] = (int)$v;
    }
}
ksort($normalized_received_array);
if ($canonical !== $normalized_received_array) {
    logSecurity("Query string não canônica em professor.php: expected=" . json_encode($canonical) . " got=" . json_encode($normalized_received_array) . " | raw_qs={$rawQuery}");
    abortClient();
}

/* -----------------------------
   Extra: validação semântica no banco (existência)
------------------------------*/
$id_professor = $canonical['id_professor'] ?? 0;
$id_ano = $canonical['id_ano'] ?? 0;

// id_professor obrigatório e deve existir
if ($id_professor <= 0) {
    logSecurity("id_professor ausente ou zero em professor.php");
    abortClient();
}
$stP = $pdo->prepare("SELECT 1 FROM professor WHERE id_professor = :id LIMIT 1");
$stP->execute([':id' => $id_professor]);
if ($stP->fetchColumn() === false) {
    logSecurity("id_professor inexistente em professor.php: {$id_professor}");
    abortClient();
}

// se id_ano informado, deve existir
if ($id_ano > 0) {
    $stA = $pdo->prepare("SELECT 1 FROM ano_letivo WHERE id_ano_letivo = :id LIMIT 1");
    $stA->execute([':id' => $id_ano]);
    if ($stA->fetchColumn() === false) {
        logSecurity("id_ano inexistente em professor.php: {$id_ano}");
        abortClient();
    }
}

// flags (booleans)
$exibeDisciplinas = !empty($canonical['disciplinas']);
$exibeRestricoes  = !empty($canonical['restricoes']);
$exibeTurnos      = !empty($canonical['turnos']);
$exibeTurmas      = !empty($canonical['turmas']);
$exibeDisciplina  = !empty($canonical['disciplina']);
$exibeHorarios    = !empty($canonical['horarios']);

/* -----------------------------
   Parâmetros do cabeçalho (ajuste aqui)
------------------------------*/
$LOGO_SIZE_MM = 15; // tamanho (largura=altura) da logo em mm
$LOGO_GAP_MM  = 5;  // espaço entre a logo e o nome em mm

class PDF extends FPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);

        // Esquerda: Página X (sem "/N")
        $this->Cell(
            0,
            10,
            iconv('UTF-8','ISO-8859-1','Página ' . $this->PageNo()),
            0,
            0,
            'L'
        );

        // Direita: Data/hora
        $this->Cell(
            0,
            10,
            iconv('UTF-8','ISO-8859-1','Impresso em: ' . date('d/m/Y H:i:s')),
            0,
            0,
            'R'
        );
    }
}

// ----------------------------------------------------------------
// 2) Inicia PDF
// ----------------------------------------------------------------
$pdf = new PDF('P','mm','A4');
$pdf->SetTitle(iconv('UTF-8','ISO-8859-1','Relatório de Professor'));
$pdf->AddPage();

// ----------------------------------------------------------------
// 3) Cabeçalho — logo e nome na mesma linha, centralizados (padrão)
// ----------------------------------------------------------------
$stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
$inst = $stmtInst->fetch(PDO::FETCH_ASSOC);
$nomeInst = $inst ? ($inst['nome_instituicao'] ?? '') : '';
$logoPath = ($inst && !empty($inst['imagem_instituicao']))
    ? LOGO_PATH . '/' . basename($inst['imagem_instituicao'])
    : null;

// calcula largura total: logo + gap + texto (se houver logo); só texto se não houver logo
$topY = 12;
$pdf->SetY($topY);
$pdf->SetFont('Arial','B',14);

$text = iconv('UTF-8','ISO-8859-1', $nomeInst);
$textW = $pdf->GetStringWidth($text);

$totalW = 0;
if ($logoPath && file_exists($logoPath)) {
    $totalW = $LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0) + $textW;
} else {
    $totalW = $textW;
}
$pageW  = $pdf->GetPageWidth();
$startX = ($pageW - $totalW) / 2;
$y      = $pdf->GetY();

// safety: only use logo inside LOGO_PATH
if ($logoPath && file_exists($logoPath)) {
    $candidate = basename($logoPath);
    $fullLogo = LOGO_PATH . '/' . $candidate;
    if (file_exists($fullLogo) && is_file($fullLogo)) {
        $realLogo = realpath($fullLogo);
        $realLogoDir = realpath(LOGO_PATH);
        if ($realLogo !== false && $realLogoDir !== false && strpos($realLogo, $realLogoDir) === 0) {
            $pdf->Image($realLogo, $startX, $y - 2, $LOGO_SIZE_MM, $LOGO_SIZE_MM);
            $startX += $LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0);
        } else {
            logSecurity("Tentativa de usar logo fora do diretório permitido: " . $logoPath);
        }
    } else {
        logSecurity("Logo informado não encontrado: " . $logoPath);
    }
}

// nome da instituição
if ($nomeInst) {
    $pdf->SetXY($startX, $y);
    $pdf->Cell($textW, $LOGO_SIZE_MM, $text, 0, 1, 'L');
}

// título do relatório — alinhado à esquerda, como nos outros
$pdf->Ln(4);
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0, 8, iconv('UTF-8','ISO-8859-1','Relatório de Professor'), 0, 1, 'L');
$pdf->Ln(2);

// ----------------------------------------------------------------
// 4) Dados do Professor
// ----------------------------------------------------------------
$stmtProf = $pdo->prepare("SELECT * FROM professor WHERE id_professor = :id");
$stmtProf->execute([':id'=>$id_professor]);
$prof = $stmtProf->fetch(PDO::FETCH_ASSOC);
if (!$prof) {
    $pdf->SetFont('Arial','I',12);
    $pdf->Cell(0,8, iconv('UTF-8','ISO-8859-1','Professor não encontrado.'), 0, 1, 'C');
    $pdf->Output();
    exit;
}

// Cabeçalho da tabela
$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(200,200,200);
$pdf->Cell(80, 10, iconv('UTF-8','ISO-8859-1','Nome Completo'), 1, 0, 'C', true);
$pdf->Cell(35, 10, iconv('UTF-8','ISO-8859-1','Nome Exibição'), 1, 0, 'C', true);
$pdf->Cell(45, 10, iconv('UTF-8','ISO-8859-1','Telefone'), 1, 0, 'C', true);
$pdf->Cell(30, 10, iconv('UTF-8','ISO-8859-1','Sexo'), 1, 1, 'C', true);

// Dados
$pdf->SetFont('Arial','',12);
$pdf->Cell(80, 10, iconv('UTF-8','ISO-8859-1',$prof['nome_completo'] ?? ''), 1, 0, 'C');
$pdf->Cell(35, 10, iconv('UTF-8','ISO-8859-1',$prof['nome_exibicao'] ?? ''), 1, 0, 'C');
$pdf->Cell(45, 10, iconv('UTF-8','ISO-8859-1',$prof['telefone'] ?? ''), 1, 0, 'C');
$pdf->Cell(30, 10, iconv('UTF-8','ISO-8859-1',$prof['sexo'] ?? ''), 1, 1, 'C');

// ----------------------------------------------------------------
// 5) Restrições (se houver ano)
// ----------------------------------------------------------------
if ($exibeRestricoes && $id_ano) {
    $stmtRes = $pdo->prepare("
        SELECT dia_semana, numero_aula 
          FROM professor_restricoes 
         WHERE id_professor = :id_prof 
           AND id_ano_letivo = :id_ano
    ");
    $stmtRes->execute([':id_prof'=>$id_professor, ':id_ano'=>$id_ano]);
    $restricoesData = $stmtRes->fetchAll(PDO::FETCH_ASSOC);

    $restricoesMap = [];
    foreach ($restricoesData as $r) {
        $dia = $r['dia_semana'];
        if (!isset($restricoesMap[$dia])) $restricoesMap[$dia] = [];
        $restricoesMap[$dia][] = (int)$r['numero_aula'];
    }

    $pdf->Ln(10);
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0, 10, iconv('UTF-8','ISO-8859-1','Restrições'), 0, 1, 'L');

    $pdf->SetFont('Arial','B',10);
    $pdf->SetFillColor(200,200,200);
    $pdf->Cell(30, 8, iconv('UTF-8','ISO-8859-1','Aula/ Dia'), 1, 0, 'C', true);
    $diasUteis = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta'];
    foreach ($diasUteis as $dia) {
        $pdf->Cell(32, 8, iconv('UTF-8','ISO-8859-1',$dia), 1, 0, 'C', true);
    }
    $pdf->Ln();

    $maxAulas = 6;
    $pdf->SetFont('Arial','',10);
    for ($aula = 1; $aula <= $maxAulas; $aula++) {
        $pdf->Cell(30, 8, $aula . iconv('UTF-8','ISO-8859-1','ª Aula'), 1, 0, 'C');
        foreach ($diasUteis as $dia) {
            if (isset($restricoesMap[$dia]) && in_array($aula, $restricoesMap[$dia], true)) {
                $pdf->SetFillColor(255,0,0);
                $pdf->SetTextColor(255,255,255);
                $pdf->Cell(32, 8, 'X', 1, 0, 'C', true);
                $pdf->SetTextColor(0,0,0);
                $pdf->SetFillColor(255,255,255);
            } else {
                $pdf->SetTextColor(0,128,0);
                $pdf->Cell(32, 8, 'V', 1, 0, 'C');
                $pdf->SetTextColor(0,0,0);
            }
        }
        $pdf->Ln();
    }
}

// ----------------------------------------------------------------
// 6) Turmas (Ano Letivo, Nível, Série e Turmas)
// ----------------------------------------------------------------
if ($exibeTurmas && $id_ano) {
    $pdf->Ln(10);
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0, 10, iconv('UTF-8','ISO-8859-1','Turmas'), 0, 1, 'L');

    $stmtTurmasInfo = $pdo->prepare("
        SELECT a.ano, 
               n.nome_nivel_ensino, 
               s.nome_serie, 
               GROUP_CONCAT(t.nome_turma ORDER BY t.nome_turma SEPARATOR ', ') AS turmas
          FROM turma t
          JOIN ano_letivo a ON t.id_ano_letivo = a.id_ano_letivo
          JOIN serie s ON t.id_serie = s.id_serie
          JOIN nivel_ensino n ON s.id_nivel_ensino = n.id_nivel_ensino
          JOIN professor_disciplinas_turmas pdt ON t.id_turma = pdt.id_turma
         WHERE pdt.id_professor = :id 
           AND a.id_ano_letivo = :id_ano
         GROUP BY s.id_serie
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
                'turmas'     => $r['turmas']
            ];
        }

        $pdf->SetFont('Arial','B',10);
        $pdf->SetFillColor(200,200,200);
        $pdf->Cell(30, 8, 'Ano Letivo', 1, 0, 'C', true);
        $pdf->Cell(60, 8, iconv('UTF-8','ISO-8859-1','Nível de Ensino'), 1, 0, 'C', true);
        $pdf->Cell(100, 8, iconv('UTF-8','ISO-8859-1','Série e Turmas'), 1, 1, 'C', true);

        $pdf->SetFont('Arial','',10);
        foreach ($agrupado as $group) {
            $ano    = $group['ano'];
            $nivel  = $group['nivel'];
            $series = $group['series'];

            $stText = '';
            foreach ($series as $serieData) {
                $stText .= iconv('UTF-8','ISO-8859-1', $serieData['nome_serie'])
                    . ' - ' . iconv('UTF-8','ISO-8859-1', $serieData['turmas']) . "\n";
            }
            $stText = rtrim($stText, "\n");

            $lineCount = substr_count($stText, "\n") + 1;
            $rowHeight = 8 * $lineCount;

            $pdf->Cell(30, $rowHeight, iconv('UTF-8','ISO-8859-1',$ano), 1, 0, 'C');
            $pdf->Cell(60, $rowHeight, iconv('UTF-8','ISO-8859-1',$nivel), 1, 0, 'C');
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->MultiCell(100, 8, $stText, 1, 'C');
        }
    } else {
        $pdf->SetFont('Arial','I',12);
        $pdf->Cell(0, 10, iconv('UTF-8','ISO-8859-1','Nenhuma turma encontrada para o ano selecionado.'), 0, 1, 'C');
    }
}

// ----------------------------------------------------------------
// 7) Disciplinas (Ano, Nível, Série e Turmas, Disciplina)
// ----------------------------------------------------------------
if ($exibeDisciplina && $id_ano) {
    $pdf->Ln(10);
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0, 10, iconv('UTF-8','ISO-8859-1','Disciplinas'), 0, 1, 'L');

    $stmtDisciplina = $pdo->prepare("
        SELECT a.ano, 
               n.nome_nivel_ensino, 
               s.nome_serie, 
               t.nome_turma, 
               d.nome_disciplina
          FROM professor_disciplinas_turmas pdt
          JOIN turma t ON pdt.id_turma = t.id_turma
          JOIN serie s ON t.id_serie = s.id_serie
          JOIN ano_letivo a ON t.id_ano_letivo = a.id_ano_letivo
          JOIN nivel_ensino n ON s.id_nivel_ensino = n.id_nivel_ensino 
          JOIN professor_disciplinas pd 
                ON pdt.id_professor = pd.id_professor 
               AND pd.id_disciplina = pdt.id_disciplina
          JOIN disciplina d ON pd.id_disciplina = d.id_disciplina
         WHERE pdt.id_professor = :id 
           AND a.id_ano_letivo = :id_ano
         ORDER BY a.ano, n.nome_nivel_ensino, s.nome_serie, t.nome_turma, d.nome_disciplina
    ");
    $stmtDisciplina->execute([':id'=>$id_professor, ':id_ano'=>$id_ano]);
    $discRows = $stmtDisciplina->fetchAll(PDO::FETCH_ASSOC);

    $agrupado = [];
    foreach ($discRows as $row) {
        $key = $row['ano'].'__'.$row['nome_nivel_ensino'].'__'.$row['nome_disciplina'];
        if (!isset($agrupado[$key])) {
            $agrupado[$key] = [
                'ano'         => $row['ano'],
                'nivel'       => $row['nome_nivel_ensino'],
                'disciplina'  => $row['nome_disciplina'],
                'seriesTurmas'=> []
            ];
        }
        $agrupado[$key]['seriesTurmas'][] = [
            'serie' => $row['nome_serie'],
            'turma' => $row['nome_turma']
        ];
    }

    if ($agrupado) {
        $pdf->SetFont('Arial','B',10);
        $pdf->SetFillColor(200,200,200);
        $pdf->Cell(30, 8, 'Ano Letivo', 1, 0, 'C', true);
        $pdf->Cell(50, 8, iconv('UTF-8','ISO-8859-1','Nível de Ensino'), 1, 0, 'C', true);
        $pdf->Cell(60, 8, iconv('UTF-8','ISO-8859-1','Série e Turmas'), 1, 0, 'C', true);
        $pdf->Cell(50, 8, iconv('UTF-8','ISO-8859-1','Disciplina'), 1, 1, 'C', true);

        $pdf->SetFont('Arial','',10);
        foreach ($agrupado as $grp) {
            $ano        = $grp['ano'];
            $nivel      = $grp['nivel'];
            $disciplina = $grp['disciplina'];
            $listaST    = $grp['seriesTurmas'];

            $serieTurmaMap = [];
            foreach ($listaST as $st) {
                $serie = $st['serie'];
                if (!isset($serieTurmaMap[$serie])) $serieTurmaMap[$serie] = [];
                $serieTurmaMap[$serie][] = $st['turma'];
            }
            $stText = '';
            foreach ($serieTurmaMap as $serie => $turmasArr) {
                $stText .= iconv('UTF-8','ISO-8859-1', $serie) . ' ' . implode(', ', $turmasArr) . "\n";
            }
            $stText = rtrim($stText, "\n");

            $lineCount  = substr_count($stText, "\n") + 1;
            $cellHeight = 8 * $lineCount;

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->Cell(30, $cellHeight, iconv('UTF-8','ISO-8859-1',$ano),   1, 0, 'C');
            $pdf->Cell(50, $cellHeight, iconv('UTF-8','ISO-8859-1',$nivel), 1, 0, 'C');

            $pdf->SetXY($x + 30 + 50, $y);
            $pdf->MultiCell(60, 8, $stText, 1, 'C');

            $pdf->SetXY($x + 30 + 50 + 60, $y);
            $pdf->Cell(50, $cellHeight, iconv('UTF-8','ISO-8859-1',$disciplina), 1, 1, 'C');
        }
    } else {
        $pdf->SetFont('Arial','I',12);
        $pdf->Cell(0,10, iconv('UTF-8','ISO-8859-1','Nenhuma disciplina encontrada.'), 0,1,'C');
    }
}

// ----------------------------------------------------------------
// 8) Turnos
// ----------------------------------------------------------------
if ($exibeTurnos) {
    $pdf->Ln(10);
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,10, iconv('UTF-8','ISO-8859-1','Turnos'), 0, 1, 'L');

    $stmtTurnos = $pdo->prepare("
        SELECT t.nome_turno 
          FROM professor_turnos pt 
          JOIN turno t ON pt.id_turno = t.id_turno 
         WHERE pt.id_professor = :id
    ");
    $stmtTurnos->execute([':id'=>$id_professor]);
    $turnosList = $stmtTurnos->fetchAll(PDO::FETCH_ASSOC);

    if ($turnosList) {
        $totalWidth  = 190;
        $countTurnos = count($turnosList);
        $cellWidth   = ($countTurnos > 0) ? floor($totalWidth / $countTurnos) : 0;
        $cellHeight  = 10;
        $pdf->SetFont('Arial','',12);

        foreach ($turnosList as $t) {
            $pdf->Cell($cellWidth, $cellHeight, iconv('UTF-8','ISO-8859-1',$t['nome_turno'] ?? ''), 1, 0, 'C');
        }
        $pdf->Ln();
    } else {
        $pdf->SetFont('Arial','I',12);
        $pdf->Cell(0,10, iconv('UTF-8','ISO-8859-1','Nenhum turno vinculado.'), 1, 1, 'C');
    }
}

// ----------------------------------------------------------------
// 9) Horários
// ----------------------------------------------------------------
if ($exibeHorarios && $id_ano) {
    $stmtAno = $pdo->prepare("SELECT ano FROM ano_letivo WHERE id_ano_letivo = :id_ano LIMIT 1");
    $stmtAno->execute([':id_ano' => $id_ano]);
    $anoLetivoRow = $stmtAno->fetch(PDO::FETCH_ASSOC);
    $anoLetivo    = $anoLetivoRow ? $anoLetivoRow['ano'] : '';

    $stmtHorarios = $pdo->prepare("
        SELECT 
            h.dia_semana, 
            h.numero_aula, 
            s.nome_serie, 
            t.nome_turma, 
            d.nome_disciplina
        FROM horario h
        JOIN turma t ON h.id_turma = t.id_turma
        JOIN serie s ON t.id_serie = s.id_serie
        JOIN disciplina d ON h.id_disciplina = d.id_disciplina
        JOIN ano_letivo a ON t.id_ano_letivo = a.id_ano_letivo
        WHERE h.id_professor = :id_prof
          AND a.id_ano_letivo = :id_ano
        ORDER BY 
          FIELD(h.dia_semana,'Segunda','Terça','Quarta','Quinta','Sexta','Sabado','Domingo'),
          h.numero_aula
    ");
    $stmtHorarios->execute([
        ':id_prof' => $id_professor,
        ':id_ano'  => $id_ano
    ]);
    $horariosData = $stmtHorarios->fetchAll(PDO::FETCH_ASSOC);

    if (count($horariosData) === 0) {
        $pdf->Ln(10);
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(190, 10, iconv('UTF-8','ISO-8859-1', 'Nenhuma aula foi encontrada'), 1, 1, 'C');
    } else {
        $dias     = ['Segunda','Terça','Quarta','Quinta','Sexta'];
        $maxAulas = 6;

        $matrix = [];
        for ($aula = 1; $aula <= $maxAulas; $aula++) {
            foreach ($dias as $dia) $matrix[$aula][$dia] = "";
        }

        foreach ($horariosData as $row) {
            $dia        = $row['dia_semana'];
            $aula       = (int)$row['numero_aula'];
            $serieTurma = $row['nome_serie'] . ' ' . $row['nome_turma'];
            $disciplina = $row['nome_disciplina'];
            $text = iconv('UTF-8','ISO-8859-1', $serieTurma . "\n" . $disciplina);

            if (isset($matrix[$aula][$dia])) {
                $matrix[$aula][$dia] = $matrix[$aula][$dia]
                    ? ($matrix[$aula][$dia] . "\n-----------\n" . $text)
                    : $text;
            }
        }

        $pdf->Ln(10);
        $pdf->SetFont('Arial','B',14);
        $tituloAno = $anoLetivo ? "Ano Letivo de " . $anoLetivo : "Horários";
        $pdf->Cell(190, 10, iconv('UTF-8','ISO-8859-1', $tituloAno), 1, 1, 'C');

        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(30, 8, iconv('UTF-8','ISO-8859-1','Aula/ Dia'), 1, 0, 'C', true);
        foreach ($dias as $dia) {
            $pdf->Cell(32, 8, iconv('UTF-8','ISO-8859-1',$dia), 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('Arial','',9);
        for ($aula = 1; $aula <= $maxAulas; $aula++) {
            $maxRowHeight = 8;

            foreach ($dias as $dia) {
                $lines  = substr_count($matrix[$aula][$dia], "\n") + 1;
                $height = 5 * $lines;
                if ($height > $maxRowHeight) $maxRowHeight = $height;
            }

            $pdf->Cell(30, $maxRowHeight, $aula . iconv('UTF-8','ISO-8859-1','ª Aula'), 1, 0, 'C');

            foreach ($dias as $dia) {
                $conteudo = $matrix[$aula][$dia];
                if ($conteudo === "") {
                    $pdf->SetFillColor(0,0,0);
                    $pdf->Cell(32, $maxRowHeight, '', 1, 0, 'C', true);
                    $pdf->SetFillColor(255,255,255);
                } else {
                    $x = $pdf->GetX();
                    $y = $pdf->GetY();
                    $pdf->MultiCell(32, 5, $conteudo, 1, 'C');
                    $pdf->SetXY($x + 32, $y);
                }
            }

            $pdf->Ln($maxRowHeight);
            $pdf->Ln(3);
        }
    }
}

// ----------------------------------------------------------------
// 10) Finaliza
// ----------------------------------------------------------------
$pdf->Output();
exit;
?>
