<?php
// app/controllers/turno/updateTurno.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$id		 = intval($_POST['id_turno'] ?? 0);
	$nome	   = trim($_POST['nome_turno'] ?? '');
	$descricao  = trim($_POST['descricao_turno'] ?? '');
	$horaInicio = trim($_POST['horario_inicio_turno'] ?? '');
	$horaFim	= trim($_POST['horario_fim_turno'] ?? '');

	if ($id <= 0 || empty($nome) || empty($horaInicio) || empty($horaFim)) {
		echo json_encode(['status' => 'error', 'message' => 'Dados incompletos.']);
		exit;
	}

	try {
		$stmt = $pdo->prepare("
			UPDATE turno
			   SET nome_turno		   = ?,
				   descricao_turno	  = ?,
				   horario_inicio_turno = ?,
				   horario_fim_turno	= ?
			 WHERE id_turno = ?
		");
		$stmt->execute([$nome, $descricao, $horaInicio, $horaFim, $id]);

		if ($stmt->rowCount() > 0) {
			echo json_encode(['status' => 'success', 'message' => 'Turno atualizado com sucesso.']);
		} else {
			// rowCount() == 0 pode significar que não houve alteração ou que o ID não foi encontrado
			echo json_encode(['status' => 'error', 'message' => 'Nenhuma alteração ou registro não encontrado.']);
		}
	} catch (PDOException $e) {
		if ($e->getCode() == 23000) {
			echo json_encode([
				'status'  => 'error',
				'message' => 'Já existe um turno com esse nome.'
			]);
		} else {
			echo json_encode([
				'status'  => 'error',
				'message' => 'Erro: ' . $e->getMessage()
			]);
		}
	}
} else {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
}
?>