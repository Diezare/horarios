<?php
// app/controllers/turma/insertTurma.php - VERSÃO FINAL CORRIGIDA
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
    exit;
}

$id_ano_letivo = isset($_POST['id_ano_letivo']) ? intval($_POST['id_ano_letivo']) : 0;
$id_serie = isset($_POST['id_serie']) ? intval($_POST['id_serie']) : 0;
$nome_turma = trim($_POST['nome_turma'] ?? '');
$turnos = $_POST['turnos'] ?? [];

// ✅ Recebe intervalos por turno (nomes dos campos como enviados pelo FormData)
$intervalos_quantidade = $_POST['intervalos_quantidade'] ?? [];
$intervalos_posicoes = $_POST['intervalos_posicoes'] ?? [];

// Validações básicas
if ($id_ano_letivo <= 0 || $id_serie <= 0 || empty($nome_turma) || empty($turnos)) {
    echo json_encode(['status' => 'error', 'message' => 'Dados incompletos.']);
    exit;
}

// Valida se cada turno tem intervalos configurados
foreach ($turnos as $id_turno) {
    $id_turno = intval($id_turno);
    
    // Verifica se os intervalos para este turno existem
    $quantidade = isset($intervalos_quantidade[$id_turno]) ? intval($intervalos_quantidade[$id_turno]) : 0;
    $posicoes = isset($intervalos_posicoes[$id_turno]) ? trim($intervalos_posicoes[$id_turno]) : '';
    
    if ($quantidade <= 0 || empty($posicoes)) {
        echo json_encode(['status' => 'error', 'message' => 'Configure os intervalos para todos os turnos selecionados.']);
        exit;
    }
    
    // Valida formato das posições (pode ser "4" ou "4,2")
    if (!preg_match('/^\d+(,\d+)?$/', $posicoes)) {
        echo json_encode(['status' => 'error', 'message' => 'Formato inválido para posições dos intervalos. Use números separados por vírgula (ex: 3 ou 3,5).']);
        exit;
    }
}

try {
    $pdo->beginTransaction();
    
    // Para cada turno selecionado, insere uma turma
    foreach ($turnos as $id_turno) {
        $id_turno = intval($id_turno);
        
        // Obtém intervalos específicos para este turno
        $quantidade = isset($intervalos_quantidade[$id_turno]) ? intval($intervalos_quantidade[$id_turno]) : 0;
        $posicoes = isset($intervalos_posicoes[$id_turno]) ? trim($intervalos_posicoes[$id_turno]) : '';
        
        // Verifica se a turma já existe (mesmo ano, série, turno e nome)
        $stmtCheck = $pdo->prepare("
            SELECT id_turma 
            FROM turma 
            WHERE id_ano_letivo = ? 
              AND id_serie = ? 
              AND id_turno = ? 
              AND nome_turma = ?
        ");
        $stmtCheck->execute([$id_ano_letivo, $id_serie, $id_turno, $nome_turma]);
        
        if ($stmtCheck->fetch()) {
            $pdo->rollBack();
            echo json_encode([
                'status' => 'error', 
                'message' => "Já existe uma turma '$nome_turma' para o turno selecionado."
            ]);
            exit;
        }
        
        // Insere a turma
        $stmt = $pdo->prepare("
            INSERT INTO turma 
            (id_ano_letivo, id_serie, id_turno, nome_turma, intervalos_por_dia, intervalos_positions)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $id_ano_letivo,
            $id_serie,
            $id_turno,
            $nome_turma,
            $quantidade,
            $posicoes
        ]);
    }
    
    $pdo->commit();
    echo json_encode([
        'status' => 'success', 
        'message' => count($turnos) > 1 
            ? 'Turmas criadas com sucesso para ' . count($turnos) . ' turnos!' 
            : 'Turma criada com sucesso!'
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao inserir turma(s): ' . $e->getMessage()
    ]);
}
?>