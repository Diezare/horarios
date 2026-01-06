<?php
// app/controllers/nivel-ensino/listNivelEnsinoByUserAndAno.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$idUsuario    = $_SESSION['id_usuario'] ?? 0;
$idAnoLetivo  = isset($_GET['id_ano_letivo']) ? intval($_GET['id_ano_letivo']) : 0;

try {
    // Filtra pelos níveis que constam em usuario_niveis
    // e que tenham turma no ano letivo especificado.
    $sql = "
      SELECT DISTINCT ne.id_nivel_ensino, ne.nome_nivel_ensino
        FROM nivel_ensino ne
        JOIN usuario_niveis un   ON un.id_nivel_ensino = ne.id_nivel_ensino
        JOIN serie s            ON s.id_nivel_ensino   = ne.id_nivel_ensino
        JOIN turma t            ON t.id_serie          = s.id_serie
       WHERE un.id_usuario = :idUsuario
         AND t.id_ano_letivo = :idAno
       ORDER BY ne.nome_nivel_ensino
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':idUsuario', $idUsuario);
    $stmt->bindValue(':idAno', $idAnoLetivo);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status'=>'success','data'=>$rows]);
} catch(Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>
