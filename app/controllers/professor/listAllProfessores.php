<?php
// /horarios/app/controllers/professor/listAllProfessores.php
require_once __DIR__ . '/../../../configs/init.php'; // Ajuste caminho se necessário

header('Content-Type: application/json; charset=utf-8');

try {
    // Exemplo simples: retorna todos os professores
    $sql = "SELECT id_professor, nome_completo, nome_exibicao FROM professor ORDER BY nome_completo";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data'   => $rows
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
?>