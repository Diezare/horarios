<?php
// app/controllers/horarios/gerarHorariosAutomaticos.php 
/**
 * Gera/Popula automaticamente os horários de TODAS as turmas
 * de um determinado Nível de Ensino em um Ano Letivo.
 * REGRAS (exemplo simplificado):
 *   - Para cada turma => distribui disciplinas e professores
 *   - Respeita restrições do professor (já consultadas)
 *   - Respeita se professor já está ocupado no mesmo dia/aula
 *
 * Observação: Essa lógica pode ser bem complexa. Aqui é apenas um ESBOÇO.
 */
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json; charset=utf-8');

$id_ano_letivo   = isset($_POST['id_ano_letivo']) ? intval($_POST['id_ano_letivo']) : 0;
$id_nivel_ensino = isset($_POST['id_nivel_ensino']) ? intval($_POST['id_nivel_ensino']) : 0;

if (!$id_ano_letivo || !$id_nivel_ensino) {
    echo json_encode([
        'status' => 'error',
        'message'=> 'Parâmetros inválidos.'
    ]);
    exit;
}

// 1) Buscar todas as turmas desse nível e ano letivo
$sqlTurmas = "
  SELECT t.id_turma, s.nome_serie, t.nome_turma 
    FROM turma t
    JOIN serie s ON t.id_serie = s.id_serie
   WHERE t.id_ano_letivo = :ano
     AND s.id_nivel_ensino = :nivel
";
$stmt = $pdo->prepare($sqlTurmas);
$stmt->execute([
    ':ano'   => $id_ano_letivo,
    ':nivel' => $id_nivel_ensino
]);
$turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$turmas) {
    echo json_encode([
        'status' => 'error',
        'message'=> 'Nenhuma turma encontrada para este nível/ano.'
    ]);
    exit;
}

// 2) Exemplo de lógica simples: para cada turma, apaga horários antigos e cria novos (cuidado!)
try {
    $pdo->beginTransaction();

    // Excluir todos os horários das turmas em questão (CUIDADO: pergunte se é isso mesmo)
    $idsTurma = array_column($turmas, 'id_turma');
    $inParams = implode(',', array_fill(0, count($idsTurma), '?'));
    $stmtDel  = $pdo->prepare("DELETE FROM horario WHERE id_turma IN ($inParams)");
    $stmtDel->execute($idsTurma);

    // Agora gerar. Precisamos de dados: 
    // - Disciplinas da série
    // - Professores de cada disciplina + restrições
    // - Turno/dias para cada turma
    // ...
    // O ideal é implementar uma **função** de IA ou backtracking 
    // que posicione cada disciplina/professor dentro dos dias/aulas livres,
    // evitando conflitos e respeitando restrições. 
    // Abaixo: Exemplo **EXTREMAMENTE** simplificado (não real):

    foreach ($turmas as $t) {
        $idTurma = $t['id_turma'];

        // Buscar disciplinas dessa série
        $sqlSD = "
          SELECT sd.id_disciplina, sd.aulas_semana
            FROM serie_disciplinas sd
            JOIN turma tx ON tx.id_serie = sd.id_serie
           WHERE tx.id_turma = :t
        ";
        $stmtSD = $pdo->prepare($sqlSD);
        $stmtSD->execute([':t'=>$idTurma]);
        $serieDisc = $stmtSD->fetchAll(PDO::FETCH_ASSOC);

        // Buscar possíveis professores para cada disciplina/turma 
        $sqlProfDiscTurma = "
          SELECT pdt.id_disciplina, pdt.id_professor
            FROM professor_disciplinas_turmas pdt
           WHERE pdt.id_turma = :t
        ";
        $stmtPDT = $pdo->prepare($sqlProfDiscTurma);
        $stmtPDT->execute([':t'=>$idTurma]);
        $mapProfDiscTurma = [];
        while ($rowP = $stmtPDT->fetch(PDO::FETCH_ASSOC)) {
            $disc = $rowP['id_disciplina'];
            if (!isset($mapProfDiscTurma[$disc])) {
                $mapProfDiscTurma[$disc] = [];
            }
            $mapProfDiscTurma[$disc][] = $rowP['id_professor'];
        }

        // Buscar dias e número de aulas
        $sqlDias = "SELECT dia_semana, aulas_no_dia FROM turno_dias td
                    JOIN turma tt ON tt.id_turno = td.id_turno
                   WHERE tt.id_turma = :t";
        $stmtDias = $pdo->prepare($sqlDias);
        $stmtDias->execute([':t'=>$idTurma]);
        $turnoDias = $stmtDias->fetchAll(PDO::FETCH_ASSOC);

        // Geração (exemplo micro):
        // Varre as disciplinas e sai distribuindo em cada dia enquanto houver "slots" livres. 
        // *** Lembrando que seria preciso verificar restrição e se o professor está disponível ***
        // Este é apenas um rascunho:

        // Exemplo fixo: dias na ordem
        $diasSemana = ['Segunda','Terca','Quarta','Quinta','Sexta','Sabado','Domingo'];

        foreach ($serieDisc as $sd) {
            $discId = $sd['id_disciplina'];
            $qtdAulas = $sd['aulas_semana']; 
            // Pegar o primeiro professor do map (muito simplista!)
            $profEscolhido = isset($mapProfDiscTurma[$discId]) 
                             ? $mapProfDiscTurma[$discId][0] 
                             : null;
            if (!$profEscolhido) {
                // se não achar professor, pula
                continue;
            }

            // Distribui a disciplina nesses dias
            $aulasRestantes = $qtdAulas;
            foreach ($diasSemana as $dia) {
                if ($aulasRestantes <= 0) break;
                // Descobrir quantas aulas existem neste dia 
                $dDia = array_filter($turnoDias, fn($v)=>$v['dia_semana']===$dia);
                if (!$dDia) continue;
                $dDia = array_values($dDia);
                $aulasNoDia = intval($dDia[0]['aulas_no_dia'] ?? 0);
                if ($aulasNoDia<=0) continue;

                // Tentar inserir 1 aula neste dia (exemplo) 
                // *** Ignorando restrições e conflitos *** – Ajuste com a sua lógica
                $numeroAula = encontrarProximoSlotLivre($pdo, $idTurma, $dia, $aulasNoDia);
                if ($numeroAula) {
                    // Insere
                    $sqlInsertH = "
                      INSERT INTO horario
                        (id_turma, dia_semana, numero_aula, id_disciplina, id_professor)
                      VALUES
                        (:t, :dia, :naula, :disc, :prof)
                    ";
                    $stmtIH = $pdo->prepare($sqlInsertH);
                    $stmtIH->execute([
                        ':t'=>$idTurma,
                        ':dia'=>$dia,
                        ':naula'=>$numeroAula,
                        ':disc'=>$discId,
                        ':prof'=>$profEscolhido
                    ]);
                    $aulasRestantes--;
                }
            }
        }
    }

    $pdo->commit();

    echo json_encode([
        'status'=>'success',
        'message'=>'Horários gerados (exemplo simplificado).'
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'status'=>'error',
        'message'=>$e->getMessage()
    ]);
}

/**
 * Função de exemplo para achar "próximo slot livre" no dia
 * CUIDADO: não verifica professorOcupado, restrição, etc. – 
 * Você pode adequar com queries adicionais.
 */
function encontrarProximoSlotLivre(PDO $pdo, $idTurma, $dia, $maxAulasDia) {
    // Consulta quais aulas já estão ocupadas nesse dia/turma
    $sql = "SELECT numero_aula FROM horario
             WHERE id_turma = :t AND dia_semana = :dia";
    $st = $pdo->prepare($sql);
    $st->execute([':t'=>$idTurma, ':dia'=>$dia]);
    $ocupadas = $st->fetchAll(PDO::FETCH_COLUMN, 0);

    for ($a=1; $a<=$maxAulasDia; $a++) {
        if (!in_array($a, $ocupadas)) {
            return $a;
        }
    }
    return null; 
}
