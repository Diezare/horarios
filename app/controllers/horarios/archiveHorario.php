<?php
// app/controllers/horarios/archiveHorarios.php
/**
 * Registra um horário no histórico antes de atualizar/excluir.
 * Agora inclui id_turno e, se data_criacao_original não vier, busca do BD.
 */
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

$id_horario_original = (int)($_POST['id_horario_original'] ?? 0);
$id_turma            = (int)($_POST['id_turma'] ?? 0);
$id_turno            = (int)($_POST['id_turno'] ?? 0); // NOVO (recomendado)
$id_ano_letivo        = (int)($_POST['id_ano_letivo'] ?? 0);

$dia_semana          = trim((string)($_POST['dia_semana'] ?? ''));
$numero_aula         = (int)($_POST['numero_aula'] ?? 0);
$id_disciplina       = (int)($_POST['id_disciplina'] ?? 0);
$id_professor        = (int)($_POST['id_professor'] ?? 0);
$data_criacao_original = $_POST['data_criacao'] ?? null;

if ($id_horario_original <= 0 || $id_turma <= 0 || $id_ano_letivo <= 0) {
	echo json_encode(['status' => 'error', 'message' => 'Parâmetros insuficientes para registrar histórico.']);
	exit;
}

$validDias = ['Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado'];
if ($dia_semana !== '' && !in_array($dia_semana, $validDias, true)) {
	echo json_encode(['status' => 'error', 'message' => 'Dia da semana inválido.']);
	exit;
}

try {
	// Se não vier id_turno, tenta buscar do horario/turma
	if ($id_turno <= 0) {
		$stmt = $pdo->prepare("
			SELECT h.id_turno, h.data_criacao, t.id_ano_letivo
			FROM horario h
			JOIN turma t ON t.id_turma = h.id_turma
			WHERE h.id_horario = ?
			LIMIT 1
		");
		$stmt->execute([$id_horario_original]);
		$hinfo = $stmt->fetch(PDO::FETCH_ASSOC);

		if ($hinfo) {
			$id_turno = (int)$hinfo['id_turno'];
			if (!$data_criacao_original) $data_criacao_original = $hinfo['data_criacao'];
			if ($id_ano_letivo <= 0) $id_ano_letivo = (int)$hinfo['id_ano_letivo'];
		}
	}

	if (!$data_criacao_original) {
		$data_criacao_original = date('Y-m-d H:i:s');
	}

	/**
	 * IMPORTANTE:
	 * seu SELECT do histórico usa h.data_arquivamento.
	 * Então sua tabela historico_horario precisa ter essa coluna.
	 * Se não tiver, ajuste o SELECT ou adicione a coluna.
	 *
	 * Aqui vou inserir: data_arquivamento = NOW()
	 */
	$sql = "
		INSERT INTO historico_horario
		(id_horario_original, id_turma, id_turno, id_ano_letivo, dia_semana, numero_aula,
		 id_disciplina, id_professor, data_criacao_original, data_arquivamento)
		VALUES
		(:id_horario_original, :id_turma, :id_turno, :id_ano_letivo, :dia_semana, :numero_aula,
		 :id_disciplina, :id_professor, :data_criacao_original, NOW())
	";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([
		':id_horario_original'   => $id_horario_original,
		':id_turma'             => $id_turma,
		':id_turno'             => $id_turno,
		':id_ano_letivo'         => $id_ano_letivo,
		':dia_semana'           => $dia_semana,
		':numero_aula'          => $numero_aula,
		':id_disciplina'        => $id_disciplina,
		':id_professor'         => $id_professor,
		':data_criacao_original'=> $data_criacao_original,
	]);

	echo json_encode(['status' => 'success', 'message' => 'Histórico inserido com sucesso.']);
	exit;

} catch (PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
	exit;
}
?>