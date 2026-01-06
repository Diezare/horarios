<?php
// app/controllers/usuario-nivel/deleteUsuarioNivel.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
    exit;
}

$userId = intval($_POST['id_usuario'] ?? 0);
$nivelId = intval($_POST['id_nivel_ensino'] ?? 0);

if($userId <= 0 || $nivelId <= 0){
    echo json_encode(['status' => 'error', 'message' => 'Dados inválidos.']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM usuario_niveis WHERE id_usuario = ? AND id_nivel_ensino = ?");
    $stmt->execute([$userId, $nivelId]);
    if($stmt->rowCount() > 0){
        echo json_encode(['status' => 'success', 'message' => 'Link removido com sucesso.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Link não encontrado.']);
    }
} catch(PDOException $e){
    echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>
