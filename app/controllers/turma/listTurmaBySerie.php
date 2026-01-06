<?php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$id_serie = isset($_GET['id_serie']) ? (int)$_GET['id_serie'] : 0;

try {
	if ($id_serie > 0) {
		$stmt = $pdo->prepare("
			SELECT t.id_turma, t.nome_turma, s.nome_serie
			FROM turma t
			JOIN serie s ON t.id_serie = s.id_serie
			WHERE t.id_serie = :id_serie
			ORDER BY t.nome_turma
		");
		$stmt->bindValue(':id_serie', $id_serie, PDO::PARAM_INT);
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		echo json_encode(['status' => 'success', 'data' => $rows]);
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Série não informada.']);
	}
} catch (Exception $e) {
	echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
