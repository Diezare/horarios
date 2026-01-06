<?php
// app/controllers/professor-categoria/updateProfessorCategoria.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

$id_categoria = intval($_POST['id_categoria'] ?? 0);
$professores  = $_POST['professores'] ?? [];

if ($id_categoria <= 0 || !is_array($professores)) {
	echo json_encode(['status' => 'error', 'message' => 'Dados incompletos ou inválidos.']);
	exit;
}

try {
	$pdo->beginTransaction();
	
	// Remove os vínculos atuais
	$stmtDel = $pdo->prepare("DELETE FROM professor_categoria WHERE id_categoria = ?");
	$stmtDel->execute([$id_categoria]);

	// Insere as novas associações
	$stmt = $pdo->prepare("INSERT INTO professor_categoria (id_professor, id_categoria) VALUES (?, ?)");
	foreach ($professores as $id_professor) {
		$id_prof = intval($id_professor);
		if ($id_prof > 0) {
			$stmt->execute([$id_prof, $id_categoria]);
		}
	}
	$pdo->commit();
	echo json_encode(['status' => 'success', 'message' => 'Associações atualizadas com sucesso!']);
} catch (PDOException $e) {
	$pdo->rollBack();
	echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar associações: ' . $e->getMessage()]);
}
?>