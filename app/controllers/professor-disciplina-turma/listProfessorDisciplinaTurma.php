<?php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$id_professor = isset($_GET['id_professor']) ? (int)$_GET['id_professor'] : 0;
$id_turma = isset($_GET['id_turma']) ? (int)$_GET['id_turma'] : 0;
$id_disciplina = isset($_GET['id_disciplina']) ? (int)$_GET['id_disciplina'] : 0;
$id_serie = isset($_GET['id_serie']) ? (int)$_GET['id_serie'] : 0;
$all = isset($_GET['all']) ? (int)$_GET['all'] : 0;

try {
	if ($all === 1) {
		$sql = "SELECT pdt.id_professor, pdt.id_disciplina, pdt.id_turma 
				FROM professor_disciplinas_turmas pdt 
				ORDER BY pdt.id_professor, pdt.id_disciplina, pdt.id_turma;";
		$stmt = $pdo->query($sql);
		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		echo json_encode(['status' => 'success', 'data' => $data]);
		exit;
	} 
 
	$conditions = [];
	$params = [];

	if ($id_professor > 0) {
		$conditions[] = "pdt.id_professor = :id_professor";
		$params[':id_professor'] = $id_professor;
	}
	if ($id_disciplina > 0) {
		$conditions[] = "pdt.id_disciplina = :id_disciplina";
		$params[':id_disciplina'] = $id_disciplina;
	}
	if ($id_turma > 0) {
		$conditions[] = "pdt.id_turma = :id_turma";
		$params[':id_turma'] = $id_turma;
	}
	if ($id_serie > 0) {
		$conditions[] = "t.id_serie = :id_serie";
		$params[':id_serie'] = $id_serie;
	}

	if (count($conditions) > 0) {
		$sql = "SELECT pdt.id_professor, pdt.id_disciplina, pdt.id_turma 
				FROM professor_disciplinas_turmas pdt 
				JOIN turma t ON pdt.id_turma = t.id_turma 
				WHERE " . implode(" AND ", $conditions) . "
				ORDER BY pdt.id_professor, pdt.id_disciplina, pdt.id_turma;";
		$stmt = $pdo->prepare($sql);
		$stmt->execute($params);
		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		echo json_encode(['status' => 'success', 'data' => $data]);
		exit;
	}

	echo json_encode(['status' => 'error', 'message' => 'Parâmetro(s) obrigatório(s) não fornecido(s).']);

} catch (PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>