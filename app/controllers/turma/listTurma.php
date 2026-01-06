<?php
// app/controllers/turma/listTurma.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

try {
	$stmt = $pdo->query("
		SELECT 
			t.id_turma,
			t.id_ano_letivo,
			al.ano,
			t.id_serie,
			s.nome_serie,
			t.id_turno,
			turno.nome_turno,
			t.nome_turma,
			t.intervalos_por_dia,
			t.intervalos_positions
		FROM turma t
		INNER JOIN ano_letivo al ON t.id_ano_letivo = al.id_ano_letivo
		INNER JOIN serie s ON t.id_serie = s.id_serie
		INNER JOIN turno ON t.id_turno = turno.id_turno
		ORDER BY al.ano, s.nome_serie, t.nome_turma
	");
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
	echo json_encode([
		'status' => 'error', 
		'message' => 'Erro ao listar turmas: ' . $e->getMessage()
	]);
}
?>
