<?php
// app/controllers/professor-disciplina-turma/updateProfessorDisciplinaTurma.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

$id_professor = intval($_POST['id_professor'] ?? 0);
$pdtItems	 = $_POST['pdtItems'] ?? [];

if ($id_professor <= 0) {
	echo json_encode(['status' => 'error', 'message' => 'ID de professor inválido.']);
	exit;
}

try {
	$pdo->beginTransaction();
	
	// Exclui todos os vínculos existentes
	$del = $pdo->prepare("DELETE FROM professor_disciplinas_turmas WHERE id_professor = ?");
	$del->execute([$id_professor]);

	// Insere novamente (mesma lógica do insert)
	$ins = $pdo->prepare("
		INSERT INTO professor_disciplinas_turmas (id_professor, id_disciplina, id_turma)
		VALUES (?, ?, ?)
	");
	foreach ($pdtItems as $item) {
		list($disc, $turma) = explode(':', $item);
		$disc = intval($disc);
		$turma = intval($turma);
		if ($disc > 0 && $turma > 0) {
			$ins->execute([$id_professor, $disc, $turma]);
		}
	}

	$pdo->commit();
	echo json_encode(['status' => 'success', 'message' => 'Vínculos atualizados com sucesso!']);
} catch (PDOException $e) {
	$pdo->rollBack();
	echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>