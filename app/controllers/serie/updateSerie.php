<?php
// app/controllers/serie/updateSerie.php 
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

$id_serie = intval($_POST['id_serie'] ?? 0);
$id_nivel_ensino = intval($_POST['id_nivel_ensino'] ?? 0);
$nome_serie = trim($_POST['nome_serie'] ?? '');
$total_aulas_semana = intval($_POST['total_aulas_semana'] ?? 0);

if ($id_serie <= 0 || $id_nivel_ensino <= 0 || empty($nome_serie) || $total_aulas_semana < 0) {
	echo json_encode(['status' => 'error', 'message' => 'Dados incompletos.']);
	exit;
}

try {
	$stmt = $pdo->prepare("UPDATE serie SET id_nivel_ensino = ?, nome_serie = ?, total_aulas_semana = ? WHERE id_serie = ?");
	$stmt->execute([$id_nivel_ensino, $nome_serie, $total_aulas_semana, $id_serie]);

	if ($stmt->rowCount() > 0) {
		echo json_encode(['status' => 'success', 'message' => 'Série atualizada com sucesso.']);
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Nenhuma alteração realizada ou série não encontrada.']);
	}
} catch (PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>
