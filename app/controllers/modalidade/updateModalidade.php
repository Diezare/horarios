<?php
// app/controllers/modalidade/updateModalidade.php

require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = intval($_POST['id_modalidade'] ?? 0);
    $nome = trim($_POST['nome_modalidade'] ?? '');

    if ($id <= 0 || empty($nome)) {
        echo json_encode(['status' => 'error', 'message' => 'Dados incompletos.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE modalidade
               SET nome_modalidade = ?
             WHERE id_modalidade = ?
        ");
        $stmt->execute([$nome, $id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Modalidade atualizada com sucesso.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Nenhuma alteração ou registro não encontrado.']);
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['status' => 'error', 'message' => 'Já existe uma modalidade com esse nome.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
}
?>
