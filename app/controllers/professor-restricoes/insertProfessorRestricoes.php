<?php
// app/controllers/professor-restricoes/insertProfessorRestricoes.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
    exit;
}

$id_professor   = intval($_POST['id_professor'] ?? 0);
$id_ano_letivo  = intval($_POST['id_ano_letivo'] ?? 0);
$id_turno       = intval($_POST['id_turno'] ?? 0); // NOVO: adicionar turno
$restricoesJson = $_POST['restricoes'] ?? '';

// Verificar se turno também foi informado
if ($id_professor <= 0 || $id_ano_letivo <= 0 || $id_turno <= 0 || empty($restricoesJson)) {
    echo json_encode(['status' => 'error', 'message' => 'Dados incompletos.']);
    exit;
}

$restricoes = json_decode($restricoesJson, true);
if (!is_array($restricoes)) {
    echo json_encode(['status' => 'error', 'message' => 'JSON de restrições inválido.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Remove restrições existentes desse professor + ano letivo + turno
    $stmtDel = $pdo->prepare("
        DELETE FROM professor_restricoes
        WHERE id_professor = ?
          AND id_ano_letivo = ?
          AND id_turno = ?
    ");
    $stmtDel->execute([$id_professor, $id_ano_letivo, $id_turno]);

    // Insere as novas com turno
    $stmt = $pdo->prepare("
        INSERT INTO professor_restricoes
            (id_professor, id_ano_letivo, id_turno, dia_semana, numero_aula)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($restricoes as $diaSemana => $aulasArr) {
        if (is_array($aulasArr)) {
            foreach ($aulasArr as $aulaNum) {
                $num = intval($aulaNum);
                if ($num > 0) {
                    $stmt->execute([
                        $id_professor, 
                        $id_ano_letivo, 
                        $id_turno, 
                        $diaSemana, 
                        $num
                    ]);
                }
            }
        }
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Restrições inseridas com sucesso.']);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao inserir restrições: ' . $e->getMessage()
    ]);
}
?>