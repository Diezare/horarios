<?php
// /app/controllers/evento/insertEvento.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idAnoLetivo  = intval($_POST['id_ano_letivo'] ?? 0);
    $tipoEvento   = trim($_POST['tipo_evento'] ?? '');
    $nomeEvento   = trim($_POST['nome_evento'] ?? '');
    $dataInicio   = trim($_POST['data_inicio'] ?? '');
    $dataFim      = trim($_POST['data_fim'] ?? '');
    $observacoes  = trim($_POST['observacoes'] ?? '');

    if ($idAnoLetivo <= 0 || empty($tipoEvento) || empty($nomeEvento) || empty($dataInicio) || empty($dataFim)) {
        echo json_encode(['status' => 'error', 'message' => 'Preencha os campos obrigatórios.']);
        exit;
    }

    // Validar se o tipo de evento é válido
    $tiposValidos = ['feriado', 'recesso', 'ferias'];
    if (!in_array($tipoEvento, $tiposValidos)) {
        echo json_encode(['status' => 'error', 'message' => 'Tipo de evento inválido.']);
        exit;
    }

    // Validar se data_inicio <= data_fim
    if ($dataInicio > $dataFim) {
        echo json_encode(['status' => 'error', 'message' => 'A data de início deve ser anterior ou igual à data de fim.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO eventos_calendario_escolar (id_ano_letivo, tipo_evento, nome_evento, data_inicio, data_fim, observacoes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$idAnoLetivo, $tipoEvento, $nomeEvento, $dataInicio, $dataFim, $observacoes]);

        echo json_encode([
            'status'  => 'success',
            'message' => 'Evento inserido com sucesso!',
            'id'      => $pdo->lastInsertId()
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Erro de integridade dos dados.'
            ]);
        } else {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Erro ao inserir evento: ' . $e->getMessage()
            ]);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
}
?>