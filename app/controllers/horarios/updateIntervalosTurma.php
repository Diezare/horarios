<?php
// app/controllers/horarios/updateIntervalosTurma.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

$id_turma   = (int)($_POST['id_turma'] ?? 0);
$intervalos = trim((string)($_POST['intervalos'] ?? ''));

if ($id_turma <= 0) {
	echo json_encode(['status' => 'error', 'message' => 'ID da turma não informado.']);
	exit;
}

$idUsuario = (int)($_SESSION['id_usuario'] ?? 0);
if ($idUsuario <= 0) {
	echo json_encode(['status' => 'error', 'message' => 'Não autenticado.']);
	exit;
}

// validação leve do formato: "3,6" ou "" (permite vazio)
if ($intervalos !== '' && !preg_match('/^\d+(,\d+)*$/', $intervalos)) {
	echo json_encode(['status' => 'error', 'message' => 'Formato inválido. Use algo como "3,6" ou deixe vazio.']);
	exit;
}

try {
	// Permissão: usuário precisa ter acesso ao nível da turma
	$stmt = $pdo->prepare("
		SELECT 1
		FROM turma t
		JOIN serie s ON t.id_serie = s.id_serie
		JOIN nivel_ensino ne ON s.id_nivel_ensino = ne.id_nivel_ensino
		JOIN usuario_niveis un ON un.id_nivel_ensino = ne.id_nivel_ensino
		WHERE t.id_turma = ?
		  AND un.id_usuario = ?
		LIMIT 1
	");
	$stmt->execute([$id_turma, $idUsuario]);

	if (!$stmt->fetchColumn()) {
		echo json_encode(['status' => 'error', 'message' => 'Sem permissão para esta turma.']);
		exit;
	}

	$stmtUpd = $pdo->prepare("UPDATE turma SET intervalos_positions = :intervalos WHERE id_turma = :id LIMIT 1");
	$stmtUpd->execute([':intervalos' => ($intervalos === '' ? null : $intervalos), ':id' => $id_turma]);

	echo json_encode([
		'status' => 'success',
		'message'=> ($stmtUpd->rowCount() > 0) ? 'Intervalos da turma atualizados.' : 'Nenhuma alteração.'
	]);
	exit;

} catch (Throwable $e) {
	echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
	exit;
}
?>