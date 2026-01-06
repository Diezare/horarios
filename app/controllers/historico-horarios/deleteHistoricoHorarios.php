<?php
// app/controllers/horarios/deleteHorarios.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

$id_turma    = intval($_POST['id_turma'] ?? 0);
$dia_semana  = trim($_POST['dia_semana'] ?? '');
$numero_aula = intval($_POST['numero_aula'] ?? 0);

if ($id_turma <= 0 || empty($dia_semana) || $numero_aula <= 0) {
	echo json_encode(['status' => 'error', 'message' => 'Parâmetros inválidos.']);
	exit;
}

try {
	// Opcional: mover para historico_horario se quiser manter logs
	// Buscar id_horario
	$sqlFind = "
		SELECT id_horario
		FROM horario
		WHERE id_turma = ? AND dia_semana = ? AND numero_aula = ?
	";
	$stmt = $pdo->prepare($sqlFind);
	$stmt->execute([$id_turma, $dia_semana, $numero_aula]);
	$found = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$found) {
		echo json_encode(['status' => 'error', 'message' => 'Horário não encontrado.']);
		exit;
	}

	$idHorario = $found['id_horario'];

	// Exclui
	$sqlDel = "
		DELETE FROM horario
		 WHERE id_horario = ?
	";
	$stmtDel = $pdo->prepare($sqlDel);
	$stmtDel->execute([$idHorario]);

	if ($stmtDel->rowCount() > 0) {
		echo json_encode(['status' => 'success', 'message' => 'Horário removido com sucesso.']);
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Falha ao remover ou já removido.']);
	}

} catch (PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>