<?php
// /app/controllers/categoria/deleteCategoria.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
        exit;
    }

    try {
        // Verifique dependências antes de excluir
        $sqlCheck = "SELECT COUNT(*) as qtd FROM outra_tabela WHERE id_categoria = ?";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$id]);
        $dependencias = $stmtCheck->fetch(PDO::FETCH_ASSOC)['qtd'];

        if ($dependencias > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Não é possível excluir. Existem dados vinculados.']);
            exit;
        }

        // Excluir caso não existam dependências
        $stmt = $pdo->prepare("DELETE FROM categoria WHERE id_categoria = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Excluído com sucesso.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Registro não encontrado.']);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Erro ao excluir: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
}
?>