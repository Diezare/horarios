<?php
// app/controllers/horarios/deleteHorarios.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
    exit;
}

$id_turma    = (int)($_POST['id_turma'] ?? 0);
$id_turno    = (int)($_POST['id_turno'] ?? 0); // NOVO
$dia_semana  = trim((string)($_POST['dia_semana'] ?? ''));
$numero_aula = (int)($_POST['numero_aula'] ?? 0);

if ($id_turma <= 0 || $id_turno <= 0 || $dia_semana === '' || $numero_aula <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Parâmetros inválidos.']);
    exit;
}

$validDias = ['Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado'];
if (!in_array($dia_semana, $validDias, true)) {
    echo json_encode(['status' => 'error', 'message' => 'Dia da semana inválido.']);
    exit;
}

$idUsuario = (int)($_SESSION['id_usuario'] ?? 0);
if ($idUsuario <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Não autenticado.']);
    exit;
}

try {
    // 1) Verifica permissão do usuário para a turma e coerência do turno
    $stmt = $pdo->prepare("
        SELECT 1
        FROM turma t
        JOIN serie s ON t.id_serie = s.id_serie
        JOIN nivel_ensino ne ON s.id_nivel_ensino = ne.id_nivel_ensino
        JOIN usuario_niveis un ON un.id_nivel_ensino = ne.id_nivel_ensino
        WHERE t.id_turma = ?
          AND t.id_turno = ?
          AND un.id_usuario = ?
        LIMIT 1
    ");
    $stmt->execute([$id_turma, $id_turno, $idUsuario]);

    if (!$stmt->fetchColumn()) {
        echo json_encode(['status' => 'error', 'message' => 'Sem permissão para esta turma/turno.']);
        exit;
    }

    // 2) Delete direto pelo slot (turma + turno + dia + aula)
    $stmtDel = $pdo->prepare("
        DELETE FROM horario
        WHERE id_turma = ?
          AND id_turno = ?
          AND dia_semana = ?
          AND numero_aula = ?
        LIMIT 1
    ");
    $stmtDel->execute([$id_turma, $id_turno, $dia_semana, $numero_aula]);

    if ($stmtDel->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Horário removido com sucesso.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Horário não encontrado.']);
    }
    exit;

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao remover horário.']);
    exit;
}
?>