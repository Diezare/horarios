<?php
// app/controllers/serie-disciplina/deleteSerieDisciplina.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

$id_serie = intval($_POST['id_serie'] ?? 0);
$id_disciplina = intval($_POST['id_disciplina'] ?? 0);

if ($id_serie <= 0 || $id_disciplina <= 0) {
	echo json_encode(['status' => 'error', 'message' => 'Dados inválidos.']);
	exit;
}

try {
	$stmt = $pdo->prepare("DELETE FROM serie_disciplinas WHERE id_serie = ? AND id_disciplina = ?");
	$stmt->execute([$id_serie, $id_disciplina]);
	if ($stmt->rowCount() > 0) {
		echo json_encode(['status' => 'success', 'message' => 'Registro deletado com sucesso.']);
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Registro não encontrado.']);
	}
} catch (PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>
