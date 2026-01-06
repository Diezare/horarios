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
   VALIDAÇÃO RÍGIDA DA QUERY STRING (CABEÇALHO)
   Permitimos só os parâmetros conhecidos e validamos tipos/valores.
------------------------------*/
$allowed = [
    'id_ano',
    'disciplina','restricoes','turnos','turmas','horarios'
];
$rawQuery = $_SERVER['QUERY_STRING'] ?? '';
parse_str($rawQuery, $receivedParams);

// rejeita parâmetros inesperados
$receivedKeys = array_keys($receivedParams);
$extra = array_diff($receivedKeys, $allowed);
if (!empty($extra)) {
    logSecurity('Parâmetros inesperados em professor-geral.php: ' . implode(', ', $extra) . ' | raw_qs=' . $rawQuery);
    abortClient();
}

// normalize & validate
$canonical = [];
foreach ($receivedParams as $k => $v) {
    if (!is_scalar($v)) {
        logSecurity("Parâmetro não escalar em professor-geral.php: {$k}");
        abortClient();
    }

    // flags (checkboxes) must be either empty (present without value) or accepted truthy strings
    if (in_array($k, ['disciplina','restricoes','turnos','turmas','horarios'], true)) {
        $val = (string)$v;
        // Acceptable values for checkbox-like params: empty, '1', 'on', 'true'
        $okValues = ['', '1', 'on', 'true'];
        if ($val !== '' && !in_array(strtolower($val), $okValues, true)) {
            logSecurity("Valor inválido para flag {$k} em professor-geral.php raw=" . var_export($v, true));
            abortClient();
        }
        $canonical[$k] = in_array(strtolower($val), ['1','on','true'], true) ? 1 : 0;
        continue;
    }

    // numeric params must be non-negative integers
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

// Defensive canonical ordering check
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
   Mantive suas constantes visuais
------------------------------*/
$LOGO_SIZE_MM = 15; // tamanho (largura=altura) da logo em mm
$LOGO_GAP_MM  = 5;  // espaço entre a logo e o nome em mm

// ----------------------------------------------------------------
// 2) Classe PDF – mesmo rodapé padronizado
// ----------------------------------------------------------------
class PDFGeral extends FPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        // Esquerda: Página X (sem "/N")
        $this->Cell(0,10,iconv('UTF-8','ISO-8859-1','Página ' . $this->PageNo()),0,0,'L');
        // Direita: Data/hora
        $this->Cell(0,10,iconv('UTF-8','ISO-8859-1','Impresso em: ' . date('d/m/Y H:i:s')),0,0,'R');
    }
}

// ----------------------------------------------------------------
// 3) Consulta todos os professores (mesma lógica)
// ----------------------------------------------------------------
$stmt = $pdo->query("SELECT * FROM professor ORDER BY nome_completo");
$professores = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pdf = new PDFGeral('P','mm','A4');
$pdf->SetTitle(iconv('UTF-8','ISO-8859-1','Relatório Geral de Professores'));

// Dados da instituição (para usar em todas as páginas)
$stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
$inst = $stmtInst->fetch(PDO::FETCH_ASSOC);
$nomeInst = $inst ? ($inst['nome_instituicao'] ?? '') : '';
$logoPathRaw = ($inst && !empty($inst['imagem_instituicao'])) ? $inst['imagem_instituicao'] : null;

// safe logo path (basename + realpath check will be applied later)
$logoPath = $logoPathRaw ? LOGO_PATH . '/' . basename($logoPathRaw) : null;

// se não houver professores, emite PDF vazio rápido
if (!$professores) {
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10, iconv('UTF-8','ISO-8859-1','Nenhum professor cadastrado.'), 0,1,'C');
    $pdf->Output();
    exit;
}

foreach ($professores as $prof) {
    // Nova página por professor
    $pdf->AddPage();

    // ---------------- Cabeçalho (logo + nome na mesma linha, centralizados)
    $topY = 12;
    $pdf->SetY($topY);
    $pdf->SetFont('Arial','B',14); // tamanho do nome da instituição

    $text  = iconv('UTF-8','ISO-8859-1', $nomeInst);
    $textW = $pdf->GetStringWidth($text);

    // calcula largura total do bloco (logo + gap + texto)
    if ($logoPath && file_exists($logoPath)) {
        $totalW = $LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0) + $textW;
    } else {
        $totalW = $textW;
    }
    $pageW  = $pdf->GetPageWidth();
    $startX = ($pageW - $totalW) / 2;
    $y      = $pdf->GetY();

    // logo seguro: basename + realpath dentro LOGO_PATH
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
                logSecurity("Tentativa de usar logo fora do diretório permitido: " . ($logoPathRaw ?? ''));
            }
        } else {
            logSecurity("Logo informado não encontrado: " . ($logoPathRaw ?? ''));
        }
    }

    // nome da instituição
    if ($nomeInst) {
        $pdf->SetXY($startX, $y);
        $pdf->Cell($textW, $LOGO_SIZE_MM, $text, 0, 1, 'L');
    }

    // Título do relatório — menor para economizar espaço
    $pdf->Ln(3);
    $pdf->SetFont('Arial','B',13);
    $pdf->Cell(0, 7, iconv('UTF-8','ISO-8859-1','Relatório Geral de Professores'), 0, 1, 'L');
    $pdf->Ln(1);

    // ----------------------------------------------------------------
    // 4) Dados do Professor – Tabela (fontes menores p/ caber 1 página)
    // ----------------------------------------------------------------
    $pdf->SetFont('Arial','B',10);
    $pdf->SetFillColor(200,200,200);
    $pdf->Cell(80, 8, iconv('UTF-8','ISO-8859-1','Nome Completo'), 1, 0, 'C', true);
    $pdf->Cell(35, 8, iconv('UTF-8','ISO-8859-1','Nome Exibição'), 1, 0, 'C', true);
    $pdf->Cell(45, 8, iconv('UTF-8','ISO-8859-1','Telefone'),      1, 0, 'C', true);
    $pdf->Cell(30, 8, iconv('UTF-8','ISO-8859-1','Sexo'),          1, 1, 'C', true);

    $pdf->SetFont('Arial','',10);
    $pdf->Cell(80, 8, iconv('UTF-8','ISO-8859-1', $prof['nome_completo'] ?? ''), 1, 0, 'C');
    $pdf->Cell(35, 8, iconv('UTF-8','ISO-8859-1', $prof['nome_exibicao'] ?? ''), 1, 0, 'C');
    $pdf->Cell(45, 8, iconv('UTF-8','ISO-8859-1', $prof['telefone'] ?? ''),      1, 0, 'C');
    $pdf->Cell(30, 8, iconv('UTF-8','ISO-8859-1', $prof['sexo'] ?? ''),          1, 1, 'C');

    $id_professor = (int)$prof['id_professor'];

    // Margens verticais mais enxutas entre seções
    $gapSection = 6;

    // ----------------------------------------------------------------
    // 5) Restrições (se marcado e se existir $id_ano)
    // ----------------------------------------------------------------
    if ($exibeRestr && $id_ano) {
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
            $dia = $r['dia_semana'];
            if (!isset($restricoesMap[$dia])) $restricoesMap[$dia] = [];
            $restricoesMap[$dia][] = (int)$r['numero_aula'];
        }

        $pdf->Ln($gapSection);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,7, iconv('UTF-8','ISO-8859-1','Restrições está marcado em vermelho'),0,1,'L');

        $pdf->SetFont('Arial','B',9);
        $pdf->SetFillColor(200,200,200);
        $pdf->Cell(30, 7, iconv('UTF-8','ISO-8859-1','Aula/Dia'), 1, 0, 'C', true);
        $diasUteis = ['Segunda','Terça','Quarta','Quinta','Sexta'];
        foreach ($diasUteis as $dia) {
            $pdf->Cell(32,7, iconv('UTF-8','ISO-8859-1',$dia), 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('Arial','',9);
        $maxAulas = 6;
        for ($aula = 1; $aula <= $maxAulas; $aula++) {
            $pdf->Cell(30, 7, $aula.iconv('UTF-8','ISO-8859-1','ª Aula'), 1, 0, 'C');
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
    // 6) Turmas (se marcado e se existir $id_ano)
    // ----------------------------------------------------------------
    if ($exibeTurmas && $id_ano) {
        $pdf->Ln($gapSection);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,7, iconv('UTF-8','ISO-8859-1','Turmas'), 0, 1, 'L');

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

            $pdf->SetFont('Arial','B',9);
            $pdf->SetFillColor(200,200,200);
            $pdf->Cell(30,7,'Ano',1,0,'C',true);
            $pdf->Cell(60,7,iconv('UTF-8','ISO-8859-1','Nível de Ensino'),1,0,'C',true);
            $pdf->Cell(100,7,iconv('UTF-8','ISO-8859-1','Séries e Turmas'),1,1,'C',true);

            $pdf->SetFont('Arial','',9);
            foreach ($agrupado as $group) {
                $ano   = $group['ano'];
                $nivel = $group['nivel'];
                $series= $group['series'];

                $stText = '';
                foreach ($series as $serieData) {
                    $stText .= iconv('UTF-8','ISO-8859-1',$serieData['nome_serie'])
                              .' - '
                              .iconv('UTF-8','ISO-8859-1',$serieData['turmas']) . "\n";
                }
                $stText = rtrim($stText,"\n");
                $lineCount = substr_count($stText,"\n") + 1;
                $rowHeight = 6 * $lineCount; // linha mais compacta

                $pdf->Cell(30,$rowHeight,iconv('UTF-8','ISO-8859-1',$ano),1,0,'C');
                $pdf->Cell(60,$rowHeight,iconv('UTF-8','ISO-8859-1',$nivel),1,0,'C');
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->MultiCell(100,6,$stText,1,'C');
            }
        } else {
            $pdf->SetFont('Arial','I',10);
            $pdf->Cell(0,8,'Nenhuma turma encontrada para o ano selecionado.',0,1,'C');
        }
    }

    // ----------------------------------------------------------------
    // 7) Disciplinas (se marcado e se existir $id_ano)
    // ----------------------------------------------------------------
    if ($exibeDisc && $id_ano) {
        $pdf->Ln($gapSection);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,7,iconv('UTF-8','ISO-8859-1','Disciplinas'),0,1,'L');

        $stmtDisciplina = $pdo->prepare("
            SELECT a.ano,
                   n.nome_nivel_ensino,
                   s.nome_serie,
                   t.nome_turma,
                   d.nome_disciplina
              FROM professor_disciplinas_turmas pdt
              JOIN turma t        ON pdt.id_turma = t.id_turma
              JOIN serie s        ON t.id_serie = s.id_serie
              JOIN ano_letivo a   ON t.id_ano_letivo = a.id_ano_letivo
              JOIN nivel_ensino n ON s.id_nivel_ensino = n.id_nivel_ensino
              JOIN professor_disciplinas pd 
                   ON pdt.id_professor = pd.id_professor 
                  AND pd.id_disciplina = pdt.id_disciplina
              JOIN disciplina d   ON pd.id_disciplina = d.id_disciplina
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
            $pdf->Cell(30,7,'Ano',1,0,'C',true);
            $pdf->Cell(50,7,iconv('UTF-8','ISO-8859-1','Nível de Ensino'),1,0,'C',true);
            $pdf->Cell(60,7,iconv('UTF-8','ISO-8859-1','Série e Turmas'),1,0,'C',true);
            $pdf->Cell(50,7,iconv('UTF-8','ISO-8859-1','Disciplina'),1,1,'C',true);

            $pdf->SetFont('Arial','',9);
            foreach ($agrupado as $grp) {
                $ano        = $grp['ano'];
                $nivel      = $grp['nivel'];
                $disciplina = $grp['disciplina'];
                $listaST    = $grp['seriesTurmas'];

                // Agrupa turmas por série
                $serieTurmaMap = [];
                foreach ($listaST as $st) {
                    $nome_serie = $st['serie'];
                    if (!isset($serieTurmaMap[$nome_serie])) $serieTurmaMap[$nome_serie] = [];
                    $serieTurmaMap[$nome_serie][] = $st['turma'];
                }

                $stText = '';
                foreach ($serieTurmaMap as $serie => $arrT) {
                    $stText .= iconv('UTF-8','ISO-8859-1',$serie) . ' ' . implode(', ', $arrT) . "\n";
                }
                $stText = rtrim($stText,"\n");
                $lineCount  = substr_count($stText,"\n") + 1;
                $cellHeight = 6 * $lineCount; // compacto

                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->Cell(30,$cellHeight, iconv('UTF-8','ISO-8859-1',$ano),   1, 0, 'C');
                $pdf->Cell(50,$cellHeight, iconv('UTF-8','ISO-8859-1',$nivel), 1, 0, 'C');
                $pdf->SetXY($x+30+50,$y);
                $pdf->MultiCell(60,6,$stText,1,'C');
                $pdf->SetXY($x+30+50+60,$y);
                $pdf->Cell(50,$cellHeight, iconv('UTF-8','ISO-8859-1',$disciplina), 1, 1, 'C');
            }
        } else {
            $pdf->SetFont('Arial','I',10);
            $pdf->Cell(0,8,'Nenhuma disciplina encontrada.',0,1,'C');
        }
    }

    // ----------------------------------------------------------------
    // 8) Turnos (se marcado)
    // ----------------------------------------------------------------
    if ($exibeTurnos) {
        $pdf->Ln($gapSection);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,7,iconv('UTF-8','ISO-8859-1','Turnos'),0,1,'L');

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
            $cellHeight  = 8;
            $pdf->SetFont('Arial','',10);

            foreach ($turnosList as $t) {
                $pdf->Cell($cellWidth, $cellHeight, iconv('UTF-8','ISO-8859-1',$t['nome_turno'] ?? ''), 1, 0, 'C');
            }
            $pdf->Ln();
        } else {
            $pdf->SetFont('Arial','I',10);
            $pdf->Cell(0,8,'Nenhum turno vinculado.',1,1,'C');
        }
    }

    // ----------------------------------------------------------------
    // 9) Horários (se marcado e se existir $id_ano)
    // ----------------------------------------------------------------
    if ($exibeHorarios && $id_ano) {
        // Ano letivo
        $stmtAno = $pdo->prepare("SELECT ano FROM ano_letivo WHERE id_ano_letivo = :id_ano LIMIT 1");
        $stmtAno->execute([':id_ano'=>$id_ano]);
        $anoLetivoRow = $stmtAno->fetch(PDO::FETCH_ASSOC);
        $anoLetivo = $anoLetivoRow ? $anoLetivoRow['ano'] : '';

        // Horários
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
            WHERE h.id_professor = :idprof
              AND a.id_ano_letivo = :idano
            ORDER BY 
              FIELD(h.dia_semana,'Segunda','Terça','Quarta','Quinta','Sexta','Sabado','Domingo'),
              h.numero_aula
        ");
        $stmtHorarios->execute([':idprof'=>$id_professor, ':idano'=>$id_ano]);
        $horariosData = $stmtHorarios->fetchAll(PDO::FETCH_ASSOC);

        if (count($horariosData) === 0) {
            $pdf->Ln($gapSection);
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(190, 8, iconv('UTF-8','ISO-8859-1','Nenhuma aula foi encontrada'), 1, 1, 'C');
        } else {
            $dias = ['Segunda','Terça','Quarta','Quinta','Sexta'];
            $maxAulas = 6;

            $matrix = [];
            for ($aula = 1; $aula <= $maxAulas; $aula++) {
                foreach ($dias as $dia) $matrix[$aula][$dia] = "";
            }

            foreach ($horariosData as $rowH) {
                $dia        = $rowH['dia_semana'];
                $aula       = (int)$rowH['numero_aula'];
                $serieTurma = $rowH['nome_serie'].' '.$rowH['nome_turma'];
                $disciplina = $rowH['nome_disciplina'];
                $text = iconv('UTF-8','ISO-8859-1',$serieTurma."\n".$disciplina);
                if (isset($matrix[$aula][$dia])) {
                    $matrix[$aula][$dia] = $matrix[$aula][$dia]
                        ? ($matrix[$aula][$dia]."\n-----------\n".$text)
                        : $text;
                }
            }

            $pdf->Ln($gapSection);
            $pdf->SetFont('Arial','B',12);
            $tituloAno = $anoLetivo ? "Ano Letivo de ".$anoLetivo : "Horários";
            $pdf->Cell(190,8,iconv('UTF-8','ISO-8859-1',$tituloAno),1,1,'C');

            // Cabeçalho
            $pdf->SetFont('Arial','B',9);
            $pdf->Cell(30,6,iconv('UTF-8','ISO-8859-1','Aula/Dia'),1,0,'C',true);
            foreach ($dias as $dia) {
                $pdf->Cell(32,6,iconv('UTF-8','ISO-8859-1',$dia),1,0,'C',true);
            }
            $pdf->Ln();

            // Conteúdo compacto
            $pdf->SetFont('Arial','',8);
            for ($aula = 1; $aula <= $maxAulas; $aula++) {
                // calcula altura da linha
                $maxRowHeight = 6; // altura mínima
                foreach ($dias as $dia) {
                    $lines  = substr_count($matrix[$aula][$dia], "\n") + 1;
                    $height = 4.5 * $lines; // linha mais compacta
                    if ($height > $maxRowHeight) $maxRowHeight = $height;
                }

                // 1ª coluna
                $pdf->Cell(30, $maxRowHeight, $aula.iconv('UTF-8','ISO-8859-1','ª Aula'),1,0,'C');

                // Demais colunas
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
                $pdf->Ln(2); // espacinho entre linhas
            }
        }
    }
}

// ----------------------------------------------------------------
// 10) Finaliza e envia ao browser
// ----------------------------------------------------------------
$pdf->Output();
exit;
?>
