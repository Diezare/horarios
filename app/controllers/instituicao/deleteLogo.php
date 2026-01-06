<?php
// app/controllers/instituicao/deleteLogo.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

// Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// Obtém o ID da instituição enviado
	$id = intval($_POST['id_instituicao'] ?? 0);
	if ($id <= 0) {
		echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
		exit;
	}
 
	// Busca a instituição e a logo cadastrada
	$stmt = $pdo->prepare("SELECT imagem_instituicao FROM instituicao WHERE id_instituicao = ?");
	$stmt->execute([$id]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);

	if ($row) {
		// Se houver logo definida, tenta remover o arquivo
		if (!empty($row['imagem_instituicao'])) {
			$imageUrl = $row['imagem_instituicao'];
			// Certifique-se de que a constante LOGO_PATH está definida em seu arquivo de paths (configs/paths.php)
			$physicalPath = LOGO_PATH . '/' . basename($imageUrl);
			if (file_exists($physicalPath)) {
				unlink($physicalPath);
			}
		}
		// Atualiza o registro para definir a coluna como NULL
		$stmt = $pdo->prepare("UPDATE instituicao SET imagem_instituicao = NULL WHERE id_instituicao = ?");
		$stmt->execute([$id]);

		echo json_encode(['status' => 'success', 'message' => 'Logo removida com sucesso.']);
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Instituição não encontrada.']);
	}
} else {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
}
?>