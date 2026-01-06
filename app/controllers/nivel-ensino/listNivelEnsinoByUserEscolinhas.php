<?php
// app/controllers/nivel-ensino/listNivelEnsinoByUserEscolinhas.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

// 1) ID do usuário logado
$idUsuario = $_SESSION['id_usuario'] ?? 0;

// 2) Consulta sem JOIN com turma
try {
    $sql = "
        SELECT ne.id_nivel_ensino, ne.nome_nivel_ensino
          FROM nivel_ensino ne
          JOIN usuario_niveis un ON un.id_nivel_ensino = ne.id_nivel_ensino
         WHERE un.id_usuario = :idUsuario
         ORDER BY ne.nome_nivel_ensino
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':idUsuario', $idUsuario, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $rows]);
} catch(Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

?>