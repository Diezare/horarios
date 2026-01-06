<?php
// app/controllers/horarios-treino/updateHorariosTreino.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status'=>'error','message'=>'Método inválido.']);
    exit;
}

$id_horario_escolinha = intval($_POST['id_horario_escolinha'] ?? 0);
$id_ano_letivo        = intval($_POST['id_ano_letivo']   ?? 0);
$id_nivel_ensino      = intval($_POST['id_nivel_ensino'] ?? 0);
$id_modalidade        = intval($_POST['id_modalidade']   ?? 0);
$id_categoria         = intval($_POST['id_categoria']    ?? 0);
$id_professor         = intval($_POST['id_professor']    ?? 0);
$id_turno             = intval($_POST['id_turno']        ?? 0);
$dia_semana           = trim($_POST['dia_semana']        ?? '');
$hora_inicio          = trim($_POST['hora_inicio']       ?? '');
$hora_fim             = trim($_POST['hora_fim']          ?? '');

if (!$id_horario_escolinha || !$id_ano_letivo || !$id_nivel_ensino || !$id_modalidade ||
    !$id_categoria || !$id_professor || !$id_turno || !$dia_semana || !$hora_inicio || !$hora_fim) {
    echo json_encode(['status'=>'error','message'=>'Parâmetros insuficientes.']);
    exit;
}

if ($hora_fim < $hora_inicio) {
    echo json_encode(['status'=>'error','message'=>'Horário de fim menor que início.']);
    exit;
}

try {
    // Checar se existe
    $sqlCheckExist = "SELECT * FROM horario_escolinha WHERE id_horario_escolinha = ?";
    $stmtCheck = $pdo->prepare($sqlCheckExist);
    $stmtCheck->execute([$id_horario_escolinha]);
    $existe = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    if (!$existe) {
        echo json_encode(['status'=>'error','message'=>'Registro não encontrado.']);
        exit;
    }

    // Verificar conflito (mesma lógica do insert)
    $sqlCheck = "
        SELECT COUNT(*) as total
          FROM horario_escolinha
         WHERE id_professor = :prof
           AND id_modalidade = :modal
           AND dia_semana    = :dia
           AND id_ano_letivo = :ano
           AND id_turno      = :turno
           AND (hora_inicio < :fim AND hora_fim > :inicio)
           AND id_horario_escolinha <> :idhorario
         LIMIT 1
    ";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([
        ':prof'     => $id_professor,
        ':modal'    => $id_modalidade,
        ':dia'      => $dia_semana,
        ':ano'      => $id_ano_letivo,
        ':turno'    => $id_turno,
        ':inicio'   => $hora_inicio,
        ':fim'      => $hora_fim,
        ':idhorario'=> $id_horario_escolinha
    ]);
    $rowCheck = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    if ($rowCheck && $rowCheck['total'] > 0) {
        echo json_encode([
            'status'=>'error',
            'message'=>'Conflito de horário: este professor e modalidade já têm treino no mesmo horário.'
        ]);
        exit;
    }

    // Atualizar
    $sqlUpd = "
        UPDATE horario_escolinha
           SET id_ano_letivo   = ?,
               id_nivel_ensino = ?,
               id_modalidade   = ?,
               id_categoria    = ?,
               id_professor    = ?,
               id_turno        = ?,
               dia_semana      = ?,
               hora_inicio     = ?,
               hora_fim        = ?
         WHERE id_horario_escolinha = ?
    ";
    $stmtUpd = $pdo->prepare($sqlUpd);
    $stmtUpd->execute([
        $id_ano_letivo,
        $id_nivel_ensino,
        $id_modalidade,
        $id_categoria,
        $id_professor,
        $id_turno,
        $dia_semana,
        $hora_inicio,
        $hora_fim,
        $id_horario_escolinha
    ]);

    if ($stmtUpd->rowCount() > 0) {
        echo json_encode(['status'=>'success','message'=>'Horário de treino atualizado com sucesso!']);
    } else {
        echo json_encode(['status'=>'error','message'=>'Nenhuma alteração ou falha na atualização.']);
    }
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}

?>