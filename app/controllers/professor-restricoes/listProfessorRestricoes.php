<?php
// app/controllers/professor-restricoes/listProfessorRestricoes.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$id_ano_letivo = (int)($_GET['id_ano_letivo'] ?? 0);
$id_turno      = (int)($_GET['id_turno'] ?? 0);
$id_professor  = (int)($_GET['id_professor'] ?? 0); // ✅ opcional (0 = todos)

if ($id_ano_letivo <= 0 || $id_turno <= 0) {
	echo json_encode([
		'status' => 'error',
		'message' => 'Parâmetros obrigatórios: id_ano_letivo e id_turno.'
	]);
	exit;
}

try {
	$sql = "
		SELECT id_professor, id_ano_letivo, id_turno, dia_semana, numero_aula
		FROM professor_restricoes
		WHERE id_ano_letivo = :ano
		  AND id_turno = :turno
	";
	if ($id_professor > 0) {
		$sql .= " AND id_professor = :prof ";
	}
	$sql .= "
		ORDER BY id_professor,
		         FIELD(dia_semana,'Segunda','Terca','Quarta','Quinta','Sexta','Sabado','Domingo'),
		         numero_aula
	";

	$stmt = $pdo->prepare($sql);
	$stmt->bindValue(':ano', $id_ano_letivo, PDO::PARAM_INT);
	$stmt->bindValue(':turno', $id_turno, PDO::PARAM_INT);
	if ($id_professor > 0) {
		$stmt->bindValue(':prof', $id_professor, PDO::PARAM_INT);
	}
	$stmt->execute();

	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	echo json_encode(['status' => 'success', 'data' => $rows]);

} catch (Throwable $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>