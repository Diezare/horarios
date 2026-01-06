<?php
// /app/controllers/categoria/insertCategoria.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idModalidade = intval($_POST['id_modalidade'] ?? 0);
    $nome         = trim($_POST['nome_categoria'] ?? '');
    $desc         = trim($_POST['descricao'] ?? '');

    if ($idModalidade <= 0 || empty($nome)) {
        echo json_encode(['status' => 'error', 'message' => 'Preencha os campos obrigatórios.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO categoria (id_modalidade, nome_categoria, descricao)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$idModalidade, $nome, $desc]);

        echo json_encode([
            'status'  => 'success',
            'message' => 'Categoria inserida com sucesso!',
            'id'      => $pdo->lastInsertId()
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            // Se quiser tratar unicidade de nome, etc.
            echo json_encode([
                'status'  => 'error',
                'message' => 'Já existe uma categoria com esse nome.'
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
