<?php
// app/views/historico-horarios-retrato.php
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

// ---------------- Segurança e logging ----------------
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
 * Aborta a requisição e retorna mensagem de erro em texto puro.
 * Remove buffers de saída para evitar que o FPDF seja enviado antes.
 */
function abortClient($msg = 'Parâmetros inválidos') {
    // limpar buffers de saída se existirem (evita pdf misturado com texto)
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit;
}

// Parâmetros do cabeçalho (como no turma.php)
$LOGO_SIZE_MM = 15; // tamanho (largura=altura) da logo em mm
$LOGO_GAP_MM  = 5;  // espaço entre a logo e o nome em mm

// ----------------------------------------------------------------
// Validação reforçada da query string
// - data_arquivamento é validada separadamente para evitar mismatch por codificação.
// ----------------------------------------------------------------
$rules = [
    'id_turma'         => ['required' => true,  'validator' => function($v){ return filter_var($v, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) !== false; }],
    'id_ano_letivo'    => ['required' => true,  'validator' => function($v){ return filter_var($v, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) !== false; }],
    'id_nivel_ensino'  => ['required' => false, 'validator' => function($v){ return filter_var($v, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]) !== false; }],
    'id_turno'         => ['required' => false, 'validator' => function($v){ return filter_var($v, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]) !== false; }],
    // data_arquivamento intentionally excluded from canonical rules
];

// parse raw query
$rawQuery = $_SERVER['QUERY_STRING'] ?? '';
parse_str($rawQuery, $receivedParams);

// permitir data_arquivamento mas detectar parâmetros inesperados
$allowedKeys = array_merge(array_keys($rules), ['data_arquivamento']);
$receivedKeys = array_keys($receivedParams);
$extra = array_diff($receivedKeys, $allowedKeys);
if (!empty($extra)) {
    logSecurity('Parâmetros inesperados em historico-horarios-retrato: '.implode(', ',$extra).' | raw_qs='.$rawQuery);
    abortClient();
}

// validar e normalizar os parâmetros que entram na canonicalização
$canonical = [];
foreach ($rules as $param => $info) {
    if (!array_key_exists($param, $receivedParams)) {
        if ($info['required']) {
            logSecurity("Parâmetro obrigatório ausente: {$param} | raw_qs={$rawQuery}");
            abortClient();
        }
        continue;
    }
    $val = $receivedParams[$param];
    $ok = false;
    try {
        $ok = (bool)call_user_func($info['validator'], $val);
    } catch (Throwable $e) {
        $ok = false;
    }
    if (!$ok) {
        logSecurity("Valor inválido para '{$param}': raw=" . ($receivedParams[$param] ?? '') . " | raw_qs={$rawQuery}");
        abortClient();
    }
    // normalização: inteiros como int
    if (strpos($param, 'id_') === 0) {
        $canonical[$param] = (int)$val;
    } else {
        $canonical[$param] = (string)$val;
    }
}

// construir query canônica apenas com os parâmetros permitidos (sem data_arquivamento)
if (!empty($canonical)) {
    ksort($canonical);
    $canonical_qs = http_build_query($canonical, '', '&', PHP_QUERY_RFC3986);
} else {
    $canonical_qs = '';
}

// Comparar com a query string recebida, ignorando apenas o trecho de data_arquivamento.
$raw_to_compare = $rawQuery;
if (isset($receivedParams['data_arquivamento'])) {
    $raw_to_compare = preg_replace('/(?:^|&)' . preg_quote('data_arquivamento', '/') . '=[^&]*/', '', $raw_to_compare, 1);
    $raw_to_compare = trim($raw_to_compare, '&');
}

// comparação estrita
if ($canonical_qs !== ($raw_to_compare === null ? '' : $raw_to_compare)) {
    logSecurity("Query string (sem data_arquivamento) não corresponde ao formato canônico: expected='{$canonical_qs}' got='{$raw_to_compare}' | full_raw='{$rawQuery}'");
    abortClient();
}

// ----------------------------------------------------------------
// Validar data_arquivamento separadamente (aceita Y-m-d H:i:s ou Y-m-d H:i)
// ----------------------------------------------------------------
$dataArquivamento_raw = isset($receivedParams['data_arquivamento']) ? $receivedParams['data_arquivamento'] : null;
if (is_string($dataArquivamento_raw)) {
    // parse_str já converte + em espaço, mas garanta substituição de '+' literal
    $dataArquivamento_raw = str_replace('+', ' ', $dataArquivamento_raw);
}
if ($dataArquivamento_raw === null || $dataArquivamento_raw === '') {
    abortClient('Data de arquivamento não informada.');
}
$d = DateTime::createFromFormat('Y-m-d H:i:s', $dataArquivamento_raw);
if (!($d && $d->format('Y-m-d H:i:s') === $dataArquivamento_raw)) {
    $d2 = DateTime::createFromFormat('Y-m-d H:i', $dataArquivamento_raw);
    if ($d2) {
        $dataArquivamento_raw = $d2->format('Y-m-d H:i:00');
    } else {
        logSecurity("Formato inválido para data_arquivamento: raw=" . ($receivedParams['data_arquivamento'] ?? '') . " | raw_qs={$rawQuery}");
        abortClient('Data de arquivamento não informada.');
    }
}
$dataArquivamento = $dataArquivamento_raw;

// ----------------------------------------------------------------
// Atribuir variáveis seguras
// ----------------------------------------------------------------
$id_turma         = isset($canonical['id_turma'])        ? (int)$canonical['id_turma']        : 0;
$id_ano_letivo    = isset($canonical['id_ano_letivo'])   ? (int)$canonical['id_ano_letivo']   : 0;
$id_nivel_ensino  = isset($canonical['id_nivel_ensino']) ? (int)$canonical['id_nivel_ensino'] : 0;
$id_turno         = isset($canonical['id_turno'])        ? (int)$canonical['id_turno']        : 0;

// Validações adicionais do original (mensagens originais preservadas para certos casos)
if ($id_turma <= 0 || $id_ano_letivo <= 0) {
    // manter mensagem original curta, mas retornar como texto claro
    abortClient('Turma ou Ano Letivo não informado.');
}
if (!$dataArquivamento) {
    abortClient('Data de arquivamento não informada.');
}

// ----------------------------------------------------------------
// Mapeamento dos Dias
// ----------------------------------------------------------------
$diaMap = [
    'Domingo' => 'Domingo',
    'Segunda' => 'Segunda',
    'Terca'   => 'Terça',
    'Quarta'  => 'Quarta',
    'Quinta'  => 'Quinta',
    'Sexta'   => 'Sexta',
    'Sabado'  => 'Sábado'
];

$orientation = 'P'; // mantido conforme seu arquivo

// ----------------------------------------------------------------
// Classe PDF com cabeçalho
// ----------------------------------------------------------------
class PDFHistoricoHorariosRetrato extends FPDF
{
    public function Header()
    {
        global $pdo, $turmaInfo, $LOGO_SIZE_MM, $LOGO_GAP_MM, $dataArquivamento;

        $stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
        $inst = $stmtInst->fetch(PDO::FETCH_ASSOC);

        $nomeInst = $inst ? ($inst['nome_instituicao'] ?? '') : '';
        $logoPath = ($inst && !empty($inst['imagem_instituicao'])) ? LOGO_PATH . '/' . basename($inst['imagem_instituicao']) : null;

        $this->SetY(12);
        $this->SetFont('Arial','B',14);
        $txt  = iconv('UTF-8','ISO-8859-1', $nomeInst);
        $txtW = $this->GetStringWidth($txt);
        $hasLogo = ($logoPath && file_exists($logoPath));
        $totalW  = $hasLogo ? ($LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0) + $txtW) : $txtW;
        $pageW   = $this->GetPageWidth();
        $x       = ($pageW - $totalW) / 2;
        $y       = $this->GetY();

        if ($hasLogo) {
            $this->Image($logoPath, $x, $y - 2, $LOGO_SIZE_MM, $LOGO_SIZE_MM);
            $x += $LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0);
        }
        if ($nomeInst) {
            $this->SetXY($x, $y);
            $this->Cell($txtW, $LOGO_SIZE_MM, $txt, 0, 1, 'L');
        }
        $this->Ln(3);
        $this->SetFont('Arial','B',13);
        $this->Cell(0,7, iconv('UTF-8','ISO-8859-1','Histórico de Horário Arquivado'), 0,1,'C');
        $this->Ln(1);

        if (!empty($turmaInfo)) {
            $titulo = 'Turma ' . $turmaInfo['nome_serie'] . ' ' . $turmaInfo['nome_turma'] . ' em: ' . $dataArquivamento;
            $this->SetFont('Arial','B',12);
            $this->Cell(0,7, iconv('UTF-8','ISO-8859-1', $titulo), 0,1,'C');
            $this->Ln(1);

            $subtitulo = sprintf('Ano Letivo %s | Turno: %s',
                $turmaInfo['ano'], $turmaInfo['nome_turno']);
            $this->SetFont('Arial','',11);
            $this->Cell(0,7, iconv('UTF-8','ISO-8859-1', $subtitulo), 0,1,'C');
            $this->Ln(3);
        }
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10, iconv('UTF-8','ISO-8859-1','Página ' . $this->PageNo()), 0, 0, 'L');
        $this->Cell(0,10, iconv('UTF-8','ISO-8859-1','Impresso em: ' . date('d/m/Y H:i:s')), 0, 0, 'R');
    }
}

// ----------------------------------------------------------------
// Buscar Informações da Turma para o cabeçalho
// ----------------------------------------------------------------
$stmtTurmaInfo = $pdo->prepare("
    SELECT t.nome_turma,
           s.nome_serie,
           n.nome_nivel_ensino,
           a.ano,
           tur.nome_turno
      FROM turma t
      JOIN serie s       ON t.id_serie      = s.id_serie
      JOIN nivel_ensino n ON s.id_nivel_ensino= n.id_nivel_ensino
      JOIN ano_letivo a   ON t.id_ano_letivo  = a.id_ano_letivo
      JOIN turno tur      ON t.id_turno     = tur.id_turno
     WHERE t.id_turma = :tid
     LIMIT 1
");
$stmtTurmaInfo->execute([':tid' => $id_turma]);
$turmaInfo = $stmtTurmaInfo->fetch(PDO::FETCH_ASSOC);

// Se turma não encontrada -> retornar texto de erro (não PDF)
if (!$turmaInfo) {
    logSecurity("Turma não encontrada: id_turma={$id_turma}");
    abortClient('Parâmetros inválidos');
}

// ----------------------------------------------------------------
// Buscar Horários registrados no arquivamento
// ----------------------------------------------------------------
$stmtHist = $pdo->prepare("
    SELECT hh.dia_semana,
           hh.numero_aula,
           d.nome_disciplina,
           p.nome_exibicao AS nome_professor
      FROM historico_horario hh
      JOIN disciplina d ON hh.id_disciplina = d.id_disciplina
      JOIN professor  p ON hh.id_professor  = p.id_professor
     WHERE hh.id_turma = :tid
       AND DATE_FORMAT(hh.data_arquivamento, '%Y-%m-%d %H:%i:%s') = :data_arch
     ORDER BY FIELD(hh.dia_semana,'Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado'),
              hh.numero_aula
");
$stmtHist->execute([
    ':tid'       => $id_turma,
    ':data_arch' => $dataArquivamento
]);
$horarios = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

// Se nenhum horário encontrado -> retornar texto de erro (não PDF)
if (!$horarios) {
    logSecurity("Nenhum horário encontrado para id_turma={$id_turma} data={$dataArquivamento}");
    abortClient('Parâmetros inválidos');
}

// ----------------------------------------------------------------
// Montar o PDF (todo o restante do fluxo permanece igual)
// ----------------------------------------------------------------
$pdf = new PDFHistoricoHorariosRetrato($orientation, 'mm', 'A4');
$pdf->SetTitle('Histórico de Horários', true);
$pdf->AddPage();

// Construir a matriz [dia_semana][numero_aula] => "Disciplina\nProfessor"
$matrix = [];
foreach ($horarios as $h) {
	$dia	 = $h['dia_semana'];
	$numAula = (int)$h['numero_aula'];
	$txt	 = $h['nome_disciplina']."\n".$h['nome_professor'];

	if (!isset($matrix[$dia])) {
		$matrix[$dia] = [];
	}
	$matrix[$dia][$numAula] = $txt;
}

// Descobrir quais dias a turma tem configurado (turno_dias)
$stmtTurno = $pdo->prepare("SELECT id_turno FROM turma WHERE id_turma = :tid LIMIT 1");
$stmtTurno->execute([':tid' => $id_turma]);
$turnoRow = $stmtTurno->fetch(PDO::FETCH_ASSOC);

$diasRelatorio = [];
$maxPorDia = [];

if ($turnoRow) {
	$stmtDias = $pdo->prepare("
		SELECT dia_semana, aulas_no_dia
		  FROM turno_dias
		 WHERE id_turno = :turno
		   AND aulas_no_dia > 0
		 ORDER BY FIELD(dia_semana,'Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado')
	");
	$stmtDias->execute([':turno' => $turnoRow['id_turno']]);
	$diasTurno = $stmtDias->fetchAll(PDO::FETCH_ASSOC);

	if ($diasTurno) {
		foreach ($diasTurno as $dt) {
			$diasRelatorio[] = $dt['dia_semana'];
			$maxPorDia[$dt['dia_semana']] = (int)$dt['aulas_no_dia'];
		}
	}
}

// Se não tiver config, adota um padrão
if (empty($diasRelatorio)) {
	$diasRelatorio = ['Segunda','Terca','Quarta','Quinta','Sexta'];
	foreach ($diasRelatorio as $dia) {
		$maxPorDia[$dia] = 8;
	}
}

$maxAulas = max($maxPorDia);

// Montar a tabela
$margin  = 10;
$pageW   = $pdf->GetPageWidth();
$usableW = $pageW - (2 * $margin);

$colAulaW  = 30; 
$daysCount = count($diasRelatorio);
$colDiaW   = ($usableW - $colAulaW) / $daysCount;

// Cabeçalho da tabela
imprimirCabecalho($pdf, $colAulaW, $colDiaW, $diasRelatorio, $diaMap);

$pdf->SetFont('Arial','',10);

for ($a = 1; $a <= $maxAulas; $a++) {
	$diaTexts = [];
	$maxLines = 1;
	foreach ($diasRelatorio as $dia) {
		if ($a <= $maxPorDia[$dia]) {
			$txt = isset($matrix[$dia][$a]) ? $matrix[$dia][$a] : '';
		} else {
			$txt = '';
		}
		$diaTexts[$dia] = $txt;
		if ($txt !== '') {
			$n = count(explode("\n", $txt));
			if ($n > $maxLines) {
				$maxLines = $n;
			}
		}
	}

	$lineHeight = 6;
	$rowH	   = $maxLines * $lineHeight;
	if ($rowH < 12) {
		$rowH = 12;
	}

	// Verifica quebra de página
	if ($pdf->GetY() + $rowH > ($pdf->GetPageHeight() - 20)) {
		$pdf->AddPage();
		// Reimprime cabeçalho (se necessário)
		imprimirCabecalho($pdf, $colAulaW, $colDiaW, $diasRelatorio, $diaMap);
		$pdf->SetFont('Arial','',10);
	}

	// Primeira célula: Xª Aula
	$pdf->SetFont('Arial','B',10);
	$pdf->Cell($colAulaW, $rowH, mb_convert_encoding($a.'ª Aula','ISO-8859-1','UTF-8'), 1, 0, 'C');

	// Demais dias
	$pdf->SetFont('Arial','',10);
	foreach ($diasRelatorio as $dia) {
		$x = $pdf->GetX();
		$y = $pdf->GetY();
		$pdf->Cell($colDiaW, $rowH, '', 1, 0, 'C');

		$texto = $diaTexts[$dia];
		if ($texto !== '') {
			$lines = explode("\n", $texto);
			$blockH = count($lines) * $lineHeight;
			$startY = $y + ($rowH - $blockH)/2;
			$pdf->SetXY($x, $startY);
			foreach ($lines as $ln) {
				$ln = mb_convert_encoding($ln,'ISO-8859-1','UTF-8');
				$pdf->Cell($colDiaW, $lineHeight, $ln, 0, 2, 'C');
			}
			$pdf->SetXY($x + $colDiaW, $y);
		}
	}

	$pdf->Ln($rowH);
}

$pdf->Output('I','HistoricoHorariosRetrato.pdf');
exit;

// ----------------------------------------------------------------
// Função de cabeçalho da tabela
// ----------------------------------------------------------------
function imprimirCabecalho(
	PDFHistoricoHorariosRetrato $pdf,
	float $colAulaW,
	float $colDiaW,
	array $diasRelatorio,
	array $diaMap
) {
	$pdf->SetFont('Arial','B',12);
	$pdf->SetFillColor(200,200,200);

	$pdf->Cell($colAulaW, 10, mb_convert_encoding('Aula / Dia','ISO-8859-1','UTF-8'), 1, 0, 'C', true);
	foreach ($diasRelatorio as $dia) {
		$display = isset($diaMap[$dia]) ? $diaMap[$dia] : $dia;
		$pdf->Cell($colDiaW, 10, mb_convert_encoding($display,'ISO-8859-1','UTF-8'), 1, 0, 'C', true);
	}
	$pdf->Ln();
}
?>
