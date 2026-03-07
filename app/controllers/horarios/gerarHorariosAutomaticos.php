<?php
// app/controllers/horarios/gerarHorariosAutomaticos.php
// v18.0
// - Gera APENAS para turmas do nível (e opcional turno) informado.
// - Apaga APENAS horários dessas turmas (não apaga outros níveis).
// - Pré-carrega ocupação REAL do banco (outras turmas/níveis) para não “inventar” professor livre.
// - Respeita: restrições, horários fixos, EF espelhada, limite semanal, conflitos de professor.
// - Agora aceita distribuição menos “bonita” em etapas de emergência para fechar mais grades.
// - Quando não fecha 100%, retorna status=partial com diagnóstico estruturado.

declare(strict_types=1);

require_once __DIR__ . '/../../../configs/init.php';

header('Content-Type: application/json; charset=utf-8');

$id_ano_letivo   = isset($_POST['id_ano_letivo']) ? (int)$_POST['id_ano_letivo'] : 0;
$id_nivel_ensino = isset($_POST['id_nivel_ensino']) ? (int)$_POST['id_nivel_ensino'] : 0;
$id_turno_filtro = isset($_POST['id_turno']) ? (int)$_POST['id_turno'] : 0; // 0 = todos

$fixarDisciplina    = isset($_POST['fixar_disciplina']) && $_POST['fixar_disciplina'] === 'true';
$disciplinaFixaId   = isset($_POST['disciplina_fixa_id']) ? (int)$_POST['disciplina_fixa_id'] : 0;
$disciplinaFixaDia  = isset($_POST['disciplina_fixa_dia']) ? trim((string)$_POST['disciplina_fixa_dia']) : '';
$disciplinaFixaAula = isset($_POST['disciplina_fixa_aula']) ? (int)$_POST['disciplina_fixa_aula'] : 0;

$max_backtracks   = isset($_POST['max_backtracks']) ? (int)$_POST['max_backtracks'] : 450000;
$max_chain_depth  = isset($_POST['max_chain_depth']) ? (int)$_POST['max_chain_depth'] : 12;
$max_global_depth = isset($_POST['max_global_depth']) ? (int)$_POST['max_global_depth'] : 9;

$ativarEF = isset($_POST['ativar_ef_espelhada']) ? ($_POST['ativar_ef_espelhada'] === 'true') : true;

if ($id_ano_letivo <= 0 || $id_nivel_ensino <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Parâmetros inválidos.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($id_turno_filtro < 0) {
    $id_turno_filtro = 0;
}

// --------- permissão ----------
$idUsuario = (int)($_SESSION['id_usuario'] ?? 0);
$stmtCheck = $pdo->prepare("
    SELECT 1
    FROM usuario_niveis
    WHERE id_usuario = :u
      AND id_nivel_ensino = :n
    LIMIT 1
");
$stmtCheck->execute([
    ':u' => $idUsuario,
    ':n' => $id_nivel_ensino
]);

if (!$stmtCheck->fetchColumn()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Você não tem acesso a este Nível de Ensino.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --------- log ----------
$logArquivo = __DIR__ . '/../../../horarios_debug_completo.log';
file_put_contents($logArquivo, '');

function logMsg(string $msg): void {
    global $logArquivo;
    file_put_contents($logArquivo, date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
}

/* =========================
   Helpers base
========================= */

function isSlotFixo($turmaId, $dia, $aula, &$fixos): bool {
    return isset($fixos[(int)$turmaId][(string)$dia][(int)$aula]);
}

function temRestricao($pid, $turno, $dia, $aula, &$profs): bool {
    return isset($profs[(int)$pid]['restricoes'][(int)$turno][(string)$dia][(int)$aula]) ||
           isset($profs[(int)$pid]['restricoes'][0][(string)$dia][(int)$aula]);
}

function profDisponivel($pid, $turno, $dia, $aula, &$profs, &$ocup): bool {
    if (temRestricao((int)$pid, (int)$turno, (string)$dia, (int)$aula, $profs)) {
        return false;
    }
    if (isset($ocup[(int)$turno][(string)$dia][(int)$aula][(int)$pid])) {
        return false;
    }
    return true;
}

function profDisponivelIgnorandoProprioSlot($pid, $turno, $dia, $aula, &$profs, &$ocup, $ignoreTurmaId, $ignoreTurno, $ignoreDia, $ignoreAula): bool {
    if (temRestricao((int)$pid, (int)$turno, (string)$dia, (int)$aula, $profs)) {
        return false;
    }

    if (!isset($ocup[(int)$turno][(string)$dia][(int)$aula][(int)$pid])) {
        return true;
    }

    $tid = (int)($ocup[(int)$turno][(string)$dia][(int)$aula][(int)$pid] ?? 0);
    if (
        $tid === (int)$ignoreTurmaId &&
        (int)$turno === (int)$ignoreTurno &&
        (string)$dia === (string)$ignoreDia &&
        (int)$aula === (int)$ignoreAula
    ) {
        return true;
    }

    return false;
}

function qtdDiasTurno($turno, &$turnoDias): int {
    return isset($turnoDias[(int)$turno]) ? count($turnoDias[(int)$turno]) : 5;
}

function maxPorDiaDisc($aulasSemana, $numDias): int {
    $base = (int)ceil($aulasSemana / max(1, $numDias));
    return max(1, min(2, $base));
}

function aulasNoDia(&$agenda, $did, $dia): int {
    $c = 0;
    foreach ($agenda[(string)$dia] ?? [] as $s) {
        if ($s !== null && (int)$s['d'] === (int)$did) {
            $c++;
        }
    }
    return $c;
}

function temConsecutiva(&$agenda, $did, $dia, $aula, $max): bool {
    if (
        $aula > 1 &&
        isset($agenda[(string)$dia][$aula - 1]) &&
        $agenda[(string)$dia][$aula - 1] !== null &&
        (int)$agenda[(string)$dia][$aula - 1]['d'] === (int)$did
    ) {
        return true;
    }

    if (
        $aula < $max &&
        isset($agenda[(string)$dia][$aula + 1]) &&
        $agenda[(string)$dia][$aula + 1] !== null &&
        (int)$agenda[(string)$dia][$aula + 1]['d'] === (int)$did
    ) {
        return true;
    }

    return false;
}

function alocar(&$turma, $did, $pid, $dia, $aula, &$profs, &$ocup): void {
    $turma['agenda'][(string)$dia][(int)$aula] = [
        'd' => (int)$did,
        'p' => (int)$pid
    ];
    $turma['demanda'][(int)$did]--;
    $profs[(int)$pid]['uso']++;
    $ocup[(int)$turma['turno_id']][(string)$dia][(int)$aula][(int)$pid] = (int)$turma['id'];
}

function desalocar(&$turma, $dia, $aula, &$profs, &$ocup): void {
    $s = $turma['agenda'][(string)$dia][(int)$aula] ?? null;
    if (!$s) {
        return;
    }

    $did = (int)$s['d'];
    $pid = (int)$s['p'];

    $turma['agenda'][(string)$dia][(int)$aula] = null;
    $turma['demanda'][(int)$did]++;
    $profs[(int)$pid]['uso']--;
    unset($ocup[(int)$turma['turno_id']][(string)$dia][(int)$aula][(int)$pid]);
}

// remanejamento interno (não mexe em demanda)
function setSlot(&$turma, $dia, $aula, $newSlot, &$profs, &$ocup): void {
    $turno = (int)$turma['turno_id'];
    $dia = (string)$dia;
    $aula = (int)$aula;

    $old = $turma['agenda'][$dia][$aula] ?? null;
    if ($old !== null) {
        $pidOld = (int)$old['p'];
        $profs[$pidOld]['uso']--;
        unset($ocup[$turno][$dia][$aula][$pidOld]);
    }

    $turma['agenda'][$dia][$aula] = $newSlot;

    if ($newSlot !== null) {
        $pidNew = (int)$newSlot['p'];
        $profs[$pidNew]['uso']++;
        $ocup[$turno][$dia][$aula][$pidNew] = (int)$turma['id'];
    }
}

function listarVaziosDaTurma(&$turma, &$turnoDias, &$fixos): array {
    $turno = (int)$turma['turno_id'];
    $out = [];

    foreach ($turnoDias[$turno] as $dia => $n) {
        for ($a = 1; $a <= (int)$n; $a++) {
            if (isSlotFixo((int)$turma['id'], (string)$dia, (int)$a, $fixos)) {
                continue;
            }
            if (($turma['agenda'][(string)$dia][(int)$a] ?? null) === null) {
                $out[] = [(string)$dia, (int)$a];
            }
        }
    }

    return $out;
}

function listarSlotsDaTurmaNaoFixos(&$turma, &$turnoDias, &$fixos): array {
    $turno = (int)$turma['turno_id'];
    $out = [];

    foreach ($turnoDias[$turno] as $dia => $n) {
        for ($a = 1; $a <= (int)$n; $a++) {
            if (isSlotFixo((int)$turma['id'], (string)$dia, (int)$a, $fixos)) {
                continue;
            }
            $out[] = [(string)$dia, (int)$a];
        }
    }

    return $out;
}

function horariosLivresProf($pid, $turno, &$turnoDias, &$profs, &$ocup): int {
    $c = 0;

    foreach ($turnoDias[(int)$turno] as $dia => $n) {
        for ($a = 1; $a <= (int)$n; $a++) {
            if (profDisponivel((int)$pid, (int)$turno, (string)$dia, (int)$a, $profs, $ocup)) {
                $c++;
            }
        }
    }

    return $c;
}

function ordenarPendenciasPorCriticidade(&$turma, $turno, $serie, &$turnoDias, &$profs, &$ocup, &$disciplinasSerie): array {
    $pend = [];

    foreach ($turma['demanda'] as $did => $dem) {
        $did = (int)$did;
        $dem = (int)$dem;

        if ($dem <= 0) {
            continue;
        }

        if (!empty($disciplinasSerie[$serie][$did]['is_ef'])) {
            continue;
        }

        $listaProfs = $turma['profs_disc'][$did] ?? [];
        $nProfs = count($listaProfs);

        $minLivres = 999999;
        $sumLivres = 0;

        if ($nProfs === 0) {
            $minLivres = 0;
            $sumLivres = 0;
        } else {
            foreach ($listaProfs as $pid) {
                $liv = horariosLivresProf((int)$pid, (int)$turno, $turnoDias, $profs, $ocup);
                $minLivres = min($minLivres, $liv);
                $sumLivres += $liv;
            }
        }

        $pend[] = [
            'did' => $did,
            'dem' => $dem,
            'nprofs' => $nProfs,
            'minLivres' => $minLivres,
            'sumLivres' => $sumLivres
        ];
    }

    usort($pend, function ($a, $b) {
        if ($a['nprofs'] !== $b['nprofs']) {
            return $a['nprofs'] <=> $b['nprofs'];
        }
        if ($a['minLivres'] !== $b['minLivres']) {
            return $a['minLivres'] <=> $b['minLivres'];
        }
        if ($a['sumLivres'] !== $b['sumLivres']) {
            return $a['sumLivres'] <=> $b['sumLivres'];
        }
        return $b['dem'] <=> $a['dem'];
    });

    return $pend;
}

function contarSlotsViaveisParaDisciplina(&$turma, $did, &$turnoDias, &$profs, &$ocup, &$fixos): int {
    $turno = (int)$turma['turno_id'];
    $profsCand = $turma['profs_disc'][(int)$did] ?? [];
    if (empty($profsCand)) {
        return 0;
    }

    $vazios = listarVaziosDaTurma($turma, $turnoDias, $fixos);
    $ok = 0;

    foreach ($vazios as $pair) {
        $dia = (string)$pair[0];
        $aula = (int)$pair[1];

        foreach ($profsCand as $pid) {
            if (profDisponivel((int)$pid, $turno, $dia, $aula, $profs, $ocup)) {
                $ok++;
                break;
            }
        }
    }

    return $ok;
}

/* =========================
   Cadeia global: libera professor em (turno,dia,aula)
========================= */

function liberarProfessorNoSlotGlobal(
    $pid,
    $turno,
    $dia,
    $aula,
    &$turmas,
    &$turnoDias,
    &$profs,
    &$ocup,
    &$fixos,
    &$disciplinasSerie,
    $depth = 4,
    &$visited = null
): bool {
    $pid = (int)$pid;
    $turno = (int)$turno;
    $dia = (string)$dia;
    $aula = (int)$aula;

    if ($depth <= 0) {
        return false;
    }

    if ($visited === null) {
        $visited = [];
    }

    $key = $pid . '|' . $turno . '|' . $dia . '|' . $aula;
    if (isset($visited[$key])) {
        return false;
    }
    $visited[$key] = true;

    if (!isset($ocup[$turno][$dia][$aula][$pid])) {
        return true;
    }

    $turmaOcupId = (int)$ocup[$turno][$dia][$aula][$pid];
    if (!isset($turmas[$turmaOcupId])) {
        return false;
    }

    $turmaOcup = &$turmas[$turmaOcupId];
    if (isSlotFixo((int)$turmaOcup['id'], $dia, $aula, $fixos)) {
        return false;
    }

    $slot = $turmaOcup['agenda'][$dia][$aula] ?? null;
    if ($slot === null) {
        unset($ocup[$turno][$dia][$aula][$pid]);
        return true;
    }

    $did = (int)$slot['d'];
    $serie = (int)$turmaOcup['serie_id'];
    if (!empty($disciplinasSerie[$serie][$did]['is_ef'])) {
        return false;
    }

    $vazios = listarVaziosDaTurma($turmaOcup, $turnoDias, $fixos);
    shuffle($vazios);

    foreach ($vazios as $pair) {
        $dia2 = (string)$pair[0];
        $a2 = (int)$pair[1];

        if (temRestricao($pid, $turno, $dia2, $a2, $profs)) {
            continue;
        }

        if (isset($ocup[$turno][$dia2][$a2][$pid])) {
            $ok = liberarProfessorNoSlotGlobal(
                $pid,
                $turno,
                $dia2,
                $a2,
                $turmas,
                $turnoDias,
                $profs,
                $ocup,
                $fixos,
                $disciplinasSerie,
                $depth - 1,
                $visited
            );
            if (!$ok) {
                continue;
            }
            if (isset($ocup[$turno][$dia2][$a2][$pid])) {
                continue;
            }
        }

        setSlot($turmaOcup, $dia2, $a2, $slot, $profs, $ocup);
        setSlot($turmaOcup, $dia, $aula, null, $profs, $ocup);
        return true;
    }

    return false;
}

/* =========================
   Cadeia genérica: abrir slot na turma
========================= */

function abrirSlotPorCadeiaGenerica(
    &$turma,
    $diaAlvo,
    $aulaAlvo,
    &$turnoDias,
    &$profs,
    &$ocup,
    &$fixos,
    &$disciplinasSerie,
    $depth,
    &$turmas,
    $globalDepth,
    &$visitedPos = null
): bool {
    $turno = (int)$turma['turno_id'];
    $serie = (int)$turma['serie_id'];
    $turmaId = (int)$turma['id'];

    $diaAlvo = (string)$diaAlvo;
    $aulaAlvo = (int)$aulaAlvo;

    if ($depth <= 0) {
        return false;
    }

    if (isSlotFixo($turmaId, $diaAlvo, $aulaAlvo, $fixos)) {
        return false;
    }

    if ($visitedPos === null) {
        $visitedPos = [];
    }

    $kpos = $diaAlvo . '|' . $aulaAlvo;
    if (isset($visitedPos[$kpos])) {
        return false;
    }
    $visitedPos[$kpos] = true;

    $slotA = $turma['agenda'][$diaAlvo][$aulaAlvo] ?? null;
    if ($slotA === null) {
        return true;
    }

    $didA = (int)$slotA['d'];
    $pidA = (int)$slotA['p'];

    if (!empty($disciplinasSerie[$serie][$didA]['is_ef'])) {
        return false;
    }

    $vazios = listarVaziosDaTurma($turma, $turnoDias, $fixos);
    shuffle($vazios);

    foreach ($vazios as $pair) {
        $diaV = (string)$pair[0];
        $aV = (int)$pair[1];

        if ($diaV === $diaAlvo && $aV === $aulaAlvo) {
            continue;
        }

        if (!profDisponivelIgnorandoProprioSlot($pidA, $turno, $diaV, $aV, $profs, $ocup, $turmaId, $turno, $diaAlvo, $aulaAlvo)) {
            continue;
        }

        if (isset($ocup[$turno][$diaV][$aV][$pidA])) {
            $visited = [];
            $ok = liberarProfessorNoSlotGlobal(
                $pidA,
                $turno,
                $diaV,
                $aV,
                $turmas,
                $turnoDias,
                $profs,
                $ocup,
                $fixos,
                $disciplinasSerie,
                $globalDepth,
                $visited
            );
            if (!$ok) {
                continue;
            }
            if (isset($ocup[$turno][$diaV][$aV][$pidA])) {
                continue;
            }
        }

        setSlot($turma, $diaV, $aV, $slotA, $profs, $ocup);
        setSlot($turma, $diaAlvo, $aulaAlvo, null, $profs, $ocup);
        return true;
    }

    $dias = array_keys($turnoDias[$turno]);
    shuffle($dias);

    foreach ($dias as $diaB) {
        $diaB = (string)$diaB;
        $maxB = (int)$turnoDias[$turno][$diaB];
        $aulasB = range(1, $maxB);
        shuffle($aulasB);

        foreach ($aulasB as $aB) {
            $aB = (int)$aB;

            if ($diaB === $diaAlvo && $aB === $aulaAlvo) {
                continue;
            }
            if (isSlotFixo($turmaId, $diaB, $aB, $fixos)) {
                continue;
            }

            $slotB = $turma['agenda'][$diaB][$aB] ?? null;
            if ($slotB === null) {
                continue;
            }

            $didB = (int)$slotB['d'];
            if (!empty($disciplinasSerie[$serie][$didB]['is_ef'])) {
                continue;
            }

            if (!profDisponivelIgnorandoProprioSlot($pidA, $turno, $diaB, $aB, $profs, $ocup, $turmaId, $turno, $diaAlvo, $aulaAlvo)) {
                continue;
            }

            $ok = abrirSlotPorCadeiaGenerica(
                $turma,
                $diaB,
                $aB,
                $turnoDias,
                $profs,
                $ocup,
                $fixos,
                $disciplinasSerie,
                $depth - 1,
                $turmas,
                $globalDepth,
                $visitedPos
            );

            if (!$ok) {
                continue;
            }
            if (($turma['agenda'][$diaB][$aB] ?? null) !== null) {
                continue;
            }

            setSlot($turma, $diaB, $aB, $slotA, $profs, $ocup);
            setSlot($turma, $diaAlvo, $aulaAlvo, null, $profs, $ocup);
            return true;
        }
    }

    return false;
}

/* =========================
   Seleção de professor
========================= */

function ordenarProfsPorDisponibilidade($turma, $did, $turno, &$turnoDias, &$profs, &$ocup): array {
    $profsCand = $turma['profs_disc'][(int)$did] ?? [];
    if (empty($profsCand)) {
        return [];
    }

    $profsCand = array_values(array_unique(array_map('intval', $profsCand)));

    usort($profsCand, function ($a, $b) use ($turno, &$turnoDias, &$profs, &$ocup) {
        return horariosLivresProf((int)$a, (int)$turno, $turnoDias, $profs, $ocup)
            <=>
               horariosLivresProf((int)$b, (int)$turno, $turnoDias, $profs, $ocup);
    });

    return $profsCand;
}

/* =========================
   Finalizador
========================= */

function alocarUmaUnidadeComFinalizador(
    &$turma,
    $did,
    &$turmas,
    &$turnoDias,
    &$profs,
    &$ocup,
    &$fixos,
    &$disciplinasSerie,
    $limiteDia,
    $chainDepth,
    $globalDepth,
    $permitirConsecutiva = false
): bool {
    $turno = (int)$turma['turno_id'];

    $profsCand = ordenarProfsPorDisponibilidade($turma, (int)$did, $turno, $turnoDias, $profs, $ocup);
    if (empty($profsCand)) {
        return false;
    }

    // (A) vazios
    $vazios = listarVaziosDaTurma($turma, $turnoDias, $fixos);
    shuffle($vazios);

    foreach ($vazios as $pair) {
        $dia = (string)$pair[0];
        $aula = (int)$pair[1];

        $max = (int)$turnoDias[$turno][$dia];
        if (aulasNoDia($turma['agenda'], (int)$did, $dia) >= (int)$limiteDia) {
            continue;
        }
        if (!$permitirConsecutiva && temConsecutiva($turma['agenda'], (int)$did, $dia, $aula, $max)) {
            continue;
        }

        foreach ($profsCand as $pid) {
            $pid = (int)$pid;
            if (profDisponivel($pid, $turno, $dia, $aula, $profs, $ocup)) {
                alocar($turma, (int)$did, $pid, $dia, $aula, $profs, $ocup);
                return true;
            }
        }
    }

    // (B) slot ensinável (remanejamento)
    $slots = listarSlotsDaTurmaNaoFixos($turma, $turnoDias, $fixos);
    shuffle($slots);

    foreach ($profsCand as $pid) {
        $pid = (int)$pid;

        foreach ($slots as $pair) {
            $dia = (string)$pair[0];
            $aula = (int)$pair[1];

            if (temRestricao($pid, $turno, $dia, $aula, $profs)) {
                continue;
            }

            $max = (int)$turnoDias[$turno][$dia];
            if (aulasNoDia($turma['agenda'], (int)$did, $dia) >= (int)$limiteDia) {
                continue;
            }
            if (!$permitirConsecutiva && temConsecutiva($turma['agenda'], (int)$did, $dia, $aula, $max)) {
                continue;
            }

            if (!profDisponivel($pid, $turno, $dia, $aula, $profs, $ocup)) {
                $visited = [];
                $ok = liberarProfessorNoSlotGlobal(
                    $pid,
                    $turno,
                    $dia,
                    $aula,
                    $turmas,
                    $turnoDias,
                    $profs,
                    $ocup,
                    $fixos,
                    $disciplinasSerie,
                    $globalDepth,
                    $visited
                );
                if (!$ok) {
                    continue;
                }
                if (!profDisponivel($pid, $turno, $dia, $aula, $profs, $ocup)) {
                    continue;
                }
            }

            $visitedPos = [];
            $okOpen = abrirSlotPorCadeiaGenerica(
                $turma,
                $dia,
                $aula,
                $turnoDias,
                $profs,
                $ocup,
                $fixos,
                $disciplinasSerie,
                $chainDepth,
                $turmas,
                $globalDepth,
                $visitedPos
            );

            if (!$okOpen) {
                continue;
            }
            if (($turma['agenda'][$dia][$aula] ?? null) !== null) {
                continue;
            }

            alocar($turma, (int)$did, $pid, $dia, $aula, $profs, $ocup);
            return true;
        }
    }

    return false;
}

/* =========================
   Ultra flexível
========================= */

function alocarUmaUnidadeUltraFlexivel(
    &$turma,
    $did,
    &$turmas,
    &$turnoDias,
    &$profs,
    &$ocup,
    &$fixos,
    &$disciplinasSerie,
    $globalDepth
): bool {
    $turno = (int)$turma['turno_id'];
    $serie = (int)$turma['serie_id'];
    $turmaId = (int)$turma['id'];

    $profsCand = ordenarProfsPorDisponibilidade($turma, (int)$did, $turno, $turnoDias, $profs, $ocup);
    if (empty($profsCand)) {
        return false;
    }

    // 1) tenta qualquer vazio válido, sem se preocupar com estética
    $vazios = listarVaziosDaTurma($turma, $turnoDias, $fixos);
    shuffle($vazios);

    foreach ($vazios as $pair) {
        $dia = (string)$pair[0];
        $aula = (int)$pair[1];

        foreach ($profsCand as $pid) {
            $pid = (int)$pid;

            if (profDisponivel($pid, $turno, $dia, $aula, $profs, $ocup)) {
                alocar($turma, (int)$did, $pid, $dia, $aula, $profs, $ocup);
                return true;
            }
        }
    }

    // 2) tenta abrir qualquer slot da turma, remanejando o que estiver lá
    $slots = listarSlotsDaTurmaNaoFixos($turma, $turnoDias, $fixos);
    shuffle($slots);

    foreach ($slots as $pair) {
        $dia = (string)$pair[0];
        $aula = (int)$pair[1];

        foreach ($profsCand as $pid) {
            $pid = (int)$pid;

            if (temRestricao($pid, $turno, $dia, $aula, $profs)) {
                continue;
            }

            if (!profDisponivel($pid, $turno, $dia, $aula, $profs, $ocup)) {
                $visited = [];
                $okFree = liberarProfessorNoSlotGlobal(
                    $pid,
                    $turno,
                    $dia,
                    $aula,
                    $turmas,
                    $turnoDias,
                    $profs,
                    $ocup,
                    $fixos,
                    $disciplinasSerie,
                    $globalDepth,
                    $visited
                );

                if (!$okFree) {
                    continue;
                }

                if (!profDisponivel($pid, $turno, $dia, $aula, $profs, $ocup)) {
                    continue;
                }
            }

            $visitedPos = [];
            $okOpen = abrirSlotPorCadeiaGenerica(
                $turma,
                $dia,
                $aula,
                $turnoDias,
                $profs,
                $ocup,
                $fixos,
                $disciplinasSerie,
                8,
                $turmas,
                $globalDepth,
                $visitedPos
            );

            if (!$okOpen) {
                continue;
            }

            if (($turma['agenda'][$dia][$aula] ?? null) !== null) {
                continue;
            }

            alocar($turma, (int)$did, $pid, $dia, $aula, $profs, $ocup);
            return true;
        }
    }

    // 3) varredura direta, sem shuffle, para não perder encaixe bobo
    foreach ($turnoDias[$turno] as $dia => $n) {
        for ($aula = 1; $aula <= (int)$n; $aula++) {
            if (isSlotFixo($turmaId, (string)$dia, (int)$aula, $fixos)) {
                continue;
            }

            if (($turma['agenda'][(string)$dia][(int)$aula] ?? null) === null) {
                foreach ($profsCand as $pid) {
                    $pid = (int)$pid;
                    if (profDisponivel($pid, $turno, (string)$dia, (int)$aula, $profs, $ocup)) {
                        alocar($turma, (int)$did, $pid, (string)$dia, (int)$aula, $profs, $ocup);
                        return true;
                    }
                }
            }
        }
    }

    return false;
}

/* =========================
   Helpers de diagnóstico
========================= */

function contarRestricoesProfessor($pid, &$professores): int {
    $total = 0;
    $restr = $professores[(int)$pid]['restricoes'] ?? [];

    foreach ($restr as $turno => $dias) {
        foreach ($dias as $dia => $aulas) {
            $total += count($aulas);
        }
    }

    return $total;
}

function diagnosticoProfessores(&$professores, &$turnoDias, &$ocupacao): array {
    $saida = [];

    foreach ($professores as $pid => $prof) {
        $restricoes = contarRestricoesProfessor((int)$pid, $professores);

        $livresPorTurno = [];
        foreach ($turnoDias as $turno => $dias) {
            $livres = 0;
            foreach ($dias as $dia => $maxAulas) {
                for ($a = 1; $a <= (int)$maxAulas; $a++) {
                    if (
                        !temRestricao((int)$pid, (int)$turno, (string)$dia, (int)$a, $professores) &&
                        !isset($ocupacao[(int)$turno][(string)$dia][(int)$a][(int)$pid])
                    ) {
                        $livres++;
                    }
                }
            }
            $livresPorTurno[(int)$turno] = $livres;
        }

        $saida[] = [
            'id_professor' => (int)$pid,
            'nome' => (string)$prof['nome'],
            'qtd_restricoes' => (int)$restricoes,
            'uso' => (int)($prof['uso'] ?? 0),
            'limite' => (int)($prof['limite'] ?? 0),
            'livres_por_turno' => $livresPorTurno
        ];
    }

    usort($saida, function ($a, $b) {
        if ($a['qtd_restricoes'] !== $b['qtd_restricoes']) {
            return $b['qtd_restricoes'] <=> $a['qtd_restricoes'];
        }

        $aLivres = array_sum($a['livres_por_turno']);
        $bLivres = array_sum($b['livres_por_turno']);

        return $aLivres <=> $bLivres;
    });

    return $saida;
}

function diagnosticoTurmasPendentes(&$turmas, &$disciplinasNomes): array {
    $saida = [];

    foreach ($turmas as $turma) {
        $faltas = [];

        foreach ($turma['demanda'] as $did => $dem) {
            if ((int)$dem > 0) {
                $faltas[] = [
                    'id_disciplina' => (int)$did,
                    'disciplina' => (string)($disciplinasNomes[(int)$did] ?? $did),
                    'faltando' => (int)$dem
                ];
            }
        }

        if (!empty($faltas)) {
            $saida[] = [
                'id_turma' => (int)$turma['id'],
                'turma' => (string)$turma['nome'],
                'serie' => (string)$turma['nome_serie'],
                'turno_id' => (int)$turma['turno_id'],
                'faltas' => $faltas
            ];
        }
    }

    return $saida;
}

function montarTextoImpressaoRestricoes($idAno, $idNivel, $idTurnoFiltro, $totalVazios, $faltasTurmas, $professoresCriticos): string {
    $linhas = [];

    $linhas[] = "RELATÓRIO DE RESTRIÇÕES - GERAÇÃO AUTOMÁTICA";
    $linhas[] = "Ano letivo: " . $idAno;
    $linhas[] = "Nível de ensino: " . $idNivel;
    $linhas[] = "Turno filtro: " . ($idTurnoFiltro > 0 ? $idTurnoFiltro : 'TODOS');
    $linhas[] = str_repeat("=", 70);
    $linhas[] = "";
    $linhas[] = "RESULTADO";
    $linhas[] = "Todas as possibilidades de geração foram executadas, porém não foi possível completar todos os horários respeitando as restrições cadastradas.";
    $linhas[] = "Total de vazios encontrados: " . $totalVazios;
    $linhas[] = "";

    if (!empty($faltasTurmas)) {
        $linhas[] = "TURMAS COM PENDÊNCIAS";
        foreach ($faltasTurmas as $item) {
            $linhas[] = "- {$item['serie']} / {$item['turma']} (turno {$item['turno_id']})";
            foreach ($item['faltas'] as $f) {
                $linhas[] = "    • {$f['disciplina']}: faltando {$f['faltando']}";
            }
        }
        $linhas[] = "";
    }

    $linhas[] = "PROFESSORES COM MAIORES RESTRIÇÕES";
    $top = array_slice($professoresCriticos, 0, 20);
    foreach ($top as $p) {
        $linhas[] = "- {$p['nome']} | restrições: {$p['qtd_restricoes']} | uso: {$p['uso']} | livres: " . array_sum($p['livres_por_turno']);
    }

    return implode("\n", $linhas);
}

/* =========================
   Main
========================= */

try {
    logMsg("========================================");
    logMsg("GERAÇÃO DE HORÁRIOS - v18.0");
    logMsg("========================================");
    logMsg(
        "Parâmetros: Ano={$id_ano_letivo}, Nível={$id_nivel_ensino}, TurnoFiltro=" . ($id_turno_filtro ?: 'TODOS') .
        " | BT={$max_backtracks} | chain={$max_chain_depth} | global={$max_global_depth}"
    );

    $pdo->beginTransaction();

    // ---------------- FASE 1: turmas do filtro ----------------
    logMsg("\n>>> FASE 1: Carregando turmas");

    $sql = "
        SELECT
            t.id_turma,
            t.id_serie,
            t.id_turno,
            t.nome_turma,
            s.nome_serie,
            s.id_nivel_ensino
        FROM turma t
        JOIN serie s ON t.id_serie = s.id_serie
        WHERE t.id_ano_letivo = ?
          AND s.id_nivel_ensino = ?
    ";
    $params = [$id_ano_letivo, $id_nivel_ensino];

    if ($id_turno_filtro > 0) {
        $sql .= " AND t.id_turno = ? ";
        $params[] = $id_turno_filtro;
    }

    $sql .= " ORDER BY s.nome_serie, t.nome_turma ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $turmasRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$turmasRaw) {
        $pdo->rollBack();
        echo json_encode([
            'status' => 'error',
            'message' => 'Nenhuma turma encontrada para os filtros informados.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    logMsg("✅ " . count($turmasRaw) . " turmas");

    $ids = array_map('intval', array_column($turmasRaw, 'id_turma'));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $pdo->prepare("DELETE FROM horario WHERE id_turma IN ($placeholders)")->execute($ids);
    logMsg("✅ Horários antigos deletados (apenas turmas filtradas)");

    // ---------------- FASE 2: Professores e restrições ----------------
    logMsg("\n>>> FASE 2: Professores e restrições");

    $professores = [];
    $stmt = $pdo->query("SELECT id_professor, nome_exibicao, limite_aulas_fixa_semana FROM professor");
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $professores[(int)$r['id_professor']] = [
            'nome' => (string)$r['nome_exibicao'],
            'limite' => (int)$r['limite_aulas_fixa_semana'],
            'uso' => 0,
            'restricoes' => []
        ];
    }
    logMsg("✅ " . count($professores) . " professores");

    $stmt = $pdo->prepare("
        SELECT id_professor, dia_semana, numero_aula, id_turno
        FROM professor_restricoes
        WHERE id_ano_letivo = ?
    ");
    $stmt->execute([$id_ano_letivo]);

    $qtdRestr = 0;
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pid = (int)$r['id_professor'];
        if (!isset($professores[$pid])) {
            continue;
        }

        $turnoR = (int)$r['id_turno'];
        $diaR   = (string)$r['dia_semana'];
        $aulaR  = (int)$r['numero_aula'];

        if ($aulaR <= 0 || $diaR === '') {
            continue;
        }

        $professores[$pid]['restricoes'][$turnoR][$diaR][$aulaR] = true;
        $qtdRestr++;
    }
    logMsg("✅ Restrições carregadas: $qtdRestr");

    $disciplinasNomes = [];
    $stmt = $pdo->query("SELECT id_disciplina, nome_disciplina FROM disciplina");
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $disciplinasNomes[(int)$r['id_disciplina']] = (string)$r['nome_disciplina'];
    }

    // ---------------- FASE 3: Estruturando dados ----------------
    logMsg("\n>>> FASE 3: Estruturando dados");

    $turnoDias = [];
    $disciplinasSerie = [];
    $turmas = [];

    foreach ($turmasRaw as $t) {
        $tid     = (int)$t['id_turma'];
        $turnoId = (int)$t['id_turno'];
        $serieId = (int)$t['id_serie'];

        if (!isset($turnoDias[$turnoId])) {
            $turnoDias[$turnoId] = [];
            $st = $pdo->prepare("
                SELECT dia_semana, aulas_no_dia
                FROM turno_dias
                WHERE id_turno = ?
                  AND aulas_no_dia > 0
            ");
            $st->execute([$turnoId]);
            while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
                $turnoDias[$turnoId][(string)$r['dia_semana']] = (int)$r['aulas_no_dia'];
            }

            if (empty($turnoDias[$turnoId])) {
                throw new Exception("Turno $turnoId não possui dias/aulas em turno_dias.");
            }
        }

        if (!isset($disciplinasSerie[$serieId])) {
            $disciplinasSerie[$serieId] = [];
            $st = $pdo->prepare("
                SELECT sd.id_disciplina, sd.aulas_semana, d.nome_disciplina
                FROM serie_disciplinas sd
                JOIN disciplina d ON sd.id_disciplina = d.id_disciplina
                WHERE sd.id_serie = ?
            ");
            $st->execute([$serieId]);
            while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
                $nomeDisc = (string)$r['nome_disciplina'];
                $disciplinasSerie[$serieId][(int)$r['id_disciplina']] = [
                    'nome'  => $nomeDisc,
                    'aulas' => (int)$r['aulas_semana'],
                    'is_ef' => (
                        stripos($nomeDisc, 'Educação Física') !== false ||
                        stripos($nomeDisc, 'Ed. Física') !== false
                    )
                ];
            }
        }

        $turmas[$tid] = [
            'id' => $tid,
            'nome' => (string)$t['nome_turma'],
            'serie_id' => $serieId,
            'turno_id' => $turnoId,
            'nome_serie' => (string)$t['nome_serie'],
            'nivel' => (int)$t['id_nivel_ensino'],
            'agenda' => [],
            'profs_disc' => [],
            'demanda' => []
        ];

        foreach ($turnoDias[$turnoId] as $dia => $n) {
            for ($a = 1; $a <= (int)$n; $a++) {
                $turmas[$tid]['agenda'][(string)$dia][(int)$a] = null;
            }
        }

        foreach ($disciplinasSerie[$serieId] as $did => $d) {
            $turmas[$tid]['demanda'][(int)$did] = (int)$d['aulas'];
        }

        $st = $pdo->prepare("
            SELECT id_disciplina, id_professor
            FROM professor_disciplinas_turmas
            WHERE id_turma = ?
        ");
        $st->execute([$tid]);
        while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
            $did = (int)$r['id_disciplina'];
            $pid = (int)$r['id_professor'];
            if ($did > 0 && $pid > 0) {
                $turmas[$tid]['profs_disc'][$did][] = $pid;
            }
        }

        foreach ($turmas[$tid]['profs_disc'] as $did => $lista) {
            $turmas[$tid]['profs_disc'][$did] = array_values(array_unique(array_map('intval', $lista)));
        }
    }

    // ---------------- FASE 3.5: Pré-carregar ocupação + fixos ----------------
    logMsg("\n>>> FASE 3.5: Pré-carregando ocupação existente + horários fixos");

    $ocupacao = [];
    $fixos = [];

    $sqlOcc = "
        SELECT h.id_professor, h.id_turno, h.dia_semana, h.numero_aula, h.id_turma
        FROM horario h
        JOIN turma t ON t.id_turma = h.id_turma
        WHERE h.id_ano_letivo = ?
          AND h.id_professor IS NOT NULL
          AND h.id_turma NOT IN ($placeholders)
    ";
    $paramsOcc = [$id_ano_letivo];
    foreach ($ids as $v) {
        $paramsOcc[] = (int)$v;
    }

    if ($id_turno_filtro > 0) {
        $sqlOcc .= " AND h.id_turno = ? ";
        $paramsOcc[] = $id_turno_filtro;
    }

    $stOcc = $pdo->prepare($sqlOcc);
    $stOcc->execute($paramsOcc);

    $occCount = 0;
    while ($r = $stOcc->fetch(PDO::FETCH_ASSOC)) {
        $pid  = (int)$r['id_professor'];
        $turn = (int)$r['id_turno'];
        $dia  = (string)$r['dia_semana'];
        $aula = (int)$r['numero_aula'];
        $tidO = (int)$r['id_turma'];

        if ($pid <= 0 || $turn <= 0 || $dia === '' || $aula <= 0) {
            continue;
        }

        $ocupacao[$turn][$dia][$aula][$pid] = $tidO;
        if (isset($professores[$pid])) {
            $professores[$pid]['uso']++;
        }
        $occCount++;
    }
    logMsg("✅ Ocupações pré-carregadas: $occCount");

    $stFix = $pdo->prepare("
        SELECT hf.id_turma, hf.dia_semana, hf.numero_aula, hf.id_professor, hf.id_disciplina
        FROM horario_fixos hf
        WHERE hf.id_turma IN ($placeholders)
    ");
    $stFix->execute($ids);

    $fixCount = 0;
    while ($r = $stFix->fetch(PDO::FETCH_ASSOC)) {
        $tid = (int)$r['id_turma'];
        if (!isset($turmas[$tid])) {
            continue;
        }

        $dia  = (string)$r['dia_semana'];
        $aula = (int)$r['numero_aula'];
        $pid  = (int)$r['id_professor'];
        $did  = (int)$r['id_disciplina'];

        if ($dia === '' || $aula <= 0 || $pid <= 0 || $did <= 0) {
            continue;
        }

        $turno = (int)$turmas[$tid]['turno_id'];
        if (!isset($turnoDias[$turno][$dia])) {
            continue;
        }
        if ($aula > (int)$turnoDias[$turno][$dia]) {
            continue;
        }

        $fixos[$tid][$dia][$aula] = true;

        if (($turmas[$tid]['agenda'][$dia][$aula] ?? null) === null) {
            if (isset($turmas[$tid]['demanda'][$did]) && (int)$turmas[$tid]['demanda'][$did] > 0) {
                $turmas[$tid]['demanda'][$did]--;
            }

            $turmas[$tid]['agenda'][$dia][$aula] = [
                'd' => $did,
                'p' => $pid
            ];
            $ocupacao[$turno][$dia][$aula][$pid] = $tid;

            if (isset($professores[$pid])) {
                $professores[$pid]['uso']++;
            }
            $fixCount++;
        }
    }
    logMsg("✅ Fixos carregados e aplicados: $fixCount");

    // ---------------- FASE 4: Fixação de disciplina extra ----------------
    if ($fixarDisciplina && $disciplinaFixaId > 0 && $disciplinaFixaDia && $disciplinaFixaAula > 0) {
        logMsg("\n>>> FASE 4: Fixação de disciplina");
        $nomeFixa = $disciplinasNomes[$disciplinaFixaId] ?? $disciplinaFixaId;
        logMsg("📌 Fixando: $nomeFixa em $disciplinaFixaDia {$disciplinaFixaAula}ª");

        foreach ($turmas as &$turma) {
            $turno = (int)$turma['turno_id'];

            if (!isset($turnoDias[$turno][$disciplinaFixaDia])) {
                continue;
            }
            if ($disciplinaFixaAula > (int)$turnoDias[$turno][$disciplinaFixaDia]) {
                continue;
            }
            if (!isset($turma['demanda'][$disciplinaFixaId]) || (int)$turma['demanda'][$disciplinaFixaId] <= 0) {
                continue;
            }
            if (($turma['agenda'][$disciplinaFixaDia][$disciplinaFixaAula] ?? null) !== null) {
                continue;
            }
            if (isSlotFixo((int)$turma['id'], $disciplinaFixaDia, (int)$disciplinaFixaAula, $fixos)) {
                continue;
            }

            $profsFixa = ordenarProfsPorDisponibilidade($turma, (int)$disciplinaFixaId, $turno, $turnoDias, $professores, $ocupacao);
            if (empty($profsFixa)) {
                continue;
            }

            foreach ($profsFixa as $pid) {
                $pid = (int)$pid;
                if (profDisponivel($pid, $turno, (string)$disciplinaFixaDia, (int)$disciplinaFixaAula, $professores, $ocupacao)) {
                    alocar($turma, (int)$disciplinaFixaId, (int)$pid, (string)$disciplinaFixaDia, (int)$disciplinaFixaAula, $professores, $ocupacao);
                    $fixos[(int)$turma['id']][(string)$disciplinaFixaDia][(int)$disciplinaFixaAula] = true;
                    logMsg("   ✅ {$turma['nome']}: {$professores[$pid]['nome']}");
                    break;
                }
            }
        }
        unset($turma);
    }

    // ---------------- FASE 5: EF espelhada ----------------
    logMsg("\n>>> FASE 5: Ed. Física espelhada");

    if ($ativarEF && (int)$id_nivel_ensino === 3) {
        $grupos = [];

        foreach ($turmas as $tid => &$t) {
            $k = (int)$t['serie_id'] . '_' . (int)$t['turno_id'];
            if (!isset($grupos[$k])) {
                $grupos[$k] = [
                    'turmas' => [],
                    'serie_id' => (int)$t['serie_id'],
                    'turno_id' => (int)$t['turno_id'],
                    'nome' => (string)$t['nome_serie']
                ];
            }
            $grupos[$k]['turmas'][(int)$tid] = &$turmas[(int)$tid];
        }
        unset($t);

        foreach ($grupos as $g) {
            if (count($g['turmas']) < 2) {
                continue;
            }

            $turno = (int)$g['turno_id'];
            $serie = (int)$g['serie_id'];

            $efId = null;
            foreach ($disciplinasSerie[$serie] as $did => $d) {
                if (!empty($d['is_ef'])) {
                    $efId = (int)$did;
                    break;
                }
            }
            if (!$efId) {
                continue;
            }

            $aulasEF = (int)$disciplinasSerie[$serie][$efId]['aulas'];
            if ($aulasEF <= 0) {
                continue;
            }

            logMsg("📌 {$g['nome']}: Ed. Física - $aulasEF aula(s)");

            $diasDisponiveis = array_keys($turnoDias[$turno]);
            shuffle($diasDisponiveis);

            $diasJaUsados = [];
            $alocadas = 0;

            foreach ($diasDisponiveis as $dia) {
                if ($alocadas >= $aulasEF) {
                    break;
                }
                if (isset($diasJaUsados[$dia])) {
                    continue;
                }

                $maxAulasDia = (int)$turnoDias[$turno][$dia];
                $aulasCandidatas = range(1, $maxAulasDia);
                shuffle($aulasCandidatas);

                foreach ($aulasCandidatas as $aula) {
                    $todasLivres = true;

                    foreach ($g['turmas'] as &$turmaChk) {
                        if (isSlotFixo((int)$turmaChk['id'], (string)$dia, (int)$aula, $fixos)) {
                            $todasLivres = false;
                            break;
                        }
                        if (($turmaChk['agenda'][(string)$dia][(int)$aula] ?? null) !== null) {
                            $todasLivres = false;
                            break;
                        }
                        if ((int)($turmaChk['demanda'][$efId] ?? 0) <= 0) {
                            $todasLivres = false;
                            break;
                        }
                    }
                    unset($turmaChk);

                    if (!$todasLivres) {
                        continue;
                    }

                    $pares = [];
                    $ok = true;

                    foreach ($g['turmas'] as $tidTurma => &$turmaEF) {
                        $profsEF = $turmaEF['profs_disc'][$efId] ?? [];
                        if (empty($profsEF)) {
                            $ok = false;
                            break;
                        }

                        shuffle($profsEF);
                        $pidEscolhido = null;

                        foreach ($profsEF as $pid) {
                            $pid = (int)$pid;
                            if (!profDisponivel($pid, $turno, (string)$dia, (int)$aula, $professores, $ocupacao)) {
                                continue;
                            }
                            $pidEscolhido = $pid;
                            break;
                        }

                        if (!$pidEscolhido) {
                            $ok = false;
                            break;
                        }

                        $pares[(int)$tidTurma] = (int)$pidEscolhido;
                    }
                    unset($turmaEF);

                    if (!$ok) {
                        continue;
                    }

                    foreach ($g['turmas'] as $tidTurma => &$turmaEF) {
                        alocar($turmaEF, (int)$efId, (int)$pares[(int)$tidTurma], (string)$dia, (int)$aula, $professores, $ocupacao);
                    }
                    unset($turmaEF);

                    $diasJaUsados[$dia] = true;
                    $alocadas++;
                    logMsg("   ✅ EF em $dia {$aula}ª");
                    break;
                }
            }

            if ($alocadas < $aulasEF) {
                logMsg("   ⚠️ Não foi possível alocar EF completa (faltam " . ($aulasEF - $alocadas) . ")");
            }
        }
    } else {
        logMsg("   (EF espelhada desativada ou nível != 3)");
    }

    // ---------------- ORDENAR TURMAS POR DIFICULDADE GLOBAL ----------------
    $turmaIds = array_keys($turmas);
    $turmaScore = [];

    foreach ($turmaIds as $tid) {
        $t = $turmas[$tid];
        $turno = (int)$t['turno_id'];
        $serie = (int)$t['serie_id'];

        $score = 0;

        foreach ($t['demanda'] as $did => $dem) {
            $dem = (int)$dem;
            $did = (int)$did;

            if ($dem <= 0) {
                continue;
            }
            if (!empty($disciplinasSerie[$serie][$did]['is_ef'])) {
                continue;
            }

            $profs = $t['profs_disc'][$did] ?? [];
            $nProfs = count($profs);

            if ($nProfs === 0) {
                $score += 1000000;
                continue;
            }

            $minLivres = PHP_INT_MAX;
            $sumLivres = 0;

            foreach ($profs as $pid) {
                $liv = horariosLivresProf((int)$pid, $turno, $turnoDias, $professores, $ocupacao);
                $minLivres = min($minLivres, $liv);
                $sumLivres += $liv;
            }

            $score += (int)(($dem * 200) / max(1, $sumLivres));
            $score += (int)(50 / max(1, $nProfs));
            $score += (int)(100 / max(1, $minLivres));
        }

        $turmaScore[$tid] = $score;
    }

    usort($turmaIds, function ($a, $b) use ($turmaScore) {
        return $turmaScore[$b] <=> $turmaScore[$a];
    });

    logMsg("\n>>> ORDEM DE PROCESSAMENTO (mais difícil primeiro)");
    foreach ($turmaIds as $tid) {
        logMsg("   • {$turmas[$tid]['nome_serie']} {$turmas[$tid]['nome']} | turno={$turmas[$tid]['turno_id']} | score={$turmaScore[$tid]}");
    }

    // ---------------- FASE 6: CSP + greedy + finalizador ----------------
    logMsg("\n>>> FASE 6: Alocação por turma");

    $totalBacktracks = 0;

    foreach ($turmaIds as $tidLoop) {
        $turma = &$turmas[$tidLoop];
        $turno = (int)$turma['turno_id'];
        $serie = (int)$turma['serie_id'];

        logMsg("\n📊 {$turma['nome_serie']} {$turma['nome']} (turno=$turno)");

        // expandir demanda em tarefas
        $tarefas = [];
        foreach ($turma['demanda'] as $did => $dem) {
            $did = (int)$did;
            $dem = (int)$dem;

            if ($dem <= 0) {
                continue;
            }
            if (!empty($disciplinasSerie[$serie][$did]['is_ef'])) {
                continue;
            }

            $profs = $turma['profs_disc'][$did] ?? [];
            $horariosTotal = 0;
            foreach ($profs as $pid) {
                $horariosTotal += horariosLivresProf((int)$pid, $turno, $turnoDias, $professores, $ocupacao);
            }

            for ($i = 0; $i < $dem; $i++) {
                $tarefas[] = [
                    'did' => $did,
                    'dificuldade' => $horariosTotal > 0 ? ($dem / $horariosTotal) : 999
                ];
            }
        }

        usort($tarefas, function ($a, $b) {
            return $b['dificuldade'] <=> $a['dificuldade'];
        });

        $bt = 0;

        $resolver = function ($idx) use (
            &$resolver,
            &$turma,
            &$tarefas,
            &$turnoDias,
            &$professores,
            &$ocupacao,
            &$disciplinasSerie,
            &$fixos,
            $turno,
            $serie,
            &$bt,
            $max_backtracks
        ) {
            if ($idx >= count($tarefas)) {
                return true;
            }

            if ($bt >= $max_backtracks) {
                return false;
            }

            $did = (int)$tarefas[$idx]['did'];

            if ((int)($turma['demanda'][$did] ?? 0) <= 0) {
                return $resolver($idx + 1);
            }

            $profs = ordenarProfsPorDisponibilidade($turma, (int)$did, $turno, $turnoDias, $professores, $ocupacao);
            if (empty($profs)) {
                return false;
            }

            $numDias = qtdDiasTurno($turno, $turnoDias);
            $aulasSemana = (int)($disciplinasSerie[$serie][$did]['aulas'] ?? 1);
            $limiteDia = maxPorDiaDisc($aulasSemana, $numDias);

            $dias = array_keys($turnoDias[$turno]);
            shuffle($dias);

            foreach ($dias as $dia) {
                $dia = (string)$dia;
                $max = (int)$turnoDias[$turno][$dia];

                if (aulasNoDia($turma['agenda'], $did, $dia) >= $limiteDia) {
                    continue;
                }

                $aulas = range(1, $max);
                shuffle($aulas);

                foreach ($aulas as $aula) {
                    $aula = (int)$aula;

                    if (isSlotFixo((int)$turma['id'], $dia, $aula, $fixos)) {
                        continue;
                    }
                    if (($turma['agenda'][$dia][$aula] ?? null) !== null) {
                        continue;
                    }

                    if ($bt < (int)($max_backtracks * 0.70) && temConsecutiva($turma['agenda'], $did, $dia, $aula, $max)) {
                        continue;
                    }

                    $profsTry = $profs;
                    shuffle($profsTry);

                    foreach ($profsTry as $pid) {
                        $pid = (int)$pid;
                        if (!profDisponivel($pid, $turno, $dia, $aula, $professores, $ocupacao)) {
                            continue;
                        }

                        alocar($turma, $did, $pid, $dia, $aula, $professores, $ocupacao);

                        if ($resolver($idx + 1)) {
                            return true;
                        }

                        desalocar($turma, $dia, $aula, $professores, $ocupacao);
                        $bt++;

                        if ($bt >= $max_backtracks) {
                            return false;
                        }
                    }
                }
            }

            return false;
        };

        $sucesso = $resolver(0);
        $totalBacktracks += $bt;

        if ($sucesso) {
            logMsg("   ✅ Completo (BT: $bt)");
            continue;
        }

        logMsg("   ⚠️ Backtrack limit ($bt). Entrando no Greedy crítico + Finalizador...");

        $tentouAlgo = true;
        while ($tentouAlgo) {
            $tentouAlgo = false;

            $pend = ordenarPendenciasPorCriticidade($turma, $turno, $serie, $turnoDias, $professores, $ocupacao, $disciplinasSerie);
            if (empty($pend)) {
                break;
            }

            foreach ($pend as $item) {
                $did = (int)$item['did'];
                if ((int)($turma['demanda'][$did] ?? 0) <= 0) {
                    continue;
                }

                $numDias = qtdDiasTurno($turno, $turnoDias);
                $aulasSemana = (int)($disciplinasSerie[$serie][$did]['aulas'] ?? 1);

                $limiteDia = maxPorDiaDisc($aulasSemana, $numDias);
                $maxLimiteDia = 4;

                while ((int)($turma['demanda'][$did] ?? 0) > 0) {
                    // Etapa 1: tenta bonito
                    $ok = alocarUmaUnidadeComFinalizador(
                        $turma,
                        $did,
                        $turmas,
                        $turnoDias,
                        $professores,
                        $ocupacao,
                        $fixos,
                        $disciplinasSerie,
                        $limiteDia,
                        $max_chain_depth,
                        $max_global_depth,
                        false
                    );

                    if ($ok) {
                        $nome = $disciplinasNomes[$did] ?? $did;
                        logMsg("   ✅ Alocado: $nome (restam {$turma['demanda'][$did]})");
                        $tentouAlgo = true;
                        continue;
                    }

                    // Etapa 2: relaxa limite por dia
                    if ($limiteDia < $maxLimiteDia) {
                        $limiteDia++;
                        logMsg("   ⚠️ Relaxando limite por dia para $limiteDia (disciplina $did)...");
                        continue;
                    }

                    // Etapa 3: aceita consecutiva
                    $ok2 = alocarUmaUnidadeComFinalizador(
                        $turma,
                        $did,
                        $turmas,
                        $turnoDias,
                        $professores,
                        $ocupacao,
                        $fixos,
                        $disciplinasSerie,
                        $limiteDia,
                        max(6, $max_chain_depth + 2),
                        $max_global_depth,
                        true
                    );

                    if ($ok2) {
                        $nome = $disciplinasNomes[$did] ?? $did;
                        logMsg("   ✅ (emergência) Alocado: $nome (restam {$turma['demanda'][$did]}) [consecutiva permitida]");
                        $tentouAlgo = true;
                        continue;
                    }

                    // Etapa 4: ultra flexível
                    $ok3 = alocarUmaUnidadeUltraFlexivel(
                        $turma,
                        $did,
                        $turmas,
                        $turnoDias,
                        $professores,
                        $ocupacao,
                        $fixos,
                        $disciplinasSerie,
                        max($max_global_depth, 8)
                    );

                    if ($ok3) {
                        $nome = $disciplinasNomes[$did] ?? $did;
                        logMsg("   ✅ (ultra flexível) Alocado: $nome (restam {$turma['demanda'][$did]})");
                        $tentouAlgo = true;
                        continue;
                    }

                    // Falhou tudo
                    $nome = $disciplinasNomes[$did] ?? $did;
                    $slotsOk = contarSlotsViaveisParaDisciplina($turma, $did, $turnoDias, $professores, $ocupacao, $fixos);
                    $slotsVazios = count(listarVaziosDaTurma($turma, $turnoDias, $fixos));
                    logMsg("   ❌ Impossível: $nome (faltam {$turma['demanda'][$did]}) | vazios={$slotsVazios} | slots_viaveis={$slotsOk}");
                    break;
                }
            }
        }

        $faltam = [];
        foreach ($turma['demanda'] as $did => $dem) {
            if ((int)$dem > 0) {
                $faltam[] = ($disciplinasNomes[(int)$did] ?? $did) . " (" . (int)$dem . ")";
            }
        }

        if (!empty($faltam)) {
            logMsg("   📋 Faltam: " . implode(', ', $faltam));
        } else {
            logMsg("   ✅ Fechado no finalizador.");
        }
    }
    unset($turma);

    // ---------------- FASE 7: validação ----------------
    logMsg("\n>>> FASE 7: Validação");

    $totalAulas = 0;
    $totalVazios = 0;
    $consecutivas = 0;

    foreach ($turmas as $turma) {
        foreach ($turma['agenda'] as $dia => $aulas) {
            $ant = null;

            foreach ($aulas as $s) {
                if ($s === null) {
                    $totalVazios++;
                    $ant = null;
                } else {
                    $totalAulas++;
                    if ($ant !== null && (int)$ant === (int)$s['d']) {
                        $consecutivas++;
                    }
                    $ant = (int)$s['d'];
                }
            }
        }
    }

    logMsg("📊 RESULTADO:");
    logMsg("   • Aulas: $totalAulas");
    logMsg("   • Vazios: $totalVazios");
    logMsg("   • Consecutivas: $consecutivas");
    logMsg("   • Backtracks total: $totalBacktracks");

    $faltasGlobais = [];
    foreach ($turmas as $turma) {
        foreach ($turma['demanda'] as $did => $dem) {
            if ((int)$dem > 0 && (int)$dem < 999999) {
                $faltasGlobais[] = "{$turma['nome_serie']} {$turma['nome']}: " . ($disciplinasNomes[(int)$did] ?? $did) . " (" . (int)$dem . ")";
            }
        }
    }

    if ($totalVazios > 0 || !empty($faltasGlobais)) {
        $faltasTurmas = diagnosticoTurmasPendentes($turmas, $disciplinasNomes);
        $professoresCriticos = diagnosticoProfessores($professores, $turnoDias, $ocupacao);
        $textoImpressao = montarTextoImpressaoRestricoes(
            $id_ano_letivo,
            $id_nivel_ensino,
            $id_turno_filtro,
            $totalVazios,
            $faltasTurmas,
            $professoresCriticos
        );

        $pdo->rollBack();

        echo json_encode([
            'status' => 'partial',
            'completed' => false,
            'message' => 'Todas as possibilidades de geração foram executadas, porém devido às restrições cadastradas não foi possível completar todos os horários.',
            'show_print_button' => true,
            'show_cancel_button' => true,
            'stats' => [
                'totalAulas' => $totalAulas,
                'totalVazios' => $totalVazios,
                'consecutivas' => $consecutivas,
                'totalBacktracks' => $totalBacktracks
            ],
            'diagnostico' => [
                'faltas_turmas' => $faltasTurmas,
                'professores_criticos' => $professoresCriticos,
                'faltas_globais' => $faltasGlobais,
                'texto_impressao' => $textoImpressao
            ]
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // ---------------- FASE 8: salvar ----------------
    logMsg("\n>>> FASE 8: Salvando");

    $stmtIns = $pdo->prepare("
        INSERT INTO horario (
            id_ano_letivo,
            id_turma,
            id_turno,
            dia_semana,
            numero_aula,
            id_professor,
            id_disciplina
        )
        VALUES (?,?,?,?,?,?,?)
    ");

    $inseridas = 0;
    foreach ($turmas as $turma) {
        $turno = (int)$turma['turno_id'];

        foreach ($turma['agenda'] as $dia => $aulas) {
            foreach ($aulas as $aula => $s) {
                if ($s !== null) {
                    $stmtIns->execute([
                        (int)$id_ano_letivo,
                        (int)$turma['id'],
                        (int)$turno,
                        (string)$dia,
                        (int)$aula,
                        (int)$s['p'],
                        (int)$s['d']
                    ]);
                    $inseridas++;
                }
            }
        }
    }

    $pdo->commit();
    logMsg("✅ Salvo: $inseridas aulas");

    echo json_encode([
        'status' => 'success',
        'completed' => true,
        'message' => 'Horários gerados com sucesso. Todos os horários foram completados respeitando as restrições.',
        'stats' => [
            'totalAulas' => $totalAulas,
            'totalVazios' => $totalVazios,
            'consecutivas' => $consecutivas,
            'totalBacktracks' => $totalBacktracks,
            'inseridas' => $inseridas
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    logMsg("❌ ERRO: " . $e->getMessage());

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>