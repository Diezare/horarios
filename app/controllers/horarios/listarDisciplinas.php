<?php
// app/controllers/horarios/listarDisciplinas.php
/**
 * Lista disciplinas por nível de ensino.
 * (Sem impacto direto do id_turno, mas padroniza validação/retorno.)
 */
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json; charset=utf-8');

$id_nivel_ensino = (int)($_GET['id_nivel_ensino'] ?? 0);

if ($id_nivel_ensino <= 0) {
	echo json_encode(['status' => 'error', 'message' => 'ID do nível de ensino não fornecido.']);
	exit;
}

try {
	$sql = "
		SELECT DISTINCT
			d.id_disciplina,
			d.nome_disciplina,
			d.sigla_disciplina
		FROM disciplina d
		JOIN serie_disciplinas sd ON d.id_disciplina = sd.id_disciplina
		JOIN serie s ON sd.id_serie = s.id_serie
		WHERE s.id_nivel_ensino = :nivel
		ORDER BY d.nome_disciplina
	";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([':nivel' => $id_nivel_ensino]);

	echo json_encode([
		'status' => 'success',
		'disciplinas' => $stmt->fetchAll(PDO::FETCH_ASSOC)
	]);
	exit;

} catch (Throwable $e) {
	echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
	exit;
}
?>