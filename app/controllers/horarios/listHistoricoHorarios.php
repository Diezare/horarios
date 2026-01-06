<?php
// app/controllers/horarios/listHistoricoHorarios.php  (nome sugerido; use o nome real do seu arquivo)
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../configs/init.php';

$idAnoLetivo   = (int)($_GET['id_ano_letivo'] ?? 0);
$idNivelEnsino = (int)($_GET['id_nivel_ensino'] ?? 0);
$idTurno       = (int)($_GET['id_turno'] ?? 0);
$idTurma       = (int)($_GET['id_turma'] ?? 0);
$dataArquivamento = $_GET['data_arquivamento'] ?? null;

if ($idAnoLetivo <= 0) {
	echo json_encode(['status' => 'error', 'message' => 'Ano letivo não informado']);
	exit;
}

$sql = "
	SELECT 
		h.id_turma,
		t.nome_turma,
		s.nome_serie,
		tur.nome_turno,
		a.ano AS ano,
		DATE_FORMAT(h.data_arquivamento, '%Y-%m-%d %H:%i:%s') AS data_arquivamento
	FROM historico_horario h
	JOIN turma t       ON h.id_turma = t.id_turma
	JOIN serie s       ON t.id_serie = s.id_serie
	JOIN turno tur     ON t.id_turno = tur.id_turno
	JOIN ano_letivo a  ON t.id_ano_letivo = a.id_ano_letivo
	WHERE t.id_ano_letivo = :ano
";
$params = [':ano' => $idAnoLetivo];

if ($idNivelEnsino > 0) {
	$sql .= " AND s.id_nivel_ensino = :nivel ";
	$params[':nivel'] = $idNivelEnsino;
}

if ($idTurno > 0) {
	// Se sua tabela historico_horario já tem id_turno, use h.id_turno.
	// Se não tiver, filtra pelo turno da turma (menos preciso, mas funciona).
	$sql .= " AND (h.id_turno = :turno OR t.id_turno = :turno) ";
	$params[':turno'] = $idTurno;
}

if ($idTurma > 0) {
	$sql .= " AND t.id_turma = :turma ";
	$params[':turma'] = $idTurma;
}

if ($dataArquivamento) {
	$sql .= " AND DATE_FORMAT(h.data_arquivamento, '%Y-%m-%d %H:%i:%s') = :data_arch ";
	$params[':data_arch'] = $dataArquivamento;
}

$sql .= " ORDER BY a.ano DESC, s.nome_serie ASC, t.nome_turma ASC, h.data_arquivamento DESC ";

try {
	$stmt = $pdo->prepare($sql);
	$stmt->execute($params);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode(['status' => 'success', 'data' => $rows ?: []]);
	exit;

} catch (Throwable $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro ao listar histórico: ' . $e->getMessage()]);
	exit;
}
?>