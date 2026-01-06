<?php
// app/controllers/instituicao/updateInstituicao.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$id	   = intval($_POST['id_instituicao'] ?? 0);
	$nome	 = trim($_POST['nome_instituicao'] ?? '');
	$cnpj	 = trim($_POST['cnpj_instituicao'] ?? '');
	$endereco = trim($_POST['endereco_instituicao'] ?? '');
	$telefone = trim($_POST['telefone_instituicao'] ?? '');
	$email	= trim($_POST['email_instituicao'] ?? '');
	
	// Flag para remoção da logo
	$remove_logo = isset($_POST['remove_logo']) && $_POST['remove_logo'] === '1';

	// Verifica se foi enviado um novo arquivo
	$imagem_instituicao = null;
	if (isset($_FILES['imagem_instituicao']) && $_FILES['imagem_instituicao']['error'] === 0) {
		// Aqui você implementa o upload da imagem, como já fez anteriormente
		// Exemplo:
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
	}
	
	if ($id <= 0 || empty($nome) || empty($cnpj)) {
		echo json_encode(['status' => 'error', 'message' => 'Dados incompletos (ID, Nome, CNPJ).']);
		exit;
	}

	try {
		// Monta a query base
		$sql = "UPDATE instituicao SET 
					nome_instituicao = ?,
					cnpj_instituicao = ?,
					endereco_instituicao = ?,
					telefone_instituicao = ?,
					email_instituicao = ?";
		$params = [$nome, $cnpj, $endereco, $telefone, $email];
		
		// Se um novo arquivo foi enviado, atualiza a logo
		if ($imagem_instituicao !== null) {
			$sql .= ", imagem_instituicao = ?";
			$params[] = $imagem_instituicao;
		} elseif ($remove_logo) {
			// Se foi solicitado remover a logo, seta a coluna para NULL
			$sql .= ", imagem_instituicao = NULL";
		}
		
		$sql .= " WHERE id_instituicao = ?";
		$params[] = $id;
		
		$stmt = $pdo->prepare($sql);
		$stmt->execute($params);
		
		// Se o update não alterou linhas, mas o remove_logo foi solicitado, consideramos como sucesso
		if ($stmt->rowCount() > 0 || $remove_logo) {
			echo json_encode(['status' => 'success', 'message' => 'Instituição atualizada com sucesso.']);
		} else {
			echo json_encode(['status' => 'error', 'message' => 'Nenhuma alteração ou registro não encontrado.']);
		}
	} catch (PDOException $e) {
		if ($e->getCode() == 23000) {
			echo json_encode(['status' => 'error', 'message' => 'Já existe uma instituição com este CNPJ.']);
		} else {
			echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
		}
	}
} else {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
}
?>