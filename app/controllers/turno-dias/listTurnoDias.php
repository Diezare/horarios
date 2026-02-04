<?php
// app/controllers/turno-dias/listTurnoDias.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$id_turno = isset($_GET['id_turno']) ? (int)$_GET['id_turno'] : 0;
$id_nivel_ensino = isset($_GET['id_nivel_ensino']) ? (int)$_GET['id_nivel_ensino'] : 0;

$diasOrdenados = ['Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado'];

try {
	if ($id_turno <= 0) {
		echo json_encode(['status' => 'error', 'message' => 'id_turno inválido.']);
		exit;
	}

	/**
	 * Se veio nível:
	 *  - retorna sempre os 7 dias
	 *  - se não houver cadastro em turno_dias_nivel, retorna 7 dias com 0
	 *  - (não faz fallback no global)
	 */
	if ($id_nivel_ensino > 0) {

		$stmt = $pdo->prepare("
			SELECT dia_semana, aulas_no_dia
			  FROM turno_dias_nivel
			 WHERE id_turno = ?
			   AND id_nivel_ensino = ?
		");
		$stmt->execute([$id_turno, $id_nivel_ensino]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// monta mapa dia_semana => aulas_no_dia
		$map = [];
		foreach ($rows as $r) {
			$dia = $r['dia_semana'] ?? '';
			if (!in_array($dia, $diasOrdenados, true)) continue;
			$map[$dia] = (int)($r['aulas_no_dia'] ?? 0);
		}

		// garante sempre 7 linhas (em ordem)
		$data = [];
		foreach ($diasOrdenados as $dia) {
			$data[] = [
				'id_turno_dia' => null, // pode deixar null
				'id_turno' => $id_turno,
				'id_nivel_ensino' => $id_nivel_ensino,
				'dia_semana' => $dia,
				'aulas_no_dia' => $map[$dia] ?? 0
			];
		}

		echo json_encode(['status' => 'success', 'data' => $data]);
		exit;
	}

	/**
	 * Sem nível (modo antigo/global):
	 *  - retorna os registros do turno_dias
	 *  - se não houver registro, também retorna 7 dias zerados
	 */
	$stmt2 = $pdo->prepare("
		SELECT dia_semana, aulas_no_dia
		  FROM turno_dias
		 WHERE id_turno = ?
	");
	$stmt2->execute([$id_turno]);
	$rows2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

	$map2 = [];
	foreach ($rows2 as $r) {
		$dia = $r['dia_semana'] ?? '';
		if (!in_array($dia, $diasOrdenados, true)) continue;
		$map2[$dia] = (int)($r['aulas_no_dia'] ?? 0);
	}

	$data2 = [];
	foreach ($diasOrdenados as $dia) {
		$data2[] = [
			'id_turno_dia' => null,
			'id_turno' => $id_turno,
			'dia_semana' => $dia,
			'aulas_no_dia' => $map2[$dia] ?? 0
		];
	}

	echo json_encode(['status' => 'success', 'data' => $data2]);

} catch (PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>