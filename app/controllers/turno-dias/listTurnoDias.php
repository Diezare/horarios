<?php
// app/controllers/turno-dias/listTurnoDias.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$id_turno = isset($_GET['id_turno']) ? intval($_GET['id_turno']) : 0;

try {
	if ($id_turno > 0) {
		$stmt = $pdo->prepare("
			SELECT *
			  FROM turno_dias
			 WHERE id_turno = ?
			 ORDER BY FIELD(dia_semana,'Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado')
		");
		$stmt->execute([$id_turno]);
	} else {
		// Se quiser listar de todos os turnos
		$stmt = $pdo->query("
			SELECT *
			  FROM turno_dias
			 ORDER BY id_turno_dia
		");
	}
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode(['status'=>'success','data'=>$rows]);

} catch (PDOException $e) {
	echo json_encode(['status'=>'error','message'=>'Erro: '.$e->getMessage()]);
}
?>