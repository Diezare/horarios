<?php
// app/controllers/professor/listProfessoresAulasRelatorio.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$id_ano_letivo   = isset($_GET['id_ano_letivo'])   ? (int)$_GET['id_ano_letivo']   : 0;
$id_nivel_ensino = isset($_GET['id_nivel_ensino']) ? (int)$_GET['id_nivel_ensino'] : 0;
$id_professor	= isset($_GET['id_professor'])	? (int)$_GET['id_professor']	: 0;

if ($id_ano_letivo <= 0) {
	echo json_encode(['status'=>'error','message'=>'Ano letivo não informado']);
	exit;
}

$sql = "SELECT DISTINCT p.id_professor, p.nome_completo AS nome_professor, al.ano
	FROM professor p
	JOIN horario h ON p.id_professor = h.id_professor
	JOIN turma t ON h.id_turma = t.id_turma
	JOIN serie s ON t.id_serie = s.id_serie
	JOIN ano_letivo al ON t.id_ano_letivo = al.id_ano_letivo
	WHERE t.id_ano_letivo = :ano";
$params = [':ano' => $id_ano_letivo];
if ($id_nivel_ensino > 0) {
	$sql .= " AND s.id_nivel_ensino = :niv";
	$params[':niv'] = $id_nivel_ensino;
}
if ($id_professor > 0) {
	$sql .= " AND p.id_professor = :prof";
	$params[':prof'] = $id_professor;
}
$sql .= " ORDER BY p.nome_completo";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$professores = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['status'=>'success', 'data'=>$professores]);
?>