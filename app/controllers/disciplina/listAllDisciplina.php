<?php
// app/controllers/disciplina/listAllDisciplina.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id_disciplina, nome_disciplina, sigla_disciplina FROM disciplina ORDER BY nome_disciplina");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data'   => $rows
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
?>