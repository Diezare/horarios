<?php
// horarios/app/controllers/turma/listTurmaPorNivelEnsino.php

require_once __DIR__ . '/../../../configs/init.php'; 
header('Content-Type: application/json');

// Habilitar exibição de erros (apenas em desenvolvimento)
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

// Verifica se os parâmetros foram passados
if (!isset($_GET['id_ano_letivo']) || !isset($_GET['id_nivel_ensino'])) {
	echo json_encode([
		'status'  => 'error',
		'message' => 'Parâmetros inválidos.'
	]);
	exit;
} 

$id_ano_letivo   = intval($_GET['id_ano_letivo']);
$id_nivel_ensino = intval($_GET['id_nivel_ensino']);

try {
	//$pdo = getPDO();
	$pdo = configurarConexaoBanco();

	$sql = "
		SELECT
			t.id_turma,
			t.id_serie,
			s.nome_serie,
			t.nome_turma,
			t.id_turno,
			turn.nome_turno
		FROM turma t
		INNER JOIN serie s ON t.id_serie = s.id_serie
		INNER JOIN turno turn ON t.id_turno = turn.id_turno
		WHERE t.id_ano_letivo = :id_ano_letivo
		  AND s.id_nivel_ensino = :id_nivel_ensino
		ORDER BY s.nome_serie, t.nome_turma
	";

	$stmt = $pdo->prepare($sql);
	$stmt->execute([
		':id_ano_letivo'   => $id_ano_letivo,
		':id_nivel_ensino' => $id_nivel_ensino
	]);

	$turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode([
		'status' => 'success',
		'data'   => $turmas
	]);
} catch (Exception $e) {
	echo json_encode([
		'status'  => 'error',
		'message' => $e->getMessage()
	]);
}
?>
