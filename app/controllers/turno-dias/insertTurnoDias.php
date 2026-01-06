<?php
// app/controllers/turno-dias/insertTurnoDias.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

/**
 * Espera:
 *  - POST id_turno
 *  - POST dias = JSON string => [ { "dia_semana": "Domingo", "aulas_no_dia": 4 }, ... ]
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

$diasArr = json_decode($diasJson,true);
if (!is_array($diasArr)) {
	echo json_encode(['status'=>'error','message'=>'JSON inválido para dias.']);
	exit;
}

try {
	$pdo->beginTransaction();

	// Remove todos os registros atuais deste turno
	$stmtDel = $pdo->prepare("DELETE FROM turno_dias WHERE id_turno = ?");
	$stmtDel->execute([$id_turno]);

	// Insere
	$stmtIns = $pdo->prepare("
		INSERT INTO turno_dias (id_turno, dia_semana, aulas_no_dia)
		VALUES (?, ?, ?)
	");

	foreach ($diasArr as $diaItem) {
		$dia_semana   = $diaItem['dia_semana']   ?? '';
		$aulas_no_dia = intval($diaItem['aulas_no_dia'] ?? 0);

		// Valida se dia_semana está entre Dom,Seg,Terca...
		if (!in_array($dia_semana,["Domingo","Segunda","Terca","Quarta","Quinta","Sexta","Sabado"])) {
			continue; // ignora
		}
		if ($aulas_no_dia < 0) $aulas_no_dia = 0;
		if ($aulas_no_dia > 99) $aulas_no_dia = 99;

		$stmtIns->execute([$id_turno, $dia_semana, $aulas_no_dia]);
	}

	$pdo->commit();
	echo json_encode(['status'=>'success','message'=>'Dias vinculados com sucesso!']);

} catch (PDOException $e) {
	$pdo->rollBack();
	echo json_encode(['status'=>'error','message'=>'Erro ao vincular dias: '.$e->getMessage()]);
}
?>