<?php
// app/controllers/sala/deleteSala.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
    exit;
}

try {
    // Verifica se existem registros vinculados na tabela sala_turno
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM sala_turno WHERE id_sala = ?");
    $checkStmt->execute([$id]);
    $count = $checkStmt->fetchColumn();

    if ($count > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Não é possível excluir a sala, pois há registros vinculados.']);
        exit;
    }

    // Prossegue com a exclusão se não houver registros vinculados
    $stmt = $pdo->prepare("DELETE FROM sala WHERE id_sala = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Sala excluída com sucesso.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Sala não encontrada.']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao processar a solicitação: ' . $e->getMessage()]);
}
?>