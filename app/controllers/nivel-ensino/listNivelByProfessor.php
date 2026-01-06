<?php
// app/controllers/nivel-ensino/listNivelByProfessor.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

try {
    $profId = isset($_GET['prof']) ? (int)$_GET['prof'] : 0;
    if ($profId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'prof inválido']); exit;
    }

    // níveis em que o professor possui turmas vinculadas
    $sql = "
        SELECT DISTINCT n.id_nivel_ensino, n.nome_nivel_ensino
        FROM professor_disciplinas_turmas pdt
        JOIN turma t   ON t.id_turma = pdt.id_turma
        JOIN serie s   ON s.id_serie = t.id_serie
        JOIN nivel_ensino n ON n.id_nivel_ensino = s.id_nivel_ensino
        WHERE pdt.id_professor = ?
        ORDER BY n.nome_nivel_ensino
    ";
    $st = $pdo->prepare($sql);
    $st->execute([$profId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
