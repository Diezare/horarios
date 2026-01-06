<?php
// app/controllers/nivel-ensino/listNivelEnsino.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

try {
	$stmt = $pdo->query("SELECT * FROM nivel_ensino ORDER BY nome_nivel_ensino ASC");
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>