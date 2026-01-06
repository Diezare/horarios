<?php
// horarios/app/controllers/dashboardData.php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../configs/init.php'; 
// se init.php já chama session_start() e pdo, ok

try {
	// Professores por Disciplina (barras):
	// Podemos usar JOIN com disciplina para pegar sigla_disciplina
	$sqlBarras = "
		SELECT d.sigla_disciplina AS sigla, COUNT(DISTINCT pd.id_professor) AS total
		FROM professor_disciplinas pd
		JOIN disciplina d ON pd.id_disciplina = d.id_disciplina
		GROUP BY d.sigla_disciplina
		ORDER BY d.sigla_disciplina
	";
	$stmtBarras = $pdo->query($sqlBarras);
	$rowsBarras = $stmtBarras->fetchAll(PDO::FETCH_ASSOC);

	// Professores por Sexo (pizza):
	$sqlPizza = "
		SELECT sexo, COUNT(*) AS total
		FROM professor
		GROUP BY sexo
	";
	$stmtPizza = $pdo->query($sqlPizza);
	$rowsPizza = $stmtPizza->fetchAll(PDO::FETCH_ASSOC);

	// Montar array final e enviar em JSON
	echo json_encode([
		'status' => 'success',
		'barras' => $rowsBarras, // array de { sigla, total }
		'pizza'  => $rowsPizza   // array de { sexo, total }
	]);
	exit;

} catch (Exception $e) {
	echo json_encode([
		'status'  => 'error',
		'message' => $e->getMessage()
	]);
	exit;
}
// Fim
?>
