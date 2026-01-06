<?php
// app/controllers/hora-aula-escolinha/deleteHoraAulaEscolinha.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$id = intval($_POST['id'] ?? 0);
	if ($id <= 0) {
		echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
		exit;
	}

	try {
		// Caso futuramente precise checar vínculos, você pode reativar essa parte com as colunas corretas

		// Exclui diretamente da tabela configuracao_hora_aula_escolinha
		$stmt = $pdo->prepare("DELETE FROM configuracao_hora_aula_escolinha WHERE id_configuracao = ?");
		$stmt->execute([$id]);

		if ($stmt->rowCount() > 0) {
			echo json_encode(['status' => 'success', 'message' => 'Excluído com sucesso.']);
		} else {
			echo json_encode(['status' => 'error', 'message' => 'Registro não encontrado.']);
		}
	} catch (PDOException $e) {
		echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir: ' . $e->getMessage()]);
	}
} else {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
}
?>
