<?php
// app/controllers/professor-turno/updateProfessorTurno.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
    exit;
}

$id_professor = intval($_POST['id_professor'] ?? 0);
$turnos       = $_POST['turnos'] ?? [];

// Se aceitar que $turnos possa estar vazio (significando desvincular tudo),
// não podemos usar empty() como erro. Mas validamos se é array.
if ($id_professor <= 0 || !is_array($turnos)) {
    echo json_encode(['status' => 'error', 'message' => 'Dados incompletos ou inválidos.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1) Remove todos os vínculos atuais deste professor
    $stmtDel = $pdo->prepare("DELETE FROM professor_turnos WHERE id_professor = ?");
    $stmtDel->execute([$id_professor]);

    // 2) Se o array de turnos não estiver vazio, insere os selecionados
    if (!empty($turnos)) {
        $stmtIns = $pdo->prepare("
            INSERT INTO professor_turnos (id_professor, id_turno)
            VALUES (?, ?)
        ");
        foreach ($turnos as $id_turno) {
            $id_turno = intval($id_turno);
            if ($id_turno > 0) {
                $stmtIns->execute([$id_professor, $id_turno]);
            }
        }
    }

    $pdo->commit();
    echo json_encode([
        'status'  => 'success',
        'message' => 'Turnos atualizados com sucesso!'
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'status'  => 'error',
        'message' => 'Erro ao atualizar turnos: ' . $e->getMessage()
    ]);
}
?>