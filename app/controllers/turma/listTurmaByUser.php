<?php
// app/controllers/turma/listTurmaByUser.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$idUsuario = $_SESSION['id_usuario'] ?? 0;
if (!$idUsuario) {
    echo json_encode(['status'=>'error','message'=>'Usuário não logado.']);
    exit;
}

try {
    $sql = "
        SELECT
            t.id_turma,
            t.nome_turma,
            t.id_ano_letivo,
            t.id_serie,
            t.id_turno,
            t.intervalos_por_dia,
            t.intervalos_positions,
            a.ano AS ano,
            s.nome_serie,
            tur.nome_turno
        FROM turma t
        JOIN serie s            ON t.id_serie       = s.id_serie
        JOIN nivel_ensino ne    ON s.id_nivel_ensino= ne.id_nivel_ensino
        JOIN turno tur          ON t.id_turno       = tur.id_turno
        JOIN ano_letivo a       ON t.id_ano_letivo  = a.id_ano_letivo
        JOIN usuario_niveis un  ON un.id_nivel_ensino = ne.id_nivel_ensino
           AND un.id_usuario = :user
        ORDER BY a.ano DESC, s.nome_serie ASC, t.nome_turma ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user', $idUsuario, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status'=>'success', 'data'=>$rows]);

} catch (Exception $e) {
    echo json_encode(['status'=>'error', 'message'=>'Erro: '.$e->getMessage()]);
}
?>