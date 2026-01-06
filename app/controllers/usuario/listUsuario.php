<?php
// app/controllers/usuario/listUsuario.php
require_once __DIR__ . '/../../../configs/init.php'; 
header('Content-Type: application/json');

try {
	// Agora buscaremos também o nivel_usuario (mas não vamos exibir no PDF)
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