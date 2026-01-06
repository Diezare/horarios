<?php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json; charset=utf-8');

try {
	$id_ano_letivo   = isset($_GET['id_ano_letivo']) ? (int)$_GET['id_ano_letivo'] : 0;
	$id_nivel_ensino = isset($_GET['id_nivel_ensino']) ? (int)$_GET['id_nivel_ensino'] : 0;
	$id_turma		= isset($_GET['id_turma']) ? (int)$_GET['id_turma'] : 0;
	$id_professor	= isset($_GET['id_professor']) ? (int)$_GET['id_professor'] : 0;
	$detalhe		 = isset($_GET['detalhe']) ? strtolower(trim($_GET['detalhe'])) : 'geral';

	if ($id_ano_letivo <= 0) {
		echo json_encode(['status' => 'error', 'message' => 'Selecione o Ano Letivo.']);
		exit;
	}

	// IDs do nível "Escolinhas" (evita mistura de parâmetros)
	$stmtNE = $pdo->prepare("SELECT id_nivel_ensino FROM nivel_ensino WHERE LOWER(nome_nivel_ensino) LIKE :nm");
	$stmtNE->execute([':nm' => '%escolinh%']);
	$idsEscolinhas = array_map('intval', $stmtNE->fetchAll(PDO::FETCH_COLUMN) ?: []);

	// ---------- TURMAS ----------
	$sqlTurmas = "
		SELECT 
			t.id_turma,
			s.nome_serie,
			t.nome_turma,
			CONCAT(s.nome_serie, ' ', t.nome_turma) AS turma_nome,
			t.id_turno
		FROM turma t
		JOIN serie s ON s.id_serie = t.id_serie
		WHERE t.id_ano_letivo = :ano
	";
	$paramsTurmas = [':ano' => $id_ano_letivo];

	if ($id_nivel_ensino > 0) {
		if (in_array($id_nivel_ensino, $idsEscolinhas, true)) {
			echo json_encode(['status' => 'success', 'data' => ['turmas' => [], 'professores' => [], 'grid' => [], 'detalhe' => $detalhe]]);
			exit;
		}
		$sqlTurmas .= " AND s.id_nivel_ensino = :niv ";
		$paramsTurmas[':niv'] = $id_nivel_ensino;
	} elseif (!empty($idsEscolinhas)) {
		// cria placeholders nomeados para NOT IN
		$ph = [];
		foreach ($idsEscolinhas as $k => $val) {
			$phName = ":e$k";
			$ph[] = $phName;
			$paramsTurmas[$phName] = $val;
		}
		$sqlTurmas .= " AND s.id_nivel_ensino NOT IN (" . implode(',', $ph) . ") ";
	}

	if ($id_turma > 0) {
		$sqlTurmas .= " AND t.id_turma = :turma ";
		$paramsTurmas[':turma'] = $id_turma;
	}

	$sqlTurmas .= " ORDER BY s.nome_serie, t.nome_turma";
	$stT = $pdo->prepare($sqlTurmas);
	$stT->execute($paramsTurmas);
	$turmas = $stT->fetchAll(PDO::FETCH_ASSOC);

	if (!$turmas) {
		echo json_encode(['status' => 'success', 'data' => ['turmas' => [], 'professores' => [], 'grid' => [], 'detalhe' => $detalhe]]);
		exit;
	}

	$turmaIds = array_map(fn($t) => (int)$t['id_turma'], $turmas);

	// ---------- PROFESSORES ----------
	// monta IN com nomeados
	$phTurmas = [];
	$paramsProf = [];
	foreach ($turmaIds as $i => $tid) { $phTurmas[] = ":t$i"; $paramsProf[":t$i"] = $tid; }

	$sqlProf = "
		SELECT DISTINCT 
			p.id_professor,
			COALESCE(NULLIF(TRIM(p.nome_exibicao), ''), p.nome_completo) AS nome_professor
		FROM horario h
		JOIN professor p ON p.id_professor = h.id_professor
		WHERE h.id_turma IN (" . implode(',', $phTurmas) . ")
	";
	if ($id_professor > 0) {
		$sqlProf .= " AND p.id_professor = :pid ";
		$paramsProf[':pid'] = $id_professor;
	}
	$sqlProf .= " ORDER BY nome_professor";

	$stP = $pdo->prepare($sqlProf);
	$stP->execute($paramsProf);
	$professores = $stP->fetchAll(PDO::FETCH_ASSOC);

	if (!$professores) {
		echo json_encode(['status' => 'success', 'data' => ['turmas' => $turmas, 'professores' => [], 'grid' => [], 'detalhe' => $detalhe]]);
		exit;
	}

	$profIds = array_map(fn($p) => (int)$p['id_professor'], $professores);

	// ---------- AGG ----------
	$phTur = []; $phPro = [];
	$paramsAgg = [];
	foreach ($turmaIds as $i => $tid) { $phTur[] = ":tt$i"; $paramsAgg[":tt$i"] = $tid; }
	foreach ($profIds  as $i => $pid) { $phPro[] = ":pp$i"; $paramsAgg[":pp$i"] = $pid; }

	$sqlAgg = "
		SELECT 
			h.id_professor,
			h.id_turma,
			SUM(h.dia_semana='Domingo') AS dom,
			SUM(h.dia_semana='Segunda') AS seg,
			SUM(h.dia_semana='Terca')   AS ter,
			SUM(h.dia_semana='Quarta')  AS qua,
			SUM(h.dia_semana='Quinta')  AS qui,
			SUM(h.dia_semana='Sexta')   AS sex,
			SUM(h.dia_semana='Sabado')  AS sab,
			COUNT(*) AS total
		FROM horario h
		WHERE h.id_turma IN (" . implode(',', $phTur) . ")
		  AND h.id_professor IN (" . implode(',', $phPro) . ")
		GROUP BY h.id_professor, h.id_turma
	";
	$stA = $pdo->prepare($sqlAgg);
	$stA->execute($paramsAgg);
	$agg = $stA->fetchAll(PDO::FETCH_ASSOC);

	$grid = [];
	foreach ($agg as $r) {
		$pid = (int)$r['id_professor'];
		$tid = (int)$r['id_turma'];
		$grid[$pid][$tid] = [
			'dom'=>(int)$r['dom'],'seg'=>(int)$r['seg'],'ter'=>(int)$r['ter'],
			'qua'=>(int)$r['qua'],'qui'=>(int)$r['qui'],'sex'=>(int)$r['sex'],
			'sab'=>(int)$r['sab'],'total'=>(int)$r['total'],
		];
	}

	echo json_encode([
		'status' => 'success',
		'data'   => [
			'turmas'	  => $turmas,
			'professores' => $professores,
			'grid'		=> $grid,
			'detalhe'	 => $detalhe
		]
	]);
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
