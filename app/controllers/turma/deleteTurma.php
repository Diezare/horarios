<?php
// app/controllers/turma/deleteTurma.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
	echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
	exit;
}

try {
	// 1) Verifica vínculos em tabelas que referenciam a turma
	$dependencias = [
		'horario' => 'id_turma',
		'historico_horario' => 'id_turma',
		'professor_disciplinas_turmas' => 'id_turma'
	];

	foreach ($dependencias as $tabela => $coluna) {
		$sqlCheck = "SELECT COUNT(*) as count FROM {$tabela} WHERE {$coluna} = ?";
		$stmtCheck = $pdo->prepare($sqlCheck);
		$stmtCheck->execute([$id]);
		$result = $stmtCheck->fetch(PDO::FETCH_ASSOC);
		if ($result && intval($result['count']) > 0) {
			echo json_encode([
				'status' => 'error',
				'message' => "Não é possível excluir a turma, pois há registros vinculados."
			]);
			exit;
		}
	} 

	// 2) Prossegue com a exclusão se não houver vínculos
	$stmt = $pdo->prepare("DELETE FROM turma WHERE id_turma = ?");
	$stmt->execute([$id]);

	if ($stmt->rowCount() > 0) {
		echo json_encode(['status' => 'success', 'message' => 'Turma excluída com sucesso.']);
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Turma não encontrada.']);
	}
} catch (PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir: ' . $e->getMessage()]);
}
?>
