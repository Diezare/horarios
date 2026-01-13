<?php
// app/controllers/horarios/listHistoricoHorarios.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json; charset=utf-8');

$idUsuario = (int)($_SESSION['id_usuario'] ?? 0);
if ($idUsuario <= 0) {
  echo json_encode(['status' => 'error', 'message' => 'Não autenticado.']);
  exit;
}

$idAnoLetivo      = (int)($_GET['id_ano_letivo'] ?? 0);
$idNivelEnsino    = (int)($_GET['id_nivel_ensino'] ?? 0);
$idTurno          = (int)($_GET['id_turno'] ?? 0);
$idTurma          = (int)($_GET['id_turma'] ?? 0);
$dataArquivamento = $_GET['data_arquivamento'] ?? null;

if ($idAnoLetivo <= 0) {
  echo json_encode(['status' => 'error', 'message' => 'Ano letivo não informado']);
  exit;
}

function hasColumn(PDO $pdo, string $table, string $column): bool {
  try {
    $stmt = $pdo->prepare("
      SELECT 1
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = :t
        AND COLUMN_NAME = :c
      LIMIT 1
    ");
    $stmt->execute([':t' => $table, ':c' => $column]);
    return (bool)$stmt->fetchColumn();
  } catch (Throwable $e) {
    return false;
  }
}

try {
  // Detecta se historico_horario tem id_ano_letivo / id_turno
  $histHasAno   = hasColumn($pdo, 'historico_horario', 'id_ano_letivo');
  $histHasTurno = hasColumn($pdo, 'historico_horario', 'id_turno');

  // Base SELECT: lista cada evento de arquivamento (um por linha)
  $sql = "
    SELECT
      h.id_turma,
      t.nome_turma,
      s.nome_serie,
      ne.nome_nivel_ensino,
      -- Turno: se histórico tiver, usa ele; senão usa turno atual da turma
      tur.nome_turno,
      -- Ano letivo (numérico): se histórico tiver, usa ele; senão usa turma
      a.ano AS ano,
      DATE_FORMAT(h.data_arquivamento, '%Y-%m-%d %H:%i:%s') AS data_arquivamento
    FROM historico_horario h
    JOIN turma t       ON h.id_turma = t.id_turma
    JOIN serie s       ON t.id_serie = s.id_serie
    JOIN nivel_ensino ne ON s.id_nivel_ensino = ne.id_nivel_ensino
    JOIN usuario_niveis un ON un.id_nivel_ensino = ne.id_nivel_ensino AND un.id_usuario = :uid
  ";

  // JOIN turno: preferir h.id_turno quando existir
  if ($histHasTurno) {
    $sql .= " JOIN turno tur ON tur.id_turno = h.id_turno ";
  } else {
    $sql .= " JOIN turno tur ON tur.id_turno = t.id_turno ";
  }

  // JOIN ano_letivo: preferir h.id_ano_letivo quando existir
  if ($histHasAno) {
    $sql .= " JOIN ano_letivo a ON a.id_ano_letivo = h.id_ano_letivo ";
  } else {
    $sql .= " JOIN ano_letivo a ON a.id_ano_letivo = t.id_ano_letivo ";
  }

  $sql .= " WHERE 1=1 ";
  $params = [':uid' => $idUsuario];

  // Filtro por ano letivo: preferir histórico quando existir
  if ($histHasAno) {
    $sql .= " AND h.id_ano_letivo = :ano ";
  } else {
    $sql .= " AND t.id_ano_letivo = :ano ";
  }
  $params[':ano'] = $idAnoLetivo;

  if ($idNivelEnsino > 0) {
    $sql .= " AND s.id_nivel_ensino = :nivel ";
    $params[':nivel'] = $idNivelEnsino;
  }

  if ($idTurno > 0) {
    if ($histHasTurno) {
      $sql .= " AND h.id_turno = :turno ";
    } else {
      $sql .= " AND t.id_turno = :turno ";
    }
    $params[':turno'] = $idTurno;
  }

  if ($idTurma > 0) {
    $sql .= " AND t.id_turma = :turma ";
    $params[':turma'] = $idTurma;
  }

  if ($dataArquivamento) {
    // aceita exatamente "YYYY-mm-dd HH:ii:ss"
    $sql .= " AND DATE_FORMAT(h.data_arquivamento, '%Y-%m-%d %H:%i:%s') = :data_arch ";
    $params[':data_arch'] = $dataArquivamento;
  }

  $sql .= " ORDER BY a.ano DESC, ne.nome_nivel_ensino ASC, s.nome_serie ASC, t.nome_turma ASC, h.data_arquivamento DESC ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['status' => 'success', 'data' => $rows ?: []]);
  exit;

} catch (Throwable $e) {
  echo json_encode(['status' => 'error', 'message' => 'Erro ao listar histórico: ' . $e->getMessage()]);
  exit;
}
?>
