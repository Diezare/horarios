<?php
// app/controllers/insertDisciplina.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$nome = trim($_POST['nome_disciplina'] ?? '');
	$sigla = trim($_POST['sigla_disciplina'] ?? '');

	if (empty($nome) || empty($sigla)) {
		echo json_encode(['status' => 'error', 'message' => 'Preencha todos os campos.']);
		exit;
	}

	try {
		$stmt = $pdo->prepare("
			INSERT INTO disciplina (nome_disciplina, sigla_disciplina)
			VALUES (?, ?)
		");
		$stmt->execute([$nome, $sigla]);

		echo json_encode([
			'status'  => 'success',
			'message' => 'Disciplina inserida com sucesso!',
			'id'	  => $pdo->lastInsertId()
		]);
	} catch (PDOException $e) {
		if ($e->getCode() == 23000) {
			echo json_encode([
				'status'  => 'error',
				'message' => 'Já existe esta disciplina cadastrada.'
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