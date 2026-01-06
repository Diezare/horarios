<?php
// app/controllers/usuario/deleteUsuario.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$id = intval($_POST['id'] ?? 0);
	if ($id <= 0) {
		echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
		exit;
	}
	
	try {
		// Recupera informações do usuário (incluindo nome e imagem)
		$stmtUser = $pdo->prepare("SELECT nome_usuario, imagem_usuario FROM usuario WHERE id_usuario = ?");
		$stmtUser->execute([$id]);
		$user = $stmtUser->fetch(PDO::FETCH_ASSOC);
		
		if (!$user) {
			echo json_encode(['status' => 'error', 'message' => 'Registro não encontrado.']);
			exit;
		}
		
		// Validação para impedir a exclusão do usuário com ID 1 ou com nome "Diezare.Conde"
		if ($id === 1 || $user['nome_usuario'] === 'Diezare.Conde') {
			echo json_encode(['status' => 'error', 'message' => 'Este usuário não pode ser excluído.']);
			exit;
		}
		
		// Se houver imagem, removê-la
		if (!empty($user['imagem_usuario'])) {
			$physicalPath = ROOT_PATH . '/app/assets/imgs/perfil/' . basename($user['imagem_usuario']);
			if (file_exists($physicalPath)) {
				unlink($physicalPath);
			}
		}
		
		// Executa a exclusão do usuário
		$stmt = $pdo->prepare("DELETE FROM usuario WHERE id_usuario = ?");
		$stmt->execute([$id]);
		
		if ($stmt->rowCount() > 0) {
			echo json_encode(['status' => 'success', 'message' => 'Usuário excluído com sucesso.']);
		} else {
			echo json_encode(['status' => 'error', 'message' => 'Falha ao excluir o usuário.']);
		}
		
	} catch (PDOException $e) {
		echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir: ' . $e->getMessage()]);
	}
} else {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
}
?>