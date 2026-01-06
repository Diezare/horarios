<?php
// app/controllers/horarios-treino/listHorariosTreino.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

try {
    $sql = "
        SELECT he.*,
               a.ano,
               n.nome_nivel_ensino,
               mo.nome_modalidade,
               c.nome_categoria,
               p.nome_exibicao AS nome_professor,
               t.nome_turno
          FROM horario_escolinha he
          JOIN ano_letivo       a   ON he.id_ano_letivo    = a.id_ano_letivo
          JOIN nivel_ensino     n   ON he.id_nivel_ensino  = n.id_nivel_ensino
          JOIN modalidade       mo  ON he.id_modalidade    = mo.id_modalidade
          JOIN categoria        c   ON he.id_categoria     = c.id_categoria
          JOIN professor        p   ON he.id_professor     = p.id_professor
          JOIN turno            t   ON he.id_turno         = t.id_turno
        ORDER BY he.id_horario_escolinha DESC
    ";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>