<?php
// app/controllers/turno/listTurnoByUserAnoNivel.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$idUsuario     = (int)($_SESSION['id_usuario'] ?? 0);
$idAnoLetivo   = (int)($_GET['id_ano_letivo'] ?? 0);
$idNivelEnsino = (int)($_GET['id_nivel_ensino'] ?? 0);

if ($idUsuario <= 0 || $idAnoLetivo <= 0 || $idNivelEnsino <= 0) {
	echo json_encode(['status' => 'error', 'message' => 'Parâmetros inválidos.']);
	exit;
}

try {
	$sql = "
		SELECT DISTINCT
			tur.id_turno,
			tur.nome_turno
		FROM turma t
		JOIN serie s            ON t.id_serie = s.id_serie
		JOIN nivel_ensino ne    ON s.id_nivel_ensino = ne.id_nivel_ensino
		JOIN turno tur          ON t.id_turno = tur.id_turno
		JOIN usuario_niveis un  ON un.id_nivel_ensino = ne.id_nivel_ensino
		WHERE t.id_ano_letivo = :ano
		  AND ne.id_nivel_ensino = :nivel
		  AND un.id_usuario = :idUsuario
		ORDER BY tur.nome_turno
	";
	$stmt = $pdo->prepare($sql);
	$stmt->bindValue(':ano', $idAnoLetivo, PDO::PARAM_INT);
	$stmt->bindValue(':nivel', $idNivelEnsino, PDO::PARAM_INT);
	$stmt->bindValue(':idUsuario', $idUsuario, PDO::PARAM_INT);
	$stmt->execute();

	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	echo json_encode(['status' => 'success', 'data' => $rows]);

} catch (Throwable $e) {
	echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>