<?php
// app/controllers/sala-turno/checkTurmasDisponiveis.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../configs/init.php';

$id_turno = isset($_GET['id_turno']) ? intval($_GET['id_turno']) : 0;
$id_sala  = isset($_GET['id_sala']) ? intval($_GET['id_sala']) : 0;

if ($id_turno <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Turno inválido']);
    exit;
}

if ($id_sala > 0) {
    $stmt = $pdo->prepare("SELECT id_turma FROM sala_turno WHERE id_turno = :id_turno AND id_sala != :id_sala");
    $stmt->execute([':id_turno' => $id_turno, ':id_sala' => $id_sala]);
} else {
    $stmt = $pdo->prepare("SELECT id_turma FROM sala_turno WHERE id_turno = :id_turno");
    $stmt->execute([':id_turno' => $id_turno]);
}

$turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);
$disabled = [];
foreach ($turmas as $t) {
    $disabled[] = $t['id_turma'];
}

echo json_encode(['status' => 'success', 'data' => $disabled]);
exit;
?>
