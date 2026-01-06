<?php
// app/controllers/serie/deleteSerie.php 
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
	echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
	exit;
}

try {
	// 1) Verifica se a série está vinculada em alguma tabela
	$dependencias = [
		'serie_disciplinas' => 'id_serie',
		'turma' => 'id_serie'
	];

	foreach ($dependencias as $tabela => $coluna) {
		$sqlCheck = "SELECT COUNT(*) as count FROM {$tabela} WHERE {$coluna} = ?";
		$stmtCheck = $pdo->prepare($sqlCheck);
		$stmtCheck->execute([$id]);
		$result = $stmtCheck->fetch(PDO::FETCH_ASSOC);
		if ($result && intval($result['count']) > 0) {
			echo json_encode([
				'status' => 'error',
				'message' => "Não é possível excluir a série, pois há registros vinculados."
			]);
			exit;
		}
	}

	// 2) Se não houver vínculos, prossegue com a exclusão
	$stmt = $pdo->prepare("DELETE FROM serie WHERE id_serie = ?");
	$stmt->execute([$id]);

	if ($stmt->rowCount() > 0) {
		echo json_encode(['status' => 'success', 'message' => 'Série excluída com sucesso.']);
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Série não encontrada.']);
	}
} catch (PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir: ' . $e->getMessage()]);
}
?>