<?php
// app/controllers/instituicao/deleteInstituicao.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$id = intval($_POST['id'] ?? 0);
	if ($id <= 0) {
		echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
		exit;
	}

	try {
		// 1) Verifica se a instituição está vinculada em alguma tabela
		$dependencias = [
			// Adicione aqui as tabelas e colunas que referenciam a instituição, se houver
			// Exemplo: 'outra_tabela' => 'id_instituicao'
		];

		foreach ($dependencias as $tabela => $coluna) {
			$sqlCheck = "SELECT COUNT(*) as qtd FROM {$tabela} WHERE {$coluna} = ?";
			$stmtCheck = $pdo->prepare($sqlCheck);
			$stmtCheck->execute([$id]);
			$result = $stmtCheck->fetch(PDO::FETCH_ASSOC);
			if ($result && intval($result['qtd']) > 0) {
				echo json_encode([
					'status' => 'error',
					'message' => "Não é possível excluir a instituição, pois há registros vinculados."
				]);
				exit;
			}
		}

		// 2) Remove a logo da instituição, se existir
		$stmtLogo = $pdo->prepare("SELECT imagem_instituicao FROM instituicao WHERE id_instituicao = ?");
		$stmtLogo->execute([$id]);
		$row = $stmtLogo->fetch(PDO::FETCH_ASSOC);

		if ($row && !empty($row['imagem_instituicao'])) {
			$imageUrl = $row['imagem_instituicao'];
			// Certifique-se de que a constante LOGO_PATH está definida (configs/paths.php)
			$physicalPath = LOGO_PATH . '/' . basename($imageUrl);
			if (file_exists($physicalPath)) {
				unlink($physicalPath);
			}
		}

		// 3) Exclui o registro da instituição
		$stmt = $pdo->prepare("DELETE FROM instituicao WHERE id_instituicao = ?");
		$stmt->execute([$id]);

		if ($stmt->rowCount() > 0) {
			echo json_encode(['status' => 'success', 'message' => 'Instituição excluída com sucesso.']);
		} else {
			echo json_encode(['status' => 'error', 'message' => 'Registro não encontrado.']);
		}
	} catch (PDOException $e) {
		echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir: ' . $e->getMessage()]);
	}
} else {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
}
?>