<?php
// app/controllers/professor/deleteProfessor.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

// Verifica se o método é POST
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
		// Tabelas dependentes e coluna que referencia o professor
		$dependencyTables = [
				'professor_disciplinas'	 => 'id_professor',
				'professor_restricoes'	=> 'id_professor',
				'professor_turnos'	=> 'id_professor'
		];

		// Verifica se há algum vínculo em cada tabela
		foreach ($dependencyTables as $table => $column) {
				$stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM {$table} WHERE {$column} = ?");
				$stmt->execute([$id]);
				$result = $stmt->fetch(PDO::FETCH_ASSOC);
				if ($result && $result['count'] > 0) {
						echo json_encode([
								'status' => 'error',
								'message' => "Não é possível excluir o(a) professor(a), pois há registros vinculados."
						]);
						exit;
				}
		} 

		// Se não houver vínculos, procede com a exclusão
		$stmt = $pdo->prepare("DELETE FROM professor WHERE id_professor = ?");
		$stmt->execute([$id]);

		if ($stmt->rowCount() > 0) {
				echo json_encode(['status' => 'success', 'message' => 'Professor excluído com sucesso.']);
		} else {
				echo json_encode(['status' => 'error', 'message' => 'Professor não encontrado.']);
		}
} catch (PDOException $e) {
		echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir: ' . $e->getMessage()]);
}
?>
