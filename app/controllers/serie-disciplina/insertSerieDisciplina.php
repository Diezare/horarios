<?php
// app/controllers/serie-disciplina/insertSerieDisciplina.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

$id_serie = intval($_POST['id_serie'] ?? 0);
$disciplinasData = json_decode($_POST['disciplinas'] ?? '[]', true);

if ($id_serie <= 0 || !is_array($disciplinasData)) {
	echo json_encode([
		'status' => 'error',
		'message' => 'Série inválida ou dados de disciplinas inválidos.'
	]);
	exit;
}

try {
	$pdo->beginTransaction();

	// Remove todos os vínculos atuais para a série
	$stmtDel = $pdo->prepare("DELETE FROM serie_disciplinas WHERE id_serie = ?");
	$stmtDel->execute([$id_serie]);

	// Preparar a inserção
	if (!empty($disciplinasData)) {
		$stmt = $pdo->prepare("INSERT INTO serie_disciplinas (id_serie, id_disciplina, aulas_semana) VALUES (?, ?, ?)");
		// Usamos um array auxiliar para evitar duplicatas
		$processed = [];
		foreach ($disciplinasData as $item) {
			$id_disciplina = intval($item['id_disciplina']);
			$aulas_semana = intval($item['aulas_semana']);
			if ($id_disciplina > 0 && $aulas_semana > 0) {
				// Se já processamos essa disciplina, ignoramos
				if (in_array($id_disciplina, $processed)) {
					continue;
				}
				$processed[] = $id_disciplina;
				$stmt->execute([$id_serie, $id_disciplina, $aulas_semana]);
			}
		}
	}

	$pdo->commit();
	echo json_encode([
		'status' => 'success',
		'message' => 'Disciplinas vinculadas com sucesso!'
	]);
} catch (PDOException $e) {
	$pdo->rollBack();
	echo json_encode([
		'status' => 'error',
		'message' => 'Erro ao vincular disciplinas: ' . $e->getMessage()
	]);
}
?>
