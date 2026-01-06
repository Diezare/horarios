<?php
// app/controllers/sala/insertSala.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

// Novo campo: id_ano_letivo
$id_ano_letivo = intval($_POST['id_ano_letivo'] ?? 0);
$nome_sala = trim($_POST['nome_sala'] ?? '');
$max_carteiras = intval($_POST['max_carteiras'] ?? 0);
$max_cadeiras = intval($_POST['max_cadeiras'] ?? 0);
$capacidade_alunos = intval($_POST['capacidade_alunos'] ?? 0);
$localizacao = trim($_POST['localizacao'] ?? '');
$recursos = trim($_POST['recursos'] ?? '');

// Validação: nome e ano letivo são obrigatórios
if(empty($nome_sala)) {
	echo json_encode(['status' => 'error', 'message' => 'Nome da sala é obrigatório.']);
	exit;
}
if($id_ano_letivo <= 0) {
	echo json_encode(['status' => 'error', 'message' => 'Ano letivo é obrigatório.']);
	exit;
}

try {
	$stmt = $pdo->prepare("INSERT INTO sala (id_ano_letivo, nome_sala, max_carteiras, max_cadeiras, capacidade_alunos, localizacao, recursos) VALUES (?, ?, ?, ?, ?, ?, ?)");
	$stmt->execute([$id_ano_letivo, $nome_sala, $max_carteiras, $max_cadeiras, $capacidade_alunos, $localizacao, $recursos]);
	echo json_encode(['status' => 'success', 'message' => 'Sala adicionada com sucesso.']);
} catch(PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro ao adicionar sala: ' . $e->getMessage()]);
}
?>
