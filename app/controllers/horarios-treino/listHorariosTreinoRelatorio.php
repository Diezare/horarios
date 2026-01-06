<?php
// /horarios/app/controllers/horarios-treino/listHorariosTreinoRelatorio.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json; charset=utf-8');

// Parâmetros obrigatórios/opcionais
$idAnoLetivo   = isset($_GET['id_ano_letivo'])   ? (int)$_GET['id_ano_letivo']   : 0;
$idNivelEnsino = isset($_GET['id_nivel_ensino']) ? (int)$_GET['id_nivel_ensino'] : 0;
$idTurno       = isset($_GET['id_turno'])        ? (int)$_GET['id_turno']        : 0;
$idModalidade  = isset($_GET['id_modalidade'])   ? (int)$_GET['id_modalidade']   : 0;
$idCategoria   = isset($_GET['id_categoria'])    ? (int)$_GET['id_categoria']    : 0;
$idProfessor   = isset($_GET['id_professor'])    ? (int)$_GET['id_professor']    : 0;

// Validação básica
if ($idAnoLetivo <= 0) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'id_ano_letivo inválido ou não informado.'
    ]);
    exit;
}

try {
    // Monta o WHERE dinamicamente
    $where  = " WHERE he.id_ano_letivo = :ano ";
    $params = [ ':ano' => $idAnoLetivo ];

    // Se veio Nível de Ensino
    if ($idNivelEnsino > 0) {
        $where .= " AND he.id_nivel_ensino = :nivel ";
        $params[':nivel'] = $idNivelEnsino;
    }

    // Se veio Turno
    if ($idTurno > 0) {
        $where .= " AND he.id_turno = :turno ";
        $params[':turno'] = $idTurno;
    }
    
    // Novos filtros
    // Se veio Modalidade
    if ($idModalidade > 0) {
        $where .= " AND he.id_modalidade = :modalidade ";
        $params[':modalidade'] = $idModalidade;
    }
    
    // Se veio Categoria
    if ($idCategoria > 0) {
        $where .= " AND he.id_categoria = :categoria ";
        $params[':categoria'] = $idCategoria;
    }
    
    // Se veio Professor
    if ($idProfessor > 0) {
        $where .= " AND he.id_professor = :professor ";
        $params[':professor'] = $idProfessor;
    }
    
    // Primeiro buscamos todos os registros individuais
    $sql = "
        SELECT 
            he.id_horario_escolinha,
            a.ano,
            p.id_professor,
            mo.id_modalidade,
            c.id_categoria,
            mo.nome_modalidade,
            c.nome_categoria,
            COALESCE(p.nome_exibicao, p.nome_completo) AS nome_professor
        FROM horario_escolinha he
        JOIN ano_letivo   a  ON he.id_ano_letivo   = a.id_ano_letivo
        JOIN nivel_ensino ne ON he.id_nivel_ensino = ne.id_nivel_ensino
        JOIN modalidade   mo ON he.id_modalidade   = mo.id_modalidade
        JOIN categoria    c  ON he.id_categoria    = c.id_categoria
        JOIN professor    p  ON he.id_professor    = p.id_professor
        JOIN turno        t  ON he.id_turno        = t.id_turno
        $where
        ORDER BY 
            p.nome_exibicao,
            mo.nome_modalidade,
            c.nome_categoria
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $allRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Agora vamos agrupar os resultados por professor
    $groupedData = [];
    foreach ($allRows as $row) {
        $profId = $row['id_professor'];
        
        // Se o professor ainda não está no array agrupado, inicializamos
        if (!isset($groupedData[$profId])) {
            $groupedData[$profId] = [
                'id_horario_escolinha' => $row['id_horario_escolinha'], // Mantemos o primeiro ID para referência
                'ano' => $row['ano'],
                'nome_professor' => $row['nome_professor'],
                'modalidades' => [],
                'categorias' => [],
                'id_professor' => $row['id_professor'],
                'id_modalidade' => [], // Adicionado para controle
                'id_categoria' => []  // Adicionado para controle
            ];
        }
        
        // Adiciona a modalidade se ainda não existe no array
        if (!in_array($row['nome_modalidade'], $groupedData[$profId]['modalidades'])) {
            $groupedData[$profId]['modalidades'][] = $row['nome_modalidade'];
            $groupedData[$profId]['id_modalidade'][] = $row['id_modalidade'];
        }
        
        // Adiciona a categoria se ainda não existe no array
        if (!in_array($row['nome_categoria'], $groupedData[$profId]['categorias'])) {
            $groupedData[$profId]['categorias'][] = $row['nome_categoria'];
            $groupedData[$profId]['id_categoria'][] = $row['id_categoria'];
        }
    }
    
    // Converte para o formato final
    $finalResults = [];
    foreach ($groupedData as $data) {
        $finalResults[] = [
            'id_horario_escolinha' => $data['id_horario_escolinha'],
            'ano' => $data['ano'],
            'nome_modalidade' => implode(', ', $data['modalidades']),
            'nome_categoria' => implode(', ', $data['categorias']),
            'nome_professor' => $data['nome_professor'],
            'id_professor' => $data['id_professor'],
            'id_modalidades' => $data['id_modalidade'], // Array de IDs
            'id_categorias' => $data['id_categoria']   // Array de IDs
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data'   => $finalResults
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
?>