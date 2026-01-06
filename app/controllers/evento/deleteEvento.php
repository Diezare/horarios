<?php
// /app/controllers/evento/deleteEvento.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
        exit;
    }

    try {
        // Excluir o evento
        $stmt = $pdo->prepare("DELETE FROM eventos_calendario_escolar WHERE id_evento = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Evento excluído com sucesso.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Evento não encontrado.']);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Erro ao excluir evento: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
}
?>