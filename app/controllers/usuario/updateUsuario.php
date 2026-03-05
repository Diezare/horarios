<?php
// app/controllers/usuario/updateUsuario.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

$id             = intval($_POST['id_usuario'] ?? 0);
$nome           = trim($_POST['nome_usuario'] ?? '');
$email          = trim($_POST['email_usuario'] ?? '');
$senha          = $_POST['senha_usuario'] ?? '';
$situacao       = trim($_POST['situacao_usuario'] ?? 'Ativo');
$nivelUsuario   = trim($_POST['nivel_usuario'] ?? 'Usuário');
$removeFoto     = isset($_POST['remove_foto']) && $_POST['remove_foto'] == '1';

if ($id <= 0 || empty($nome) || empty($email)) {
	echo json_encode(['status' => 'error', 'message' => 'Dados incompletos (ID, Nome, E-mail).']);
	exit;
}

// Monta update base
$queryFields = "nome_usuario = ?, email_usuario = ?, situacao_usuario = ?, nivel_usuario = ?";
$params      = [$nome, $email, $situacao, $nivelUsuario];

// Se uma nova senha for fornecida
if (!empty($senha)) {
	$senhaCriptografada = password_hash($senha, PASSWORD_DEFAULT);
	$queryFields .= ", senha_usuario = ?";
	$params[] = $senhaCriptografada;
}

// Se pediu para remover foto
if ($removeFoto) {
	$queryFields .= ", imagem_usuario = NULL";
}

// Se enviou nova imagem
if (isset($_FILES['imagem_usuario']) && $_FILES['imagem_usuario']['error'] === 0) {
	$uploadDir = ROOT_PATH . 'app/assets/imgs/perfil';
	if (!is_dir($uploadDir)) {
		mkdir($uploadDir, 0777, true);
	}

	$fileName   = time() . '_' . basename($_FILES['imagem_usuario']['name']);
	$targetFile = $uploadDir . '/' . $fileName;

	if (move_uploaded_file($_FILES['imagem_usuario']['tmp_name'], $targetFile)) {
		// SALVAR CAMINHO RELATIVO
		$imagem_usuario = '/horarios/app/assets/imgs/perfil/' . $fileName;
		$queryFields .= ", imagem_usuario = ?";
		$params[] = $imagem_usuario;
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Erro ao fazer upload da imagem.']);
		exit;
	}
}

$params[] = $id;

try {
	$sql = "UPDATE usuario SET $queryFields WHERE id_usuario = ?";
	$stmt = $pdo->prepare($sql);
	$stmt->execute($params);

	echo json_encode(['status' => 'success', 'message' => 'Usuário atualizado com sucesso.']);

} catch (PDOException $e) {
	if ($e->getCode() == 23000) {
		echo json_encode(['status' => 'error', 'message' => 'Já existe um usuário com este e-mail.']);
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
	}
}
?>