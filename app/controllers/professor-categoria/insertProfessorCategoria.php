<?php
// app/controllers/professor-categoria/insertProfessorCategoria.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

$id_categoria  = intval($_POST['id_categoria'] ?? 0);
$professores   = $_POST['professores'] ?? []; // array de id_professor

if ($id_categoria <= 0) {
	echo json_encode([
		'status'  => 'error',
		'message' => 'Categoria inválida ou dados de professores inválidos.'
	]);
	exit;
}

try {
	$pdo->beginTransaction();

	// Remove todos os vínculos atuais desse id_categoria
	$stmtDel = $pdo->prepare("DELETE FROM professor_categoria WHERE id_categoria = ?");
	$stmtDel->execute([$id_categoria]);

	// Insere as novas associações (se houver professores selecionados)
	if (!empty($professores)) {
		$stmt = $pdo->prepare("
			INSERT INTO professor_categoria (id_professor, id_categoria)
			VALUES (?, ?)
		");
		foreach ($professores as $prof) {
			$id_prof = intval($prof);
			if ($id_prof > 0) {
				$stmt->execute([$id_prof, $id_categoria]);
			}
		}
	}

	$pdo->commit();
	echo json_encode([
		'status'  => 'success',
		'message' => 'Professores vinculados com sucesso!'
	]);
} catch (PDOException $e) {
	$pdo->rollBack();
	echo json_encode([
		'status'  => 'error',
		'message' => 'Erro ao vincular professores: ' . $e->getMessage()
	]);
}
?>