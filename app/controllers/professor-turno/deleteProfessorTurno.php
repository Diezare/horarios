<?php
// app/controllers/professor-turno/deleteProfessorTurno.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

$id_professor = intval($_POST['id_professor'] ?? 0);
$id_turno		 = intval($_POST['id_turno'] ?? 0);

if ($id_professor <= 0 || $id_turno <= 0) {
		echo json_encode(['status' => 'error', 'message' => 'Dados inválidos.']);
		exit;
}

try {
		$stmt = $pdo->prepare("
				DELETE FROM professor_turnos 
				 WHERE id_professor = ? 
					 AND id_turno = ?
		");
		$stmt->execute([$id_professor, $id_turno]);

		if ($stmt->rowCount() > 0) {
				echo json_encode([
						'status'	=> 'success',
						'message' => 'Vínculo excluído com sucesso.'
				]);
		} else {
				echo json_encode([
						'status'	=> 'error',
						'message' => 'Vínculo não encontrado.'
				]);
		}
} catch (PDOException $e) {
		echo json_encode([
				'status'	=> 'error',
				'message' => 'Erro: ' . $e->getMessage()
		]);
}
?>