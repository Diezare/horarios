<?php
// app/controllers/sala/listSala.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

try {
	if(isset($_GET['id'])) {
		$id = intval($_GET['id']);
		$stmt = $pdo->prepare("
			SELECT 
			s.*,
			al.ano,
			(
				SELECT GROUP_CONCAT(
						 CONCAT(
						 LEFT(tu.nome_turno,1), 
						 ': ', 
						 se.nome_serie, 
						 ' ', 
						 tm.nome_turma
						 )
						 SEPARATOR ', '
					 )
				FROM sala_turno st
				JOIN turma tm	 ON st.id_turma = tm.id_turma
				JOIN serie se	 ON tm.id_serie = se.id_serie
				JOIN turno tu	 ON st.id_turno = tu.id_turno
				WHERE st.id_sala = s.id_sala
			) AS turmas_vinculadas
			FROM sala s 
			LEFT JOIN ano_letivo al ON s.id_ano_letivo = al.id_ano_letivo
			WHERE s.id_sala = ?
		");
		$stmt->execute([$id]);
		$data = $stmt->fetch(PDO::FETCH_ASSOC);
	} else {
		// Versão quando não tem ?id=	 (lista geral)
		$stmt = $pdo->query("
			SELECT 
			s.*,
			al.ano,
			(
				SELECT GROUP_CONCAT(
						 CONCAT(
						 LEFT(tu.nome_turno,1), 
						 ': ', 
						 se.nome_serie, 
						 ' ', 
						 tm.nome_turma
						 )
						 SEPARATOR ', '
					 )
				FROM sala_turno st
				JOIN turma tm	 ON st.id_turma = tm.id_turma
				JOIN serie se	 ON tm.id_serie = se.id_serie
				JOIN turno tu	 ON st.id_turno = tu.id_turno
				WHERE st.id_sala = s.id_sala
			) AS turmas_vinculadas
			FROM sala s
			LEFT JOIN ano_letivo al ON s.id_ano_letivo = al.id_ano_letivo
			ORDER BY s.nome_sala
		");
		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	echo json_encode(['status' => 'success', 'data' => $data]);
} catch(PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro ao listar sala: ' . $e->getMessage()]);
}
?>