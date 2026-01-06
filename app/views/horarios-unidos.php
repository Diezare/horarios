<?php
// app/views/horarios-unidos.php
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
 * Aborta com resposta padronizada ao cliente.
 */
function abortClient($msg = 'Parâmetros inválidos') {
    while (ob_get_level() > 0) @ob_end_clean();
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit;
}

/* -----------------------------
   Funções utilitárias para conversão segura
------------------------------*/
/**
 * Converte texto UTF-8 para ISO-8859-1 de forma tolerante.
 * Tenta iconv com TRANSLIT+IGNORE, depois tenta limpar/forçar UTF-8,
 * por fim usa utf8_decode como último recurso. Registra string crua em caso de falha.
 */
function safeToIso(string $s): string {
    // garantia string
    $s = (string)$s;

    // 1) se parece UTF-8 válido, tente iconv com translit+ignore
    if (function_exists('mb_check_encoding') && mb_check_encoding($s, 'UTF-8')) {
        $out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $s);
        if ($out !== false) return $out;
    }

    // 2) tenta normalizar (remover bytes inválidos) e reconverter
    $clean = @mb_convert_encoding($s, 'UTF-8', 'UTF-8'); // remove sequences inválidas
    $out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $clean);
    if ($out !== false) return $out;

    // 3) tenta como se fosse Windows-1252 (comum em uploads)
    $try1252 = @mb_convert_encoding($s, 'UTF-8', 'Windows-1252');
    $out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $try1252);
    if ($out !== false) return $out;

    // 4) fallback simples: utf8_decode (pode perder acentos)
    $out = @utf8_decode($s);
    if ($out !== false && $out !== null) return $out;

    // 5) último recurso: remove bytes non-printable e retorna ASCII
    $plain = preg_replace('/[^\x09\x0A\x0D\x20-\x7E\xA0-\xFF]/', '', $s);
    logSecurity('safeToIso: conversão falhou, retornando fallback. raw_sample=' . substr($s,0,200));
    return $plain !== null ? $plain : '';
}

/* -----------------------------
   PARÂMETROS ESPERADOS
   Só aceita: id_ano_letivo, id_nivel_ensino, id_turno
------------------------------*/
$allowed = ['id_ano_letivo', 'id_nivel_ensino', 'id_turno'];
parse_str($_SERVER['QUERY_STRING'] ?? '', $receivedParams);

// rejeita parâmetros inesperados
$extra = array_diff(array_keys($receivedParams), $allowed);
if (!empty($extra)) {
    logSecurity('Parâmetros inesperados em horarios-unidos.php: ' . implode(', ', $extra));
    abortClient();
}

// normaliza recebidos e valida escalaridade
$canonical = [];
foreach ($allowed as $k) {
    if (isset($receivedParams[$k])) {
        if (!is_scalar($receivedParams[$k])) {
            logSecurity("Parâmetro não escalar {$k} em horarios-unidos.php");
            abortClient();
        }
        // aceitar somente inteiros na query (sem sinal)
        $s = (string)$receivedParams[$k];
        if ($s === '') {
            $canonical[$k] = 0;
        } else {
            if (!preg_match('/^\d+$/', $s)) {
                logSecurity("Valor inválido para {$k} em horarios-unidos.php raw=" . var_export($receivedParams[$k], true));
                abortClient();
            }
            $canonical[$k] = (int)$s;
        }
    } else {
        $canonical[$k] = 0;
    }
}

// agora atribui parâmetros (canônicos)
$id_ano_letivo   = $canonical['id_ano_letivo'];
$id_nivel_ensino = $canonical['id_nivel_ensino'];
$id_turno        = $canonical['id_turno'];

// valida existência de ano letivo (obrigatório)
if ($id_ano_letivo <= 0) {
    logSecurity("Ano letivo não informado em horarios-unidos.php");
    abortClient('Parâmetros inválidos');
}
$stAno = $pdo->prepare("SELECT ano FROM ano_letivo WHERE id_ano_letivo = :id LIMIT 1");
$stAno->execute([':id' => $id_ano_letivo]);
$anoLegivel = $stAno->fetchColumn();
if (!$anoLegivel) {
    logSecurity("Ano letivo inexistente: id={$id_ano_letivo}");
    abortClient('Parâmetros inválidos');
}

// valida opcional nível de ensino
if ($id_nivel_ensino > 0) {
    $stN = $pdo->prepare("SELECT 1 FROM nivel_ensino WHERE id_nivel_ensino = :id LIMIT 1");
    $stN->execute([':id' => $id_nivel_ensino]);
    if ($stN->fetchColumn() === false) {
        logSecurity("Nivel de ensino inexistente: id={$id_nivel_ensino}");
        abortClient('Parâmetros inválidos');
    }
}

// valida opcional turno
if ($id_turno > 0) {
    $stT = $pdo->prepare("SELECT 1 FROM turno WHERE id_turno = :id LIMIT 1");
    $stT->execute([':id' => $id_turno]);
    if ($stT->fetchColumn() === false) {
        logSecurity("Turno inexistente: id={$id_turno}");
        abortClient('Parâmetros inválidos');
    }
}

/* -----------------------------
   Cabeçalho padrão (mesma linha)
------------------------------*/
$LOGO_SIZE_MM = 15;
$LOGO_GAP_MM  = 5;

class PDFHorariosUnidos extends FPDF
{
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0, 10, safeToIso('Página ' . $this->PageNo()), 0, 0, 'L');
        $this->Cell(0, 10, safeToIso('Impresso em: ' . date('d/m/Y H:i:s')), 0, 0, 'R');
    }
}

/* -----------------------------
   Mapeamentos dias
------------------------------*/
$diaParaSigla = [
    'Domingo' => 'DOM',
    'Segunda' => 'SEG',
    'Terca'   => 'TER',
    'Quarta'  => 'QUA',
    'Quinta'  => 'QUI',
    'Sexta'   => 'SEX',
    'Sabado'  => 'SÁB'
];
$siglaVertical = [
    'DOM' => "D\nO\nM\nI\nN\nG\nO",
    'SEG' => "S\nE\nG\nU\nN\nD\nA",
    'TER' => "T\nE\nR\nÇ\nA",
    'QUA' => "Q\nU\nA\nR\nT\nA",
    'QUI' => "Q\nU\nI\nN\nT\nA",
    'SEX' => "S\nE\nX\nT\nA",
    'SÁB' => "S\nÁ\nB\nA\nD\nO"
];

/* -----------------------------
   Funções de render (usam safeToIso)
------------------------------*/
function renderCabecalhoPadrao(
    FPDF $pdf,
    string $nomeInst,
    ?string $logoPath,
    string $anoLetivo,
    string $nomeNivel,
    string $nomeTurno,
    int $LOGO_SIZE_MM,
    int $LOGO_GAP_MM,
    ?string $logoRaw = null
) {
    $pdf->SetY(12);
    $pdf->SetFont('Arial','B',14);
    $txt  = mb_substr($nomeInst, 0, 200);
    $txtEnc = safeToIso($txt);
    $txtW = $pdf->GetStringWidth($txtEnc);

    $hasLogo = ($logoPath && file_exists($logoPath));
    $totalW  = $hasLogo ? ($LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0) + $txtW) : $txtW;
    $pageW   = $pdf->GetPageWidth();
    $x       = ($pageW - $totalW) / 2;
    $y       = $pdf->GetY();

    if ($hasLogo) {
        // proteção path-traversal
        $candidate = basename($logoPath);
        $fullLogo = LOGO_PATH . '/' . $candidate;
        if (file_exists($fullLogo) && is_file($fullLogo)) {
            $realLogo = realpath($fullLogo);
            $realLogoDir = realpath(LOGO_PATH);
            if ($realLogo !== false && $realLogoDir !== false && strpos($realLogo, $realLogoDir) === 0) {
                $pdf->Image($realLogo, $x, $y - 2, $LOGO_SIZE_MM, $LOGO_SIZE_MM);
                $x += $LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0);
            } else {
                logSecurity("Tentativa de usar logo fora do diretório permitido: " . ($logoRaw ?? ''));
            }
        } else {
            logSecurity("Logo informado não encontrado: " . ($logoRaw ?? ''));
        }
    }

    if (!empty($nomeInst)) {
        $pdf->SetXY($x, $y);
        $pdf->Cell($txtW, $LOGO_SIZE_MM, $txtEnc, 0, 1, 'L');
    }

    $pdf->Ln(3);
    $pdf->SetFont('Arial','B',10);
    $linhaInfo = sprintf(
        'Ano Letivo %s | %s | %s',
        ($anoLetivo ?: '—'),
        ($nomeNivel ?: '—'),
        ($nomeTurno ?: '—')
    );
    $pdf->Cell(0, 7, safeToIso($linhaInfo), 0, 1, 'C');
    $pdf->Ln(2);
}

function desenharTabelaComCabecalho(
    FPDF $pdf,
    array $turmas,
    array $dias,
    array $horariosMap,
    int $maxAulas,
    array $siglaVertical
) {
    // Layout
    $leftMargin   = 10;
    $rightMargin  = 10;
    $usableWidth  = $pdf->GetPageWidth() - ($leftMargin + $rightMargin);

    $colDiaW      = 10;  // Dia vertical
    $colAulaW     = 20;  // "1ª Aula"

    $numTurmas    = max(1, count($turmas));
    $rest         = $usableWidth - ($colDiaW + $colAulaW);
    $colTurmaW    = $rest / $numTurmas;

    // Cabeçalho
    $pdf->SetFont('Arial','B',9);
    $pdf->SetFillColor(220,220,220);

    $pdf->Cell($colDiaW,  8, safeToIso('Dia'), 1,0,'C',true);
    $pdf->Cell($colAulaW, 8, safeToIso('Aula'), 1,0,'C',true);
    foreach ($turmas as $tm) {
        $rot = $tm['nome_serie'].' '.$tm['nome_turma'];
        $pdf->Cell($colTurmaW, 8, safeToIso($rot), 1,0,'C',true);
    }
    $pdf->Ln();
    $pdf->Ln(2);

    // Conteúdo
    $pdf->SetFont('Arial','',8);
    $lineH = 9;

    foreach ($dias as $diaSigla) {
        $verticalText = $siglaVertical[$diaSigla] ?? $diaSigla;
        $heightDia    = $maxAulas * $lineH;
        $xDia         = $pdf->GetX();
        $yDia         = $pdf->GetY();

        $pdf->Rect($xDia, $yDia, $colDiaW, $heightDia);

        $numLines  = count(explode("\n",$verticalText));
        $textTotal = $numLines * 3.5;
        $startY    = $yDia + ($heightDia/2) - ($textTotal/2);
        $pdf->SetXY($xDia, $startY);
        $pdf->MultiCell($colDiaW, 3.5, safeToIso($verticalText), 0,'C');

        $pdf->SetXY($xDia + $colDiaW, $yDia);

        for ($a=1; $a<=$maxAulas; $a++) {
            $labelAula = $a.'ª Aula';
            $pdf->Cell($colAulaW, $lineH, safeToIso($labelAula), 1,0,'C');

            foreach ($turmas as $tm) {
                $tID  = (int)$tm['id_turma'];
                $txt  = $horariosMap[$tID][$diaSigla][$a] ?? '';
                $xCel = $pdf->GetX();
                $yCel = $pdf->GetY();
                $pdf->Cell($colTurmaW, $lineH, '', 1,0);

                if ($txt !== '') {
                    // Limpa quebras/espacos extras e trim
                    $txt = preg_replace('/[ \t]+\n/', "\n", trim($txt));
                    $txt = preg_replace("/\n{2,}/", "\n", $txt);

                    // Altura de linha reduzida para juntar linhas (experimente 3.5 se quiser ainda mais junto)
                    $cellLineH = max(3.0, ($lineH / 2) - 1.0);

                    // Posiciona com menor padding vertical
                    $pdf->SetXY($xCel + 1, $yCel + 0.8);
                    $pdf->MultiCell($colTurmaW - 2, $cellLineH, safeToIso($txt), 0, 'C');
                    $pdf->SetXY($xCel + $colTurmaW, $yCel);
                }

            }
            $pdf->Ln($lineH);
            $pdf->SetX($xDia + $colDiaW);
        }
        $pdf->Ln(2);
    }
}

function desenharDiasSemCabecalho(
    FPDF $pdf,
    array $turmas,
    array $dias,
    array $horariosMap,
    int $maxAulas,
    array $siglaVertical
) {
    // Layout (mesmo cálculo)
    $leftMargin   = 10;
    $rightMargin  = 10;
    $usableWidth  = $pdf->GetPageWidth() - ($leftMargin + $rightMargin);

    $colDiaW      = 10;
    $colAulaW     = 20;

    $numTurmas    = max(1, count($turmas));
    $rest         = $usableWidth - ($colDiaW + $colAulaW);
    $colTurmaW    = $rest / $numTurmas;

    $pdf->Ln(2);
    $pdf->SetFont('Arial','',8);
    $lineH = 9;

    foreach ($dias as $diaSigla) {
        $verticalText = $siglaVertical[$diaSigla] ?? $diaSigla;
        $heightDia    = $maxAulas * $lineH;
        $xDia         = $pdf->GetX();
        $yDia         = $pdf->GetY();

        $pdf->Rect($xDia, $yDia, $colDiaW, $heightDia);

        $numLines  = count(explode("\n",$verticalText));
        $textTotal = $numLines * 3.5;
        $startY    = $yDia + ($heightDia/2) - ($textTotal/2);
        $pdf->SetXY($xDia, $startY);
        $pdf->MultiCell($colDiaW, 3.5, safeToIso($verticalText), 0,'C');

        $pdf->SetXY($xDia + $colDiaW, $yDia);

        for ($a=1; $a<=$maxAulas; $a++) {
            $labelAula = $a.'ª Aula';
            $pdf->Cell($colAulaW, $lineH, safeToIso($labelAula), 1,0,'C');

            foreach ($turmas as $tm) {
                $tID  = (int)$tm['id_turma'];
                $txt  = $horariosMap[$tID][$diaSigla][$a] ?? '';
                $xCel = $pdf->GetX();
                $yCel = $pdf->GetY();
                $pdf->Cell($colTurmaW, $lineH, '', 1,0);

                if ($txt !== '') {
                    // Limpa quebras/espacos extras e trim
                    $txt = preg_replace('/[ \t]+\n/', "\n", trim($txt));
                    $txt = preg_replace("/\n{2,}/", "\n", $txt);

                    // Altura de linha reduzida para juntar linhas
                    $cellLineH = max(3.0, ($lineH / 2) - 1.0);

                    // Posiciona com menor padding vertical
                    $pdf->SetXY($xCel + 1, $yCel + 0.8);
                    $pdf->MultiCell($colTurmaW - 2, $cellLineH, safeToIso($txt), 0, 'C');
                    $pdf->SetXY($xCel + $colTurmaW, $yCel);
                }

            }
            $pdf->Ln($lineH);
            $pdf->SetX($xDia + $colDiaW);
        }
        $pdf->Ln(2);
    }
}

/* -----------------------------
   Função principal: carregar dados e gerar PDF
------------------------------*/
function gerarHorariosUnidos(PDO $pdo, PDFHorariosUnidos $pdf, int $idAno, int $idNiv, int $idTurno, int $LOGO_SIZE_MM, int $LOGO_GAP_MM)
{
    global $diaParaSigla, $siglaVertical;

    // instituição
    $stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
    $inst = $stmtInst->fetch(PDO::FETCH_ASSOC);
    $nomeInst = $inst['nome_instituicao'] ?? '';
    $logoPath = (!empty($inst['imagem_instituicao'])) ? LOGO_PATH . '/' . basename($inst['imagem_instituicao']) : null;
    $logoRaw  = $inst['imagem_instituicao'] ?? null;

    // nomes
    $nomeNivel = '';
    if ($idNiv > 0) {
        $stN = $pdo->prepare("SELECT nome_nivel_ensino FROM nivel_ensino WHERE id_nivel_ensino = :id LIMIT 1");
        $stN->execute([':id' => $idNiv]); // corrigido para $idNiv
        $nomeNivel = $stN->fetchColumn() ?: '';
    }
    $nomeTurno = '';
    if ($idTurno > 0) {
        $stT = $pdo->prepare("SELECT nome_turno FROM turno WHERE id_turno = :id LIMIT 1");
        $stT->execute([':id' => $idTurno]);
        $nomeTurno = $stT->fetchColumn() ?: '';
    }
    $stA = $pdo->prepare("SELECT ano FROM ano_letivo WHERE id_ano_letivo = :id LIMIT 1");
    $stA->execute([':id' => $idAno]);
    $anoLetivo = $stA->fetchColumn() ?: '';

    // turmas
    $sqlTur = "
        SELECT t.id_turma, s.nome_serie, t.nome_turma
          FROM turma t
          JOIN serie s ON t.id_serie = s.id_serie
         WHERE t.id_ano_letivo = ?
    ";
    $params = [$idAno];
    if ($idNiv > 0) {
        $sqlTur .= " AND s.id_nivel_ensino = ? ";
        $params[] = $idNiv;
    }
    if ($idTurno > 0) {
        $sqlTur .= " AND t.id_turno = ? ";
        $params[] = $idTurno;
    }
    $sqlTur .= " ORDER BY s.nome_serie, t.nome_turma";
    $stTur = $pdo->prepare($sqlTur);
    $stTur->execute($params);
    $turmas = $stTur->fetchAll(PDO::FETCH_ASSOC);

    if (!$turmas) {
        // antes: gerava PDF vazio. agora: abort com mensagem padronizada
        $msg = sprintf('Nenhuma turma encontrada para os filtros informados. ano=%d nivel=%d turno=%d', $idAno, $idNiv, $idTurno);
        logSecurity($msg);
        abortClient('Parâmetros inválidos');
    }

    // busca horários em bloco (safe: ids inteiros já validados)
    $arrIds = array_map('intval', array_column($turmas, 'id_turma'));
    $strIds = implode(',', $arrIds);

    $sqlHor = "
        SELECT h.id_turma,
               h.dia_semana,
               h.numero_aula,
               d.nome_disciplina,
               COALESCE(p.nome_exibicao, p.nome_completo) AS prof
          FROM horario h
          JOIN disciplina d ON h.id_disciplina = d.id_disciplina
          JOIN professor  p ON h.id_professor  = p.id_professor
         WHERE h.id_turma IN ($strIds)
         ORDER BY 
           FIELD(h.dia_semana,'Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado'),
           h.numero_aula
    ";
    $rowsHor = $pdo->query($sqlHor)->fetchAll(PDO::FETCH_ASSOC);

    // mapeia [id_turma][SIGLA][numero_aula] => texto
    $horariosMap = [];
    foreach ($rowsHor as $r) {
        $tid = (int)$r['id_turma'];
        $diaCompleto = $r['dia_semana'];
        if (!isset($diaParaSigla[$diaCompleto])) continue;
        $diaSig = $diaParaSigla[$diaCompleto];
        $numAula = (int)$r['numero_aula'];
        $texto   = $r['nome_disciplina'];
        if (!empty($r['prof'])) $texto .= "\n" . $r['prof'];
        $horariosMap[$tid][$diaSig][$numAula] = $texto;
    }

    $maxAulasDia = 6;
    $diasPag1 = ['SEG','TER','QUA','QUI'];
    $diasPag2 = ['SEX'];

    // pagina 1
    $pdf->AddPage('P','A4');
    renderCabecalhoPadrao($pdf, $nomeInst, $logoPath, $anoLetivo, $nomeNivel, $nomeTurno, $LOGO_SIZE_MM, $LOGO_GAP_MM, $logoRaw);
    desenharTabelaComCabecalho($pdf, $turmas, $diasPag1, $horariosMap, $maxAulasDia, $siglaVertical);

    // pagina 2
    $pdf->AddPage('P','A4');
    renderCabecalhoPadrao($pdf, $nomeInst, $logoPath, $anoLetivo, $nomeNivel, $nomeTurno, $LOGO_SIZE_MM, $LOGO_GAP_MM, $logoRaw);
    desenharDiasSemCabecalho($pdf, $turmas, $diasPag2, $horariosMap, $maxAulasDia, $siglaVertical);
}

/* -----------------------------
   Execução
------------------------------*/
$pdf = new PDFHorariosUnidos('P','mm','A4');
$pdf->SetTitle('Horários Unificados', true);

gerarHorariosUnidos($pdo, $pdf, $id_ano_letivo, $id_nivel_ensino, $id_turno, $LOGO_SIZE_MM, $LOGO_GAP_MM);

$pdf->Output('I','HorariosUnidos.pdf');
exit;
?>
