<?php
// app/controllers/turno-dias/deleteTurnoDias.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

// MODO 1: apagar por ID (um registro)
$id_turno_dia_nivel = (int)($_POST['id_turno_dia_nivel'] ?? 0);

// MODO 2: apagar tudo por turno+nivel
$id_turno = (int)($_POST['id_turno'] ?? 0);
$id_nivel_ensino = (int)($_POST['id_nivel_ensino'] ?? 0);

try {
	if ($id_turno_dia_nivel > 0) {
		$stmt = $pdo->prepare("DELETE FROM turno_dias_nivel WHERE id_turno_dia_nivel = ?");
		$stmt->execute([$id_turno_dia_nivel]);

		if ($stmt->rowCount() > 0) {
			echo json_encode(['status' => 'success', 'message' => 'Registro deletado com sucesso.']);
		} else {
			echo json_encode(['status' => 'error', 'message' => 'Registro não encontrado.']);
		}
		exit;
	}

	if ($id_turno > 0 && $id_nivel_ensino > 0) {
		$stmt = $pdo->prepare("DELETE FROM turno_dias_nivel WHERE id_turno = ? AND id_nivel_ensino = ?");
		$stmt->execute([$id_turno, $id_nivel_ensino]);

		echo json_encode(['status' => 'success', 'message' => 'Configuração do nível removida com sucesso.']);
		exit;
	}

	echo json_encode(['status' => 'error', 'message' => 'Informe id_turno_dia_nivel OU (id_turno e id_nivel_ensino).']);

} catch (PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir: ' . $e->getMessage()]);
}
?>