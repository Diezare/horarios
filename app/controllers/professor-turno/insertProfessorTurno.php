<?php
// app/controllers/professor-turno/insertProfessorTurno.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

$id_professor = intval($_POST['id_professor'] ?? 0);
$turnos = $_POST['turnos'] ?? [];

// Checar se professorId é válido
if ($id_professor <= 0 || !is_array($turnos)) {
	echo json_encode([
		'status'	=> 'error',
		'message' => 'Professor inválido ou dados de turnos inválidos.'
	]);
	exit;
}

try {
	$pdo->beginTransaction();

	// Remove TODOS os vínculos anteriores
	$stmtDel = $pdo->prepare("DELETE FROM professor_turnos WHERE id_professor = ?");
	$stmtDel->execute([$id_professor]);

	// Se houver turnos no array, insere
	if (!empty($turnos)) {
		$stmtIns = $pdo->prepare("INSERT INTO professor_turnos (id_professor, id_turno) VALUES (?, ?)");
		foreach ($turnos as $id_turno) {
			$id_turno = intval($id_turno);
			if ($id_turno > 0) {
				$stmtIns->execute([$id_professor, $id_turno]);
			}
		}
	}

	$pdo->commit();
	echo json_encode([
		'status'	=> 'success',
		'message' => 'Turnos vinculados com sucesso!'
	]);

} catch (PDOException $e) {
	$pdo->rollBack();
	echo json_encode([
		'status'	=> 'error',
		'message' => 'Erro ao vincular turnos: ' . $e->getMessage()
	]);
}
?>