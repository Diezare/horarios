<?php
// app/controllers/turno-dias/updateTurnoDias.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

/*
 Espera:
  - POST id_turno
  - POST id_nivel_ensino
  - POST dias => JSON string:
    [
      { "dia_semana": "Domingo", "aulas_no_dia": 3 },
      ...
    ]
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status'=>'error','message'=>'Método inválido.']);
	exit;
}

$id_turno = (int)($_POST['id_turno'] ?? 0);
$id_nivel_ensino = (int)($_POST['id_nivel_ensino'] ?? 0);
$diasJson = $_POST['dias'] ?? '';

if ($id_turno <= 0 || $id_nivel_ensino <= 0 || $diasJson === '') {
	echo json_encode(['status'=>'error','message'=>'Dados incompletos (turno, nível e dias).']);
	exit;
}

$diasArr = json_decode($diasJson, true);
if (!is_array($diasArr)) {
	echo json_encode(['status'=>'error','message'=>'Formato (JSON) inválido.']);
	exit;
}

$validos = ["Domingo","Segunda","Terca","Quarta","Quinta","Sexta","Sabado"];

try {
	$pdo->beginTransaction();

	// Remove todos os registros atuais desse turno + nível
	$stmtDel = $pdo->prepare("DELETE FROM turno_dias_nivel WHERE id_turno = ? AND id_nivel_ensino = ?");
	$stmtDel->execute([$id_turno, $id_nivel_ensino]);

	// Reinsere
	$stmtIns = $pdo->prepare("
		INSERT INTO turno_dias_nivel (id_turno, id_nivel_ensino, dia_semana, aulas_no_dia)
		VALUES (?, ?, ?, ?)
	");

	foreach ($diasArr as $item) {
		$dia_semana   = $item['dia_semana'] ?? '';
		$aulas_no_dia = (int)($item['aulas_no_dia'] ?? 0);

		if (!in_array($dia_semana, $validos, true)) continue;

		if ($aulas_no_dia < 0)  $aulas_no_dia = 0;
		if ($aulas_no_dia > 99) $aulas_no_dia = 99;

		$stmtIns->execute([$id_turno, $id_nivel_ensino, $dia_semana, $aulas_no_dia]);
	}

	$pdo->commit();
	echo json_encode(['status'=>'success','message'=>'Dias do turno (por nível) atualizados com sucesso!']);

} catch (PDOException $e) {
	$pdo->rollBack();
	echo json_encode(['status'=>'error','message'=>'Erro ao atualizar: '.$e->getMessage()]);
}
?>