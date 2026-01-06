<?php
// app/controllers/nivel-ensino/deleteNivelEnsino.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$id = intval($_POST['id'] ?? 0);
	if ($id <= 0) {
		echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
		exit;
	}

	try {
		$dependencias = [
			'serie' => 'id_nivel_ensino'
		];

		foreach ($dependencias as $tabela => $coluna) {
			$sqlCheck = "SELECT COUNT(*) as qtd FROM {$tabela} WHERE {$coluna} = ?";
			$stmtCheck = $pdo->prepare($sqlCheck);
			$stmtCheck->execute([$id]);
			$result = $stmtCheck->fetch(PDO::FETCH_ASSOC);
			if ($result && intval($result['qtd']) > 0) {
				echo json_encode([
					'status' => 'error',
					'message' => "Não é possível excluir nível de ensino, pois há registros vinculados."
				]);
				exit;
			}
		}

		$stmt = $pdo->prepare("DELETE FROM nivel_ensino WHERE id_nivel_ensino = ?");
		$stmt->execute([$id]);

		if ($stmt->rowCount() > 0) {
			echo json_encode(['status' => 'success', 'message' => 'Excluído com sucesso.']);
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