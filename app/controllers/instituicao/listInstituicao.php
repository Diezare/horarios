<?php
// app/controllers/instituicao/listInstituicao.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

try {
	$stmt = $pdo->query("SELECT * FROM instituicao ORDER BY nome_instituicao ASC");
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// Não é necessário converter para base64 – a URL já está pronta para uso.
	echo json_encode(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>