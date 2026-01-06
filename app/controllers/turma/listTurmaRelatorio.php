<?php
// app/controllers/turma/listTurmaRelatorio.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

/*--------------------------
  1) Captura dos parâmetros
---------------------------*/
$idAnoLetivo   = isset($_GET['id_ano_letivo'])   ? (int)$_GET['id_ano_letivo']   : 0;
$idNivelEnsino = isset($_GET['id_nivel_ensino']) ? (int)$_GET['id_nivel_ensino'] : 0;
$idTurno       = isset($_GET['id_turno'])        ? (int)$_GET['id_turno']        : 0;   //  << NOVO

/*--------------------------
  2) SQL base
---------------------------*/
$sql = "
 SELECT 
   t.id_turma,
   t.nome_turma,
   s.nome_serie,
   tur.nome_turno,
   a.ano
 FROM turma t
 JOIN serie      s   ON s.id_serie       = t.id_serie
 JOIN turno      tur ON tur.id_turno     = t.id_turno
 JOIN ano_letivo a   ON a.id_ano_letivo  = t.id_ano_letivo
 WHERE 1=1
";

/*--------------------------
  3) Filtros dinâmicos
---------------------------*/
$params = [];

if ($idAnoLetivo > 0) {
    $sql .= " AND t.id_ano_letivo = :ano ";
    $params[':ano'] = $idAnoLetivo;
}

if ($idNivelEnsino > 0) {
    $sql .= " AND s.id_nivel_ensino = :nivel ";
    $params[':nivel'] = $idNivelEnsino;
}

if ($idTurno > 0) {                             //  << NOVO
    $sql .= " AND t.id_turno = :turno ";
    $params[':turno'] = $idTurno;
}

/*--------------------------
  4) Ordenação e execução
---------------------------*/
$sql .= "
 ORDER BY a.ano DESC,
          s.nome_serie ASC,
          t.nome_turma ASC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Erro ao listar turmas: ' . $e->getMessage()
    ]);
}
?>
