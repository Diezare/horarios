<?php
// app/controllers/sala-turno/deleteSalaTurno.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$id_sala = intval($_POST['id_sala'] ?? 0);
$id_turno = intval($_POST['id_turno'] ?? 0);

if($id_sala <= 0 || $id_turno <= 0) {
	echo json_encode(['status' => 'error', 'message' => 'Dados inválidos.']);
	exit;
}

try {
	$stmt = $pdo->prepare("DELETE FROM sala_turno WHERE id_sala = ? AND id_turno = ?");
	$stmt->execute([$id_sala, $id_turno]);
	echo json_encode(['status' => 'success', 'message' => 'Vinculação excluída com sucesso.']);
} catch(PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir vinculação: ' . $e->getMessage()]);
}
?>