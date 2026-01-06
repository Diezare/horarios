<?php
// app/controllers/horarios/updateHorarios.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

$id_horario    = intval($_POST['id_horario'] ?? 0);
$id_disciplina = intval($_POST['id_disciplina'] ?? 0);
$id_professor  = intval($_POST['id_professor'] ?? 0);

if ($id_horario <= 0 || $id_disciplina <= 0 || $id_professor <= 0) {
	echo json_encode(['status' => 'error', 'message' => 'Dados incompletos.']);
	exit;
}

try {
	// (Opcional) Buscar dados antigos para gravar no historico_horario
	// ...

	$stmt = $pdo->prepare("
		UPDATE horario
		   SET id_disciplina = ?,
		       id_professor  = ?
		 WHERE id_horario    = ?
	");
	$stmt->execute([$id_disciplina, $id_professor, $id_horario]);

	if ($stmt->rowCount() > 0) {
		// (Opcional) Inserir registro no historico_horario
		echo json_encode(['status' => 'success', 'message' => 'Horário atualizado.']);
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Nenhuma alteração ou registro não encontrado.']);
	}
} catch (PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>