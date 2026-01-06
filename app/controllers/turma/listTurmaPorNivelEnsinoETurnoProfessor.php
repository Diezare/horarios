<?php
// app/controllers/turma/listTurmaPorNivelEnsinoETurnoProfessor.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$id_ano_letivo = isset($_GET['id_ano_letivo']) ? intval($_GET['id_ano_letivo']) : 0;
$id_nivel_ensino = isset($_GET['id_nivel_ensino']) ? intval($_GET['id_nivel_ensino']) : 0;
$id_professor = isset($_GET['id_professor']) ? intval($_GET['id_professor']) : 0;

if ($id_ano_letivo <= 0 || $id_nivel_ensino <= 0 || $id_professor <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Parâmetros inválidos.']);
    exit;
}

try {
    // Primeiro, pega os turnos em que o professor está cadastrado
    $sqlTurnosProfessor = "
        SELECT pt.id_turno 
        FROM professor_turnos pt
        WHERE pt.id_professor = :id_professor
    ";
    
    $stmtTurnos = $pdo->prepare($sqlTurnosProfessor);
    $stmtTurnos->execute([':id_professor' => $id_professor]);
    $turnosProfessor = $stmtTurnos->fetchAll(PDO::FETCH_COLUMN, 0);
    
    if (empty($turnosProfessor)) {
        echo json_encode(['status' => 'success', 'data' => []]);
        exit;
    }
    
    // Converte array de turnos para string para usar no IN()
    $placeholders = implode(',', array_fill(0, count($turnosProfessor), '?'));
    
    // Agora busca as turmas que estão nos turnos do professor
    $sql = "
        SELECT 
            t.id_turma,
            t.nome_turma,
            s.id_serie,
            s.nome_serie,
            s.id_nivel_ensino,
            tn.id_turno,
            tn.nome_turno,
            ne.nome_nivel_ensino
        FROM turma t
        INNER JOIN serie s ON t.id_serie = s.id_serie
        INNER JOIN nivel_ensino ne ON s.id_nivel_ensino = ne.id_nivel_ensino
        INNER JOIN turno tn ON t.id_turno = tn.id_turno
        WHERE t.id_ano_letivo = ?
          AND s.id_nivel_ensino = ?
          AND tn.id_turno IN ($placeholders)
        ORDER BY s.nome_serie, t.nome_turma, tn.nome_turno
    ";
    
    // Prepara os parâmetros
    $params = array_merge([$id_ano_letivo, $id_nivel_ensino], $turnosProfessor);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'debug' => [
            'turnos_professor' => $turnosProfessor,
            'total_turmas' => count($data)
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>