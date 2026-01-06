<?php
// app/controllers/modalidade/insertModalidade.php

require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome_modalidade'] ?? '');

    if (empty($nome)) {
        echo json_encode(['status' => 'error', 'message' => 'Preencha o nome da modalidade.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO modalidade (nome_modalidade) VALUES (?)");
        $stmt->execute([$nome]);

        echo json_encode([
            'status'  => 'success',
            'message' => 'Modalidade inserida com sucesso!',
            'id'      => $pdo->lastInsertId()
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Já existe uma modalidade cadastrada com esse nome.'
            ]);
        } else {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Erro ao inserir: ' . $e->getMessage()
            ]);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
}
?>
