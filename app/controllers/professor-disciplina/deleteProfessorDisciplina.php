<?php
// app/controllers/professor-disciplina/deleteProfessorDisciplina.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
		exit;
}

$id_professor = intval($_POST['id_professor'] ?? 0);
$id_disciplina = intval($_POST['id_disciplina'] ?? 0);

if ($id_professor <= 0 || $id_disciplina <= 0) {
		echo json_encode(['status' => 'error', 'message' => 'Dados inválidos.']);
		exit;
}

try {
		$stmt = $pdo->prepare("DELETE FROM professor_disciplinas WHERE id_professor = ? AND id_disciplina = ?");
		$stmt->execute([$id_professor, $id_disciplina]);
		if ($stmt->rowCount() > 0) {
				echo json_encode(['status' => 'success', 'message' => 'Registro deletado com sucesso.']);
		} else {
				echo json_encode(['status' => 'error', 'message' => 'Registro não encontrado.']);
		}
} catch (PDOException $e) {
		echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>
 