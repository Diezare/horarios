<?php
// app/controllers/sala/updateSala.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);
$id_ano_letivo = intval($_POST['id_ano_letivo'] ?? 0);
$nome_sala = trim($_POST['nome_sala'] ?? '');
$max_carteiras = intval($_POST['max_carteiras'] ?? 0);
$max_cadeiras = intval($_POST['max_cadeiras'] ?? 0);
$capacidade_alunos = intval($_POST['capacidade_alunos'] ?? 0);
$localizacao = trim($_POST['localizacao'] ?? '');
$recursos = trim($_POST['recursos'] ?? '');

if($id <= 0 || empty($nome_sala)) {
	echo json_encode(['status' => 'error', 'message' => 'Dados inválidos.']);
	exit;
}
if($id_ano_letivo <= 0) {
	echo json_encode(['status' => 'error', 'message' => 'Ano letivo é obrigatório.']);
	exit;
}

try {
	$stmt = $pdo->prepare("UPDATE sala SET id_ano_letivo = ?, nome_sala = ?, max_carteiras = ?, max_cadeiras = ?, capacidade_alunos = ?, localizacao = ?, recursos = ? WHERE id_sala = ?");
	$stmt->execute([$id_ano_letivo, $nome_sala, $max_carteiras, $max_cadeiras, $capacidade_alunos, $localizacao, $recursos, $id]);
	echo json_encode(['status' => 'success', 'message' => 'Sala atualizada com sucesso.']);
} catch(PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar sala: ' . $e->getMessage()]);
}
?>
