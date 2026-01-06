<?php
// app/controllers/nivel-ensino/insertNivelEnsino.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$nome = trim($_POST['nome_nivel_ensino'] ?? '');

	if (empty($nome)) {
		echo json_encode(['status' => 'error', 'message' => 'Preencha o nome do nível de ensino.']);
		exit;
	}

	try {
		$stmt = $pdo->prepare("INSERT INTO nivel_ensino (nome_nivel_ensino) VALUES (?)");
		$stmt->execute([$nome]);

		echo json_encode([
			'status'  => 'success',
			'message' => 'Nível de Ensino inserido com sucesso!',
			'id'	  => $pdo->lastInsertId()
		]);
	} catch (PDOException $e) {
		if ($e->getCode() == 23000) {
			echo json_encode([
				'status'  => 'error',
				'message' => 'Já existe um nível de ensino cadastrado com esse nome.'
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