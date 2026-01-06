<?php
// app/controllers/serie-disciplina/updateSerieDisciplina.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

$id_serie = intval($_POST['id_serie'] ?? 0);
$disciplinas = $_POST['disciplinas'] ?? [];

if ($id_serie <= 0 || empty($disciplinas) || !is_array($disciplinas)) {
	echo json_encode(['status' => 'error', 'message' => 'Dados incompletos ou inválidos.']);
	exit;
}

try {
	$pdo->beginTransaction();

	// Remove os vínculos atuais
	$stmtDel = $pdo->prepare("DELETE FROM serie_disciplinas WHERE id_serie = ?");
	$stmtDel->execute([$id_serie]);

	// Insere os novos vínculos
	$stmt = $pdo->prepare("INSERT INTO serie_disciplinas (id_serie, id_disciplina, aulas_semana) VALUES (?, ?, ?)");
	foreach ($disciplinas as $id_disciplina) {
		$id_disciplina = intval($id_disciplina);
		if ($id_disciplina > 0) {
			$stmt->execute([$id_serie, $id_disciplina, 0]);
		}
	}
	$pdo->commit();
	echo json_encode(['status' => 'success', 'message' => 'Associações atualizadas com sucesso!']);
} catch (PDOException $e) {
	$pdo->rollBack();
	echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar associações: ' . $e->getMessage()]);
}
?>
