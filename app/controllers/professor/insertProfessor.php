<?php
// app/controllers/professor/insertProfessor.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

$nomeCompleto = trim($_POST['nome_completo'] ?? '');
$nomeExibicao = trim($_POST['nome_exibicao'] ?? '');
$telefone     = trim($_POST['telefone'] ?? '');
$sexo         = trim($_POST['sexo'] ?? 'Masculino');
$limite       = trim($_POST['limite_aulas'] ?? '0');

if (empty($nomeCompleto)) {
	echo json_encode(['status' => 'error', 'message' => 'Preencha o nome completo.']);
	exit;
}
// Valida sexo
if (!in_array($sexo, ['Masculino','Feminino','Outro'])) {
	$sexo = 'Masculino';
}
// Valida limite int 0..99
$limiteInt = (int)$limite;
if ($limiteInt < 0) $limiteInt = 0;
if ($limiteInt > 99) $limiteInt = 99;

try {
	$stmt = $pdo->prepare("
		INSERT INTO professor (nome_completo, nome_exibicao, telefone, sexo, limite_aulas_fixa_semana)
		VALUES (?, ?, ?, ?, ?)
	");
	$stmt->execute([$nomeCompleto, $nomeExibicao, $telefone, $sexo, $limiteInt]);

	echo json_encode([
		'status'  => 'success',
		'message' => 'Professor inserido com sucesso!',
		'id'      => $pdo->lastInsertId()
	]);
} catch (PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro ao inserir: ' . $e->getMessage()]);
}
?>
