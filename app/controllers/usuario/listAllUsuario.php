<?php
// app/controllers/usuario/listAllUsuario.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

try {
	// Buscamos os dados de todos os usuários
	$stmt = $pdo->query("
		SELECT
			id_usuario,
			nome_usuario,
			email_usuario,
			situacao_usuario,
			nivel_usuario,
			imagem_usuario
		FROM usuario
		ORDER BY nome_usuario ASC
	");
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	echo json_encode(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>
