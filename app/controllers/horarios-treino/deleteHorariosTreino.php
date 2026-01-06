<?php
// app/controllers/horarios-treino/deleteHorariosTreino.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status'=>'error','message'=>'Método inválido.']);
    exit;
}

$id_horario_escolinha = intval($_POST['id_horario_escolinha'] ?? 0);
if (!$id_horario_escolinha) {
    echo json_encode(['status'=>'error','message'=>'ID inválido.']);
    exit;
}

try {
    $sql = "DELETE FROM horario_escolinha WHERE id_horario_escolinha = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_horario_escolinha]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status'=>'success','message'=>'Horário de treino excluído com sucesso.']);
    } else {
        echo json_encode(['status'=>'error','message'=>'Nenhum registro excluído.']);
    }

} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}

?>