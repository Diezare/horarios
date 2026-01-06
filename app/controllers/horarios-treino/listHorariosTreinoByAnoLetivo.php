<?php
// app/controllers/horarios-treino/listHorariosTreinoByAnoLetivo.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$id_ano_letivo   = isset($_GET['id_ano_letivo']) ? (int)$_GET['id_ano_letivo'] : 0;
$id_nivel_ensino = isset($_GET['id_nivel_ensino']) ? (int)$_GET['id_nivel_ensino'] : 0;
$id_turno        = isset($_GET['id_turno']) ? (int)$_GET['id_turno'] : 0;

if ($id_ano_letivo <= 0 || $id_nivel_ensino <= 0 || $id_turno <= 0) {
    echo json_encode(['status' => 'error','message'=>'Parâmetros inválidos.']);
    exit;
}

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
         WHERE he.id_ano_letivo   = :ano
           AND he.id_nivel_ensino = :nivel
           AND he.id_turno        = :turno
        ORDER BY FIELD(he.dia_semana,'Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado'),
                 he.hora_inicio
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':ano'   => $id_ano_letivo,
        ':nivel' => $id_nivel_ensino,
        ':turno' => $id_turno
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status'=>'success','data'=>$rows]);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}

?>