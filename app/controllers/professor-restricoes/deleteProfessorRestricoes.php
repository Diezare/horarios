<?php
// app/controllers/professor-restricoes/deleteProfessorRestricoes.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
    exit;
}

$id_professor   = intval($_POST['id_professor'] ?? 0);
$id_ano_letivo  = intval($_POST['id_ano_letivo'] ?? 0);
$id_turno       = intval($_POST['id_turno'] ?? 0); // NOVO
$dia_semana     = $_POST['dia_semana'] ?? '';
$numero_aula    = intval($_POST['numero_aula'] ?? 0);

// Adicione turno na verificação
if ($id_professor <= 0 || $id_ano_letivo <= 0 || $id_turno <= 0 || !$dia_semana || $numero_aula <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Dados inválidos.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        DELETE FROM professor_restricoes 
        WHERE id_professor = ?
          AND id_ano_letivo = ?
          AND id_turno = ?
          AND dia_semana = ?
          AND numero_aula = ?
    ");
    $stmt->execute([$id_professor, $id_ano_letivo, $id_turno, $dia_semana, $numero_aula]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Restrição removida com sucesso.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Registro não encontrado.']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir: ' . $e->getMessage()]);
}
?>