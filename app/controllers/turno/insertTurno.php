<?php
// app/controllers/turno/insertTurno.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$nome	   = trim($_POST['nome_turno'] ?? '');
	$descricao  = trim($_POST['descricao_turno'] ?? '');
	$horaInicio = trim($_POST['horario_inicio_turno'] ?? '');
	$horaFim	= trim($_POST['horario_fim_turno'] ?? '');

	// Verificações básicas
	if (empty($nome) || empty($horaInicio) || empty($horaFim)) {
		echo json_encode(['status' => 'error', 'message' => 'Preencha todos os campos obrigatórios.']);
		exit;
	}

	try {
		$stmt = $pdo->prepare("
			INSERT INTO turno 
				(nome_turno, descricao_turno, horario_inicio_turno, horario_fim_turno)
			VALUES (?, ?, ?, ?)
		");
		$stmt->execute([$nome, $descricao, $horaInicio, $horaFim]);

		echo json_encode([
			'status'  => 'success',
			'message' => 'Turno inserido com sucesso!',
			'id'	  => $pdo->lastInsertId()
		]);
	} catch (PDOException $e) {
		if ($e->getCode() == 23000) {
			// Caso tenha UNIQUE em algum campo
			echo json_encode([
				'status'  => 'error',
				'message' => 'Já existe um turno com esse nome.'
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