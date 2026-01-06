<?php
// app/controllers/serie/listSerieByUser.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

// Define o ID do usuário a partir da sessão
$idUsuario = $_SESSION['id_usuario'] ?? 0;

try {
    $sql = "
        SELECT s.id_serie,
               s.id_nivel_ensino,
               s.nome_serie,
               s.total_aulas_semana,
               ne.nome_nivel_ensino
          FROM serie s
          JOIN nivel_ensino ne ON s.id_nivel_ensino = ne.id_nivel_ensino
          JOIN usuario_niveis un ON un.id_nivel_ensino = ne.id_nivel_ensino
         WHERE un.id_usuario = :idUsuario
         ORDER BY s.nome_serie
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