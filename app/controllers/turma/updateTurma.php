<?php
// app/controllers/turma/updateTurma.php - VERSÃO COM VALIDAÇÃO
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
    exit;
}
 
$id_turma = intval($_POST['id_turma'] ?? 0);
$id_ano_letivo = intval($_POST['id_ano_letivo'] ?? 0);
$id_serie = intval($_POST['id_serie'] ?? 0);
$id_turno = intval($_POST['id_turno'] ?? 0);
$nome_turma = trim($_POST['nome_turma'] ?? '');
$intervalos_por_dia = intval($_POST['intervalos_por_dia'] ?? 0);
$intervalos_positions = trim($_POST['intervalos_positions'] ?? '');

if ($id_turma <= 0 || $id_ano_letivo <= 0 || $id_serie <= 0 || $id_turno <= 0 || empty($nome_turma) || $intervalos_por_dia <= 0 || empty($intervalos_positions)) {    
    echo json_encode(['status' => 'error', 'message' => 'Dados incompletos ou inválidos.']);
    exit;
}

// Valida formato das posições (pode ser "4" ou "4,2")
if (!preg_match('/^\d+(,\d+)?$/', $intervalos_positions)) {
    echo json_encode(['status' => 'error', 'message' => 'Formato inválido para posições dos intervalos. Use números separados por vírgula (ex: 3 ou 3,5).']);
    exit;
}

try {
    // ✅ NOVA VALIDAÇÃO: Verifica se já existe outra turma com mesmo nome, série e turno (exceto a própria)
    $stmtCheck = $pdo->prepare("
        SELECT id_turma 
        FROM turma 
        WHERE id_ano_letivo = ? 
          AND id_serie = ? 
          AND id_turno = ? 
          AND nome_turma = ?
          AND id_turma != ?
    ");
    $stmtCheck->execute([$id_ano_letivo, $id_serie, $id_turno, $nome_turma, $id_turma]);
    
    if ($stmtCheck->fetch()) {
        echo json_encode([
            'status' => 'error', 
            'message' => "Já existe uma turma '$nome_turma' para este turno na mesma série."
        ]);
        exit;
    }
    
    // Atualiza a turma
    $stmt = $pdo->prepare("
        UPDATE turma
        SET 
            id_ano_letivo = ?,
            id_serie = ?,
            id_turno = ?,
            nome_turma = ?,
            intervalos_por_dia = ?,
            intervalos_positions = ?
        WHERE id_turma = ?
    ");
    $stmt->execute([$id_ano_letivo, $id_serie, $id_turno, $nome_turma, $intervalos_por_dia, $intervalos_positions, $id_turma]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Turma atualizada com sucesso.']);
    } else {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Nenhuma alteração ou turma não encontrada.'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar: ' . $e->getMessage()]);
}
?>