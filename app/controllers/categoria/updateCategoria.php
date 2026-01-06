<?php
// /app/controllers/categoria/updateCategoria.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id           = intval($_POST['id_categoria'] ?? 0);
    $idModalidade = intval($_POST['id_modalidade'] ?? 0);
    $nome         = trim($_POST['nome_categoria'] ?? '');
    $desc         = trim($_POST['descricao'] ?? '');

    if ($id <= 0 || $idModalidade <= 0 || empty($nome)) {
        echo json_encode(['status' => 'error', 'message' => 'Dados incompletos.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE categoria
               SET id_modalidade = ?,
                   nome_categoria = ?,
                   descricao = ?
             WHERE id_categoria = ?
        ");
        $stmt->execute([$idModalidade, $nome, $desc, $id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'status'  => 'success',
                'message' => 'Categoria atualizada com sucesso.'
            ]);
        } else {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Nenhuma alteração ou registro não encontrado.'
            ]);
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['status' => 'error', 'message' => 'Já existe uma categoria com esse nome.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
}
?>
