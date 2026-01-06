<?php
// app/controllers/professor/listProfessor.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

try {
	$stmt = $pdo->query("
		SELECT 
		  id_professor,
		  nome_completo,
		  nome_exibicao,
		  telefone,
		  sexo,
		  limite_aulas_fixa_semana
		FROM professor
		ORDER BY nome_completo ASC
	");
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	echo json_encode(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>
