<?php
// app/controllers/turno/deleteTurno.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$id = intval($_POST['id'] ?? 0);
	if ($id <= 0) {
		echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
		exit;
	}

	try {
		// 1) Verifica vínculos em tabelas que referenciam o turno
		$dependencias = [
			'professor_turnos' => 'id_turno',
			'turma' => 'id_turno',
			'turno_dias' => 'id_turno'
		];

		foreach ($dependencias as $tabela => $coluna) {
			$sqlCheck = "SELECT COUNT(*) as count FROM {$tabela} WHERE {$coluna} = ?";
			$stmtCheck = $pdo->prepare($sqlCheck);
			$stmtCheck->execute([$id]);
			$result = $stmtCheck->fetch(PDO::FETCH_ASSOC);
			if ($result && intval($result['count']) > 0) {
				echo json_encode([
					'status' => 'error',
					'message' => "Não é possível excluir o turno, pois há registros vinculados."
				]);
				exit;
			}
		}

		// 2) Prossegue com a exclusão se não houver vínculos
		$stmt = $pdo->prepare("DELETE FROM turno WHERE id_turno = ?");
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