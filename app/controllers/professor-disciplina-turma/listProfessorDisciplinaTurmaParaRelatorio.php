<?php
// app/controllers/professor-disciplina-turma/listProfessorDisciplinaTurmaParaRelatorio.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$idDisc = isset($_GET['id_disc']) ? intval($_GET['id_disc']) : 0;

try {
	if ($idDisc > 0) {
		// Exemplo: retorna professor + turma para esta disciplina
		$sql = "
			SELECT p.id_professor, p.nome_completo AS nome_professor,
				   t.id_turma, t.nome_turma
			  FROM professor_disciplinas_turmas pdt
			  JOIN professor p ON pdt.id_professor = p.id_professor
			  JOIN turma t	 ON pdt.id_turma = t.id_turma
			 WHERE pdt.id_disciplina = :id
			 ORDER BY p.nome_completo
		";
		$stmt = $pdo->prepare($sql);
		$stmt->bindValue(':id', $idDisc, PDO::PARAM_INT);
		$stmt->execute();
		
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		echo json_encode([
			'status' => 'success',
			'data'   => $rows
		]);
	} else {
		echo json_encode([
			'status' => 'error',
			'message' => 'ID Disciplina não informado.'
		]);
	}
} catch (PDOException $e) {
	echo json_encode([
		'status' => 'error',
		'message' => 'Erro: ' . $e->getMessage()
	]);
}
?>