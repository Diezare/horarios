<?php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

try {
	$stmt = $pdo->query("
		SELECT id_usuario, nome_usuario, email_usuario, situacao_usuario, nivel_usuario, imagem_usuario
		FROM usuario
		ORDER BY nome_usuario ASC
	");
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	foreach ($rows as &$r) {
		if (!empty($r['imagem_usuario'])) {
			$img = trim($r['imagem_usuario']);

			// Se veio sem /horarios, corrige
			if (strpos($img, '/app/assets/') === 0) {
				$img = '/horarios' . $img;
			}

			$r['imagem_usuario'] = $img;
		}
	}
	unset($r);

	echo json_encode(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>