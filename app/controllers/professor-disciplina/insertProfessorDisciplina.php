<?php
// app/controllers/professor-disciplina/insertProfessorDisciplina.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

$id_professor = intval($_POST['id_professor'] ?? 0);
$disciplinas  = $_POST['disciplinas'] ?? [];

// Retire "empty($disciplinas)" do if de validação
if ($id_professor <= 0 || !is_array($disciplinas)) {
	echo json_encode([
		'status'  => 'error',
		'message' => 'Professor inválido ou dados de disciplinas inválidos.'
	]);
	exit;
}

try {
	$pdo->beginTransaction();
	
	// Remove todos os vínculos atuais (sempre)
	$stmtDel = $pdo->prepare("DELETE FROM professor_disciplinas WHERE id_professor = ?");
	$stmtDel->execute([$id_professor]);

	// Se o array não estiver vazio, insere as disciplinas selecionadas
	if (!empty($disciplinas)) {
		$stmt = $pdo->prepare("
			INSERT INTO professor_disciplinas (id_professor, id_disciplina)
			VALUES (?, ?)
		");
		foreach ($disciplinas as $id_disciplina) {
			$id_disciplina = intval($id_disciplina);
			if ($id_disciplina > 0) {
				$stmt->execute([$id_professor, $id_disciplina]);
			}
		}
	}

	$pdo->commit();
	echo json_encode([
		'status'  => 'success',
		'message' => 'Disciplinas vinculadas com sucesso!'
	]);

} catch (PDOException $e) {
	$pdo->rollBack();
	echo json_encode([
		'status'  => 'error',
		'message' => 'Erro ao vincular disciplinas: ' . $e->getMessage()
	]);
}
?>