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

// opcional: ano/turno selecionado na tela (para validar coerência)
$id_ano_letivo_post = (int)($_POST['id_ano_letivo'] ?? 0);
$id_turno_post      = (int)($_POST['id_turno'] ?? 0);

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
    // 1) Carrega o slot do horário + turma (fonte da verdade para ano/turno) + permissão do usuário
    $stmt = $pdo->prepare("
        SELECT
            h.id_horario,
            h.id_turma,
            h.dia_semana,
            h.numero_aula,
            -- valores atuais no horario (podem estar 0, vamos corrigir)
            h.id_ano_letivo AS h_ano,
            h.id_turno      AS h_turno,
            -- valores corretos vindos da turma
            t.id_ano_letivo AS t_ano,
            t.id_turno      AS t_turno
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

    $id_turma     = (int)$dados['id_turma'];
    $dia_semana   = (string)$dados['dia_semana'];
    $numero_aula  = (int)$dados['numero_aula'];

    $idAnoLetivoCorreto = (int)$dados['t_ano'];
    $idTurnoCorreto     = (int)$dados['t_turno'];

    if ($idAnoLetivoCorreto <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Turma sem ano letivo válido.']);
        exit;
    }
    if ($idTurnoCorreto <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Turno inválido para a turma.']);
        exit;
    }

    // 1.1) Se o front mandou ano/turno selecionado, valida coerência
    if ($id_ano_letivo_post > 0 && $id_ano_letivo_post !== $idAnoLetivoCorreto) {
        echo json_encode(['status' => 'error', 'message' => 'Ano letivo selecionado não corresponde ao ano da turma.']);
        exit;
    }
    if ($id_turno_post > 0 && $id_turno_post !== $idTurnoCorreto) {
        echo json_encode(['status' => 'error', 'message' => 'Turno selecionado não corresponde ao turno da turma.']);
        exit;
    }

    // 2) Conflito do professor: mesmo ano/turno/dia/aula em OUTRA turma
    $stmt = $pdo->prepare("
        SELECT 1
        FROM horario h
        WHERE h.id_professor = ?
          AND h.id_ano_letivo = ?
          AND h.id_turno      = ?
          AND h.dia_semana    = ?
          AND h.numero_aula   = ?
          AND h.id_turma     <> ?
        LIMIT 1
    ");
    $stmt->execute([
        $id_professor,
        $idAnoLetivoCorreto,
        $idTurnoCorreto,
        $dia_semana,
        $numero_aula,
        $id_turma
    ]);

    if ($stmt->fetchColumn()) {
        echo json_encode(['status' => 'error', 'message' => 'Professor já está alocado neste ano/turno/dia/aula em outra turma.']);
        exit;
    }

    // 3) UPDATE
    // - Atualiza disciplina/professor
    // - E também "conserta" id_ano_letivo/id_turno se estiverem errados (ex.: 0)
    $stmt = $pdo->prepare("
        UPDATE horario
        SET
            id_disciplina = ?,
            id_professor  = ?,
            id_ano_letivo = ?,
            id_turno      = ?
        WHERE id_horario = ?
        LIMIT 1
    ");
    $stmt->execute([
        $id_disciplina,
        $id_professor,
        $idAnoLetivoCorreto,
        $idTurnoCorreto,
        $id_horario
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Horário atualizado com sucesso.']);
    exit;

} catch (PDOException $e) {
    if ((int)($e->errorInfo[1] ?? 0) === 1062) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Não foi possível salvar este horário. Verifique conflitos e restrições.'
        ]);
        exit;
    }
    echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar horário: ' . $e->getMessage()]);
    exit;
}
?>