<?php
// app/controllers/usuario/updateUsuario.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$id           = intval($_POST['id_usuario'] ?? 0);
	$nome         = trim($_POST['nome_usuario'] ?? '');
	$email        = trim($_POST['email_usuario'] ?? '');
	$senha        = $_POST['senha_usuario'] ?? '';
	$situacao     = trim($_POST['situacao_usuario'] ?? 'Ativo');
	$nivelUsuario = trim($_POST['nivel_usuario'] ?? 'Usuário'); // <-- NOVO
	$imagem_usuario = null;

	if ($id <= 0 || empty($nome) || empty($email)) {
		echo json_encode(['status' => 'error', 'message' => 'Dados incompletos (ID, Nome, E-mail).']);
		exit;
	}

	// Se uma nova senha for fornecida, criptografa
	$senhaQuery  = "";
	$params      = [$nome, $email, $situacao, $nivelUsuario]; // <-- ajustado
	$queryFields = "nome_usuario = ?, email_usuario = ?, situacao_usuario = ?, nivel_usuario = ?"; 

	if (!empty($senha)) {
		$senhaCriptografada = password_hash($senha, PASSWORD_DEFAULT);
		$senhaQuery = ", senha_usuario = ?";
		$params[]   = $senhaCriptografada;
		$queryFields .= $senhaQuery;
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
			$queryFields .= ", imagem_usuario = ?";
			$params[]    = $imagem_usuario;
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

		if ($stmt->rowCount() > 0) {
			echo json_encode(['status' => 'success', 'message' => 'Usuário atualizado com sucesso.']);
		} else {
			echo json_encode(['status' => 'error', 'message' => 'Nenhuma alteração ou registro não encontrado.']);
		}
	} catch (PDOException $e) {
		if ($e->getCode() == 23000) {
			echo json_encode(['status' => 'error', 'message' => 'Já existe um usuário com este e-mail.']);
		} else {
			echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
		}
	}
} else {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
}
?>