<?php
// app/controllers/updateAnoLetivo.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$id		 = intval($_POST['id'] ?? 0);
	$ano		= trim($_POST['ano'] ?? '');
	$dataInicio = trim($_POST['data_inicio'] ?? '');
	$dataFim	= trim($_POST['data_fim'] ?? '');

	if ($id <= 0 || empty($ano) || empty($dataInicio) || empty($dataFim)) {
		echo json_encode(['status' => 'error', 'message' => 'Dados incompletos.']);
		exit;
	}

	try {
		$stmt = $pdo->prepare("UPDATE ano_letivo
								 SET ano = ?, data_inicio = ?, data_fim = ?
							   WHERE id_ano_letivo = ?");
		$stmt->execute([$ano, $dataInicio, $dataFim, $id]);

		if ($stmt->rowCount() > 0) {
			echo json_encode(['status' => 'success', 'message' => 'Atualizado com sucesso.']);
		} else {
			echo json_encode(['status' => 'error',
							  'message' => 'Nenhuma alteração ou registro não encontrado.']);
		}
	} catch (PDOException $e) {
		if ($e->getCode() == 23000) {
			echo json_encode(['status' => 'error', 'message' => 'Já existe este ano cadastrado.']);
		} else {
			echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
		}
	}
} else {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
}
?>
