<?php
// app/controllers/horarios/insertHorarios.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

// Verifica método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

// Campos
$id_turma     = intval($_POST['id_turma'] ?? 0);
$dia_semana   = trim($_POST['dia_semana'] ?? '');
$numero_aula  = intval($_POST['numero_aula'] ?? 0);
$id_disciplina= intval($_POST['id_disciplina'] ?? 0);
$id_professor = intval($_POST['id_professor'] ?? 0);

if ($id_turma <= 0 || empty($dia_semana) || $numero_aula <= 0 ||
	$id_disciplina <= 0 || $id_professor <= 0) {
	echo json_encode(['status' => 'error', 'message' => 'Dados insuficientes.']);
	exit;
}

try {
	// Insere
	$stmt = $pdo->prepare("
		INSERT INTO horario (
			id_turma, 
			dia_semana,
			numero_aula,
			id_disciplina,
			id_professor
		) VALUES (?, ?, ?, ?, ?)
	");
	$stmt->execute([$id_turma, $dia_semana, $numero_aula, $id_disciplina, $id_professor]);
	$newId = $pdo->lastInsertId();

	// Retornar objeto recém-criado (caso queira atualizar no front-end sem novo fetch)
	$sql = "
		SELECT 
			h.id_horario,
			h.id_turma,
			h.dia_semana,
			h.numero_aula,
			h.id_disciplina,
			h.id_professor
		FROM horario h
		WHERE h.id_horario = ?
	";
	$stmt2 = $pdo->prepare($sql);
	$stmt2->execute([$newId]);
	$inserted = $stmt2->fetch(PDO::FETCH_ASSOC);

	echo json_encode([
		'status' => 'success',
		'message' => 'Registro inserido com sucesso!',
		'data'   => $inserted
	]);

} catch (PDOException $e) {
	// Se for violação da UNIQUE KEY (mesmo diaSemana e numero_aula), retorne erro
	if ($e->getCode() == 23000) {
		echo json_encode([
			'status'  => 'error',
			'message' => 'Já existe um horário cadastrado para este dia e aula.'
		]);
	} else {
		echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
	}
}
?>