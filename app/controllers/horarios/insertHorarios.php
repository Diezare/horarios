<?php
// app/controllers/horarios/insertHorarios.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
    exit;
}

$id_turma      = (int)($_POST['id_turma'] ?? 0);
$id_turno      = (int)($_POST['id_turno'] ?? 0);
$dia_semana    = trim((string)($_POST['dia_semana'] ?? ''));
$numero_aula   = (int)($_POST['numero_aula'] ?? 0);
$id_disciplina = (int)($_POST['id_disciplina'] ?? 0);
$id_professor  = (int)($_POST['id_professor'] ?? 0);

if ($id_turma <= 0 || $id_turno <= 0 || $dia_semana === '' || $numero_aula <= 0 || $id_disciplina <= 0 || $id_professor <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Dados insuficientes.']);
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
    // 1) turma + permissão + ano_letivo + turno
    $sqlTurma = "
        SELECT
            t.id_turma,
            t.id_ano_letivo,
            t.id_turno
        FROM turma t
        JOIN serie s ON t.id_serie = s.id_serie
        JOIN nivel_ensino ne ON s.id_nivel_ensino = ne.id_nivel_ensino
        JOIN usuario_niveis un ON un.id_nivel_ensino = ne.id_nivel_ensino
        WHERE t.id_turma = ?
          AND un.id_usuario = ?
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sqlTurma);
    $stmt->execute([$id_turma, $idUsuario]);
    $turma = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$turma) {
        echo json_encode(['status' => 'error', 'message' => 'Sem permissão para esta turma.']);
        exit;
    }

    if ((int)$turma['id_turno'] !== $id_turno) {
        echo json_encode(['status' => 'error', 'message' => 'Turno informado não corresponde ao turno da turma.']);
        exit;
    }

    $idAnoLetivo = (int)$turma['id_ano_letivo'];
    if ($idAnoLetivo <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Turma sem ano letivo válido.']);
        exit;
    }

    // 2) Conflito do professor no mesmo slot (AGORA COM ANO LETIVO NO PRÓPRIO HORARIO)
    /*$sqlConflito = "
        SELECT 1
        FROM horario h
        WHERE h.id_professor = ?
          AND h.id_ano_letivo = ?
          AND h.id_turno     = ?
          AND h.dia_semana   = ?
          AND h.numero_aula  = ?
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sqlConflito);
    $stmt->execute([$id_professor, $idAnoLetivo, $id_turno, $dia_semana, $numero_aula]);
    */

    $sqlConflito = "
        SELECT 1
        FROM horario h
        WHERE h.id_professor = ?
        AND h.id_ano_letivo = ?
        AND h.id_turno     = ?
        AND h.dia_semana   = ?
        AND h.numero_aula  = ?
        AND h.id_turma <> ?         -- ✅ ignora a própria turma
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sqlConflito);
    $stmt->execute([$id_professor, $idAnoLetivo, $id_turno, $dia_semana, $numero_aula, $id_turma]);


    if ($stmt->fetchColumn()) {
        echo json_encode(['status' => 'error', 'message' => 'Professor já está alocado neste ano/turno/dia/aula em outra turma.']);
        exit;
    }

    // 3) INSERT (inclui id_ano_letivo e id_turno) -> compatível com uq_slot
    /*$stmt = $pdo->prepare("
        INSERT INTO horario (
            id_ano_letivo,
            id_turma,
            id_turno,
            dia_semana,
            numero_aula,
            id_disciplina,
            id_professor
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$idAnoLetivo, $id_turma, $id_turno, $dia_semana, $numero_aula, $id_disciplina, $id_professor]);

    $newId = (int)$pdo->lastInsertId();
    */

    $stmt = $pdo->prepare("
        INSERT INTO horario (
            id_ano_letivo,
            id_turma,
            id_turno,
            dia_semana,
            numero_aula,
            id_disciplina,
            id_professor
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            id_disciplina = VALUES(id_disciplina),
            id_professor  = VALUES(id_professor)
    ");
    $stmt->execute([$idAnoLetivo, $id_turma, $id_turno, $dia_semana, $numero_aula, $id_disciplina, $id_professor]);

    // pegar o id da linha (inserida ou atualizada)
    $stmt = $pdo->prepare("
        SELECT id_horario
        FROM horario
        WHERE id_ano_letivo=? AND id_turma=? AND id_turno=? AND dia_semana=? AND numero_aula=?
        LIMIT 1
    ");
    $stmt->execute([$idAnoLetivo, $id_turma, $id_turno, $dia_semana, $numero_aula]);
    $newId = (int)$stmt->fetchColumn();



    // 4) Retorna o registro criado
    $stmt = $pdo->prepare("
        SELECT
            h.id_horario,
            h.id_ano_letivo,
            h.id_turma,
            h.id_turno,
            h.dia_semana,
            h.numero_aula,
            h.id_disciplina,
            h.id_professor
        FROM horario h
        WHERE h.id_horario = ?
        LIMIT 1
    ");
    $stmt->execute([$newId]);

    echo json_encode([
        'status' => 'success',
        'message'=> 'Horário inserido com sucesso.',
        'data'   => $stmt->fetch(PDO::FETCH_ASSOC)
    ]);
    exit;

} catch (PDOException $e) {
    // 1062 = violação de unique (uq_slot ou outra)
    /*if ((int)($e->errorInfo[1] ?? 0) === 1062) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Conflito: este slot já está ocupado (mesma turma/turno/dia/aula neste ano letivo).'
        ]);
        exit;
    }*/

    if ((int)($e->errorInfo[1] ?? 0) === 1062) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Não foi possível salvar este horário. Verifique conflitos e restrições.'
        ]);
        exit;
    }

    echo json_encode([
        'status'  => 'error',
        'message' => 'Erro ao inserir horário.'
    ]);
    exit;
}
?>