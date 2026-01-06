<?php
// app/controllers/professor-restricoes/listProfessorRestricoesPorAnoLetivo.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

/*
  Retorna listagem distinta de professores que tenham 
  alguma restrição no ano letivo informado.
*/
$id_ano = isset($_GET['id_ano']) ? intval($_GET['id_ano']) : 0;

try {
    if ($id_ano > 0) {
        // Modifiquei para mostrar também os turnos vinculados
        $stmt = $pdo->prepare("
            SELECT DISTINCT 
                p.id_professor, 
                p.nome_completo AS nome_professor,
                t.id_turno,
                t.nome_turno
            FROM professor_restricoes pr
            JOIN professor p ON pr.id_professor = p.id_professor
            JOIN turno t ON pr.id_turno = t.id_turno
            WHERE pr.id_ano_letivo = :id_ano
            ORDER BY p.nome_completo, t.nome_turno
        ");
        $stmt->bindValue(':id_ano', $id_ano, PDO::PARAM_INT);
        $stmt->execute();
        
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $rows]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Ano letivo não informado.']);
    }
} catch (PDOException $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>