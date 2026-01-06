<?php
// app/controllers/nivel-ensino/listNivelEnsinoByUser.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

// Verifica se o usuário está autenticado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['status' => 'error', 'message' => 'Usuário não autenticado.']);
    exit;
}

$idUsuario = $_SESSION['id_usuario'];

try {
    // Filtra APENAS os níveis que o usuário tem em usuario_niveis
    $sql = "
        SELECT ne.id_nivel_ensino, ne.nome_nivel_ensino
        FROM nivel_ensino AS ne
        JOIN usuario_niveis un ON un.id_nivel_ensino = ne.id_nivel_ensino
        WHERE un.id_usuario = :idUsuario
        ORDER BY ne.nome_nivel_ensino
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':idUsuario', $idUsuario, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>