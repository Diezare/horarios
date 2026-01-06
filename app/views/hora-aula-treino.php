<?php
// app/views/hora-aula-treino.php
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

// ---------------- Segurança / logging ----------------
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
 * Garante limpeza de buffers para evitar PDF misturado com texto.
 */
function abortClient($msg = 'Parâmetros inválidos') {
    while (ob_get_level() > 0) @ob_end_clean();
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit;
}

// ---------------- Validação rígida da query string ----------------
// Whitelist dos parâmetros permitidos
$allowed = ['id_ano_letivo', 'id_nivel_ensino', 'id_professor'];
$rawQuery = $_SERVER['QUERY_STRING'] ?? '';
parse_str($rawQuery, $receivedParams);

// Detecta parâmetros inesperados
$receivedKeys = array_keys($receivedParams);
$extra = array_diff($receivedKeys, $allowed);
if (!empty($extra)) {
    logSecurity('Parâmetros inesperados em hora-aula-treino: ' . implode(', ', $extra) . ' | raw_qs=' . $rawQuery);
    abortClient();
}

// Normalizadores e validadores (monta array canônico)
$canonical = [];

// id_ano_letivo é obrigatório e deve ser inteiro positivo
if (!array_key_exists('id_ano_letivo', $receivedParams)) {
    logSecurity('id_ano_letivo ausente em hora-aula-treino | raw_qs=' . $rawQuery);
    abortClient('Ano letivo não informado.');
}
$raw = $receivedParams['id_ano_letivo'];
if (!is_scalar($raw) || !preg_match('/^[1-9]\d*$/', (string)$raw)) {
    logSecurity('id_ano_letivo inválido em hora-aula-treino: raw=' . var_export($raw, true) . ' | raw_qs=' . $rawQuery);
    abortClient('Parâmetros inválidos');
}
$canonical['id_ano_letivo'] = (int)$raw;

// id_nivel_ensino opcional, quando presente deve ser inteiro positivo
if (array_key_exists('id_nivel_ensino', $receivedParams)) {
    $raw = $receivedParams['id_nivel_ensino'];
    if (!is_scalar($raw) || !preg_match('/^[1-9]\d*$/', (string)$raw)) {
        logSecurity('id_nivel_ensino inválido em hora-aula-treino: raw=' . var_export($raw, true) . ' | raw_qs=' . $rawQuery);
        abortClient('Parâmetros inválidos');
    }
    $canonical['id_nivel_ensino'] = (int)$raw;
}

// id_professor opcional, quando presente deve ser inteiro positivo
if (array_key_exists('id_professor', $receivedParams)) {
    $raw = $receivedParams['id_professor'];
    if (!is_scalar($raw) || !preg_match('/^[1-9]\d*$/', (string)$raw)) {
        logSecurity('id_professor inválido em hora-aula-treino: raw=' . var_export($raw, true) . ' | raw_qs=' . $rawQuery);
        abortClient('Parâmetros inválidos');
    }
    $canonical['id_professor'] = (int)$raw;
}

// Comparar arrays normalizados (independente da ordem)
$normalized_received_array = [];
foreach ($receivedParams as $k => $v) {
    if ($k === 'id_ano_letivo' || $k === 'id_nivel_ensino' || $k === 'id_professor') {
        $normalized_received_array[$k] = (int)$v;
    } else {
        $normalized_received_array[$k] = $v;
    }
}
ksort($canonical);
ksort($normalized_received_array);
if ($canonical !== $normalized_received_array) {
    logSecurity("Query string não corresponde ao formato canônico em hora-aula-treino: expected_array=" . json_encode($canonical) . " got_array=" . json_encode($normalized_received_array) . " | full_raw={$rawQuery}");
    abortClient();
}

// Atribuir variáveis seguras
$id_ano_letivo   = $canonical['id_ano_letivo'];
$id_nivel_ensino = isset($canonical['id_nivel_ensino']) ? $canonical['id_nivel_ensino'] : 0;
$id_professor    = isset($canonical['id_professor'])    ? $canonical['id_professor']    : 0;

// ---------------- Proteção do logo (safe path) ----------------
$logoPath = null;
try {
    $stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
    $inst = $stmtInst->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    logSecurity("Erro SQL buscando instituicao: " . $e->getMessage());
    $inst = null;
}
if ($inst && !empty($inst['imagem_instituicao'])) {
    $logoCandidate = basename($inst['imagem_instituicao']);
    $fullLogoPath = LOGO_PATH . '/' . $logoCandidate;
    if (file_exists($fullLogoPath) && is_file($fullLogoPath) && strpos(realpath($fullLogoPath), realpath(LOGO_PATH)) === 0) {
        $logoPath = $fullLogoPath;
    } else {
        logSecurity("Logo inválido ou inacessível em hora-aula-treino: " . $inst['imagem_instituicao']);
        $logoPath = null;
    }
}

// ---------------- Classe PDF ----------------
class PDFRelProfAulas extends FPDF
{
    public function Header()
    {
        global $pdo, $logoPath, $inst;
        if ($logoPath) {
            // 90 px convertido para mm (aprox)
            $desiredSize = 90 * 25.4 / 96;
            $pageWidth = $this->GetPageWidth();
            $x = ($pageWidth - $desiredSize) / 2;
            $this->Image($logoPath, $x, 10, $desiredSize, $desiredSize);
        }
        $this->Ln(25);
        $this->SetFont('Arial','B',16);
        if ($inst && !empty($inst['nome_instituicao'])) {
            $this->Cell(0,10, iconv('UTF-8','ISO-8859-1', $inst['nome_instituicao']), 0, 1, 'C');
        }
        $this->Ln(5);
        $this->SetFont('Arial','B',14);
        $this->Ln(2);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $texto = 'Impresso em: ' . date('d/m/Y H:i:s');
        $this->Cell(0, 10, iconv('UTF-8','ISO-8859-1', $texto), 0, 0, 'R');
    }
}

// ---------------- Criar PDF (apenas após validações) ----------------
$pdf = new PDFRelProfAulas('P','mm','A4');
$pdf->SetTitle(iconv('UTF-8','ISO-8859-1','Relatório de Professores com Aulas na Turma'));

// ---------------- Consulta dos Professores com Aulas ----------------
$sqlProf = "
	SELECT DISTINCT p.id_professor, p.nome_completo AS nome_professor, al.ano
	  FROM professor p
	  JOIN horario h ON p.id_professor = h.id_professor
	  JOIN turma t ON h.id_turma = t.id_turma
	  JOIN serie s ON t.id_serie = s.id_serie
	  JOIN ano_letivo al ON t.id_ano_letivo = al.id_ano_letivo
	 WHERE t.id_ano_letivo = :ano
";
$params = [':ano' => $id_ano_letivo];
if ($id_nivel_ensino > 0) {
	$sqlProf .= " AND s.id_nivel_ensino = :niv ";
	$params[':niv'] = $id_nivel_ensino;
}
if ($id_professor > 0) {
	$sqlProf .= " AND p.id_professor = :prof ";
	$params[':prof'] = $id_professor;
}
$sqlProf .= " ORDER BY p.nome_completo ";

$stP = $pdo->prepare($sqlProf);
$stP->execute($params);
$professores = $stP->fetchAll(PDO::FETCH_ASSOC);

if (!$professores) {
    logSecurity("Nenhum professor encontrado para filtros em hora-aula-treino: ano={$id_ano_letivo} nivel={$id_nivel_ensino} prof={$id_professor}");
	$pdf->AddPage();
	$pdf->SetFont('Arial','B',12);
	$pdf->Cell(0, 10, iconv('UTF-8','ISO-8859-1','Nenhum professor encontrado para os filtros.'), 0, 1, 'C');
	$pdf->Output('I','RelatorioProfessoresAulas.pdf');
	exit;
}

// ---------------- Dias da semana (do banco) ----------------
$sqlDias = "SELECT DISTINCT h.dia_semana
			FROM horario h
			JOIN turma t ON h.id_turma = t.id_turma
			JOIN serie s ON t.id_serie = s.id_serie
			WHERE t.id_ano_letivo = :ano";
$paramsDias = [':ano' => $id_ano_letivo];
if ($id_nivel_ensino > 0) {
	$sqlDias .= " AND s.id_nivel_ensino = :niv";
	$paramsDias[':niv'] = $id_nivel_ensino;
}
$sqlDias .= " ORDER BY FIELD(h.dia_semana, 'Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado')";
$stmtDias = $pdo->prepare($sqlDias);
$stmtDias->execute($paramsDias);
$diasSemana = $stmtDias->fetchAll(PDO::FETCH_COLUMN);
if (!$diasSemana) {
	$diasSemana = ['Segunda','Terca','Quarta','Quinta','Sexta'];
}

// ---------------- Para cada professor montar relatório ----------------
foreach ($professores as $prof) {
	$pid = (int)$prof['id_professor'];

	$sqlH = "
		SELECT h.dia_semana, h.numero_aula, 
			   s.nome_serie, t.nome_turma
		  FROM horario h
		  JOIN turma t ON h.id_turma = t.id_turma
		  JOIN serie s ON t.id_serie = s.id_serie
		 WHERE h.id_professor = :pid
		   AND t.id_ano_letivo = :ano
	";
	$pp = [':pid' => $pid, ':ano' => $id_ano_letivo];
	if ($id_nivel_ensino > 0) {
		$sqlH .= " AND s.id_nivel_ensino = :niv ";
		$pp[':niv'] = $id_nivel_ensino;
	}
	$sqlH .= "
		 ORDER BY FIELD(h.dia_semana, 'Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado'),
				  h.numero_aula
	";
	$stmtH = $pdo->prepare($sqlH);
	$stmtH->execute($pp);
	$horarios = $stmtH->fetchAll(PDO::FETCH_ASSOC);

	$pdf->AddPage();

	$pdf->SetFont('Arial','B',14);
	// sanitizar nome do professor para evitar chars de controle
	$nomeProf = is_string($prof['nome_professor']) ? preg_replace('/[[:cntrl:]]+/', ' ', trim($prof['nome_professor'])) : 'N/A';
	$pdf->Cell(0,10, iconv('UTF-8','ISO-8859-1',$nomeProf), 0,1,'C');
	$pdf->Ln(2);

	if (!$horarios) {
		$pdf->SetFont('Arial','',12);
		$pdf->Cell(0,10, iconv('UTF-8','ISO-8859-1','Nenhuma aula encontrada.'), 0,1,'C');
		continue;
	}

	// agrupar em matriz [dia_semana][numero_aula]
	$dataMatrix = [];
	$maxAula = 0;
	foreach ($horarios as $hrow) {
		$dia = $hrow['dia_semana'];
		$na  = (int)$hrow['numero_aula'];
		$serieTurma = trim($hrow['nome_serie'] . ' ' . $hrow['nome_turma']);
		// remover chars de controle
		$serieTurma = preg_replace('/[[:cntrl:]]+/', ' ', $serieTurma);
		if (!isset($dataMatrix[$dia])) $dataMatrix[$dia] = [];
		if (!isset($dataMatrix[$dia][$na])) $dataMatrix[$dia][$na] = [];
		$dataMatrix[$dia][$na][] = $serieTurma;
		if ($na > $maxAula) $maxAula = $na;
	}

	// montar tabela
	$pdf->SetFont('Arial','B',10);
	$pdf->SetFillColor(200,200,200);
	$marginLeft   = 10;
	$marginRight  = 10;
	$usableWidth  = $pdf->GetPageWidth() - $marginLeft - $marginRight;
	$colAulaW	 = 20;
	$daysCount	= count($diasSemana);
	$colDiaW	  = ($usableWidth - $colAulaW) / max(1, $daysCount);

	$pdf->Cell($colAulaW, 8, iconv('UTF-8','ISO-8859-1','Aula/Dia'), 1, 0, 'C', true);
	foreach ($diasSemana as $dia) {
		// sanitizar label do dia
		$diaLabel = preg_replace('/[[:cntrl:]]+/', ' ', trim($dia));
		$pdf->Cell($colDiaW, 8, iconv('UTF-8','ISO-8859-1',$diaLabel), 1, 0, 'C', true);
	}
	$pdf->Ln();

	$pdf->SetFont('Arial','',9);
	for ($a = 1; $a <= max(1, $maxAula); $a++) {
		$pdf->Cell($colAulaW, 10, iconv('UTF-8','ISO-8859-1',$a.'ª Aula'), 1, 0, 'C');
		foreach ($diasSemana as $dia) {
			$texto = '';
			if (isset($dataMatrix[$dia][$a])) {
				// limitar comprimento para não quebrar layout
				$txt = implode(", ", $dataMatrix[$dia][$a]);
				if (mb_strlen($txt) > 120) $txt = mb_substr($txt, 0, 117) . '...';
				$texto = preg_replace('/[[:cntrl:]]+/', ' ', $txt);
			}
			$pdf->Cell($colDiaW, 10, iconv('UTF-8','ISO-8859-1',$texto), 1, 0, 'C');
		}
		$pdf->Ln();
	}
}

// ---------------- Finaliza e envia PDF ----------------
$pdf->Output('I','RelatorioProfessoresAulas.pdf');
exit;

?>