<?php
// app/controllers/turno-dias/updateTurnoDias.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

/*
 Esperamos:
  - POST id_turno
  - POST dias => JSON string com array de objetos, ex:
    [
      { "dia_semana": "Domingo", "aulas_no_dia": 3 },
      { "dia_semana": "Segunda", "aulas_no_dia": 5 },
      ...
    ]
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status'=>'error','message'=>'Método inválido.']);
	exit;
}

$id_turno = intval($_POST['id_turno'] ?? 0);
$diasJson = $_POST['dias'] ?? '';

if ($id_turno <= 0 || empty($diasJson)) {
	echo json_encode(['status'=>'error','message'=>'Dados incompletos.']);
	exit;
}

$diasArr = json_decode($diasJson, true);
if (!is_array($diasArr)) {
	echo json_encode(['status'=>'error','message'=>'Formato de dados (JSON) inválido.']);
	exit;
}

try {
	$pdo->beginTransaction();

	// Remove todos os registros atuais
	$stmtDel = $pdo->prepare("DELETE FROM turno_dias WHERE id_turno = ?");
	$stmtDel->execute([$id_turno]);

	// Reinsere
	$stmtIns = $pdo->prepare("
		INSERT INTO turno_dias (id_turno, dia_semana, aulas_no_dia)
		VALUES (?, ?, ?)
	");

	$validos = ["Domingo","Segunda","Terca","Quarta","Quinta","Sexta","Sabado"];

	foreach ($diasArr as $item) {
		$dia_semana   = $item['dia_semana']   ?? '';
		$aulas_no_dia = intval($item['aulas_no_dia'] ?? 0);

		if (!in_array($dia_semana, $validos)) {
			continue; // ignora se o dia for inválido
		}
		if ($aulas_no_dia < 0)  $aulas_no_dia = 0;
		if ($aulas_no_dia > 99) $aulas_no_dia = 99;

		$stmtIns->execute([$id_turno, $dia_semana, $aulas_no_dia]);
	}

	$pdo->commit();
	echo json_encode(['status'=>'success','message'=>'Dias do turno atualizados com sucesso!']);

} catch (PDOException $e) {
	$pdo->rollBack();
	echo json_encode([
		'status'=>'error',
		'message'=>'Erro ao atualizar dias do turno: '.$e->getMessage()
	]);
}
?>