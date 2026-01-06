<?php
// app/controllers/usuario/insertUsuario.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

// Recebe os dados
$nome         = trim($_POST['nome_usuario'] ?? '');
$email        = trim($_POST['email_usuario'] ?? '');
$senha        = $_POST['senha_usuario'] ?? '';
$situacao     = trim($_POST['situacao_usuario'] ?? 'Ativo'); 
$nivelUsuario = trim($_POST['nivel_usuario'] ?? 'Usuário'); // <-- NOVO
$imagem_usuario = null;

if (empty($nome) || empty($email) || empty($senha)) {
	echo json_encode(['status' => 'error', 'message' => 'Preencha os campos obrigatórios (Nome, E-mail, Senha).']);
	exit;
}

// Verifica se o e-mail já está cadastrado
$stmt = $pdo->prepare("SELECT COUNT(*) as qtd FROM usuario WHERE email_usuario = ?");
$stmt->execute([$email]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row && intval($row['qtd']) > 0) {
	echo json_encode(['status' => 'error', 'message' => 'Já existe um usuário com este e-mail.']);
	exit;
}

// Processa a imagem se enviada
if (isset($_FILES['imagem_usuario']) && $_FILES['imagem_usuario']['error'] === 0) {
	$uploadDir = ROOT_PATH . '/app/assets/imgs/perfil';
	if (!is_dir($uploadDir)) {
		mkdir($uploadDir, 0777, true);
	}
	$fileName = time() . '_' . basename($_FILES['imagem_usuario']['name']);
	$targetFile = $uploadDir . '/' . $fileName;
	if (move_uploaded_file($_FILES['imagem_usuario']['tmp_name'], $targetFile)) {
		$imagem_usuario = ASSETS_PATH . '/imgs/perfil/' . $fileName;
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Erro ao fazer upload da imagem.']);
		exit;
	}
}

// Criptografa a senha
$senhaCriptografada = password_hash($senha, PASSWORD_DEFAULT);

try {
	$stmt = $pdo->prepare("
		INSERT INTO usuario (
			nome_usuario, 
			email_usuario, 
			senha_usuario, 
			situacao_usuario,
			nivel_usuario,       -- NOVO
			imagem_usuario
		) VALUES (?, ?, ?, ?, ?, ?)
	");
	$stmt->execute([
		$nome,
		$email,
		$senhaCriptografada,
		$situacao,
		$nivelUsuario,      // Passamos o valor do dropdown
		$imagem_usuario
	]);

	echo json_encode([
		'status'  => 'success',
		'message' => 'Usuário cadastrado com sucesso!',
		'id'      => $pdo->lastInsertId()
	]);
} catch (PDOException $e) {
	if ($e->getCode() == 23000) {
		echo json_encode(['status' => 'error', 'message' => 'Já existe um usuário com este e-mail.']);
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Erro ao inserir: ' . $e->getMessage()]);
	}
}
?>