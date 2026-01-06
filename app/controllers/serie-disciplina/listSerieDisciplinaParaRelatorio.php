<?php
// app/controllers/serie-disciplina/listSerieDisciplinaParaRelatorio.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$idDisc = isset($_GET['id_disc']) ? intval($_GET['id_disc']) : 0;

try {
	if ($idDisc > 0) {
		// Exemplo: retorna series e nivel_ensino ligadas a esta disciplina
		$sql = "
			SELECT s.id_serie, s.nome_serie, n.nome_nivel_ensino
			  FROM serie_disciplinas sd
			  JOIN serie s ON sd.id_serie = s.id_serie
			  JOIN nivel_ensino n ON s.id_nivel_ensino = n.id_nivel_ensino
			 WHERE sd.id_disciplina = :id
			 ORDER BY s.nome_serie
		";
		$stmt = $pdo->prepare($sql);
		$stmt->bindValue(':id', $idDisc, PDO::PARAM_INT);
		$stmt->execute();

		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		echo json_encode([
			'status' => 'success',
			'data'   => $rows
		]);
	} else {
		echo json_encode([
			'status' => 'error',
			'message' => 'ID Disciplina não informado.'
		]);
	}
} catch (PDOException $e) {
	echo json_encode([
		'status' => 'error',
		'message' => 'Erro: ' . $e->getMessage()
	]);
}
?>