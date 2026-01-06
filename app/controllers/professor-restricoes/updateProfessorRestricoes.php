<?php
// app/controllers/professor-restricoes/updateProfessorRestricoes.php

require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json; charset=utf-8');

function respond($status, $message, $extra = []) {
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', 'Método inválido.');
}

$id_professor  = isset($_POST['id_professor'])  ? (int)$_POST['id_professor']  : 0;
$id_ano_letivo = isset($_POST['id_ano_letivo']) ? (int)$_POST['id_ano_letivo'] : 0;
$id_turno      = isset($_POST['id_turno'])      ? (int)$_POST['id_turno']      : 0;

// Pode ser {} para "limpar tudo"
$restricoesJson = isset($_POST['restricoes']) ? (string)$_POST['restricoes'] : '';

if ($id_professor <= 0 || $id_ano_letivo <= 0 || $id_turno <= 0) {
    respond('error', 'Selecione corretamente Professor, Ano Letivo e Turno.');
}

if ($restricoesJson === '') {
    // Se vier vazio, interpreta como "sem restrições" (limpar)
    $restricoes = [];
} else {
    $restricoes = json_decode($restricoesJson, true);
    if (!is_array($restricoes)) {
        respond('error', 'JSON de restrições inválido.');
    }
}

// Dias permitidos (conforme seu BD)
$diasValidos = ['Domingo', 'Segunda', 'Terca', 'Quarta', 'Quinta', 'Sexta', 'Sabado'];

// (Opcional) Limite de aulas por dia vindo do BD (turno-dias). Se falhar, assume 6.
$limitesPorDia = array_fill_keys($diasValidos, 6);
try {
    $stmtTD = $pdo->prepare("SELECT dia_semana, aulas_no_dia FROM turno_dias WHERE id_turno = ?");
    $stmtTD->execute([$id_turno]);
    $rowsTD = $stmtTD->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rowsTD as $r) {
        $dia = $r['dia_semana'] ?? '';
        $lim = isset($r['aulas_no_dia']) ? (int)$r['aulas_no_dia'] : 0;
        if (in_array($dia, $diasValidos, true) && $lim > 0) {
            $limitesPorDia[$dia] = $lim;
        }
    }
} catch (Throwable $e) {
    // mantém fallback 6
}

try {
    $pdo->beginTransaction();

    // Sempre limpa o conjunto atual do professor/ano/turno
    $stmtDel = $pdo->prepare("
        DELETE FROM professor_restricoes
        WHERE id_professor = ?
          AND id_ano_letivo = ?
          AND id_turno = ?
    ");
    $stmtDel->execute([$id_professor, $id_ano_letivo, $id_turno]);

    // Se não veio nada (ou veio {}), só limpa e pronto
    if (empty($restricoes)) {
        $pdo->commit();
        respond('success', 'Restrições limpas com sucesso.');
    }

    $stmtIns = $pdo->prepare("
        INSERT INTO professor_restricoes (id_professor, id_ano_letivo, id_turno, dia_semana, numero_aula)
        VALUES (?, ?, ?, ?, ?)
    ");

    $inseridos = 0;

    foreach ($restricoes as $diaSemana => $aulasArr) {
        if (!in_array($diaSemana, $diasValidos, true)) {
            continue;
        }
        if (!is_array($aulasArr)) {
            continue;
        }

        // Normaliza: remove duplicados e converte para int
        $aulasUniq = [];
        foreach ($aulasArr as $aulaNum) {
            $n = (int)$aulaNum;
            if ($n <= 0) continue;

            // valida limite do dia (turno_dias)
            $maxDia = $limitesPorDia[$diaSemana] ?? 6;
            if ($n > $maxDia) continue;

            $aulasUniq[$n] = true;
        }

        foreach (array_keys($aulasUniq) as $n) {
            $stmtIns->execute([$id_professor, $id_ano_letivo, $id_turno, $diaSemana, $n]);
            $inseridos++;
        }
    }

    $pdo->commit();
    respond('success', 'Restrições atualizadas com sucesso!', ['inseridos' => $inseridos]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // Evita vazar detalhes SQL pro front; se quiser logar, faça no servidor.
    respond('error', 'Erro ao atualizar restrições.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond('error', 'Erro inesperado ao atualizar restrições.');
}
