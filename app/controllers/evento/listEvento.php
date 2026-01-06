<?php
// /app/controllers/evento/listEvento.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT e.id_evento,
               e.id_ano_letivo,
               e.tipo_evento,
               e.nome_evento,
               e.data_inicio,
               e.data_fim,
               e.observacoes,
               a.ano,
               a.data_inicio as ano_inicio,
               a.data_fim as ano_fim
          FROM eventos_calendario_escolar e
          JOIN ano_letivo a ON e.id_ano_letivo = a.id_ano_letivo
        ORDER BY e.data_inicio ASC, e.nome_evento ASC
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data'   => $rows
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Erro ao listar eventos: ' . $e->getMessage()
    ]);
}
?>