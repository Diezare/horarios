<?php
// app/controllers/professor/listAllProfessors.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

try {
    // Ajuste o nome da tabela/colunas conforme seu BD
    $stmt = $pdo->query("SELECT id_professor, nome_completo FROM professor ORDER BY nome_completo");
    $professores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data'   => $professores
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
