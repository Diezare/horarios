<?php
// app/controllers/usuario/deleteFoto.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$id = intval($_POST['id_usuario'] ?? 0);
	if ($id <= 0) {
		echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
		exit;
	}

	// Busca o usuário e a imagem do perfil cadastrada
	$stmt = $pdo->prepare("SELECT imagem_usuario FROM usuario WHERE id_usuario = ?");
	$stmt->execute([$id]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);

	if ($row) {
		if (!empty($row['imagem_usuario'])) {
			$imageUrl = $row['imagem_usuario'];
			$physicalPath = ROOT_PATH . '/app/assets/imgs/perfil/' . basename($imageUrl);
			if (file_exists($physicalPath)) {
				unlink($physicalPath);
			}
		}
		// Atualiza o registro para definir a coluna como NULL
		$stmt = $pdo->prepare("UPDATE usuario SET imagem_usuario = NULL WHERE id_usuario = ?");
		$stmt->execute([$id]);
		echo json_encode(['status' => 'success', 'message' => 'Foto de perfil removida com sucesso.']);
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Usuário não encontrado.']);
	}
} else {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
}
?>