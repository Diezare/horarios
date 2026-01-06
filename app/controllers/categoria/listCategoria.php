<?php
// /app/controllers/categoria/listCategoria.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT c.id_categoria,
               c.nome_categoria,
               c.descricao,
               c.id_modalidade,
               m.nome_modalidade
          FROM categoria c
          JOIN modalidade m ON c.id_modalidade = m.id_modalidade
        ORDER BY c.nome_categoria ASC
    ");
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
