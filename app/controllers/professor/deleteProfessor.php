<?php
// app/controllers/professor/deleteProfessor.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
    exit;
}

try {
    // IMPORTANTÍSSIMO: incluir a tabela que realmente está travando (professor_disciplinas_turmas)
    $dependencyTables = [
        'professor_disciplinas_turmas' => 'id_professor',
        'professor_disciplinas'        => 'id_professor',
        'professor_restricoes'         => 'id_professor',
        'professor_turnos'             => 'id_professor',
        // se existir e referenciar professor, inclua também:
        // 'horario'                    => 'id_professor',
    ];

    foreach ($dependencyTables as $table => $column) {
        // evita injection de nome de tabela (aqui é fixo em array, OK)
        $stmt = $pdo->prepare("SELECT 1 FROM {$table} WHERE {$column} = ? LIMIT 1");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn()) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Não é possível excluir o(a) professor(a), pois há registros vinculados.'
            ]);
            exit;
        }
    }

    $stmt = $pdo->prepare("DELETE FROM professor WHERE id_professor = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Professor excluído com sucesso.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Professor não encontrado.']);
    }
    exit;

} catch (PDOException $e) {
    // MySQL/MariaDB:
    // 1451 = Cannot delete or update a parent row (FK constraint)
    $errorCode = (int)($e->errorInfo[1] ?? 0);

    if ($errorCode === 1451) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Não é possível excluir o(a) professor(a), pois há registros vinculados.'
        ]);
        exit;
    }

    // fallback genérico
    echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir.']);
    exit;
}
?>