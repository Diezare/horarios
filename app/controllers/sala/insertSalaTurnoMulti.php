<?php
// app/controllers/sala/insertSalaTurnoMulti.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$id_sala    = intval($_POST['id_sala']    ?? 0);
$vinculosStr = trim($_POST['vinculos']   ?? '');

if (!$id_sala) {
	echo json_encode(['status'=>'error','message'=>'ID da sala inválido.']);
	exit;
}

// Se vier vazio (não marcou nada), iremos apagar tudo e não inserir nada
// (Isso remove todos os turnos).
$vinculosArr = [];
if (!empty($vinculosStr)) {
	$vinculosArr = explode('|', $vinculosStr);
}

try {
	$pdo->beginTransaction();

	// 1) Apaga todos os vínculos existentes para esta sala
	$stmtDelete = $pdo->prepare("DELETE FROM sala_turno WHERE id_sala = ?");
	$stmtDelete->execute([$id_sala]);

	// 2) Reinsere somente os vínculos informados agora
	// Formato: "id_turno,id_nivel,id_turma"
	foreach($vinculosArr as $item) {
		$partes = explode(',', $item); // ex: ["1", "2", "10"]
		if (count($partes) !== 3) {
			continue; // dado inválido, ignora
		}
		$id_turno = (int)$partes[0];
		// $id_nivel = (int)$partes[1]; // se quiser guardar em outro lugar
		$id_turma = (int)$partes[2];

		// Insert
		$stmtInsert = $pdo->prepare("INSERT INTO sala_turno (id_sala, id_turno, id_turma) VALUES (?,?,?)");
		$stmtInsert->execute([$id_sala, $id_turno, $id_turma]);
	}

	$pdo->commit();
	echo json_encode(['status'=>'success','message'=>'Vínculos inseridos/atualizados com sucesso!']);
} catch(Exception $e) {
	$pdo->rollBack();
	echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>