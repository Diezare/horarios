<?php
// app/controllers/horarios/buscarHorarios.php

require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $id_ano_letivo = isset($_POST['id_ano_letivo']) ? (int)$_POST['id_ano_letivo'] : 0;
    $id_turma      = isset($_POST['id_turma']) ? (int)$_POST['id_turma'] : 0;
    $id_turno      = isset($_POST['id_turno']) ? (int)$_POST['id_turno'] : 0;

    if ($id_ano_letivo <= 0 || $id_turma <= 0) {
        echo json_encode(['ok' => false, 'erro' => 'Parâmetros inválidos.']);
        exit;
    }

    // Fallback: se não veio turno, pega da turma (evita “não carrega”)
    if ($id_turno <= 0) {
        $st = $pdo->prepare("SELECT turno_id FROM turma WHERE id_turma = ?");
        $st->execute([$id_turma]);
        $id_turno = (int)($st->fetchColumn() ?: 0);
    }

    $sql = "
        SELECT
            h.dia_semana,
            h.numero_aula,
            h.id_disciplina,
            d.nome_disciplina,
            h.id_professor,
            p.nome_exibicao AS nome_professor
        FROM horario h
        LEFT JOIN disciplina d ON d.id_disciplina = h.id_disciplina
        LEFT JOIN professor  p ON p.id_professor  = h.id_professor
        WHERE h.id_ano_letivo = ?
          AND h.id_turma      = ?
          AND h.id_turno      = ?
        ORDER BY FIELD(h.dia_semana,'Segunda','Terca','Quarta','Quinta','Sexta','Sabado','Domingo'),
                 h.numero_aula
    ";

    $st = $pdo->prepare($sql);
    $st->execute([$id_ano_letivo, $id_turma, $id_turno]);
    $horarios = $st->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'id_turno' => $id_turno,
        'horarios' => $horarios
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'erro' => $e->getMessage()]);
}
