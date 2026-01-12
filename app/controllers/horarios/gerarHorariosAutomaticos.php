<?php
// app/controllers/horarios/gerarHorariosAutomaticos.php
// v16.5 (FIX: não mexe no que já foi gerado + respeita ocupação REAL do banco + inclui id_turno no INSERT + carrega horario_fixos)
// - Gera APENAS para turmas do nível (e opcional turno) informado.
// - Apaga APENAS horários dessas turmas (não apaga outros níveis).
// - Antes de alocar, CARREGA a ocupação já existente no BD (outros níveis/turmas) para não “inventar” que o professor está livre.
// - Corrige o erro de chave única uq_prof_slot: agora o INSERT inclui id_turno (o seu índice usa id_turno).
// - Mantém: fixação (disciplina fixa), EF espelhada, cadeias, cotas.

require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json; charset=utf-8');

$id_ano_letivo   = isset($_POST['id_ano_letivo']) ? (int)$_POST['id_ano_letivo'] : 0;
$id_nivel_ensino = isset($_POST['id_nivel_ensino']) ? (int)$_POST['id_nivel_ensino'] : 0;
$id_turno_filtro = isset($_POST['id_turno']) ? (int)$_POST['id_turno'] : 0; // 0 = todos

$fixarDisciplina    = isset($_POST['fixar_disciplina']) && $_POST['fixar_disciplina'] === 'true';
$disciplinaFixaId   = isset($_POST['disciplina_fixa_id']) ? (int)$_POST['disciplina_fixa_id'] : 0;
$disciplinaFixaDia  = isset($_POST['disciplina_fixa_dia']) ? trim((string)$_POST['disciplina_fixa_dia']) : '';
$disciplinaFixaAula = isset($_POST['disciplina_fixa_aula']) ? (int)$_POST['disciplina_fixa_aula'] : 0;

$max_backtracks   = isset($_POST['max_backtracks']) ? (int)$_POST['max_backtracks'] : 300000;
$max_chain_depth  = isset($_POST['max_chain_depth']) ? (int)$_POST['max_chain_depth'] : 10;
$max_global_depth = isset($_POST['max_global_depth']) ? (int)$_POST['max_global_depth'] : 7;
$ativarEF         = isset($_POST['ativar_ef_espelhada']) ? ($_POST['ativar_ef_espelhada'] === 'true') : true;

if ($id_ano_letivo <= 0 || $id_nivel_ensino <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Parâmetros inválidos.']);
    exit;
}
if ($id_turno_filtro < 0) $id_turno_filtro = 0;

// --------- permissão ----------
$idUsuario = $_SESSION['id_usuario'] ?? 0;
$stmtCheck = $pdo->prepare("SELECT 1 FROM usuario_niveis WHERE id_usuario = :u AND id_nivel_ensino = :n LIMIT 1");
$stmtCheck->execute([':u' => $idUsuario, ':n' => $id_nivel_ensino]);
if (!$stmtCheck->fetchColumn()) {
    echo json_encode(['status' => 'error', 'message' => 'Você não tem acesso a este Nível de Ensino.']);
    exit;
}

// --------- log ----------
$logArquivo = __DIR__ . '/../../../horarios_debug_completo.log';
file_put_contents($logArquivo, '');

function logMsg($msg) {
    global $logArquivo;
    file_put_contents($logArquivo, date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
}

/* =========================
   Helpers base
========================= */

function isSlotFixo($turmaId, $dia, $aula, &$fixos) {
    return isset($fixos[(int)$turmaId][(string)$dia][(int)$aula]);
}

function temRestricao($pid, $turno, $dia, $aula, &$profs) {
    return isset($profs[(int)$pid]['restricoes'][(int)$turno][(string)$dia][(int)$aula]) ||
           isset($profs[(int)$pid]['restricoes'][0][(string)$dia][(int)$aula]);
}

function profDisponivel($pid, $turno, $dia, $aula, &$profs, &$ocup) {
    if (temRestricao((int)$pid, (int)$turno, (string)$dia, (int)$aula, $profs)) return false;
    if (isset($ocup[(int)$turno][(string)$dia][(int)$aula][(int)$pid])) return false;
    return true;
}

function profDisponivelIgnorandoProprioSlot($pid, $turno, $dia, $aula, &$profs, &$ocup, $ignoreTurmaId, $ignoreTurno, $ignoreDia, $ignoreAula) {
    if (temRestricao((int)$pid, (int)$turno, (string)$dia, (int)$aula, $profs)) return false;

    if (!isset($ocup[(int)$turno][(string)$dia][(int)$aula][(int)$pid])) return true;

    $tid = (int)($ocup[(int)$turno][(string)$dia][(int)$aula][(int)$pid] ?? 0);
    if ($tid === (int)$ignoreTurmaId &&
        (int)$turno === (int)$ignoreTurno &&
        (string)$dia === (string)$ignoreDia &&
        (int)$aula === (int)$ignoreAula) {
        return true;
    }
    return false;
}

function qtdDiasTurno($turno, &$turnoDias) {
    return isset($turnoDias[(int)$turno]) ? count($turnoDias[(int)$turno]) : 5;
}

function maxPorDiaDisc($aulasSemana, $numDias) {
    $base = (int)ceil($aulasSemana / max(1, $numDias));
    return max(1, min(2, $base));
}

function aulasNoDia(&$agenda, $did, $dia) {
    $c = 0;
    foreach ($agenda[(string)$dia] ?? [] as $s) {
        if ($s !== null && (int)$s['d'] === (int)$did) $c++;
    }
    return $c;
}

function temConsecutiva(&$agenda, $did, $dia, $aula, $max) {
    if ($aula > 1 && isset($agenda[(string)$dia][$aula-1]) && $agenda[(string)$dia][$aula-1] !== null && (int)$agenda[(string)$dia][$aula-1]['d'] === (int)$did) return true;
    if ($aula < $max && isset($agenda[(string)$dia][$aula+1]) && $agenda[(string)$dia][$aula+1] !== null && (int)$agenda[(string)$dia][$aula+1]['d'] === (int)$did) return true;
    return false;
}

function _didNoAgendaComOverride(&$agenda, $dia, $aula, &$override) {
    $k = (string)$dia . '|' . (int)$aula;
    if (array_key_exists($k, $override)) return $override[$k]; // pode ser null
    $s = $agenda[(string)$dia][(int)$aula] ?? null;
    return $s ? (int)$s['d'] : null;
}

function violaConsecutivaAoColocarComOverride(&$agenda, $dia, $aula, $didNew, $max, &$override) {
    $dia = (string)$dia; $aula = (int)$aula; $didNew = (int)$didNew; $max = (int)$max;

    $left  = ($aula > 1)    ? _didNoAgendaComOverride($agenda, $dia, $aula - 1, $override) : null;
    $right = ($aula < $max) ? _didNoAgendaComOverride($agenda, $dia, $aula + 1, $override) : null;

    if ($left !== null  && (int)$left  === $didNew) return true;
    if ($right !== null && (int)$right === $didNew) return true;
    return false;
}


function ensureQuotaBucket(&$turma, $did, $pid) {
    if (!isset($turma['quota'][$did])) $turma['quota'][$did] = [];
    if (!isset($turma['quota'][$did][$pid])) $turma['quota'][$did][$pid] = 0;
}

function quotaRestante($turma, $did, $pid) {
    if (!isset($turma['quota'][$did])) return null; // sem cotas => ilimitado
    if (!array_key_exists($pid, $turma['quota'][$did])) return 0;
    return (int)$turma['quota'][$did][$pid];
}

function decrementarQuotaSeHouver(&$turma, $did, $pid) {
    if (!isset($turma['quota'][$did])) return;
    ensureQuotaBucket($turma, $did, $pid);
    $turma['quota'][$did][$pid] = max(0, (int)$turma['quota'][$did][$pid] - 1);
}

function incrementarQuotaSeHouver(&$turma, $did, $pid) {
    if (!isset($turma['quota'][$did])) return;
    ensureQuotaBucket($turma, $did, $pid);
    $turma['quota'][$did][$pid] = (int)$turma['quota'][$did][$pid] + 1;
}

function alocar(&$turma, $did, $pid, $dia, $aula, &$profs, &$ocup) {
    $turma['agenda'][(string)$dia][(int)$aula] = ['d' => (int)$did, 'p' => (int)$pid];
    $turma['demanda'][(int)$did]--;
    decrementarQuotaSeHouver($turma, (int)$did, (int)$pid);
    $profs[(int)$pid]['uso']++;
    $ocup[(int)$turma['turno_id']][(string)$dia][(int)$aula][(int)$pid] = (int)$turma['id'];
}

function desalocar(&$turma, $dia, $aula, &$profs, &$ocup) {
    $s = $turma['agenda'][(string)$dia][(int)$aula] ?? null;
    if (!$s) return;
    $did = (int)$s['d'];
    $pid = (int)$s['p'];
    $turma['agenda'][(string)$dia][(int)$aula] = null;
    $turma['demanda'][(int)$did]++;
    incrementarQuotaSeHouver($turma, (int)$did, (int)$pid);
    $profs[(int)$pid]['uso']--;
    unset($ocup[(int)$turma['turno_id']][(string)$dia][(int)$aula][(int)$pid]);
}

// move slots sem mexer em demanda/quota (é remanejamento interno do mesmo slot)
function setSlot(&$turma, $dia, $aula, $newSlot, &$profs, &$ocup) {
    $turno = (int)$turma['turno_id'];
    $dia = (string)$dia; $aula = (int)$aula;

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

function listarVaziosDaTurma(&$turma, &$turnoDias, &$fixos) {
    $turno = (int)$turma['turno_id'];
    $out = [];
    foreach ($turnoDias[$turno] as $dia => $n) {
        for ($a = 1; $a <= (int)$n; $a++) {
            if (isSlotFixo((int)$turma['id'], (string)$dia, (int)$a, $fixos)) continue;
            if (($turma['agenda'][(string)$dia][(int)$a] ?? null) === null) $out[] = [(string)$dia, (int)$a];
        }
    }
    return $out;
}

// lista TODOS os slots não-fixos (ocupados + vazios)
function listarSlotsDaTurmaNaoFixos(&$turma, &$turnoDias, &$fixos) {
    $turno = (int)$turma['turno_id'];
    $out = [];
    foreach ($turnoDias[$turno] as $dia => $n) {
        for ($a = 1; $a <= (int)$n; $a++) {
            if (isSlotFixo((int)$turma['id'], (string)$dia, (int)$a, $fixos)) continue;
            $out[] = [(string)$dia, (int)$a];
        }
    }
    return $out;
}

function horariosLivresProf($pid, $turno, &$turnoDias, &$profs, &$ocup) {
    $c = 0;
    foreach ($turnoDias[(int)$turno] as $dia => $n) {
        for ($a = 1; $a <= (int)$n; $a++) {
            if (profDisponivel((int)$pid, (int)$turno, (string)$dia, (int)$a, $profs, $ocup)) $c++;
        }
    }
    return $c;
}

function ordenarPendenciasPorCriticidade(&$turma, $turno, $serie, &$turnoDias, &$profs, &$ocup, &$disciplinasSerie) {
    $pend = [];
    foreach ($turma['demanda'] as $did => $dem) {
        $did = (int)$did; $dem = (int)$dem;
        if ($dem <= 0) continue;
        if (!empty($disciplinasSerie[$serie][$did]['is_ef'])) continue;

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

    usort($pend, function($a, $b) {
        if ($a['nprofs'] !== $b['nprofs']) return $a['nprofs'] <=> $b['nprofs'];
        if ($a['minLivres'] !== $b['minLivres']) return $a['minLivres'] <=> $b['minLivres'];
        if ($a['sumLivres'] !== $b['sumLivres']) return $a['sumLivres'] <=> $b['sumLivres'];
        return $b['dem'] <=> $a['dem'];
    });

    return $pend;
}

function contarSlotsViaveisParaDisciplina(&$turma, $did, &$turnoDias, &$profs, &$ocup, &$fixos) {
    $turno = (int)$turma['turno_id'];
    $profsCand = $turma['profs_disc'][(int)$did] ?? [];
    if (empty($profsCand)) return 0;

    $vazios = listarVaziosDaTurma($turma, $turnoDias, $fixos);
    $ok = 0;

    foreach ($vazios as $pair) {
        $dia = (string)$pair[0];
        $aula = (int)$pair[1];

        foreach ($profsCand as $pid) {
            $qr = quotaRestante($turma, (int)$did, (int)$pid);
            if ($qr !== null && $qr <= 0) continue;

            if (profDisponivel((int)$pid, $turno, $dia, $aula, $profs, $ocup)) { $ok++; break; }
        }
    }
    return $ok;
}

/* =========================
   Cadeia global: libera professor em (turno,dia,aula)
========================= */

/*function liberarProfessorNoSlotGlobal(
    $pid, $turno, $dia, $aula,
    &$turmas, &$turnoDias, &$profs, &$ocup, &$fixos, &$disciplinasSerie,
    $depth = 4,
    &$visited = null
) {
    $pid = (int)$pid; $turno = (int)$turno; $dia = (string)$dia; $aula = (int)$aula;
    if ($depth <= 0) return false;

    if ($visited === null) $visited = [];
    $key = $pid . '|' . $turno . '|' . $dia . '|' . $aula;
    if (isset($visited[$key])) return false;
    $visited[$key] = true;

    if (!isset($ocup[$turno][$dia][$aula][$pid])) return true;

    $turmaOcupId = (int)$ocup[$turno][$dia][$aula][$pid];
    if (!isset($turmas[$turmaOcupId])) return false;

    $turmaOcup = &$turmas[$turmaOcupId];
    if (isSlotFixo((int)$turmaOcup['id'], $dia, $aula, $fixos)) return false;

    $slot = $turmaOcup['agenda'][$dia][$aula] ?? null;
    if ($slot === null) { unset($ocup[$turno][$dia][$aula][$pid]); return true; }

    $did = (int)$slot['d'];
    $serie = (int)$turmaOcup['serie_id'];
    if (!empty($disciplinasSerie[$serie][$did]['is_ef'])) return false;

    $vazios = listarVaziosDaTurma($turmaOcup, $turnoDias, $fixos);
    shuffle($vazios);

    foreach ($vazios as $pair) {
        $dia2 = (string)$pair[0];
        $a2 = (int)$pair[1];

        if (temRestricao($pid, $turno, $dia2, $a2, $profs)) continue;

        if (isset($ocup[$turno][$dia2][$a2][$pid])) {
            $ok = liberarProfessorNoSlotGlobal(
                $pid, $turno, $dia2, $a2,
                $turmas, $turnoDias, $profs, $ocup, $fixos, $disciplinasSerie,
                $depth - 1, $visited
            );
            if (!$ok) continue;
            if (isset($ocup[$turno][$dia2][$a2][$pid])) continue;
        }

        setSlot($turmaOcup, $dia2, $a2, $slot, $profs, $ocup);
        setSlot($turmaOcup, $dia, $aula, null, $profs, $ocup);
        return true;
    }

    return false;
}*/

function liberarProfessorNoSlotGlobal(
    $pid, $turno, $dia, $aula,
    &$turmas, &$turnoDias, &$profs, &$ocup, &$fixos, &$disciplinasSerie,
    $depth = 4,
    &$visited = null
) {
    $pid = (int)$pid; $turno = (int)$turno; $dia = (string)$dia; $aula = (int)$aula;
    if ($depth <= 0) return false;

    if ($visited === null) $visited = [];
    $key = $pid . '|' . $turno . '|' . $dia . '|' . $aula;
    if (isset($visited[$key])) return false;
    $visited[$key] = true;

    if (!isset($ocup[$turno][$dia][$aula][$pid])) return true;

    $turmaOcupId = (int)$ocup[$turno][$dia][$aula][$pid];
    if (!isset($turmas[$turmaOcupId])) return false;

    $turmaOcup = &$turmas[$turmaOcupId];
    $serie = (int)$turmaOcup['serie_id'];

    if (isSlotFixo((int)$turmaOcup['id'], $dia, $aula, $fixos)) return false;

    $slotA = $turmaOcup['agenda'][$dia][$aula] ?? null;
    if ($slotA === null) { unset($ocup[$turno][$dia][$aula][$pid]); return true; }

    $didA = (int)$slotA['d'];
    $pidA = (int)$slotA['p'];
    if ($pidA !== $pid) return false;

    if (!empty($disciplinasSerie[$serie][$didA]['is_ef'])) return false;

    /* (1) mover para vazio dentro da turma bloqueadora */
    $vazios = listarVaziosDaTurma($turmaOcup, $turnoDias, $fixos);
    shuffle($vazios);

    foreach ($vazios as $pair) {
        $dia2 = (string)$pair[0];
        $a2   = (int)$pair[1];

        if (temRestricao($pid, $turno, $dia2, $a2, $profs)) continue;

        // NÃO criar consecutiva
        $max2 = (int)$turnoDias[$turno][$dia2];
        $ov = []; // sem override (destino vazio)
        if (violaConsecutivaAoColocarComOverride($turmaOcup['agenda'], $dia2, $a2, $didA, $max2, $ov)) continue;

        if (isset($ocup[$turno][$dia2][$a2][$pid])) {
            $ok = liberarProfessorNoSlotGlobal(
                $pid, $turno, $dia2, $a2,
                $turmas, $turnoDias, $profs, $ocup, $fixos, $disciplinasSerie,
                $depth - 1, $visited
            );
            if (!$ok) continue;
            if (isset($ocup[$turno][$dia2][$a2][$pid])) continue;
        }

        setSlot($turmaOcup, $dia2, $a2, $slotA, $profs, $ocup);
        setSlot($turmaOcup, $dia,  $aula, null,  $profs, $ocup);
        return true;
    }

    /* (2) sem vazios? => swap 1x1 dentro da turma bloqueadora, sem criar consecutiva */
    $slotsAll = listarSlotsDaTurmaNaoFixos($turmaOcup, $turnoDias, $fixos);
    shuffle($slotsAll);

    foreach ($slotsAll as $pair) {
        $diaB = (string)$pair[0];
        $aB   = (int)$pair[1];

        if ($diaB === $dia && $aB === $aula) continue;
        if (isSlotFixo((int)$turmaOcup['id'], $diaB, $aB, $fixos)) continue;

        $slotB = $turmaOcup['agenda'][$diaB][$aB] ?? null;
        if ($slotB === null) continue;

        $didB = (int)$slotB['d'];
        $pidB = (int)$slotB['p'];
        if ($pidB <= 0) continue;

        if (!empty($disciplinasSerie[$serie][$didB]['is_ef'])) continue;

        // pid (A) precisa poder ir para B
        if (temRestricao($pid, $turno, $diaB, $aB, $profs)) continue;
        if (isset($ocup[$turno][$diaB][$aB][$pid])) {
            $ok = liberarProfessorNoSlotGlobal(
                $pid, $turno, $diaB, $aB,
                $turmas, $turnoDias, $profs, $ocup, $fixos, $disciplinasSerie,
                $depth - 1, $visited
            );
            if (!$ok) continue;
            if (isset($ocup[$turno][$diaB][$aB][$pid])) continue;
        }

        // pidB (B) precisa poder ir para A
        if (temRestricao($pidB, $turno, $dia, $aula, $profs)) continue;
        if (isset($ocup[$turno][$dia][$aula][$pidB])) {
            $ok = liberarProfessorNoSlotGlobal(
                $pidB, $turno, $dia, $aula,
                $turmas, $turnoDias, $profs, $ocup, $fixos, $disciplinasSerie,
                $depth - 1, $visited
            );
            if (!$ok) continue;
            if (isset($ocup[$turno][$dia][$aula][$pidB])) continue;
        }

        // checagem final de disponibilidade (ignora o próprio slot)
        if (!profDisponivelIgnorandoProprioSlot($pid,  $turno, $diaB, $aB, $profs, $ocup, $turmaOcupId, $turno, $dia,  $aula)) continue;
        if (!profDisponivelIgnorandoProprioSlot($pidB, $turno, $dia,  $aula, $profs, $ocup, $turmaOcupId, $turno, $diaB, $aB)) continue;

        // NÃO criar consecutivas após o swap
        $ov = [
            $dia . '|' . $aula => $didB,
            $diaB . '|' . $aB  => $didA
        ];

        $maxA = (int)$turnoDias[$turno][$dia];
        $maxB = (int)$turnoDias[$turno][$diaB];

        if (violaConsecutivaAoColocarComOverride($turmaOcup['agenda'], $dia,  $aula, $didB, $maxA, $ov)) continue;
        if (violaConsecutivaAoColocarComOverride($turmaOcup['agenda'], $diaB, $aB,  $didA, $maxB, $ov)) continue;

        setSlot($turmaOcup, $diaB, $aB, $slotA, $profs, $ocup);
        setSlot($turmaOcup, $dia,  $aula, $slotB, $profs, $ocup);
        return true;
    }

    return false;
}


/* =========================
   Cadeia genérica: abrir slot na turma (empurra aula atual para vazio, ou recursão)
========================= */

/*function abrirSlotPorCadeiaGenerica(
    &$turma, $diaAlvo, $aulaAlvo,
    &$turnoDias, &$profs, &$ocup, &$fixos, &$disciplinasSerie,
    $depth,
    &$turmas,
    $globalDepth,
    &$visitedPos = null
) {
    $turno = (int)$turma['turno_id'];
    $serie = (int)$turma['serie_id'];
    $turmaId = (int)$turma['id'];

    $diaAlvo = (string)$diaAlvo; $aulaAlvo = (int)$aulaAlvo;

    if ($depth <= 0) return false;
    if (isSlotFixo($turmaId, $diaAlvo, $aulaAlvo, $fixos)) return false;
    if (!isset($turma['agenda'][$diaAlvo])) return false;
    if (!array_key_exists($aulaAlvo, $turma['agenda'][$diaAlvo])) return false;

    if ($visitedPos === null) $visitedPos = [];
    $kpos = $diaAlvo . '|' . $aulaAlvo;
    if (isset($visitedPos[$kpos])) return false;
    $visitedPos[$kpos] = true;

    $slotA = $turma['agenda'][$diaAlvo][$aulaAlvo];
    if ($slotA === null) return true;

    $didA = (int)$slotA['d'];
    $pidA = (int)$slotA['p'];
    if (!empty($disciplinasSerie[$serie][$didA]['is_ef'])) return false;

    $vazios = listarVaziosDaTurma($turma, $turnoDias, $fixos);
    shuffle($vazios);

    foreach ($vazios as $pair) {
        $diaV = (string)$pair[0];
        $aV = (int)$pair[1];

        if ($diaV === $diaAlvo && $aV === $aulaAlvo) continue;
        if (!profDisponivelIgnorandoProprioSlot($pidA, $turno, $diaV, $aV, $profs, $ocup, $turmaId, $turno, $diaAlvo, $aulaAlvo)) continue;

        if (isset($ocup[$turno][$diaV][$aV][$pidA])) {
            $visited = [];
            $ok = liberarProfessorNoSlotGlobal(
                $pidA, $turno, $diaV, $aV,
                $turmas, $turnoDias, $profs, $ocup, $fixos, $disciplinasSerie,
                $globalDepth, $visited
            );
            if (!$ok) continue;
            if (isset($ocup[$turno][$diaV][$aV][$pidA])) continue;
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

            if ($diaB === $diaAlvo && $aB === $aulaAlvo) continue;
            if (isSlotFixo($turmaId, $diaB, $aB, $fixos)) continue;

            $slotB = $turma['agenda'][$diaB][$aB] ?? null;
            if ($slotB === null) continue;

            $didB = (int)$slotB['d'];
            if (!empty($disciplinasSerie[$serie][$didB]['is_ef'])) continue;

            if (!profDisponivelIgnorandoProprioSlot($pidA, $turno, $diaB, $aB, $profs, $ocup, $turmaId, $turno, $diaAlvo, $aulaAlvo)) continue;

            $ok = abrirSlotPorCadeiaGenerica(
                $turma, $diaB, $aB,
                $turnoDias, $profs, $ocup, $fixos, $disciplinasSerie,
                $depth - 1,
                $turmas,
                $globalDepth,
                $visitedPos
            );

            if (!$ok) continue;
            if (($turma['agenda'][$diaB][$aB] ?? null) !== null) continue;

            setSlot($turma, $diaB, $aB, $slotA, $profs, $ocup);
            setSlot($turma, $diaAlvo, $aulaAlvo, null, $profs, $ocup);
            return true;
        }
    }

    return false;
}*/

function abrirSlotPorCadeiaGenerica(
    &$turma, $diaAlvo, $aulaAlvo,
    &$turnoDias, &$profs, &$ocup, &$fixos, &$disciplinasSerie,
    $depth,
    &$turmas,
    $globalDepth,
    &$visitedPos = null
) {
    $turno = (int)$turma['turno_id'];
    $serie = (int)$turma['serie_id'];
    $turmaId = (int)$turma['id'];

    $diaAlvo = (string)$diaAlvo; $aulaAlvo = (int)$aulaAlvo;

    if ($depth <= 0) return false;
    if (isSlotFixo($turmaId, $diaAlvo, $aulaAlvo, $fixos)) return false;
    if (!isset($turma['agenda'][$diaAlvo])) return false;
    if (!array_key_exists($aulaAlvo, $turma['agenda'][$diaAlvo])) return false;

    if ($visitedPos === null) $visitedPos = [];
    $kpos = $diaAlvo . '|' . $aulaAlvo;
    if (isset($visitedPos[$kpos])) return false;
    $visitedPos[$kpos] = true;

    $slotA = $turma['agenda'][$diaAlvo][$aulaAlvo];
    if ($slotA === null) return true;

    $didA = (int)$slotA['d'];
    $pidA = (int)$slotA['p'];
    if (!empty($disciplinasSerie[$serie][$didA]['is_ef'])) return false;

    // tenta mover slotA para um vazio (sem criar consecutiva)
    $vazios = listarVaziosDaTurma($turma, $turnoDias, $fixos);
    shuffle($vazios);

    foreach ($vazios as $pair) {
        $diaV = (string)$pair[0];
        $aV   = (int)$pair[1];

        if ($diaV === $diaAlvo && $aV === $aulaAlvo) continue;

        if (!profDisponivelIgnorandoProprioSlot($pidA, $turno, $diaV, $aV, $profs, $ocup, $turmaId, $turno, $diaAlvo, $aulaAlvo)) continue;

        // NÃO criar consecutiva do didA no destino
        $maxV = (int)$turnoDias[$turno][$diaV];
        $ov = [];
        if (violaConsecutivaAoColocarComOverride($turma['agenda'], $diaV, $aV, $didA, $maxV, $ov)) continue;

        if (isset($ocup[$turno][$diaV][$aV][$pidA])) {
            $visited = [];
            $ok = liberarProfessorNoSlotGlobal(
                $pidA, $turno, $diaV, $aV,
                $turmas, $turnoDias, $profs, $ocup, $fixos, $disciplinasSerie,
                $globalDepth, $visited
            );
            if (!$ok) continue;
            if (isset($ocup[$turno][$diaV][$aV][$pidA])) continue;
        }

        setSlot($turma, $diaV, $aV, $slotA, $profs, $ocup);
        setSlot($turma, $diaAlvo, $aulaAlvo, null, $profs, $ocup);
        return true;
    }

    // recursão: abre outro slot e empurra
    $dias = array_keys($turnoDias[$turno]);
    shuffle($dias);

    foreach ($dias as $diaB) {
        $diaB = (string)$diaB;
        $maxB = (int)$turnoDias[$turno][$diaB];
        $aulasB = range(1, $maxB);
        shuffle($aulasB);

        foreach ($aulasB as $aB) {
            $aB = (int)$aB;

            if ($diaB === $diaAlvo && $aB === $aulaAlvo) continue;
            if (isSlotFixo($turmaId, $diaB, $aB, $fixos)) continue;

            $slotB = $turma['agenda'][$diaB][$aB] ?? null;
            if ($slotB === null) continue;

            $didB = (int)$slotB['d'];
            if (!empty($disciplinasSerie[$serie][$didB]['is_ef'])) continue;

            if (!profDisponivelIgnorandoProprioSlot($pidA, $turno, $diaB, $aB, $profs, $ocup, $turmaId, $turno, $diaAlvo, $aulaAlvo)) continue;

            $ok = abrirSlotPorCadeiaGenerica(
                $turma, $diaB, $aB,
                $turnoDias, $profs, $ocup, $fixos, $disciplinasSerie,
                $depth - 1,
                $turmas,
                $globalDepth,
                $visitedPos
            );

            if (!$ok) continue;
            if (($turma['agenda'][$diaB][$aB] ?? null) !== null) continue; // tem que ter virado vazio

            // NÃO criar consecutiva do didA no slotB agora vazio
            $ov = [];
            if (violaConsecutivaAoColocarComOverride($turma['agenda'], $diaB, $aB, $didA, $maxB, $ov)) continue;

            setSlot($turma, $diaB, $aB, $slotA, $profs, $ocup);
            setSlot($turma, $diaAlvo, $aulaAlvo, null, $profs, $ocup);
            return true;
        }
    }

    return false;
}


/* =========================
   Seleção de professor respeitando cotas
========================= */

/*function ordenarProfsPorDisponibilidadeEQuota($turma, $did, $turno, &$turnoDias, &$profs, &$ocup) {
    $profsCand = $turma['profs_disc'][(int)$did] ?? [];
    if (empty($profsCand)) return [];

    $temQuota = isset($turma['quota'][(int)$did]);
    if ($temQuota) {
        $filtrados = array_values(array_filter($profsCand, function($pid) use ($turma, $did) {
            $qr = quotaRestante($turma, (int)$did, (int)$pid);
            return $qr !== null && $qr > 0;
        }));
        if (!empty($filtrados)) $profsCand = $filtrados;
    }

    usort($profsCand, function($a, $b) use ($turno, &$turnoDias, &$profs, &$ocup) {
        return horariosLivresProf((int)$a, (int)$turno, $turnoDias, $profs, $ocup)
            <=>
               horariosLivresProf((int)$b, (int)$turno, $turnoDias, $profs, $ocup);
    });

    return $profsCand;
}*/

function ordenarProfsPorDisponibilidadeEQuota($turma, $did, $turno, &$turnoDias, &$profs, &$ocup) {
    $profsCand = $turma['profs_disc'][(int)$did] ?? [];
    if (empty($profsCand)) return [];

    // Se existe quota para essa disciplina, vira HARD:
    // só retorna professores com quota > 0. Se ninguém tiver, retorna vazio.
    if (isset($turma['quota'][(int)$did])) {
        $profsCand = array_values(array_filter($profsCand, function($pid) use ($turma, $did) {
            $qr = quotaRestante($turma, (int)$did, (int)$pid);
            return $qr !== null && (int)$qr > 0;
        }));
        if (empty($profsCand)) return [];
    }

    usort($profsCand, function($a, $b) use ($turno, &$turnoDias, &$profs, &$ocup) {
        return horariosLivresProf((int)$a, (int)$turno, $turnoDias, $profs, $ocup)
            <=>
               horariosLivresProf((int)$b, (int)$turno, $turnoDias, $profs, $ocup);
    });

    return $profsCand;
}


/* =========================
   Finalizador: abre slot ensinável
========================= */

/*function alocarUmaUnidadeComFinalizador(
    &$turma, $did,
    &$turmas, &$turnoDias, &$profs, &$ocup, &$fixos, &$disciplinasSerie,
    $limiteDia,
    $chainDepth,
    $globalDepth,
    $permitirConsecutiva = false
) {
    $turno = (int)$turma['turno_id'];
    $serie = (int)$turma['serie_id'];

    $profsCand = ordenarProfsPorDisponibilidadeEQuota($turma, (int)$did, $turno, $turnoDias, $profs, $ocup);
    if (empty($profsCand)) return false;

    // (A) vazios
    $vazios = listarVaziosDaTurma($turma, $turnoDias, $fixos);
    shuffle($vazios);

    foreach ($vazios as $pair) {
        $dia = (string)$pair[0];
        $aula = (int)$pair[1];

        $max = (int)$turnoDias[$turno][$dia];
        if (aulasNoDia($turma['agenda'], (int)$did, $dia) >= (int)$limiteDia) continue;
        if (!$permitirConsecutiva && temConsecutiva($turma['agenda'], (int)$did, $dia, $aula, $max)) continue;

        foreach ($profsCand as $pid) {
            $pid = (int)$pid;

            $qr = quotaRestante($turma, (int)$did, (int)$pid);
            if ($qr !== null && $qr <= 0) continue;

            if (profDisponivel($pid, $turno, $dia, $aula, $profs, $ocup)) {
                alocar($turma, (int)$did, $pid, $dia, $aula, $profs, $ocup);
                return true;
            }
        }
    }

    // (B) slot ensinável
    $slots = listarSlotsDaTurmaNaoFixos($turma, $turnoDias, $fixos);
    shuffle($slots);

    foreach ($profsCand as $pid) {
        $pid = (int)$pid;

        $qr = quotaRestante($turma, (int)$did, (int)$pid);
        if ($qr !== null && $qr <= 0) continue;

        foreach ($slots as $pair) {
            $dia = (string)$pair[0];
            $aula = (int)$pair[1];

            if (temRestricao($pid, $turno, $dia, $aula, $profs)) continue;

            $max = (int)$turnoDias[$turno][$dia];
            if (aulasNoDia($turma['agenda'], (int)$did, $dia) >= (int)$limiteDia) continue;
            if (!$permitirConsecutiva && temConsecutiva($turma['agenda'], (int)$did, $dia, $aula, $max)) continue;

            if (!profDisponivel($pid, $turno, $dia, $aula, $profs, $ocup)) {
                $visited = [];
                $ok = liberarProfessorNoSlotGlobal(
                    $pid, $turno, $dia, $aula,
                    $turmas, $turnoDias, $profs, $ocup, $fixos, $disciplinasSerie,
                    $globalDepth, $visited
                );
                if (!$ok) continue;
                if (!profDisponivel($pid, $turno, $dia, $aula, $profs, $ocup)) continue;
            }

            $visitedPos = [];
            $okOpen = abrirSlotPorCadeiaGenerica(
                $turma, $dia, $aula,
                $turnoDias, $profs, $ocup, $fixos, $disciplinasSerie,
                $chainDepth,
                $turmas,
                $globalDepth,
                $visitedPos
            );
            if (!$okOpen) continue;
            if (($turma['agenda'][$dia][$aula] ?? null) !== null) continue;

            alocar($turma, (int)$did, $pid, $dia, $aula, $profs, $ocup);
            return true;
        }
    }

    return false;
}*/

function alocarUmaUnidadeComFinalizador(
    &$turma, $did,
    &$turmas, &$turnoDias, &$profs, &$ocup, &$fixos, &$disciplinasSerie,
    $limiteDia,
    $chainDepth,
    $globalDepth,
    $permitirConsecutiva = false
) {
    $turno = (int)$turma['turno_id'];
    $serie = (int)$turma['serie_id'];

    $profsCand = ordenarProfsPorDisponibilidadeEQuota($turma, (int)$did, $turno, $turnoDias, $profs, $ocup);
    if (empty($profsCand)) return false;

    // (A) tentar nos VAZIOS, mas agora tentando liberar professor se estiver ocupado em outra turma
    $vazios = listarVaziosDaTurma($turma, $turnoDias, $fixos);
    shuffle($vazios);

    foreach ($vazios as $pair) {
        $dia  = (string)$pair[0];
        $aula = (int)$pair[1];

        $max = (int)$turnoDias[$turno][$dia];
        if (aulasNoDia($turma['agenda'], (int)$did, $dia) >= (int)$limiteDia) continue;
        if (!$permitirConsecutiva && temConsecutiva($turma['agenda'], (int)$did, $dia, $aula, $max)) continue;

        foreach ($profsCand as $pid) {
            $pid = (int)$pid;

            $qr = quotaRestante($turma, (int)$did, (int)$pid);
            if ($qr !== null && $qr <= 0) continue;

            // se está restrito, nem tenta
            if (temRestricao($pid, $turno, $dia, $aula, $profs)) continue;

            // se está ocupado, tenta liberar globalmente (swap/move dentro da turma onde ele está)
            if (isset($ocup[$turno][$dia][$aula][$pid])) {
                $visited = [];
                $ok = liberarProfessorNoSlotGlobal(
                    $pid, $turno, $dia, $aula,
                    $turmas, $turnoDias, $profs, $ocup, $fixos, $disciplinasSerie,
                    $globalDepth, $visited
                );
                if (!$ok) continue;
            }

            if (!profDisponivel($pid, $turno, $dia, $aula, $profs, $ocup)) continue;

            alocar($turma, (int)$did, $pid, $dia, $aula, $profs, $ocup);
            return true;
        }
    }

    // (B) slot ensinável (ocupado+vazio) - mantém sua lógica atual
    $slots = listarSlotsDaTurmaNaoFixos($turma, $turnoDias, $fixos);
    shuffle($slots);

    foreach ($profsCand as $pid) {
        $pid = (int)$pid;

        $qr = quotaRestante($turma, (int)$did, (int)$pid);
        if ($qr !== null && $qr <= 0) continue;

        foreach ($slots as $pair) {
            $dia  = (string)$pair[0];
            $aula = (int)$pair[1];

            if (temRestricao($pid, $turno, $dia, $aula, $profs)) continue;

            $max = (int)$turnoDias[$turno][$dia];
            if (aulasNoDia($turma['agenda'], (int)$did, $dia) >= (int)$limiteDia) continue;
            if (!$permitirConsecutiva && temConsecutiva($turma['agenda'], (int)$did, $dia, $aula, $max)) continue;

            if (!profDisponivel($pid, $turno, $dia, $aula, $profs, $ocup)) {
                $visited = [];
                $ok = liberarProfessorNoSlotGlobal(
                    $pid, $turno, $dia, $aula,
                    $turmas, $turnoDias, $profs, $ocup, $fixos, $disciplinasSerie,
                    $globalDepth, $visited
                );
                if (!$ok) continue;
                if (!profDisponivel($pid, $turno, $dia, $aula, $profs, $ocup)) continue;
            }

            $visitedPos = [];
            $okOpen = abrirSlotPorCadeiaGenerica(
                $turma, $dia, $aula,
                $turnoDias, $profs, $ocup, $fixos, $disciplinasSerie,
                $chainDepth,
                $turmas,
                $globalDepth,
                $visitedPos
            );
            if (!$okOpen) continue;
            if (($turma['agenda'][$dia][$aula] ?? null) !== null) continue;

            alocar($turma, (int)$did, $pid, $dia, $aula, $profs, $ocup);
            return true;
        }
    }

    return false;
}


/* =========================
   Main
========================= */

try {
    logMsg("========================================");
    logMsg("GERAÇÃO DE HORÁRIOS - v16.5 (NÍVEL sem apagar outros + PRELOAD ocupação + INSERT com id_turno + horario_fixos)");
    logMsg("========================================");
    logMsg("Parâmetros: Ano=$id_ano_letivo, Nível=$id_nivel_ensino, TurnoFiltro=" . ($id_turno_filtro ?: 'TODOS') . " | BT=$max_backtracks | chain=$max_chain_depth | global=$max_global_depth");

    $pdo->beginTransaction();

    // ---------------- FASE 1: turmas do filtro ----------------
    logMsg("\n>>> FASE 1: Carregando turmas");

    $sql = "
        SELECT t.id_turma, t.id_serie, t.id_turno, t.nome_turma, s.nome_serie, s.id_nivel_ensino
        FROM turma t
        JOIN serie s ON t.id_serie = s.id_serie
        WHERE t.id_ano_letivo = ? AND s.id_nivel_ensino = ?
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
        echo json_encode(['status' => 'error', 'message' => 'Nenhuma turma encontrada para os filtros informados.']);
        exit;
    }
    logMsg("✅ " . count($turmasRaw) . " turmas");

    $ids = array_map('intval', array_column($turmasRaw, 'id_turma'));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // apaga APENAS as turmas do filtro (não apaga nada de outro nível)
    $pdo->prepare("DELETE FROM horario WHERE id_turma IN ($placeholders)")->execute($ids);
    logMsg("✅ Horários antigos deletados (apenas turmas filtradas)");

    // ---------------- FASE 2: Professores, restrições, disciplinas ----------------
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
        if (!isset($professores[$pid])) continue;

        $turnoR = (int)$r['id_turno']; // 0 = geral
        $diaR   = (string)$r['dia_semana'];
        $aulaR  = (int)$r['numero_aula'];
        if ($aulaR <= 0 || $diaR === '') continue;

        $professores[$pid]['restricoes'][$turnoR][$diaR][$aulaR] = true;
        $qtdRestr++;
    }
    logMsg("✅ Restrições carregadas: $qtdRestr");

    $disciplinasNomes = [];
    $stmt = $pdo->query("SELECT id_disciplina, nome_disciplina FROM disciplina");
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $disciplinasNomes[(int)$r['id_disciplina']] = (string)$r['nome_disciplina'];
    }

    // ---------------- FASE 2.5: COTAS ----------------
    logMsg("\n>>> FASE 2.5: Carregando cotas (professor_disciplinas_turmas.aulas_semana/prioridade)");

    $cotas = [];
    $stmt = $pdo->prepare("
        SELECT id_turma, id_disciplina, id_professor, aulas_semana, prioridade
        FROM professor_disciplinas_turmas
        WHERE id_turma IN ($placeholders) AND aulas_semana > 0
        ORDER BY prioridade DESC, aulas_semana DESC
    ");
    $stmt->execute($ids);

    $cotasCount = 0;
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tid = (int)$r['id_turma'];
        $did = (int)$r['id_disciplina'];
        $pid = (int)$r['id_professor'];
        $q   = (int)$r['aulas_semana'];
        $prio= (int)($r['prioridade'] ?? 0);
        $cotas[$tid][$did][] = ['pid'=>$pid, 'q'=>$q, 'prio'=>$prio];
        $cotasCount++;
    }
    logMsg("✅ Cotas carregadas: $cotasCount (linhas com aulas_semana > 0)");

    // ---------------- FASE 3: Estruturando dados (turnos, disciplinas por série, turmas) ----------------
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
                WHERE id_turno = ? AND aulas_no_dia > 0
            ");
            $st->execute([$turnoId]);
            while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
                $turnoDias[$turnoId][(string)$r['dia_semana']] = (int)$r['aulas_no_dia'];
            }
            if (empty($turnoDias[$turnoId])) {
                throw new Exception("Turno $turnoId não possui dias/aulas em turno_dias (aulas_no_dia > 0).");
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
                    'is_ef' => (stripos($nomeDisc, 'Educação Física') !== false) || (stripos($nomeDisc, 'Ed. Física') !== false)
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
            'demanda' => [],
            'quota' => []
        ];

        foreach ($turnoDias[$turnoId] as $dia => $n) {
            for ($a = 1; $a <= (int)$n; $a++) $turmas[$tid]['agenda'][(string)$dia][(int)$a] = null;
        }

        foreach ($disciplinasSerie[$serieId] as $did => $d) {
            $turmas[$tid]['demanda'][(int)$did] = (int)$d['aulas'];
        }

        // professores vinculados
        $st = $pdo->prepare("
            SELECT id_disciplina, id_professor
            FROM professor_disciplinas_turmas
            WHERE id_turma = ?
        ");
        $st->execute([$tid]);
        while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
            $did = (int)$r['id_disciplina'];
            $pid = (int)$r['id_professor'];
            $turmas[$tid]['profs_disc'][$did][] = $pid;
        }

        // aplica cotas explícitas
        if (!empty($cotas[$tid])) {
            foreach ($cotas[$tid] as $did => $rows) {
                foreach ($rows as $row) {
                    $pid = (int)$row['pid'];
                    $q   = (int)$row['q'];
                    if ($q > 0) $turmas[$tid]['quota'][(int)$did][(int)$pid] = (int)$q;
                }
            }
        }

        // regra específica 9º ano (mantida)
        $nomeSerie = (string)$turmas[$tid]['nome_serie'];
        if (preg_match('/^\s*9/i', $nomeSerie)) {
            $didMat = 2;
            if (!isset($turmas[$tid]['quota'][$didMat])) {
                $lista = $turmas[$tid]['profs_disc'][$didMat] ?? [];
                $lista = array_values(array_unique(array_map('intval', $lista)));
                if (count($lista) === 2) {
                    $turmas[$tid]['quota'][$didMat][(int)$lista[0]] = 3;
                    $turmas[$tid]['quota'][$didMat][(int)$lista[1]] = 3;
                }
            }
        }
    }

    // ---------------- FASE 3.5: PRELOAD ocupação do BD (OUTROS níveis/turmas) + horario_fixos (deste nível) ----------------
    // IMPORTANTE: isso é o que impede duplicidade e garante que trocar nível não mexe no que já existe.
    $ocupacao = [];
    $fixos = [];

    logMsg("\n>>> FASE 3.5: Pré-carregando ocupação existente (não alterar outros níveis) + horários fixos");

    // (A) Ocupação existente (OUTRAS turmas fora do filtro atual), do mesmo ano e (se houver) mesmo turno
    // Ajuste conforme seu schema: aqui consideramos que horario tem id_turno (por causa da uq_prof_slot).
    $sqlOcc = "
        SELECT h.id_professor, h.id_turno, h.dia_semana, h.numero_aula, h.id_turma
        FROM horario h
        JOIN turma t ON t.id_turma = h.id_turma
        JOIN serie s ON s.id_serie = t.id_serie
        WHERE t.id_ano_letivo = :ano
          AND h.id_professor IS NOT NULL
          AND h.id_turma NOT IN ($placeholders)
    ";
    $paramsOcc = [':ano' => $id_ano_letivo];
    // bind dos placeholders dos ids:
    // (como PDO não aceita misto nomeado + posicional fácil, vamos montar posicional aqui)
    // => faremos outra query só posicional para não dar dor de cabeça.

    $sqlOcc2 = "
        SELECT h.id_professor, h.id_turno, h.dia_semana, h.numero_aula, h.id_turma
        FROM horario h
        JOIN turma t ON t.id_turma = h.id_turma
        JOIN serie s ON s.id_serie = t.id_serie
        WHERE t.id_ano_letivo = ?
          AND h.id_professor IS NOT NULL
          AND h.id_turma NOT IN ($placeholders)
    ";
    $params2 = [$id_ano_letivo];
    foreach ($ids as $v) $params2[] = (int)$v;

    if ($id_turno_filtro > 0) {
        $sqlOcc2 .= " AND h.id_turno = ? ";
        $params2[] = $id_turno_filtro;
    }

    $stOcc = $pdo->prepare($sqlOcc2);
    $stOcc->execute($params2);

    $occCount = 0;
    while ($r = $stOcc->fetch(PDO::FETCH_ASSOC)) {
        $pid  = (int)$r['id_professor'];
        $turn = (int)$r['id_turno'];
        $dia  = (string)$r['dia_semana'];
        $aula = (int)$r['numero_aula'];
        $tidO = (int)$r['id_turma'];
        if ($pid <= 0 || $turn <= 0 || $dia === '' || $aula <= 0) continue;
        $ocupacao[$turn][$dia][$aula][$pid] = $tidO;
        if (isset($professores[$pid])) $professores[$pid]['uso']++;
        $occCount++;
    }
    logMsg("✅ Ocupações pré-carregadas: $occCount (de outras turmas/níveis)");

    // (B) Carrega horario_fixos (somente das turmas do filtro)
    // Ajuste para seu schema real:
    // Esperado: horario_fixos(id_turma,dia_semana,numero_aula,id_professor,id_disciplina,id_turno?)
    // Se não existir id_turno na tabela, tiramos do turno da turma.
    $stFix = $pdo->prepare("
        SELECT hf.id_turma, hf.dia_semana, hf.numero_aula, hf.id_professor, hf.id_disciplina
        FROM horario_fixos hf
        WHERE hf.id_turma IN ($placeholders)
    ");
    $stFix->execute($ids);

    $fixCount = 0;
    while ($r = $stFix->fetch(PDO::FETCH_ASSOC)) {
        $tid = (int)$r['id_turma'];
        if (!isset($turmas[$tid])) continue;

        $dia  = (string)$r['dia_semana'];
        $aula = (int)$r['numero_aula'];
        $pid  = (int)$r['id_professor'];
        $did  = (int)$r['id_disciplina'];

        if ($dia === '' || $aula <= 0 || $pid <= 0 || $did <= 0) continue;

        $turno = (int)$turmas[$tid]['turno_id'];
        if (!isset($turnoDias[$turno][$dia])) continue;
        if ($aula > (int)$turnoDias[$turno][$dia]) continue;

        // marca como fixo
        $fixos[$tid][$dia][$aula] = true;

        // coloca na agenda e ocupa professor
        if (($turmas[$tid]['agenda'][$dia][$aula] ?? null) === null) {
            // se já tinha demanda 0, não decrementar abaixo de 0
            if (isset($turmas[$tid]['demanda'][$did]) && (int)$turmas[$tid]['demanda'][$did] > 0) {
                $turmas[$tid]['demanda'][$did]--;
            }
            $turmas[$tid]['agenda'][$dia][$aula] = ['d' => $did, 'p' => $pid];
            $ocupacao[$turno][$dia][$aula][$pid] = $tid;
            if (isset($professores[$pid])) $professores[$pid]['uso']++;
            decrementarQuotaSeHouver($turmas[$tid], $did, $pid);
            $fixCount++;
        }
    }
    logMsg("✅ Fixos carregados e aplicados: $fixCount");

    // ---------------- FASE 4: Fixação de disciplina (extra) ----------------
    if ($fixarDisciplina && $disciplinaFixaId > 0 && $disciplinaFixaDia && $disciplinaFixaAula > 0) {
        logMsg("\n>>> FASE 4: Fixação de disciplina");
        $nomeFixa = $disciplinasNomes[$disciplinaFixaId] ?? $disciplinaFixaId;
        logMsg("📌 Fixando: $nomeFixa em $disciplinaFixaDia {$disciplinaFixaAula}ª");

        foreach ($turmas as &$turma) {
            $turno = (int)$turma['turno_id'];

            if (!isset($turnoDias[$turno][$disciplinaFixaDia])) continue;
            if ($disciplinaFixaAula > (int)$turnoDias[$turno][$disciplinaFixaDia]) continue;
            if (!isset($turma['demanda'][$disciplinaFixaId]) || (int)$turma['demanda'][$disciplinaFixaId] <= 0) continue;
            if (($turma['agenda'][$disciplinaFixaDia][$disciplinaFixaAula] ?? null) !== null) continue;
            if (isSlotFixo((int)$turma['id'], $disciplinaFixaDia, (int)$disciplinaFixaAula, $fixos)) continue;

            $profsFixa = ordenarProfsPorDisponibilidadeEQuota($turma, (int)$disciplinaFixaId, $turno, $turnoDias, $professores, $ocupacao);

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
                    'nome' => (string)$t['nome_serie'],
                ];
            }
            $grupos[$k]['turmas'][(int)$tid] = &$turmas[(int)$tid];
        }
        unset($t);

        foreach ($grupos as $g) {
            if (count($g['turmas']) < 2) continue;

            $turno = (int)$g['turno_id'];
            $serie = (int)$g['serie_id'];

            $efId = null;
            foreach ($disciplinasSerie[$serie] as $did => $d) {
                if (!empty($d['is_ef'])) { $efId = (int)$did; break; }
            }
            if (!$efId) continue;

            $aulasEF = (int)$disciplinasSerie[$serie][$efId]['aulas'];
            if ($aulasEF <= 0) continue;

            logMsg("📌 {$g['nome']}: Ed. Física - $aulasEF aula(s)");

            $diasDisponiveis = array_keys($turnoDias[$turno]);
            shuffle($diasDisponiveis);

            $diasJaUsados = [];
            $alocadas = 0;

            foreach ($diasDisponiveis as $dia) {
                if ($alocadas >= $aulasEF) break;
                if (isset($diasJaUsados[$dia])) continue;

                $maxAulasDia = (int)$turnoDias[$turno][$dia];
                $aulasCandidatas = range(1, $maxAulasDia);
                shuffle($aulasCandidatas);

                foreach ($aulasCandidatas as $aula) {
                    $todasLivres = true;
                    foreach ($g['turmas'] as &$turmaChk) {
                        if (isSlotFixo((int)$turmaChk['id'], (string)$dia, (int)$aula, $fixos)) { $todasLivres = false; break; }
                        if (($turmaChk['agenda'][(string)$dia][(int)$aula] ?? null) !== null) { $todasLivres = false; break; }
                        if ((int)($turmaChk['demanda'][$efId] ?? 0) <= 0) { $todasLivres = false; break; }
                    }
                    unset($turmaChk);
                    if (!$todasLivres) continue;

                    $pares = [];
                    $ok = true;

                    foreach ($g['turmas'] as $tidTurma => &$turmaEF) {
                        $profsEF = $turmaEF['profs_disc'][$efId] ?? [];
                        if (empty($profsEF)) { $ok = false; break; }

                        shuffle($profsEF);
                        $pidEscolhido = null;

                        foreach ($profsEF as $pid) {
                            $pid = (int)$pid;
                            if (!profDisponivel($pid, $turno, (string)$dia, (int)$aula, $professores, $ocupacao)) continue;
                            $pidEscolhido = $pid;
                            break;
                        }

                        if (!$pidEscolhido) { $ok = false; break; }
                        $pares[(int)$tidTurma] = (int)$pidEscolhido;
                    }
                    unset($turmaEF);

                    if (!$ok) continue;

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
            if ($dem <= 0) continue;
            if (!empty($disciplinasSerie[$serie][$did]['is_ef'])) continue;

            $profs = $t['profs_disc'][$did] ?? [];
            $nProfs = count($profs);

            if ($nProfs === 0) { $score += 1000000; continue; }

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

    usort($turmaIds, function($a, $b) use ($turmaScore) {
        return $turmaScore[$b] <=> $turmaScore[$a];
    });

    logMsg("\n>>> ORDEM DE PROCESSAMENTO (mais difícil primeiro)");
    foreach ($turmaIds as $tid) {
        logMsg("   • {$turmas[$tid]['nome_serie']} {$turmas[$tid]['nome']} | turno={$turmas[$tid]['turno_id']} | score={$turmaScore[$tid]}");
    }

    // ---------------- FASE 6: CSP + greedy + finalizador ----------------
    logMsg("\n>>> FASE 6: Alocação por turma (CSP + Greedy + Finalizador)");

    $totalBacktracks = 0;

    foreach ($turmaIds as $tidLoop) {
        $turma = &$turmas[$tidLoop];

        $turno = (int)$turma['turno_id'];
        $serie = (int)$turma['serie_id'];

        logMsg("\n📊 {$turma['nome_serie']} {$turma['nome']} (turno=$turno)");

        $tarefas = [];
        foreach ($turma['demanda'] as $did => $dem) {
            $did = (int)$did; $dem = (int)$dem;
            if ($dem <= 0) continue;
            if (!empty($disciplinasSerie[$serie][$did]['is_ef'])) continue;

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

        usort($tarefas, function($a, $b) { return $b['dificuldade'] <=> $a['dificuldade']; });

        $bt = 0;
        $resolver = function($idx) use (
            &$resolver, &$turma, &$tarefas, &$turnoDias, &$professores, &$ocupacao, &$disciplinasSerie, &$fixos,
            $turno, $serie, &$bt, $max_backtracks, &$turmas, $max_global_depth
        ) {
            if ($idx >= count($tarefas)) return true;
            if ($bt >= $max_backtracks) return false;

            $did = (int)$tarefas[$idx]['did'];
            if ((int)($turma['demanda'][$did] ?? 0) <= 0) return $resolver($idx + 1);

            $profs = ordenarProfsPorDisponibilidadeEQuota($turma, (int)$did, $turno, $turnoDias, $professores, $ocupacao);
            if (empty($profs)) return false;

            $numDias = qtdDiasTurno($turno, $turnoDias);
            $aulasSemana = (int)($disciplinasSerie[$serie][$did]['aulas'] ?? 1);
            $limiteDia = maxPorDiaDisc($aulasSemana, $numDias);

            $dias = array_keys($turnoDias[$turno]);
            shuffle($dias);

            foreach ($dias as $dia) {
                $dia = (string)$dia;
                $max = (int)$turnoDias[$turno][$dia];
                if (aulasNoDia($turma['agenda'], $did, $dia) >= $limiteDia) continue;

                $aulas = range(1, $max);
                shuffle($aulas);

                foreach ($aulas as $aula) {
                    $aula = (int)$aula;

                    if (isSlotFixo((int)$turma['id'], $dia, $aula, $fixos)) continue;
                    if (($turma['agenda'][$dia][$aula] ?? null) !== null) continue;
                    if (temConsecutiva($turma['agenda'], $did, $dia, $aula, $max)) continue;

                    $profsTry = $profs;
                    shuffle($profsTry);

                    foreach ($profsTry as $pid) {
                        $pid = (int)$pid;

                        $qr = quotaRestante($turma, (int)$did, (int)$pid);
                        if ($qr !== null && $qr <= 0) continue;

                        if (!profDisponivel($pid, $turno, $dia, $aula, $professores, $ocupacao)) continue;

                        alocar($turma, $did, $pid, $dia, $aula, $professores, $ocupacao);

                        if ($resolver($idx + 1)) return true;

                        desalocar($turma, $dia, $aula, $professores, $ocupacao);
                        $bt++;
                        if ($bt >= $max_backtracks) return false;
                    }
                }
            }

            return false;
        };

        $sucesso = $resolver(0);
        $totalBacktracks += $bt;

        if ($sucesso) { logMsg("   ✅ Completo (BT: $bt)"); continue; }

        logMsg("   ⚠️ Backtrack limit ($bt). Entrando no Greedy crítico + Finalizador...");

        $tentouAlgo = true;
        while ($tentouAlgo) {
            $tentouAlgo = false;

            $pend = ordenarPendenciasPorCriticidade($turma, $turno, $serie, $turnoDias, $professores, $ocupacao, $disciplinasSerie);
            if (empty($pend)) break;

            foreach ($pend as $item) {
                $did = (int)$item['did'];
                if ((int)($turma['demanda'][$did] ?? 0) <= 0) continue;

                $numDias = qtdDiasTurno($turno, $turnoDias);
                $aulasSemana = (int)($disciplinasSerie[$serie][$did]['aulas'] ?? 1);

                $limiteDia = maxPorDiaDisc($aulasSemana, $numDias);
                $maxLimiteDia = 3;

                while ((int)($turma['demanda'][$did] ?? 0) > 0) {
                    $ok = alocarUmaUnidadeComFinalizador(
                        $turma, $did,
                        $turmas, $turnoDias, $professores, $ocupacao, $fixos, $disciplinasSerie,
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

                    if ($limiteDia < $maxLimiteDia) {
                        $limiteDia++;
                        logMsg("   ⚠️ Relaxando limite por dia para $limiteDia (disciplina $did)...");
                        continue;
                    }

                    $ok2 = alocarUmaUnidadeComFinalizador(
                        $turma, $did,
                        $turmas, $turnoDias, $professores, $ocupacao, $fixos, $disciplinasSerie,
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
            if ((int)$dem > 0) $faltam[] = ($disciplinasNomes[(int)$did] ?? $did) . " (" . (int)$dem . ")";
        }
        if (!empty($faltam)) logMsg("   📋 Faltam: " . implode(', ', $faltam));
        else logMsg("   ✅ Fechado no finalizador.");
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
                    if ($ant !== null && (int)$ant === (int)$s['d']) $consecutivas++;
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

    // ---------------- FASE 8: salvar ----------------
    logMsg("\n>>> FASE 8: Salvando");

    // IMPORTANTE: inclui id_turno (seu índice uq_prof_slot usa isso)
    $stmtIns = $pdo->prepare("
        INSERT INTO horario (id_turma, id_turno, dia_semana, numero_aula, id_professor, id_disciplina)
        VALUES (?,?,?,?,?,?)
    ");

    $inseridas = 0;
    foreach ($turmas as $turma) {
        $turno = (int)$turma['turno_id'];
        foreach ($turma['agenda'] as $dia => $aulas) {
            foreach ($aulas as $aula => $s) {
                if ($s !== null) {
                    $stmtIns->execute([
                        (int)$turma['id'],
                        $turno,
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

    if (!empty($faltasGlobais)) {
        echo json_encode([
            'status'    => 'success',
            'completed' => false,
            'message'   =>
                "Gerado, porém NÃO foi possível completar 100% mantendo as restrições.\n" .
                "Filtro aplicado: Turno=" . ($id_turno_filtro ?: 'TODOS') . "\n\n" .
                "Faltas:\n- " . implode("\n- ", $faltasGlobais),
            'stats' => [
                'totalAulas'       => $totalAulas,
                'totalVazios'      => $totalVazios,
                'consecutivas'     => $consecutivas,
                'totalBacktracks'  => $totalBacktracks,
                'inseridas'        => $inseridas
            ]
        ]);
        exit;
    }

    echo json_encode([
        'status'    => 'success',
        'completed' => true,
        'message'   =>
            "✅ Horários gerados (v16.5) 100%!\n" .
            "Filtro aplicado: Turno=" . ($id_turno_filtro ?: 'TODOS') . "\n\n" .
            "📊 Aulas: $totalAulas\n" .
            "📊 Vazios: $totalVazios\n" .
            "📊 Consecutivas: $consecutivas",
        'stats' => [
            'totalAulas' => $totalAulas,
            'totalVazios' => $totalVazios,
            'consecutivas' => $consecutivas,
            'totalBacktracks' => $totalBacktracks,
            'inseridas' => $inseridas
        ]
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    logMsg("❌ ERRO: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
