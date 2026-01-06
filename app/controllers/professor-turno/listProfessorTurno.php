<?php
// app/controllers/professor-turno/listProfessorTurno.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$id_professor = intval($_GET['id_professor'] ?? 0);

try {
	if ($id_professor > 0) {
		// Seleciona somente os turnos daquele professor
		$stmt = $pdo->prepare("
			SELECT pt.*, t.nome_turno
			FROM professor_turnos pt
			JOIN turno t ON pt.id_turno = t.id_turno
			WHERE pt.id_professor = ?
		");
		$stmt->execute([$id_professor]);
	} else {
		// Lista geral (se quiser exibir todos os vínculos de todos os professores)
		$stmt = $pdo->query("
			SELECT pt.*, t.nome_turno, p.nome_completo
			FROM professor_turnos pt
			JOIN turno t ON pt.id_turno = t.id_turno
			JOIN professor p ON pt.id_professor = p.id_professor
			ORDER BY p.nome_completo, t.nome_turno
		");
	}
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode(['status' => 'success', 'data' => $rows]);
} catch (PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>