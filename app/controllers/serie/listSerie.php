<?php
// app/controllers/serie/listSerie.php 
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

try {
	$stmt = $pdo->query("
		SELECT s.id_serie, s.id_nivel_ensino, s.nome_serie, s.total_aulas_semana, n.nome_nivel_ensino
		FROM serie s
		JOIN nivel_ensino n ON s.id_nivel_ensino = n.id_nivel_ensino
		ORDER BY s.nome_serie ASC
	");
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	echo json_encode(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>
