<?php
// app/controllers/usuario-nivel/updateUsuarioNivel.php
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
    // Remove os vínculos atuais
    $stmtDel = $pdo->prepare("DELETE FROM usuario_niveis WHERE id_usuario = ?");
    $stmtDel->execute([$userId]);
    
    // Insere os novos vínculos, se houver
    if(!empty($niveis)){
        $stmt = $pdo->prepare("INSERT INTO usuario_niveis (id_usuario, id_nivel_ensino) VALUES (?, ?)");
        foreach($niveis as $nivelId){
            $nivelId = intval($nivelId);
            if($nivelId > 0){
                $stmt->execute([$userId, $nivelId]);
            }
        }
    }
    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Níveis de ensino atualizados com sucesso.']);
} catch(PDOException $e){
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>
