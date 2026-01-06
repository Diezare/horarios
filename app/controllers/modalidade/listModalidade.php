<?php
// app/controllers/modalidade/listModalidade.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

try {
    // Se chegar um id_modalidade via GET, filtramos uma única modalidade
    if (isset($_GET['id_modalidade']) && intval($_GET['id_modalidade']) > 0) {
        $id = intval($_GET['id_modalidade']);
        $stmt = $pdo->prepare("SELECT * FROM modalidade WHERE id_modalidade = ?");
        $stmt->execute([$id]);
    } else {
        // Caso contrário, listamos todas (o comportamento é semelhante ao listAllModalidade.php)
        $stmt = $pdo->query("SELECT * FROM modalidade ORDER BY nome_modalidade ASC");
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data'   => $rows
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>
