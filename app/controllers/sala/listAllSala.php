<?php
// app/controllers/sala/listAllSala.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

try {
	$stmt = $pdo->query("SELECT * FROM sala ORDER BY nome_sala");
	$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
	echo json_encode(['status' => 'success', 'data' => $data]);
} catch(PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro ao listar todas as salas: ' . $e->getMessage()]);
}
?>
