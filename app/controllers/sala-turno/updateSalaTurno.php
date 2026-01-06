<?php
// app/controllers/sala-turno/updateSalaTurno.php   
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$id_sala = intval($_POST['id_sala'] ?? 0);
$id_turno = intval($_POST['id_turno'] ?? 0);
$id_turma = intval($_POST['id_turma'] ?? 0);

if($id_sala <= 0 || $id_turno <= 0 || $id_turma <= 0) {
	echo json_encode(['status' => 'error', 'message' => 'Dados inválidos.']);
	exit;
}

try {
	$stmt = $pdo->prepare("UPDATE sala_turno SET id_turma = ? WHERE id_sala = ? AND id_turno = ?");
	$stmt->execute([$id_turma, $id_sala, $id_turno]);
	echo json_encode(['status' => 'success', 'message' => 'Vinculação atualizada com sucesso.']);
} catch(PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar vinculação: ' . $e->getMessage()]);
}
?>
