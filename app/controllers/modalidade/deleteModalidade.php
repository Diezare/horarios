<?php
// app/controllers/modalidade/deleteModalidade.php

require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

/*
  Mantemos a mesma lógica de verificação de dependências.
  Se modalidade tiver tabela que dependa diretamente dela,
  checar antes de excluir (ex.: categoria).
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
        exit;
    }

    try {
        // Exemplo de verificação de dependência:
        // Se existem categorias atreladas a esta modalidade
        $sqlCheck = "SELECT COUNT(*) as qtd FROM categoria WHERE id_modalidade = ?";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$id]);
        $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        if ($result && intval($result['qtd']) > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Não é possível excluir esta modalidade pois há categorias vinculadas.'
            ]);
            exit;
        }

        // Se passou pela verificação, pode excluir
        $stmt = $pdo->prepare("DELETE FROM modalidade WHERE id_modalidade = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Excluído com sucesso.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Registro não encontrado.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
}
?>
