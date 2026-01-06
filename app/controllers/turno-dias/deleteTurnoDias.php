<?php
// app/controllers/turno-dias/deleteTurnoDias.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

$id_turno_dia = intval($_POST['id_turno_dia'] ?? 0);

if ($id_turno_dia <= 0) {
	echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
	exit;
}

try {
	$stmt = $pdo->prepare("DELETE FROM turno_dias WHERE id_turno_dia = ?");
	$stmt->execute([$id_turno_dia]);

	if ($stmt->rowCount() > 0) {
		echo json_encode(['status' => 'success', 'message' => 'Registro deletado com sucesso.']);
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Registro não encontrado.']);
	}
} catch (PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir: ' . $e->getMessage()]);
}
?>