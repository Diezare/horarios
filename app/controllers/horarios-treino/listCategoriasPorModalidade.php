<?php
// app/controllers/horarios-treino/listCategoriasPorModalidade.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json; charset=utf-8');

// Parâmetros obrigatórios/opcionais
$idAnoLetivo  = isset($_GET['id_ano_letivo']) ? (int)$_GET['id_ano_letivo'] : 0;
$idModalidade = isset($_GET['id_modalidade']) ? (int)$_GET['id_modalidade'] : 0;
$idTurno      = isset($_GET['id_turno']) ? (int)$_GET['id_turno'] : 0;

// Validação básica
if ($idAnoLetivo <= 0) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'id_ano_letivo inválido ou não informado.'
    ]);
    exit;
}

if ($idModalidade <= 0) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'id_modalidade inválido ou não informado.'
    ]);
    exit;
}

try {
    // Monta o WHERE dinamicamente
    $where  = " WHERE he.id_ano_letivo = :ano AND he.id_modalidade = :modalidade ";
    $params = [ 
        ':ano' => $idAnoLetivo,
        ':modalidade' => $idModalidade 
    ];

    // Se veio Turno
    if ($idTurno > 0) {
        $where .= " AND he.id_turno = :turno ";
        $params[':turno'] = $idTurno;
    }
    
    // Buscamos as categorias que estão presentes nos horários de treino
    $sql = "
        SELECT DISTINCT
            c.id_categoria,
            c.nome_categoria
        FROM horario_escolinha he
        JOIN categoria c ON he.id_categoria = c.id_categoria
        $where
        ORDER BY c.nome_categoria ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data'   => $categorias
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
?>