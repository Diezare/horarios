<?php
// app/controllers/professor-disciplina/listProfessorDisciplinaCategoria.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$id_disciplina = isset($_GET['id_disciplina']) ? intval($_GET['id_disciplina']) : 0;
if ($id_disciplina <= 0) {
	echo json_encode([
		'status' => 'error',
		'message' => 'Disciplina inválida ou não informada.'
	]);
	exit;
}

try {
	// Faz o join com a tabela professor e filtra pela disciplina:
	$stmt = $pdo->prepare("
		SELECT p.id_professor, p.nome_completo
		FROM professor_disciplinas pd
		JOIN professor p ON p.id_professor = pd.id_professor
		WHERE pd.id_disciplina = ?
		ORDER BY p.nome_completo
	");
	$stmt->execute([$id_disciplina]);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode([
		'status' => 'success',
		'data'   => $rows
	]);
} catch (PDOException $e) {
	echo json_encode([
		'status'  => 'error',
		'message' => 'Erro: ' . $e->getMessage()
	]);
}
?>