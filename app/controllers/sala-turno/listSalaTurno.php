<?php
// app/controllers/sala-turno/listSalaTurno.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$id_sala = intval($_GET['id_sala'] ?? 0);

try {
	if ($id_sala > 0) {
		$stmt = $pdo->prepare("
			SELECT
				st.id_sala,
				st.id_turno,
				st.id_turma,
				t.nome_turno,
				tr.nome_turma,
				se.id_serie,
				se.nome_serie,
				se.id_nivel_ensino,
				n.nome_nivel_ensino
			FROM sala_turno st
			JOIN turno t ON st.id_turno = t.id_turno
			JOIN turma tr  ON st.id_turma = tr.id_turma
			JOIN serie se  ON se.id_serie = tr.id_serie
			JOIN nivel_ensino n ON n.id_nivel_ensino = se.id_nivel_ensino
			WHERE st.id_sala = ?
		");
		$stmt->execute([$id_sala]);
	} else {
		$stmt = $pdo->query("
			SELECT
				st.id_sala,
				st.id_turno,
				st.id_turma,
				t.nome_turno,
				tr.nome_turma,
				se.id_serie,
				se.nome_serie,
				se.id_nivel_ensino,
				n.nome_nivel_ensino
			FROM sala_turno st
			JOIN turno t ON st.id_turno = t.id_turno
			JOIN turma tr ON st.id_turma = tr.id_turma
			JOIN serie se ON se.id_serie = tr.id_serie
			JOIN nivel_ensino n ON n.id_nivel_ensino = se.id_nivel_ensino
		");
	}

	$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode(['status'=>'success','data'=>$data]);
} catch (PDOException $e) {
	echo json_encode([
		'status'=>'error',
		'message'=>'Erro ao listar vinculações: ' . $e->getMessage()
	]);
}
?>