<?php
// app/controllers/categoria/listCategoriaByModalidade.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$id_modalidade = isset($_GET['id_modalidade']) ? intval($_GET['id_modalidade']) : 0;
if ($id_modalidade <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID de modalidade inválido.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT c.id_categoria,
               c.nome_categoria,
               c.descricao,
               c.id_modalidade,
               m.nome_modalidade
          FROM categoria c
          JOIN modalidade m ON c.id_modalidade = m.id_modalidade
         WHERE c.id_modalidade = ?
         ORDER BY c.nome_categoria
    ");
    $stmt->execute([$id_modalidade]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>