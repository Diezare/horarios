<?php
// app/controllers/usuario-nivel/listUsuarioNivel.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if($userId <= 0){
    echo json_encode(['status' => 'error', 'message' => 'ID de usuário inválido.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id_nivel_ensino FROM usuario_niveis WHERE id_usuario = ?");
    $stmt->execute([$userId]);
    $niveis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'data' => $niveis]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>
