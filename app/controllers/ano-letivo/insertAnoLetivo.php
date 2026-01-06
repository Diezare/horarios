<?php
// app/controllers/insertAnoLetivo.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$ano		= trim($_POST['ano'] ?? '');
	$dataInicio = trim($_POST['data_inicio'] ?? '');
	$dataFim	= trim($_POST['data_fim'] ?? '');

	if (empty($ano) || empty($dataInicio) || empty($dataFim)) {
		echo json_encode(['status' => 'error', 'message' => 'Preencha todos os campos.']);
		exit;
	}

	try {
		// Removendo id_usuario
		$stmt = $pdo->prepare("
			INSERT INTO ano_letivo (ano, data_inicio, data_fim)
			VALUES (?, ?, ?)
		");
		$stmt->execute([$ano, $dataInicio, $dataFim]);

		echo json_encode([
			'status'  => 'success',
			'message' => 'Ano Letivo inserido com sucesso!',
			'id'	  => $pdo->lastInsertId()
		]);
	} catch (PDOException $e) {
		if ($e->getCode() == 23000) {
			// Este código indica violação de chave única,
			// que no seu caso é a coluna "ano".
			echo json_encode([
				'status'  => 'error',
				'message' => 'Já existe este ano cadastrado.'
			]);
		} else {
			echo json_encode([
				'status'  => 'error',
				'message' => 'Erro ao inserir: ' . $e->getMessage()
			]);
		}
	}
} else {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
}
?>
