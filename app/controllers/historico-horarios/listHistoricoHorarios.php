<?php
// app/controllers/horarios/listHorarios.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$id_turma = isset($_GET['id_turma']) ? (int)$_GET['id_turma'] : 0;
if ($id_turma <= 0) {
	echo json_encode(['status' => 'error', 'message' => 'Parâmetro id_turma inválido.']);
	exit;
}

try {
	// 1) Dados da turma
	$sqlTurma = "
		SELECT 
			t.*,
			s.nome_serie,
			s.id_serie,
			al.ano,
			turno.nome_turno
		FROM turma t
		JOIN serie s      ON t.id_serie     = s.id_serie
		JOIN ano_letivo al ON t.id_ano_letivo = al.id_ano_letivo
		JOIN turno        ON t.id_turno     = turno.id_turno
		WHERE t.id_turma = ?
	";
	$stmt = $pdo->prepare($sqlTurma);
	$stmt->execute([$id_turma]);
	$turma = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$turma) {
		echo json_encode(['status' => 'error', 'message' => 'Turma não encontrada.']);
		exit;
	}

	// 2) Dias do turno (turno_dias)
	$sqlDias = "SELECT * FROM turno_dias WHERE id_turno = ? ORDER BY FIELD(dia_semana,'Segunda','Terca','Quarta','Quinta','Sexta','Sabado','Domingo')";
	$stmt = $pdo->prepare($sqlDias);
	$stmt->execute([$turma['id_turno']]);
	$turno_dias = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// 3) Disciplinas da série (serie_disciplinas + disciplina)
	$sqlDisciplinas = "
		SELECT 
			d.id_disciplina,
			d.nome_disciplina,
			d.sigla_disciplina,
			sd.aulas_semana
		FROM serie_disciplinas sd
		JOIN disciplina d ON sd.id_disciplina = d.id_disciplina
		WHERE sd.id_serie = ?
		ORDER BY d.nome_disciplina
	";
	$stmt = $pdo->prepare($sqlDisciplinas);
	$stmt->execute([$turma['id_serie']]);
	$serie_disciplinas = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// 4) Lista (básica) de professores
	$sqlProfs = "
		SELECT 
			p.id_professor,
			COALESCE(p.nome_exibicao, p.nome_completo) AS nome_exibicao
		FROM professor p
		ORDER BY nome_exibicao
	";
	$stmt = $pdo->query($sqlProfs);
	$professores = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// 5) Horários já cadastrados
	$sqlHorarios = "
		SELECT 
			h.id_horario,
			h.id_turma,
			h.dia_semana,
			h.numero_aula,
			h.id_disciplina,
			h.id_professor,
			d.nome_disciplina,
			p.nome_exibicao AS nome_professor
		FROM horario h
		JOIN disciplina d ON h.id_disciplina = d.id_disciplina
		JOIN professor p  ON h.id_professor  = p.id_professor
		WHERE h.id_turma = ?
	";
	$stmt = $pdo->prepare($sqlHorarios);
	$stmt->execute([$id_turma]);
	$horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$data = [
		'turma'            => $turma,
		'turno_dias'       => $turno_dias,
		'serie_disciplinas'=> $serie_disciplinas,
		'professores'      => $professores,
		'horarios'         => $horarios
	];

	echo json_encode(['status' => 'success', 'data' => $data]);

} catch (Exception $e) {
	echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>