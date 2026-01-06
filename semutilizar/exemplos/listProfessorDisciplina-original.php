<?php
// app/controllers/professor-disciplina/listProfessorDisciplina.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$id_professor = isset($_GET['id_professor']) ? intval($_GET['id_professor']) : 0;

try {
		if ($id_professor > 0) {
				$stmt = $pdo->prepare("
						SELECT pd.*, d.nome_disciplina, d.sigla_disciplina
						FROM professor_disciplinas pd
						JOIN disciplina d ON pd.id_disciplina = d.id_disciplina
						WHERE pd.id_professor = ?
				");
				$stmt->execute([$id_professor]);
		} else {
				$stmt = $pdo->query("
						SELECT pd.*, d.nome_disciplina, d.sigla_disciplina, p.nome_completo
						FROM professor_disciplinas pd
						JOIN disciplina d ON pd.id_disciplina = d.id_disciplina
						JOIN professor p ON pd.id_professor = p.id_professor
						ORDER BY p.nome_completo, d.nome_disciplina
				");
		}
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		echo json_encode(['status' => 'success', 'data' => $rows]);
} catch (PDOException $e) {
		echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>
