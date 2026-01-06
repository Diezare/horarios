<?php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json; charset=utf-8');

$id_serie = isset($_GET['id_serie']) ? intval($_GET['id_serie']) : 0;
$id_ano   = isset($_GET['id_ano_letivo']) ? intval($_GET['id_ano_letivo']) : 0;

if (!$id_serie || !$id_ano) {
	echo json_encode([
		'status' => 'error',
		'message'=> 'Parâmetros inválidos.'
	]);
	exit;
}

try {
	// Consulta as turmas que tenham id_serie e id_ano_letivo correspondentes
	$sql = "SELECT t.id_turma, t.id_serie, t.id_turno, t.nome_turma,
				   s.nome_serie, s.total_aulas_semana,
				   a.ano AS ano_letivo,
				   tn.nome_turno
			  FROM turma t
		 LEFT JOIN serie s ON t.id_serie = s.id_serie
		 LEFT JOIN ano_letivo a ON t.id_ano_letivo = a.id_ano_letivo
		 LEFT JOIN turno tn ON t.id_turno = tn.id_turno
			 WHERE t.id_serie = :serie
			   AND t.id_ano_letivo = :ano";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([
		':serie' => $id_serie,
		':ano'   => $id_ano
	]);
	$turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode([
		'status' => 'success',
		'data'   => $turmas
	]);
} catch (Exception $e) {
	echo json_encode([
		'status' => 'error',
		'message'=> $e->getMessage()
	]);
}
?>