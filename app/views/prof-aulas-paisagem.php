<?php
// app/views/prof-aulas-paisagem.php
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

/* -----------------------------
   LOG SIMPLE PARA SEGURANÇA (APENAS CABEÇALHO)
------------------------------*/
$SEG_LOG = __DIR__ . '/../../logs/seguranca.log';
if (!function_exists('logSecurity')) {
    function logSecurity($msg) {
        global $SEG_LOG;
        $meta = [
            'ts'    => date('c'),
            'ip'    => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'ua'    => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'qs'    => $_SERVER['QUERY_STRING'] ?? '',
            'script'=> basename($_SERVER['SCRIPT_NAME'] ?? '')
        ];
        $entry = '['.date('d-M-Y H:i:s T').'] [SEGURANCA] '.$msg.' | META='.json_encode($meta, JSON_UNESCAPED_UNICODE).PHP_EOL;
        @file_put_contents($SEG_LOG, $entry, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Emite resposta de erro padrão e encerra.
 */
function abortClient($msg = 'Parâmetros inválidos') {
    while (ob_get_level() > 0) @ob_end_clean();
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit;
}

/* -----------------------------
   CABEÇALHO: validação rígida da query string
   Permitimos somente parâmetros específicos e checamos consistência
------------------------------*/
$allowed_keys = ['id_ano_letivo','id_nivel_ensino','id_turma','id_professor','detalhe'];
$rawQuery = $_SERVER['QUERY_STRING'] ?? '';
parse_str($rawQuery, $receivedParams);

// rejeita parâmetros inesperados
$receivedKeys = array_keys($receivedParams);
$extra = array_diff($receivedKeys, $allowed_keys);
if (!empty($extra)) {
    logSecurity('Parâmetros inesperados em prof-aulas-paisagem: ' . implode(', ', $extra) . ' | raw_qs=' . $rawQuery);
    abortClient();
}

// normalização e validação básica de tipos
$canonical = [];
foreach ($receivedParams as $k => $v) {
    if (!is_scalar($v)) {
        logSecurity("Parâmetro não escalar em prof-aulas-paisagem: {$k}");
        abortClient();
    }
    if ($k === 'detalhe') {
        $detalhe_raw = strtolower((string)$v);
        $allowedDetalhe = ['geral','quantidade','dias'];
        if (!in_array($detalhe_raw, $allowedDetalhe, true)) {
            logSecurity("Valor inválido para detalhe em prof-aulas-paisagem: {$detalhe_raw}");
            abortClient();
        }
        $canonical['detalhe'] = $detalhe_raw;
        continue;
    }
    // demais devem ser inteiros não-negativos
    if (!preg_match('/^\d+$/', (string)$v)) {
        logSecurity("Parâmetro não numérico permitido em prof-aulas-paisagem: {$k} raw=" . var_export($v, true) . " | raw_qs={$rawQuery}");
        abortClient();
    }
    $ival = (int)$v;
    if ($ival < 0) {
        logSecurity("Parâmetro negativo rejeitado em prof-aulas-paisagem: {$k} raw=" . var_export($v, true) . " | raw_qs={$rawQuery}");
        abortClient();
    }
    $canonical[$k] = $ival;
}

// ordenar para comparação canônica (defensivo)
ksort($canonical);
$normalized_received_array = [];
foreach ($receivedParams as $k => $v) {
    if ($k === 'detalhe') {
        $normalized_received_array[$k] = strtolower((string)$v);
    } else {
        $normalized_received_array[$k] = (int)$v;
    }
}
ksort($normalized_received_array);

if ($canonical !== $normalized_received_array) {
    logSecurity("Query string não canônica em prof-aulas-paisagem: expected_array=" . json_encode($canonical) . " got_array=" . json_encode($normalized_received_array) . " | full_raw={$rawQuery}");
    abortClient();
}

/* -----------------------------
   Validações semânticas (existe no banco)
------------------------------*/
// extrai valores canônicos (se não setados, ficam null/0)
$id_ano_letivo   = $canonical['id_ano_letivo']   ?? 0;
$id_nivel_ensino = $canonical['id_nivel_ensino'] ?? 0;
$id_turma        = $canonical['id_turma']        ?? 0;
$id_professor    = $canonical['id_professor']    ?? 0;
$detalhe         = $canonical['detalhe']         ?? 'geral';

// ano letivo deve existir
$stAno = $pdo->prepare("SELECT 1 FROM ano_letivo WHERE id_ano_letivo = :id LIMIT 1");
$stAno->execute([':id' => $id_ano_letivo]);
if ($stAno->fetchColumn() === false) {
    logSecurity("id_ano_letivo inexistente em prof-aulas-paisagem: {$id_ano_letivo}");
    abortClient();
}

// se informou nivel, deve existir
if ($id_nivel_ensino > 0) {
    $stN = $pdo->prepare("SELECT 1 FROM nivel_ensino WHERE id_nivel_ensino = :id LIMIT 1");
    $stN->execute([':id' => $id_nivel_ensino]);
    if ($stN->fetchColumn() === false) {
        logSecurity("id_nivel_ensino inexistente em prof-aulas-paisagem: {$id_nivel_ensino}");
        abortClient();
    }
}

// se informou turma, deve existir e pertencer ao ano (e ao nível, se informado)
if ($id_turma > 0) {
    $sqlT = "SELECT t.id_turma
               FROM turma t
               JOIN serie s ON s.id_serie = t.id_serie
              WHERE t.id_turma = :tid
                AND t.id_ano_letivo = :ano
           LIMIT 1";
    $paramsT = [':tid' => $id_turma, ':ano' => $id_ano_letivo];
    if ($id_nivel_ensino > 0) {
        $sqlT = "SELECT t.id_turma
                   FROM turma t
                   JOIN serie s ON s.id_serie = t.id_serie
                  WHERE t.id_turma = :tid
                    AND t.id_ano_letivo = :ano
                    AND s.id_nivel_ensino = :niv
               LIMIT 1";
        $paramsT[':niv'] = $id_nivel_ensino;
    }
    $stT = $pdo->prepare($sqlT);
    $stT->execute($paramsT);
    if ($stT->fetchColumn() === false) {
        logSecurity("id_turma inválida ou não pertence ao ano/nível informado em prof-aulas-paisagem: turma={$id_turma} ano={$id_ano_letivo} nivel={$id_nivel_ensino}");
        abortClient();
    }
}

// se informou professor, verificar existência (opcional: poderia validar se tem horário)
if ($id_professor > 0) {
    $stP = $pdo->prepare("SELECT 1 FROM professor WHERE id_professor = :id LIMIT 1");
    $stP->execute([':id' => $id_professor]);
    if ($stP->fetchColumn() === false) {
        logSecurity("id_professor inexistente em prof-aulas-paisagem: {$id_professor}");
        abortClient();
    }
}

/* -----------------------------
   Parâmetros OK. segue a execução original
------------------------------*/

/* -----------------------------
   CABEÇALHO (ANO / NÍVEL / LOGO)
------------------------------*/
// Ano legível (ex.: 2025)
$stAno = $pdo->prepare("SELECT ano FROM ano_letivo WHERE id_ano_letivo = :id");
$stAno->execute([':id' => $id_ano_letivo]);
$anoLegivel = $stAno->fetchColumn();
if (!$anoLegivel) $anoLegivel = (string)$id_ano_letivo;

// Nome do nível (quando filtrado)
$nivelNome = '';
if ($id_nivel_ensino > 0) {
  $stN = $pdo->prepare("SELECT nome_nivel_ensino FROM nivel_ensino WHERE id_nivel_ensino = :id");
  $stN->execute([':id' => $id_nivel_ensino]);
  $nivelNome = $stN->fetchColumn() ?: '';
}

// Instituição (logo e nome) - consulta simples, sem alterações na lógica
$inst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1")
            ->fetch(PDO::FETCH_ASSOC);

/* -----------------------------
   CARGA DE DADOS (sem HY093)
   Mantive sua função original
------------------------------*/
function loadData(PDO $pdo, int $id_ano_letivo, int $id_nivel_ensino, int $id_turma, int $id_professor) {
  // Níveis "escolinhas" (para excluir quando não filtra nível)
  $idsEscolinhas = $pdo->query("
      SELECT id_nivel_ensino
      FROM nivel_ensino
      WHERE LOWER(nome_nivel_ensino) LIKE '%escolinh%'
  ")->fetchAll(PDO::FETCH_COLUMN);
  $idsEscolinhas = array_map('intval', $idsEscolinhas ?: []);

  /* ---- TURMAS ---- */
  $sqlTurmas = "
    SELECT t.id_turma, s.nome_serie, t.nome_turma, t.id_turno
    FROM turma t
    JOIN serie s ON s.id_serie = t.id_serie
    WHERE t.id_ano_letivo = :ano
  ";
  $params = [':ano' => $id_ano_letivo];

  if ($id_nivel_ensino > 0) {
    $sqlTurmas .= " AND s.id_nivel_ensino = :niv ";
    $params[':niv'] = $id_nivel_ensino;
  } else if (!empty($idsEscolinhas)) {
    // constrói placeholders nomeados para NOT IN
    $ph = [];
    foreach ($idsEscolinhas as $k => $val) {
      $phName = ':es' . $k;
      $ph[] = $phName;
      $params[$phName] = $val;
    }
    $sqlTurmas .= " AND s.id_nivel_ensino NOT IN (" . implode(',', $ph) . ") ";
  }

  if ($id_turma > 0) {
    $sqlTurmas .= " AND t.id_turma = :tur ";
    $params[':tur'] = $id_turma;
  }

  $sqlTurmas .= " ORDER BY s.nome_serie, t.nome_turma";
  $st = $pdo->prepare($sqlTurmas);
  $st->execute($params);
  $turmas = $st->fetchAll(PDO::FETCH_ASSOC);
  if (!$turmas) return [[], [], []];

  $turmaIds = array_map(fn($t)=>(int)$t['id_turma'], $turmas);

  /* ---- PROFESSORES (apenas os que têm horário nessas turmas) ---- */
  // placeholders nomeados para IN
  $phT = [];
  $paramsProf = [];
  foreach ($turmaIds as $i => $tid) {
    $name = ':t' . $i;
    $phT[] = $name;
    $paramsProf[$name] = $tid;
  }
  $sqlProf = "
    SELECT DISTINCT p.id_professor,
      COALESCE(NULLIF(TRIM(p.nome_exibicao),''), p.nome_completo) AS nome_professor
    FROM horario h
    JOIN professor p ON p.id_professor = h.id_professor
    WHERE h.id_turma IN (" . implode(',', $phT) . ")
  ";
  if ($id_professor > 0) {
    $sqlProf .= " AND p.id_professor = :pfilter ";
    $paramsProf[':pfilter'] = $id_professor;
  }
  $sqlProf .= " ORDER BY nome_professor";
  $sp = $pdo->prepare($sqlProf);
  $sp->execute($paramsProf);
  $professores = $sp->fetchAll(PDO::FETCH_ASSOC);
  if (!$professores) return [$turmas, [], []];

  $profIds = array_map(fn($p)=>(int)$p['id_professor'], $professores);

  /* ---- AGREGAÇÃO POR DIA ---- */
  $phP = [];
  $paramsAgg = [];
  foreach ($turmaIds as $i => $tid) { $paramsAgg[':tt'.$i] = $tid; $phTT[]=':tt'.$i; }
  foreach ($profIds as $i => $pid)  { $paramsAgg[':pp'.$i] = $pid; $phPP[]=':pp'.$i; }

  $sqlAgg = "
    SELECT h.id_professor, h.id_turma,
      SUM(h.dia_semana='Domingo') AS dom,
      SUM(h.dia_semana='Segunda') AS seg,
      SUM(h.dia_semana='Terca')   AS ter,
      SUM(h.dia_semana='Quarta')  AS qua,
      SUM(h.dia_semana='Quinta')  AS qui,
      SUM(h.dia_semana='Sexta')   AS sex,
      SUM(h.dia_semana='Sabado')  AS sab,
      COUNT(*) AS total
    FROM horario h
    WHERE h.id_turma IN (" . implode(',', $phTT) . ")
      AND h.id_professor IN (" . implode(',', $phPP) . ")
    GROUP BY h.id_professor, h.id_turma
  ";
  $sa = $pdo->prepare($sqlAgg);
  $sa->execute($paramsAgg);
  $agg = $sa->fetchAll(PDO::FETCH_ASSOC);

  $grid = [];
  foreach ($agg as $r) {
    $grid[(int)$r['id_professor']][(int)$r['id_turma']] = [
      'dom'=>(int)$r['dom'], 'seg'=>(int)$r['seg'], 'ter'=>(int)$r['ter'],
      'qua'=>(int)$r['qua'], 'qui'=>(int)$r['qui'], 'sex'=>(int)$r['sex'],
      'sab'=>(int)$r['sab'], 'total'=>(int)$r['total'],
    ];
  }

  return [$turmas, $professores, $grid];
}

[$turmas, $professores, $grid] = loadData($pdo, $id_ano_letivo, $id_nivel_ensino, $id_turma, $id_professor);

/* -----------------------------
   PDF
------------------------------*/
class PDF extends FPDF {
  function Footer() {
    $this->SetY(-15);
    $this->SetFont('Arial','I',8);
    $this->Cell(0,10,mb_convert_encoding('Impresso em: '.date('d/m/Y H:i:s'),'ISO-8859-1','UTF-8'),0,0,'R');
  }
}

$pdf = new PDF('L','mm','A4');
$pdf->SetTitle('Professores x Turmas', true);
$pdf->AddPage();

/* -----------------------------
   Cabeçalho original (logo à esquerda, nome centrado)
   Com proteção contra violação de link na logo (basename + realpath)
------------------------------*/
if (!empty($inst['imagem_instituicao'])) {
  $candidate = basename($inst['imagem_instituicao']);
  $fullLogo = LOGO_PATH . '/' . $candidate;
  if (file_exists($fullLogo) && is_file($fullLogo)) {
      // garantir que o arquivo esteja dentro do diretório LOGO_PATH (evita ../)
      $realLogo = realpath($fullLogo);
      $realLogoDir = realpath(LOGO_PATH);
      if ($realLogo !== false && $realLogoDir !== false && strpos($realLogo, $realLogoDir) === 0) {
          // imagem segura
          $pdf->Image($realLogo, 10, 8, 15);
      } else {
          logSecurity("Tentativa de usar logo fora do diretório permitido: " . $inst['imagem_instituicao']);
          // não exibe a imagem
      }
  } else {
      logSecurity("Logo informado não encontrado: " . $inst['imagem_instituicao']);
  }
}

$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,7,mb_convert_encoding($inst['nome_instituicao'] ?? '','ISO-8859-1','UTF-8'),0,1,'C');

$pdf->SetFont('Arial','',12);
$linha2 = "Ano Letivo: ".$anoLegivel;
if ($nivelNome) $linha2 .= "  |  Nível: ".$nivelNome;
$pdf->Cell(0,6,mb_convert_encoding($linha2,'ISO-8859-1','UTF-8'),0,1,'C');
$pdf->Ln(3);

/* Tabela */
if (!$turmas || !$professores) {
  $pdf->SetFont('Arial','B',12);
  $pdf->Cell(0,10,mb_convert_encoding('Nenhum registro encontrado.','ISO-8859-1','UTF-8'),0,1,'C');
  $pdf->Output('I','ProfessoresTurmas.pdf'); exit;
}

$marginL = 10;
$pageW   = $pdf->GetPageWidth();
$usableW = $pageW - 2*$marginL;

$pdf->SetFont('Arial','B',11);
$colProfW = 70;
$cols     = count($turmas);
$colTotW  = ($detalhe==='geral') ? 0 : 20;
$colW     = ($usableW - $colProfW - $colTotW) / max(1, $cols);

/* Cabeçalho */
$pdf->SetFillColor(200,200,200);
$pdf->Cell($colProfW,10,mb_convert_encoding('Nome do Professor','ISO-8859-1','UTF-8'),1,0,'L',true);
foreach ($turmas as $t) {
  $pdf->Cell($colW,10,mb_convert_encoding($t['nome_serie'].' '.$t['nome_turma'],'ISO-8859-1','UTF-8'),1,0,'C',true);
}
if ($detalhe!=='geral') {
  $pdf->Cell($colTotW,10,mb_convert_encoding('Total','ISO-8859-1','UTF-8'),1,0,'C',true);
}
$pdf->Ln();

/* Linhas */
foreach ($professores as $p) {
  $pdf->SetFont('Arial','',10);
  $totalGeral = 0;
  $pdf->Cell($colProfW,10,mb_convert_encoding($p['nome_professor'],'ISO-8859-1','UTF-8'),1,0,'L');

  foreach ($turmas as $t) {
    $cell = $grid[$p['id_professor']][$t['id_turma']] ?? null;

    if ($detalhe === 'geral') {
      // “V” centralizado quando tem, vazio quando não
      $pdf->Cell($colW,10, ($cell ? 'V' : ''), 1, 0, 'C');
    } elseif ($detalhe === 'quantidade') {
      $q = $cell ? (int)$cell['total'] : 0;
      $totalGeral += $q;
      $pdf->Cell($colW,10, ($q>0 ? (string)$q : ''), 1, 0, 'C');
    } else {
      // Detalhes por dia: 2 linhas (D S T Q Q S S) + (valores ou "-")
      $x = $pdf->GetX(); $y = $pdf->GetY();
      // borda da célula
      $pdf->Cell($colW,10,'',1,0,'C');
      // volta para desenhar o conteúdo interno
      $pdf->SetXY($x,$y);

      $labels = ['D','S','T','Q','Q','S','S']; // Dom..Sab
      $vals   = $cell
        ? [$cell['dom'],$cell['seg'],$cell['ter'],$cell['qua'],$cell['qui'],$cell['sex'],$cell['sab']]
        : [0,0,0,0,0,0,0];
      $totalGeral += ($cell ? (int)$cell['total'] : 0);

      $boxW = $colW / 7;

      // 1ª linha: labels
      $pdf->SetFont('Arial','',8);
      for ($i=0; $i<7; $i++) $pdf->Cell($boxW,5,$labels[$i],0,0,'C');

      // 2ª linha: valores (ou "-")
      $pdf->SetXY($x, $y + 5);
      $pdf->SetFont('Arial','',10);
      for ($i=0; $i<7; $i++) {
        $txt = ($vals[$i] > 0) ? (string)$vals[$i] : '-';
        $pdf->Cell($boxW,5,$txt,0,0,'C');
      }

      // reposiciona o “cursor” no fim da célula (garantia)
      $pdf->SetXY($x + $colW, $y);
    }
  }

  if ($detalhe !== 'geral') {
    $pdf->Cell($colTotW,10,(string)$totalGeral,1,0,'C');
  }
  $pdf->Ln();
}

$pdf->Output('I','ProfAulas-Paisagem.pdf');
exit;
?>
