<?php
// app/controllers/professor-categoria/listProfessorCategoria.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$id_categoria = isset($_GET['id_categoria']) ? (int)$_GET['id_categoria'] : 0;

if ($id_categoria <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Parâmetro id_categoria inválido.'
    ]);
    exit;
}

try {
    $sql = "
        SELECT pc.id_professor,
               p.nome_completo
          FROM professor_categoria pc
          JOIN professor p ON pc.id_professor = p.id_professor
         WHERE pc.id_categoria = :cat
         ORDER BY p.nome_completo
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':cat', $id_categoria, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data'   => $rows
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>
