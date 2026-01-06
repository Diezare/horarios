<?php
// app/controllers/professor-disciplina/listProfessorDisciplina.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

// Tenta capturar o id_professor (caso exista)
$id_professor = isset($_GET['id_professor']) ? (int)$_GET['id_professor'] : 0;

try {
	// Se NÃO foi passado (ou é inválido) um id_professor > 0,
	// então listamos TODAS as disciplinas (sem filtrar por professor)
	if ($id_professor <= 0) {
		// Aqui você acessa diretamente a tabela 'disciplina'
		// (ajuste para o nome exato da sua tabela, se for diferente)
		$stmt = $pdo->query("
			SELECT 
				d.id_disciplina,
				d.nome_disciplina,
				d.sigla_disciplina
			FROM disciplina d
			ORDER BY d.nome_disciplina
		");
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		echo json_encode([
			'status' => 'success',
			'data'   => $rows
		]);
		exit; // Finaliza aqui
	}

	// Caso contrário, se TEMOS um id_professor válido,
	// lista somente as disciplinas vinculadas a esse professor
	$stmt = $pdo->prepare("
		SELECT 
			pd.id_professor,
			pd.id_disciplina,
			d.nome_disciplina,
			d.sigla_disciplina
		FROM professor_disciplinas pd
		JOIN disciplina d 
			ON pd.id_disciplina = d.id_disciplina
		WHERE pd.id_professor = ?
		ORDER BY d.nome_disciplina
	");
	$stmt->execute([$id_professor]);
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