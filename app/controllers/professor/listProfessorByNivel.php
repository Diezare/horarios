<?php
// app/controllers/professor/listProfessorByNivel.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

try {
    $nivel = $_GET['nivel'] ?? '';

    if ($nivel === '' || strtolower($nivel) === 'todas' || strtolower($nivel) === 'todos') {
        $stmt = $pdo->query("
            SELECT DISTINCT p.id_professor, p.nome_completo
            FROM professor p
            ORDER BY p.nome_completo
        ");
        echo json_encode(['status'=>'success','data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    $params = [];
    if (ctype_digit((string)$nivel)) {
        $where = "s.id_nivel_ensino = ?";
        $params[] = (int)$nivel;
    } else {
        $where = "n.nome_nivel_ensino = ?";
        $params[] = $nivel;
    }

    $sql = "
        SELECT DISTINCT p.id_professor, p.nome_completo
        FROM professor p
        JOIN professor_disciplinas_turmas pdt ON pdt.id_professor = p.id_professor
        JOIN turma t ON t.id_turma = pdt.id_turma
        JOIN serie s ON s.id_serie = t.id_serie
        JOIN nivel_ensino n ON n.id_nivel_ensino = s.id_nivel_ensino
        WHERE {$where}
        ORDER BY p.nome_completo
    ";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    echo json_encode(['status'=>'success','data'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>