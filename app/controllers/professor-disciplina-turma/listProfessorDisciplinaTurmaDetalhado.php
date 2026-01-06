<?php
// app/controllers/professor-disciplina-turma/listProfessorDisciplinaTurmaDetalhado.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$id_professor = isset($_GET['id_professor']) ? (int)$_GET['id_professor'] : 0;
$id_disciplina = isset($_GET['id_disciplina']) ? (int)$_GET['id_disciplina'] : 0;

if ($id_professor <= 0) {
	echo json_encode(['status' => 'error', 'message' => 'ID do professor é obrigatório.']);
	exit;
}
 
try {
	$sql = "SELECT 
				pdt.id_professor,
				pdt.id_disciplina,
				pdt.id_turma,
				d.nome_disciplina,
				t.nome_turma,
				s.id_serie,
				s.nome_serie,
				s.id_nivel_ensino,
				ne.nome_nivel_ensino,
				tn.id_turno,
				tn.nome_turno,
				al.ano
			FROM professor_disciplinas_turmas pdt
			INNER JOIN disciplina d ON pdt.id_disciplina = d.id_disciplina
			INNER JOIN turma t ON pdt.id_turma = t.id_turma
			INNER JOIN serie s ON t.id_serie = s.id_serie
			INNER JOIN nivel_ensino ne ON s.id_nivel_ensino = ne.id_nivel_ensino
			INNER JOIN turno tn ON t.id_turno = tn.id_turno
			INNER JOIN ano_letivo al ON t.id_ano_letivo = al.id_ano_letivo
			WHERE pdt.id_professor = :id_professor";
	
	$params = [':id_professor' => $id_professor];
	
	if ($id_disciplina > 0) {
		$sql .= " AND pdt.id_disciplina = :id_disciplina";
		$params[':id_disciplina'] = $id_disciplina;
	}
	
	$sql .= " ORDER BY d.nome_disciplina, s.nome_serie, t.nome_turma, tn.nome_turno";
	
	$stmt = $pdo->prepare($sql);
	$stmt->execute($params);
	$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	echo json_encode(['status' => 'success', 'data' => $data]);
	
} catch (PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>