<?php
// app/controllers/updateDisciplina.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$id = intval($_POST['id_disciplina'] ?? 0);
	$nome = trim($_POST['nome_disciplina'] ?? '');
	$sigla = trim($_POST['sigla_disciplina'] ?? '');

	if ($id <= 0 || empty($nome) || empty($sigla)) {
		echo json_encode(['status' => 'error', 'message' => 'Dados incompletos.']);
		exit;
	}

	try {
		$stmt = $pdo->prepare("UPDATE disciplina
							   SET nome_disciplina = ?, sigla_disciplina = ?
							   WHERE id_disciplina = ?");
		$stmt->execute([$nome, $sigla, $id]);

		if ($stmt->rowCount() > 0) {
			echo json_encode(['status' => 'success', 'message' => 'Disciplina atualizada com sucesso.']);
		} else {
			echo json_encode(['status' => 'error', 'message' => 'Nenhuma alteração ou registro não encontrado.']);
		}
	} catch (PDOException $e) {
		if ($e->getCode() == 23000) {
			echo json_encode(['status' => 'error', 'message' => 'Já existe uma disciplina com esse nome ou sigla.']);
		} else {
			echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
		}
	}
} else {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
}
?>
