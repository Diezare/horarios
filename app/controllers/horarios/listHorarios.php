<?php
// app/controllers/horarios/listHorarios.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json; charset=utf-8');

$id_turma = isset($_GET['id_turma']) ? (int)$_GET['id_turma'] : 0;
$id_turno = isset($_GET['id_turno']) ? (int)$_GET['id_turno'] : 0; // opcional (recomendado)

if ($id_turma <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Parâmetro id_turma inválido.']);
    exit;
}

try {
    // 1) Dados da turma
    $sqlTurma = "
        SELECT 
            t.*,
            s.nome_serie,
            s.id_serie,
            al.ano,
            turno.nome_turno
        FROM turma t
        JOIN serie s       ON t.id_serie      = s.id_serie
        JOIN ano_letivo al ON t.id_ano_letivo = al.id_ano_letivo
        JOIN turno         ON t.id_turno      = turno.id_turno
        WHERE t.id_turma = ?
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sqlTurma);
    $stmt->execute([$id_turma]);
    $turma = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$turma) {
        echo json_encode(['status' => 'error', 'message' => 'Turma não encontrada.']);
        exit;
    }

    // se vier id_turno, valida coerência; senão usa o turno da turma
    if ($id_turno > 0 && (int)$turma['id_turno'] !== $id_turno) {
        echo json_encode(['status' => 'error', 'message' => 'Turno informado não corresponde ao turno da turma.']);
        exit;
    }
    if ($id_turno <= 0) $id_turno = (int)$turma['id_turno'];

    $idAnoLetivo = (int)$turma['id_ano_letivo'];

    // 2) Dias do turno
    $sqlDias = "
        SELECT *
        FROM turno_dias
        WHERE id_turno = ?
        ORDER BY FIELD(dia_semana,'Segunda','Terca','Quarta','Quinta','Sexta','Sabado','Domingo')
    ";
    $stmt = $pdo->prepare($sqlDias);
    $stmt->execute([$id_turno]);
    $turno_dias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3) Disciplinas da série
    $sqlDisciplinas = "
        SELECT 
            d.id_disciplina,
            d.nome_disciplina,
            d.sigla_disciplina,
            sd.aulas_semana
        FROM serie_disciplinas sd
        JOIN disciplina d ON sd.id_disciplina = d.id_disciplina
        WHERE sd.id_serie = ?
        ORDER BY d.nome_disciplina
    ";
    $stmt = $pdo->prepare($sqlDisciplinas);
    $stmt->execute([(int)$turma['id_serie']]);
    $serie_disciplinas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4) Professores
    $sqlProfs = "
        SELECT 
            p.id_professor,
            COALESCE(p.nome_exibicao, p.nome_completo) AS nome_exibicao
        FROM professor p
        ORDER BY nome_exibicao
    ";
    $stmt = $pdo->query($sqlProfs);
    $professores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5) Horários cadastrados (FILTRA ANO + TURNO)
    $sqlHorarios = "
        SELECT 
            h.id_horario,
            h.id_ano_letivo,
            h.id_turma,
            h.id_turno,
            h.dia_semana,
            h.numero_aula,
            h.id_disciplina,
            h.id_professor,
            COALESCE(d.nome_disciplina,'') AS nome_disciplina,
            COALESCE(p.nome_exibicao, p.nome_completo,'') AS nome_professor
        FROM horario h
        LEFT JOIN disciplina d ON h.id_disciplina = d.id_disciplina
        LEFT JOIN professor  p ON h.id_professor  = p.id_professor
        WHERE h.id_turma = ?
        AND h.id_turno = ?
        AND h.id_ano_letivo = ?
    ";
    $stmt = $pdo->prepare($sqlHorarios);
    $stmt->execute([$id_turma, $id_turno, $idAnoLetivo]);
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);


    echo json_encode([
        'status' => 'success',
        'data' => [
            'turma'             => $turma,
            'turno_dias'        => $turno_dias,
            'serie_disciplinas' => $serie_disciplinas,
            'professores'       => $professores,
            'horarios'          => $horarios
        ]
    ]);
    exit;

} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>