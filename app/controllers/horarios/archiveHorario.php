<?php
// app/controllers/horarios/archiveHorario.php

require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
  exit;
}

$idUsuario = (int)($_SESSION['id_usuario'] ?? 0);
if ($idUsuario <= 0) {
  echo json_encode(['status' => 'error', 'message' => 'Não autenticado.']);
  exit;
}

$id_horario_original   = (int)($_POST['id_horario_original'] ?? 0);
$id_turma              = (int)($_POST['id_turma'] ?? 0);
$id_turno              = (int)($_POST['id_turno'] ?? 0);
$id_ano_letivo          = (int)($_POST['id_ano_letivo'] ?? 0);
$dia_semana            = trim((string)($_POST['dia_semana'] ?? ''));
$numero_aula           = (int)($_POST['numero_aula'] ?? 0);
$id_disciplina         = (int)($_POST['id_disciplina'] ?? 0);
$id_professor          = (int)($_POST['id_professor'] ?? 0);
$data_criacao_original = $_POST['data_criacao'] ?? null;

if ($id_horario_original <= 0) {
  echo json_encode(['status' => 'error', 'message' => 'id_horario_original não informado.']);
  exit;
}

$validDias = ['Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado'];
if ($dia_semana !== '' && !in_array($dia_semana, $validDias, true)) {
  echo json_encode(['status' => 'error', 'message' => 'Dia da semana inválido.']);
  exit;
}
 
try {
  /**
   * 1) Carrega dados do horário original + valida permissão do usuário via nível
   *    (isso também serve como fallback para preencher campos ausentes do POST).
   */
  $stmt = $pdo->prepare("
    SELECT
      h.id_horario,
      h.id_turma,
      h.id_turno,
      h.dia_semana,
      h.numero_aula,
      h.id_disciplina,
      h.id_professor,
      h.data_criacao,
      t.id_ano_letivo
    FROM horario h
    JOIN turma t ON t.id_turma = h.id_turma
    JOIN serie s ON t.id_serie = s.id_serie
    JOIN nivel_ensino ne ON s.id_nivel_ensino = ne.id_nivel_ensino
    JOIN usuario_niveis un ON un.id_nivel_ensino = ne.id_nivel_ensino
    WHERE h.id_horario = ?
      AND un.id_usuario = ?
    LIMIT 1
  ");
  $stmt->execute([$id_horario_original, $idUsuario]);
  $hinfo = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$hinfo) {
    echo json_encode(['status' => 'error', 'message' => 'Horário não encontrado ou sem permissão.']);
    exit;
  }

  // 2) Completa campos faltantes do POST com os do BD (prioriza POST se veio preenchido)
  if ($id_turma <= 0)     $id_turma = (int)$hinfo['id_turma'];
  if ($id_turno <= 0)     $id_turno = (int)$hinfo['id_turno'];
  if ($id_ano_letivo <= 0)$id_ano_letivo = (int)$hinfo['id_ano_letivo'];

  if ($dia_semana === '') $dia_semana = (string)$hinfo['dia_semana'];
  if ($numero_aula <= 0)  $numero_aula = (int)$hinfo['numero_aula'];
  if ($id_disciplina <= 0)$id_disciplina = (int)$hinfo['id_disciplina'];
  if ($id_professor <= 0) $id_professor = (int)$hinfo['id_professor'];

  if (!$data_criacao_original) {
    $data_criacao_original = $hinfo['data_criacao'] ?: date('Y-m-d H:i:s');
  }

  // 3) Insere no histórico
  $stmtIns = $pdo->prepare("
    INSERT INTO historico_horario
      (id_horario_original, id_turma, id_turno, id_ano_letivo, dia_semana, numero_aula,
       id_disciplina, id_professor, data_criacao_original, data_arquivamento)
    VALUES
      (:id_horario_original, :id_turma, :id_turno, :id_ano_letivo, :dia_semana, :numero_aula,
       :id_disciplina, :id_professor, :data_criacao_original, NOW())
  ");

  $stmtIns->execute([
    ':id_horario_original'    => $id_horario_original,
    ':id_turma'              => $id_turma,
    ':id_turno'              => $id_turno,
    ':id_ano_letivo'          => $id_ano_letivo,
    ':dia_semana'            => $dia_semana,
    ':numero_aula'           => $numero_aula,
    ':id_disciplina'         => $id_disciplina,
    ':id_professor'          => $id_professor,
    ':data_criacao_original' => $data_criacao_original,
  ]);

  echo json_encode(['status' => 'success', 'message' => 'Histórico inserido com sucesso.']);
  exit;

} catch (Throwable $e) {
  echo json_encode(['status' => 'error', 'message' => 'Erro ao arquivar: ' . $e->getMessage()]);
  exit;
}
?>