<?php
// app/controllers/serie/listSerieByNivel.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$idNivel = intval($_GET['id_nivel'] ?? 0);

try {
	if ($idNivel > 0) {
		$stmt = $pdo->prepare("
			SELECT s.id_serie, s.nome_serie, s.id_nivel_ensino
			FROM serie s
			WHERE s.id_nivel_ensino = :idNivel
			ORDER BY s.nome_serie
		");
		$stmt->bindValue(':idNivel', $idNivel, PDO::PARAM_INT);
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		echo json_encode(['status' => 'success', 'data' => $rows]);
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Nível de ensino não informado.']);
	}
} catch (Exception $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>