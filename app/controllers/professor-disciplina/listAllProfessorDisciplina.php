<?php
// app/controllers/professor-disciplina/listAllProfessorDisciplinas.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

try {
	$stmt = $pdo->query("
		SELECT id_professor, id_disciplina
		FROM professor_disciplinas
		ORDER BY id_professor
	");
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode(['status' => 'success', 'data' => $rows]);

} catch (PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>