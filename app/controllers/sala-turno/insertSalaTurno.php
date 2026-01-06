<?php
// app/controllers/sala-turno/insertSalaTurno.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$id_sala = intval($_POST['id_sala'] ?? 0);
$id_turma = intval($_POST['id_turma'] ?? 0);
$turnos = isset($_POST['id_turnos']) ? $_POST['id_turnos'] : '';

if($id_sala <= 0 || $id_turma <= 0 || empty($turnos)) {
	echo json_encode(['status' => 'error', 'message' => 'Dados inválidos para vinculação.']);
	exit;
}

$turnoArray = explode(',', $turnos);

try {
	$pdo->beginTransaction();
	foreach($turnoArray as $id_turno) {
		$id_turno = intval($id_turno);
		if($id_turno > 0) {
			$stmtCheck = $pdo->prepare("SELECT * FROM sala_turno WHERE id_sala = ? AND id_turno = ?");
			$stmtCheck->execute([$id_sala, $id_turno]);
			if($stmtCheck->rowCount() > 0) {
				$stmtUpdate = $pdo->prepare("UPDATE sala_turno SET id_turma = ? WHERE id_sala = ? AND id_turno = ?");
				$stmtUpdate->execute([$id_turma, $id_sala, $id_turno]);
			} else {
				$stmtInsert = $pdo->prepare("INSERT INTO sala_turno (id_sala, id_turno, id_turma) VALUES (?, ?, ?)");
				$stmtInsert->execute([$id_sala, $id_turno, $id_turma]);
			}
		}
	}
	$pdo->commit();
	echo json_encode(['status' => 'success', 'message' => 'Vinculação realizada com sucesso.']);
} catch(PDOException $e) {
	$pdo->rollBack();
	echo json_encode(['status' => 'error', 'message' => 'Erro ao vincular turma: ' . $e->getMessage()]);
}
?>