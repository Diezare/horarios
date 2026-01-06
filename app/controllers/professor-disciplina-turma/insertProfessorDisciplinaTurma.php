<?php
// app/controllers/professor-disciplina-turma/insertProfessorDisciplinaTurma.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

// Verifica método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

$id_professor = isset($_POST['id_professor']) ? intval($_POST['id_professor']) : 0;
$pdtItems	 = $_POST['pdtItems'] ?? []; 
// $pdtItems[] deve vir no formato "id_disciplina:id_turma"

if ($id_professor <= 0) {
	echo json_encode(['status' => 'error', 'message' => 'ID de professor inválido.']);
	exit;
}

try {
	$pdo->beginTransaction();

	// Remove todos os vínculos atuais do professor
	$stmtDel = $pdo->prepare("DELETE FROM professor_disciplinas_turmas WHERE id_professor = ?");
	$stmtDel->execute([$id_professor]);

	// Agora insere os novos vínculos
	if (!empty($pdtItems)) {
		$stmt = $pdo->prepare("
			INSERT INTO professor_disciplinas_turmas (id_professor, id_disciplina, id_turma)
			VALUES (?, ?, ?)
		");
		foreach ($pdtItems as $item) {
			// "discId:turmaId"
			$arr = explode(':', $item);
			if (count($arr) === 2) {
				$id_disciplina = intval($arr[0]);
				$id_turma	  = intval($arr[1]);
				// só insere se ambos > 0
				if ($id_disciplina > 0 && $id_turma > 0) {
					$stmt->execute([$id_professor, $id_disciplina, $id_turma]);
				}
			}
		}
	}

	$pdo->commit();
	echo json_encode([
		'status'  => 'success',
		'message' => 'Vínculos atualizados com sucesso!'
	]);
} catch (PDOException $e) {
	$pdo->rollBack();
	echo json_encode([
		'status'  => 'error',
		'message' => 'Erro ao vincular professor-disciplina-turma: ' . $e->getMessage()
	]);
}
?>