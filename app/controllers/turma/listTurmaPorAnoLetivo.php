<?php
// app/controllers/turma/listTurmaPorAnoLetivo.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

// Recebe o ano letivo e o nível de ensino
$id_ano_letivo   = isset($_GET['id_ano_letivo']) ? (int)$_GET['id_ano_letivo'] : 0;
$id_nivel_ensino = isset($_GET['id_nivel_ensino']) ? (int)$_GET['id_nivel_ensino'] : 0;

if ($id_ano_letivo <= 0) {
	echo json_encode(['status' => 'error', 'message' => 'Ano letivo inválido.']);
	exit;
}

if ($id_nivel_ensino <= 0) {
	echo json_encode(['status' => 'error', 'message' => 'Nível de ensino inválido.']);
	exit;
}
 
try {
	// A query agora filtra também pelo nível de ensino, que está relacionado à série (s.id_nivel_ensino)
	$sql = "
		SELECT 
			t.id_turma, 
			s.nome_serie,
			t.nome_turma,
			turno.nome_turno,
			t.intervalos_positions
		FROM turma t
		JOIN serie s ON t.id_serie = s.id_serie
		JOIN turno ON t.id_turno = turno.id_turno
		WHERE t.id_ano_letivo = ? AND s.id_nivel_ensino = ?
		ORDER BY s.nome_serie, t.nome_turma
	";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$id_ano_letivo, $id_nivel_ensino]);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode(['status' => 'success', 'data' => $rows]);
} catch (PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>