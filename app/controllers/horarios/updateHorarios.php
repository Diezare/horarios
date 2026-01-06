<?php
// app/controllers/horarios/updateHorarios.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
    exit;
}

$id_horario    = (int)($_POST['id_horario'] ?? 0);
$id_disciplina = (int)($_POST['id_disciplina'] ?? 0);
$id_professor  = (int)($_POST['id_professor'] ?? 0);

if ($id_horario <= 0 || $id_disciplina <= 0 || $id_professor <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Dados incompletos.']);
    exit;
}

$idUsuario = (int)($_SESSION['id_usuario'] ?? 0);
if ($idUsuario <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Não autenticado.']);
    exit;
}

try {
    // 1) Carrega dados atuais do horário + permissão do usuário sobre a turma (via nível)
    $stmt = $pdo->prepare("
        SELECT
            h.id_horario,
            h.id_turma,
            h.id_turno,
            h.dia_semana,
            h.numero_aula,
            t.id_ano_letivo
        FROM horario h
        JOIN turma t ON t.id_turma = h.id_turma
        JOIN serie s ON t.id_serie = s.id_serie
        JOIN nivel_ensino ne ON s.id_nivel_ensino = ne.id_nivel_ensino
        JOIN usuario_niveis un ON un.id_nivel_ensino = ne.id_nivel_ensino
        WHERE h.id_horario = ?
          AND un.id_usuario = ?
        LIMIT 1
    ");
    $stmt->execute([$id_horario, $idUsuario]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dados) {
        echo json_encode(['status' => 'error', 'message' => 'Horário não encontrado ou sem permissão.']);
        exit;
    }

    // 2) Conflito do professor (AGORA COM TURNO + ANO LETIVO)
    $stmt = $pdo->prepare("
        SELECT 1
        FROM horario h
        JOIN turma t ON t.id_turma = h.id_turma
        WHERE h.id_professor = ?
          AND h.id_turno     = ?
          AND h.dia_semana   = ?
          AND h.numero_aula  = ?
          AND t.id_ano_letivo = ?
          AND h.id_horario <> ?
        LIMIT 1
    ");
    $stmt->execute([
        $id_professor,
        (int)$dados['id_turno'],
        $dados['dia_semana'],
        (int)$dados['numero_aula'],
        (int)$dados['id_ano_letivo'],
        $id_horario
    ]);

    if ($stmt->fetchColumn()) {
        echo json_encode(['status' => 'error', 'message' => 'Professor já está alocado neste turno/dia/aula.']);
        exit;
    }

    // 3) UPDATE
    $stmt = $pdo->prepare("
        UPDATE horario
        SET id_disciplina = ?,
            id_professor  = ?
        WHERE id_horario = ?
        LIMIT 1
    ");
    $stmt->execute([$id_disciplina, $id_professor, $id_horario]);

    echo json_encode(['status' => 'success', 'message' => 'Horário atualizado com sucesso.']);
    exit;

} catch (PDOException $e) {
    if ((int)($e->errorInfo[1] ?? 0) === 1062) {
        echo json_encode(['status' => 'error', 'message' => 'Conflito: este slot já está ocupado (turma ou professor).']);
        exit;
    }
    echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar horário.']);
    exit;
}
?>