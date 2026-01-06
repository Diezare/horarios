<?php
// app/controllers/horarios/listarDiasTurno.php
/**
 * Retorna dias do turno e aulas_no_dia.
 * Ajuste: aceita id_turno diretamente (mais correto).
 * Mantém fallback (descobre turno via turmas) para compatibilidade.
 */
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json; charset=utf-8');

$id_nivel_ensino = (int)($_GET['id_nivel_ensino'] ?? 0);
$id_ano_letivo   = (int)($_GET['id_ano_letivo'] ?? 0);
$id_turno        = (int)($_GET['id_turno'] ?? 0); // NOVO

if ($id_ano_letivo <= 0 || ($id_nivel_ensino <= 0 && $id_turno <= 0)) {
	echo json_encode(['status' => 'error', 'message' => 'Parâmetros inválidos']);
	exit;
}

try {
	if ($id_turno <= 0) {
		// Fallback: busca 1 turno de alguma turma do nível/ano
		$sqlTurno = "
			SELECT DISTINCT t.id_turno
			FROM turma t
			JOIN serie s ON t.id_serie = s.id_serie
			WHERE s.id_nivel_ensino = :nivel
			  AND t.id_ano_letivo = :ano
			ORDER BY t.id_turno ASC
			LIMIT 1
		";
		$stmt = $pdo->prepare($sqlTurno);
		$stmt->execute([':nivel' => $id_nivel_ensino, ':ano' => $id_ano_letivo]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$row) {
			echo json_encode(['status' => 'error', 'message' => 'Nenhuma turma encontrada para este nível/ano']);
			exit;
		}
		$id_turno = (int)$row['id_turno'];
	}

	$sqlDias = "
		SELECT dia_semana, aulas_no_dia
		FROM turno_dias
		WHERE id_turno = :turno
		  AND aulas_no_dia > 0
		ORDER BY FIELD(dia_semana, 'Segunda', 'Terca', 'Quarta', 'Quinta', 'Sexta', 'Sabado', 'Domingo')
	";
	$stmtDias = $pdo->prepare($sqlDias);
	$stmtDias->execute([':turno' => $id_turno]);
	$dias = $stmtDias->fetchAll(PDO::FETCH_ASSOC);

	$nomesExibicao = [
		'Segunda' => 'Segunda-feira',
		'Terca'   => 'Terça-feira',
		'Quarta'  => 'Quarta-feira',
		'Quinta'  => 'Quinta-feira',
		'Sexta'   => 'Sexta-feira',
		'Sabado'  => 'Sábado',
		'Domingo' => 'Domingo'
	];

	$resultado = [];
	foreach ($dias as $dia) {
		$ds = $dia['dia_semana'];
		$resultado[] = [
			'dia_semana'    => $ds,
			'nome_exibicao' => $nomesExibicao[$ds] ?? $ds,
			'aulas_no_dia'  => (int)$dia['aulas_no_dia']
		];
	}

	echo json_encode([
		'status'  => 'success',
		'id_turno'=> $id_turno,
		'dias'    => $resultado
	]);
	exit;

} catch (Throwable $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro ao buscar dias: ' . $e->getMessage()]);
	exit;
}
?>