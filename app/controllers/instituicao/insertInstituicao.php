<?php 
// app/controllers/instituicao/insertInstituicao.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$nome	 = trim($_POST['nome_instituicao'] ?? '');
$cnpj	 = trim($_POST['cnpj_instituicao'] ?? '');
$endereco = trim($_POST['endereco_instituicao'] ?? '');
$telefone = trim($_POST['telefone_instituicao'] ?? '');
$email	= trim($_POST['email_instituicao'] ?? '');
$imagem_instituicao = null;

if (empty($nome) || empty($cnpj)) {
	echo json_encode(['status' => 'error', 'message' => 'Preencha, ao menos, Nome e CNPJ.']);
	exit;
}

// Verifica se já existe uma instituição cadastrada
try {
	$stmt = $pdo->query("SELECT COUNT(*) as qtd FROM instituicao");
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($row && intval($row['qtd']) > 0) {
		echo json_encode([
			'status' => 'error',
			'message' => 'Já existe uma instituição cadastrada. Para inserir outra, contate o administrador do sistema.'
		]);
		exit;
	}
} catch (Exception $e) {
	echo json_encode([
		'status' => 'error', 
		'message' => 'Erro ao verificar existência de instituição: ' . $e->getMessage()
	]);
	exit;
}

if (isset($_FILES['imagem_instituicao']) && $_FILES['imagem_instituicao']['error'] === 0) {
	if (!is_dir(LOGO_PATH)) {
		mkdir(LOGO_PATH, 0777, true);
	}
	$fileName   = time() . '_' . basename($_FILES['imagem_instituicao']['name']);
	$targetFile = LOGO_PATH . '/' . $fileName;
	
	if (move_uploaded_file($_FILES['imagem_instituicao']['tmp_name'], $targetFile)) {
		$imagem_instituicao = LOGO_URL . '/' . $fileName;
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Erro ao fazer upload da imagem.']);
		exit;
	}
} else {
	echo json_encode(['status' => 'error', 'message' => 'O campo Imagem é obrigatório.']);
	exit;
}

try {
	$stmt = $pdo->prepare("
		INSERT INTO instituicao (
			nome_instituicao,
			cnpj_instituicao,
			endereco_instituicao,
			telefone_instituicao,
			email_instituicao,
			imagem_instituicao
		) VALUES (?, ?, ?, ?, ?, ?)
	");
	$stmt->execute([$nome, $cnpj, $endereco, $telefone, $email, $imagem_instituicao]);

	echo json_encode([
		'status'  => 'success',
		'message' => 'Instituição inserida com sucesso!',
		'id'	  => $pdo->lastInsertId()
	]);
} catch (PDOException $e) {
	if ($e->getCode() == 23000) {
		echo json_encode([
			'status'  => 'error',
			'message' => 'Já existe uma instituição com este CNPJ.'
		]);
	} else {
		echo json_encode([
			'status'  => 'error',
			'message' => 'Erro ao inserir: ' . $e->getMessage()
		]);
	}
}
?>