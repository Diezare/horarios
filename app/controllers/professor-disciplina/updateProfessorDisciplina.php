<?php
// app/controllers/professor-disciplina/updateProfessorDisciplina.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
		exit;
}

$id_professor = intval($_POST['id_professor'] ?? 0);
$disciplinas	= $_POST['disciplinas'] ?? [];

if ($id_professor <= 0 || empty($disciplinas) || !is_array($disciplinas)) {
		echo json_encode(['status' => 'error', 'message' => 'Dados incompletos ou inválidos.']);
		exit;
}

try {
		$pdo->beginTransaction();
		
		// Remove os vínculos atuais
		$stmtDel = $pdo->prepare("DELETE FROM professor_disciplinas WHERE id_professor = ?");
		$stmtDel->execute([$id_professor]);
		
		// Insere as novas associações
		$stmt = $pdo->prepare("INSERT INTO professor_disciplinas (id_professor, id_disciplina) VALUES (?, ?)");
		foreach ($disciplinas as $id_disciplina) {
				$id_disciplina = intval($id_disciplina);
				if ($id_disciplina > 0) {
						$stmt->execute([$id_professor, $id_disciplina]);
				}
		}
		$pdo->commit();
		echo json_encode(['status' => 'success', 'message' => 'Associações atualizadas com sucesso!']);
} catch (PDOException $e) {
		$pdo->rollBack();
		echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar associações: ' . $e->getMessage()]);
}
?>
