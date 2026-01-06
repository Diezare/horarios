<?php
// app/controllers/deleteDisciplina.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$id = intval($_POST['id'] ?? 0);
	if ($id <= 0) {
		echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
		exit;
	}

	try {
		// 1) Verifica se a disciplina está vinculada em alguma tabela
		$dependencias = [
			'serie_disciplinas'  => 'id_disciplina',
			'professor_disciplinas' => 'id_disciplina',
			'horario' => 'id_disciplina',
			'professor_disciplinas_turmas' => 'id_disciplina'
			// inclua outras tabelas que referenciem a disciplina, se houver
		];

		foreach ($dependencias as $tabela => $coluna) {
			$sqlCheck = "SELECT COUNT(*) as qtd FROM {$tabela} WHERE {$coluna} = ?";
			$stmtCheck = $pdo->prepare($sqlCheck);
			$stmtCheck->execute([$id]);
			$result = $stmtCheck->fetch(PDO::FETCH_ASSOC);
			if ($result && intval($result['qtd']) > 0) {
				echo json_encode([
					'status' => 'error',
					'message' => "Não é possível excluir a disciplina, pois há registros vinculados."
				]);
				exit;
			}
		}

		// 2) Se não encontrou vínculos, prossegue com o DELETE
		$stmt = $pdo->prepare("DELETE FROM disciplina WHERE id_disciplina = ?");
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