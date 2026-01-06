<?php
// app/controllers/sala-turno/listAllSalaTurno.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

try {
	$stmt = $pdo->query("SELECT st.*, t.nome_turno, tr.nome_turma FROM sala_turno st
			JOIN turno t ON st.id_turno = t.id_turno
			JOIN turma tr ON st.id_turma = tr.id_turma
			ORDER BY st.id_sala, st.id_turno");
	$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
	echo json_encode(['status' => 'success', 'data' => $data]);
} catch(PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro ao listar todas as vinculações: ' . $e->getMessage()]);
}
?>