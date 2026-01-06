<?php
// app/controllers/horarios/listHorariosByAnoLetivo.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$id_ano_letivo = isset($_GET['id_ano_letivo']) ? (int)$_GET['id_ano_letivo'] : 0;
if ($id_ano_letivo <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Parâmetro id_ano_letivo inválido.']);
    exit;
}

try {
    $sql = "
        SELECT
            h.id_horario,
            h.id_turma,
            t.id_ano_letivo,
            h.dia_semana,
            h.numero_aula,
            h.id_professor,
            h.id_disciplina,
            s.nome_serie,
            t.nome_turma
        FROM horario h
        JOIN turma t ON h.id_turma = t.id_turma
        JOIN serie s ON t.id_serie = s.id_serie
        WHERE t.id_ano_letivo = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_ano_letivo]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $rows]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
