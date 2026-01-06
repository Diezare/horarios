<?php
// app/controllers/modalidade/listAllModalidade.php

require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM modalidade ORDER BY nome_modalidade ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>
