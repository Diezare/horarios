<?php
// /app/controllers/evento/updateEvento.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id           = intval($_POST['id_evento'] ?? 0);
    $idAnoLetivo  = intval($_POST['id_ano_letivo'] ?? 0);
    $tipoEvento   = trim($_POST['tipo_evento'] ?? '');
    $nomeEvento   = trim($_POST['nome_evento'] ?? '');
    $dataInicio   = trim($_POST['data_inicio'] ?? '');
    $dataFim      = trim($_POST['data_fim'] ?? '');
    $observacoes  = trim($_POST['observacoes'] ?? '');

    if ($id <= 0 || $idAnoLetivo <= 0 || empty($tipoEvento) || empty($nomeEvento) || empty($dataInicio) || empty($dataFim)) {
        echo json_encode(['status' => 'error', 'message' => 'Dados incompletos.']);
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
            UPDATE eventos_calendario_escolar
               SET id_ano_letivo = ?,
                   tipo_evento = ?,
                   nome_evento = ?,
                   data_inicio = ?,
                   data_fim = ?,
                   observacoes = ?
             WHERE id_evento = ?
        ");
        $stmt->execute([$idAnoLetivo, $tipoEvento, $nomeEvento, $dataInicio, $dataFim, $observacoes, $id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'status'  => 'success',
                'message' => 'Evento atualizado com sucesso.'
            ]);
        } else {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Nenhuma alteração ou evento não encontrado.'
            ]);
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['status' => 'error', 'message' => 'Erro de integridade dos dados.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar evento: ' . $e->getMessage()]);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
}
?>