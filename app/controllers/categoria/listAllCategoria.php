<?php
// /app/controllers/categoria/listAllCategoria.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

try {
    $sql = "
        SELECT c.id_categoria,
               c.nome_categoria,
               c.descricao,
               c.id_modalidade,
               m.nome_modalidade
          FROM categoria c
          JOIN modalidade m ON c.id_modalidade = m.id_modalidade
        ORDER BY m.nome_modalidade, c.nome_categoria
    ";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data'   => $rows
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>
