<?php
// app/controllers/horarios/listHorariosByAnoLetivo.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json; charset=utf-8');

$idAnoLetivo = (int)($_GET['id_ano_letivo'] ?? 0);
$idTurno     = (int)($_GET['id_turno'] ?? 0); // opcional

if ($idAnoLetivo <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Parâmetro id_ano_letivo inválido.']);
    exit;
}

try {
    $sql = "
        SELECT
            h.id_horario,
            h.id_turma,
            h.id_turno,
            h.id_ano_letivo,
            h.dia_semana,
            h.numero_aula,
            h.id_professor,
            h.id_disciplina,
            t.id_turno AS id_turno_turma,
            s.nome_serie,
            t.nome_turma,
            ne.nome_nivel_ensino
        FROM horario h
        JOIN turma t         ON h.id_turma = t.id_turma
        JOIN serie s         ON t.id_serie = s.id_serie
        JOIN nivel_ensino ne ON s.id_nivel_ensino = ne.id_nivel_ensino
        WHERE h.id_ano_letivo = :ano
    ";
    if ($idTurno > 0) {
        $sql .= " AND h.id_turno = :turno ";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':ano', $idAnoLetivo, PDO::PARAM_INT);
    if ($idTurno > 0) {
        $stmt->bindValue(':turno', $idTurno, PDO::PARAM_INT);
    }
    $stmt->execute();

    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;

} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>