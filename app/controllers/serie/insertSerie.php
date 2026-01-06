<?php
// app/controllers/serie/insertSerie.php 
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

$id_nivel_ensino = intval($_POST['id_nivel_ensino'] ?? 0);
$nome_serie = trim($_POST['nome_serie'] ?? '');
$total_aulas_semana = intval($_POST['total_aulas_semana'] ?? 0);

if ($id_nivel_ensino <= 0 || empty($nome_serie) || $total_aulas_semana < 0) {
	echo json_encode(['status' => 'error', 'message' => 'Preencha todos os campos corretamente.']);
	exit;
}

try {
	$stmt = $pdo->prepare("INSERT INTO serie (id_nivel_ensino, nome_serie, total_aulas_semana) VALUES (?, ?, ?)");
	$stmt->execute([$id_nivel_ensino, $nome_serie, $total_aulas_semana]);

	echo json_encode([
		'status' => 'success',
		'message' => 'Série inserida com sucesso!',
		'id' => $pdo->lastInsertId()
	]);
} catch (PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro ao inserir: ' . $e->getMessage()]);
}
?>
