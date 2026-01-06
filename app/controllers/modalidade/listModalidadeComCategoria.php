<?php
// app/controllers/modalidade/listModalidadeComCategoria.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

try {
	$stmt = $pdo->query("
		SELECT 
			m.id_modalidade,
			m.nome_modalidade,
			c.id_categoria,
			c.nome_categoria,
			CONCAT(m.nome_modalidade, ' - ', c.nome_categoria) as nome_completo
		FROM modalidade m
		INNER JOIN categoria c ON m.id_modalidade = c.id_modalidade
		ORDER BY m.nome_modalidade, c.nome_categoria
	");
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>