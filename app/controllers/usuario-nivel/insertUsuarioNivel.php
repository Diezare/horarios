<?php
// app/controllers/usuario-nivel/insertUsuarioNivel.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
    exit;
}

$userId = intval($_POST['id_usuario'] ?? 0);
$niveis = json_decode($_POST['niveis'] ?? '[]', true);

if($userId <= 0 || !is_array($niveis)){
    echo json_encode(['status' => 'error', 'message' => 'Dados inválidos.']);
    exit;
}

try {
    $pdo->beginTransaction();
    // Insere os vínculos
    $stmt = $pdo->prepare("INSERT INTO usuario_niveis (id_usuario, id_nivel_ensino) VALUES (?, ?)");
    foreach($niveis as $nivelId){
        $nivelId = intval($nivelId);
        if($nivelId > 0){
            $stmt->execute([$userId, $nivelId]);
        }
    }
    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Níveis de ensino vinculados com sucesso.']);
} catch(PDOException $e){
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>
