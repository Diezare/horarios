<?php
// app/controllers/serie-disciplina/listSerieDisciplina.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$id_serie = isset($_GET['id_serie']) ? intval($_GET['id_serie']) : 0;

try {
	if ($id_serie > 0) {
		$stmt = $pdo->prepare("
			SELECT sd.*, d.sigla_disciplina, d.nome_disciplina
			FROM serie_disciplinas sd
			JOIN disciplina d ON sd.id_disciplina = d.id_disciplina
			WHERE sd.id_serie = ?
		");
		$stmt->execute([$id_serie]);
	} else {
		$stmt = $pdo->query("
			SELECT sd.*, d.sigla_disciplina, d.nome_disciplina
			FROM serie_disciplinas sd
			JOIN disciplina d ON sd.id_disciplina = d.id_disciplina
			ORDER BY d.nome_disciplina
		");
	}
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	echo json_encode(['status' => 'success', 'data' => $rows]);
} catch (PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>
