<?php
// app/views/horarios-professores.php
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

// ---------------- Segurança / logging (cabeçalho) ----------------
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
 * Encerra a requisição emitindo texto puro. Evita qualquer saída de PDF.
 */
function abortClient($msg = 'Parâmetros inválidos') {
    while (ob_get_level() > 0) @ob_end_clean();
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit;
}

// ---------------- Validação rígida da query string ----------------
// Permitimos somente estes parâmetros
$allowed = ['id_ano_letivo','id_nivel_ensino','id_professor'];
$rawQuery = $_SERVER['QUERY_STRING'] ?? '';
parse_str($rawQuery, $receivedParams);

// Rejeita parâmetros inesperados
$receivedKeys = array_keys($receivedParams);
$extra = array_diff($receivedKeys, $allowed);
if (!empty($extra)) {
    logSecurity('Parâmetros inesperados em horarios-professores: ' . implode(', ', $extra) . ' | raw_qs=' . $rawQuery);
    abortClient();
}

// Normalização e validação: aceitar somente inteiros não-negativos (>=0) quando presentes.
$canonical = [];
foreach ($receivedParams as $k => $v) {
    if (!is_scalar($v)) {
        logSecurity("Parâmetro não escalar em horarios-professores: {$k}");
        abortClient();
    }
    if (!preg_match('/^\d+$/', (string)$v)) {
        logSecurity("Parâmetro não numérico permitido em horarios-professores: {$k} raw=" . var_export($v, true) . " | raw_qs={$rawQuery}");
        abortClient();
    }
    $ival = (int)$v;
    if ($ival < 0) {
        logSecurity("Parâmetro negativo rejeitado em horarios-professores: {$k} raw=" . var_export($v, true) . " | raw_qs={$rawQuery}");
        abortClient();
    }
    $canonical[$k] = $ival;
}

// Ordenar para comparação canônica
ksort($canonical);
$normalized_received_array = [];
foreach ($receivedParams as $k => $v) $normalized_received_array[$k] = (int)$v;
ksort($normalized_received_array);

if ($canonical !== $normalized_received_array) {
    logSecurity("Query string não canônica em horarios-professores: expected_array=" . json_encode($canonical) . " got_array=" . json_encode($normalized_received_array) . " | full_raw={$rawQuery}");
    abortClient();
}

// id_ano_letivo é obrigatório e deve existir no banco
if (!isset($canonical['id_ano_letivo']) || $canonical['id_ano_letivo'] <= 0) {
    logSecurity("id_ano_letivo ausente ou inválido em horarios-professores | raw_qs={$rawQuery}");
    abortClient('Ano letivo não informado.');
}

// ---------------- Validações de existência / consistência no banco ----------------
try {
    // ano letivo
    $st = $pdo->prepare("SELECT 1 FROM ano_letivo WHERE id_ano_letivo = :id LIMIT 1");
    $st->execute([':id' => $canonical['id_ano_letivo']]);
    if (!$st->fetchColumn()) {
        logSecurity("id_ano_letivo inexistente em horarios-professores: id=" . $canonical['id_ano_letivo'] . " | raw_qs={$rawQuery}");
        abortClient();
    }

    // nivel_ensino (se informado)
    if (isset($canonical['id_nivel_ensino']) && $canonical['id_nivel_ensino'] > 0) {
        $st = $pdo->prepare("SELECT 1 FROM nivel_ensino WHERE id_nivel_ensino = :id LIMIT 1");
        $st->execute([':id' => $canonical['id_nivel_ensino']]);
        if (!$st->fetchColumn()) {
            logSecurity("id_nivel_ensino inexistente em horarios-professores: id=" . $canonical['id_nivel_ensino'] . " | raw_qs={$rawQuery}");
            abortClient();
        }
    }

    // professor (se informado)
    if (isset($canonical['id_professor']) && $canonical['id_professor'] > 0) {
        $st = $pdo->prepare("SELECT 1 FROM professor WHERE id_professor = :id LIMIT 1");
        $st->execute([':id' => $canonical['id_professor']]);
        if (!$st->fetchColumn()) {
            logSecurity("id_professor inexistente em horarios-professores: id=" . $canonical['id_professor'] . " | raw_qs={$rawQuery}");
            abortClient();
        }
    }
} catch (Throwable $e) {
    logSecurity("Erro ao validar IDs em horarios-professores: " . $e->getMessage() . " | raw_qs={$rawQuery}");
    abortClient();
}

// ---------------- FIM da camada de cabeçalho/segurança ----------------

// Atribuir variáveis seguras
$id_ano_letivo   = $canonical['id_ano_letivo'];
$id_nivel_ensino = $canonical['id_nivel_ensino'] ?? 0;
$id_professor    = $canonical['id_professor'] ?? 0;

// ---------------- Parâmetros do cabeçalho (ajuste aqui) ----------------
$LOGO_SIZE_MM = 15; // tamanho (largura=altura) da logo em mm
$LOGO_GAP_MM  = 5;  // espaço entre a logo e o nome em mm

// Proteção do logo: carregamos $inst e $logoPath (somente leitura segura)
$inst = null;
$logoPath = null;
try {
    $stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
    $inst = $stmtInst->fetch(PDO::FETCH_ASSOC);
    if ($inst && !empty($inst['imagem_instituicao'])) {
        $candidate = basename($inst['imagem_instituicao']);
        $fullLogo = LOGO_PATH . '/' . $candidate;
        if (file_exists($fullLogo) && is_file($fullLogo) && strpos(realpath($fullLogo), realpath(LOGO_PATH)) === 0) {
            $logoPath = $fullLogo;
        } else {
            logSecurity("Logo inválido/inacessível em horarios-professores: " . $inst['imagem_instituicao']);
            $logoPath = null;
        }
    }
} catch (Throwable $e) {
    logSecurity("Erro SQL buscando instituicao em horarios-professores: " . $e->getMessage());
    $inst = null;
    $logoPath = null;
}

// ---------------- Classe PDF customizada com Cabeçalho e Rodapé ----------------
class PDFRelProfAulas extends FPDF
{
    public function Header()
    {
        global $pdo, $LOGO_SIZE_MM, $LOGO_GAP_MM, $inst, $logoPath;

        $nomeInst = $inst ? ($inst['nome_instituicao'] ?? '') : '';
        $logo = $logoPath && file_exists($logoPath) ? $logoPath : null;

        // Linha única: logo + nome, centralizados
        $this->SetY(12);
        $this->SetFont('Arial','B',14);
        $txt  = iconv('UTF-8','ISO-8859-1', $nomeInst);
        $txtW = $this->GetStringWidth($txt);
        $hasLogo = ($logo !== null);
        $totalW  = $hasLogo ? ($LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0) + $txtW) : $txtW;
        $pageW   = $this->GetPageWidth();
        $x       = ($pageW - $totalW) / 2;
        $y       = $this->GetY();

        if ($hasLogo) {
            // imagem somente do path já verificado
            $this->Image($logo, $x, $y-2, $LOGO_SIZE_MM, $LOGO_SIZE_MM);
            $x += $LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0);
        }
        if ($nomeInst) {
            $this->SetXY($x, $y);
            $this->Cell($txtW, $LOGO_SIZE_MM, $txt, 0, 1, 'L');
        }

        // Subtítulo do relatório
        $this->Ln(3);
        $this->SetFont('Arial','B',13);
        $this->Cell(0,7, iconv('UTF-8','ISO-8859-1','Relatório de Professores com Aulas na Turma'), 0,1,'C');
        $this->Ln(1);
    }

    public function Footer()
    {
        // Rodapé: página (esq.) e data/hora (dir.)
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        // Esquerda: Página X
        $this->Cell(0,10, iconv('UTF-8','ISO-8859-1','Página ' . $this->PageNo()), 0, 0, 'L');
        // Direita: data/hora
        $this->Cell(0,10, iconv('UTF-8','ISO-8859-1','Impresso em: ' . date('d/m/Y H:i:s')), 0, 0, 'R');
    }
}

// ---------------- Execução do relatório (mantive lógica original) ----------------
$pdf = new PDFRelProfAulas('P','mm','A4');
$pdf->SetTitle(iconv('UTF-8','ISO-8859-1','Relatório de Professores com Aulas na Turma'));

// Consulta dos Professores com Aulas (usa campo nome_completo; filtra por professor se informado)
$sqlProf = "
    SELECT DISTINCT p.id_professor, p.nome_completo AS nome_professor, al.ano
      FROM professor p
      JOIN horario h ON p.id_professor = h.id_professor
      JOIN turma t ON h.id_turma = t.id_turma
      JOIN serie s ON t.id_serie = s.id_serie
      JOIN ano_letivo al ON t.id_ano_letivo = al.id_ano_letivo
     WHERE t.id_ano_letivo = :ano
";
$params = [':ano' => $id_ano_letivo];
if ($id_nivel_ensino > 0) {
    $sqlProf .= " AND s.id_nivel_ensino = :niv ";
    $params[':niv'] = $id_nivel_ensino;
}
if ($id_professor > 0) {
    $sqlProf .= " AND p.id_professor = :prof ";
    $params[':prof'] = $id_professor;
}
$sqlProf .= " ORDER BY p.nome_completo ";

$stP = $pdo->prepare($sqlProf);
$stP->execute($params);
$professores = $stP->fetchAll(PDO::FETCH_ASSOC);

if (!$professores) {
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0, 10, iconv('UTF-8','ISO-8859-1','Nenhum professor encontrado para os filtros.'), 0, 1, 'C');
    $pdf->Output('I','RelatorioProfessoresAulas.pdf');
    exit;
}

// Ordem padrão dos dias
$ordemDias = ['Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado'];

// Loop: Para Cada Professor, Monta o Relatório de Aulas
foreach ($professores as $prof) {

    // Consulta dos Horários do Professor
    $sqlH = "
        SELECT h.dia_semana, h.numero_aula, 
               s.nome_serie, t.nome_turma
          FROM horario h
          JOIN turma t ON h.id_turma = t.id_turma
          JOIN serie s ON t.id_serie = s.id_serie
         WHERE h.id_professor = :pid
           AND t.id_ano_letivo = :ano
    ";
    $pp = [':pid' => (int)$prof['id_professor'], ':ano' => $id_ano_letivo];
    if ($id_nivel_ensino > 0) {
        $sqlH .= " AND s.id_nivel_ensino = :niv ";
        $pp[':niv'] = $id_nivel_ensino;
    }
    $sqlH .= "
         ORDER BY FIELD(h.dia_semana, 'Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado'),
                  h.numero_aula
    ";
    $stmtH = $pdo->prepare($sqlH);
    $stmtH->execute($pp);
    $horarios = $stmtH->fetchAll(PDO::FETCH_ASSOC);

    // Nova página para o professor
    $pdf->AddPage();

    // Cabeçalho do professor (nome)
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,10, iconv('UTF-8','ISO-8859-1',$prof['nome_professor']), 0,1,'C');
    $pdf->Ln(2);

    // Se não houver horários, informa e segue
    if (!$horarios) {
        $pdf->SetFont('Arial','',12);
        $pdf->Cell(0,10, iconv('UTF-8','ISO-8859-1','Nenhuma aula encontrada.'), 0,1,'C');
        continue;
    }

    // Dias que ESSE professor realmente tem aula
    $diasProf = array_values(array_unique(array_column($horarios, 'dia_semana')));
    usort($diasProf, function($a,$b) use ($ordemDias) {
        return array_search($a,$ordemDias) <=> array_search($b,$ordemDias);
    });
    if (count($diasProf) === 0) {
        $pdf->SetFont('Arial','',12);
        $pdf->Cell(0,10, iconv('UTF-8','ISO-8859-1','Nenhum dia com aula para este professor.'), 0,1,'C');
        continue;
    }

    // Agrupa em matriz: [dia][aula] => ["Série Turma", ...] e encontra a última aula
    $dataMatrix = [];
    $maxAula = 0;
    foreach ($horarios as $hrow) {
        $dia = $hrow['dia_semana'];
        $na  = (int)$hrow['numero_aula'];
        $serieTurma = $hrow['nome_serie'] . ' ' . $hrow['nome_turma'];
        if (!isset($dataMatrix[$dia]))      $dataMatrix[$dia]      = [];
        if (!isset($dataMatrix[$dia][$na])) $dataMatrix[$dia][$na] = [];
        $dataMatrix[$dia][$na][] = $serieTurma;
        if ($na > $maxAula) $maxAula = $na;
    }

    // Monta a Tabela SOMENTE com $diasProf
    $pdf->SetFont('Arial','B',10);
    $pdf->SetFillColor(200,200,200);
    $marginLeft   = 10;
    $marginRight  = 10;
    $usableWidth  = $pdf->GetPageWidth() - $marginLeft - $marginRight;
    $colAulaW     = 20;
    $daysCount    = count($diasProf);
    $colDiaW      = $daysCount > 0 ? (($usableWidth - $colAulaW) / $daysCount) : ($usableWidth - $colAulaW);

    // Cabeçalho da Tabela
    $pdf->Cell($colAulaW, 8, iconv('UTF-8','ISO-8859-1','Aula/Dia'), 1, 0, 'C', true);
    foreach ($diasProf as $dia) {
        // sanitize label
        $dlabel = preg_replace('/[[:cntrl:]]+/', ' ', (string)$dia);
        $pdf->Cell($colDiaW, 8, iconv('UTF-8','ISO-8859-1',$dlabel), 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Linhas: 1ª aula até a última encontrada
    $pdf->SetFont('Arial','',9);
    for ($a = 1; $a <= $maxAula; $a++) {
        $pdf->Cell($colAulaW, 10, iconv('UTF-8','ISO-8859-1',$a.'ª Aula'), 1, 0, 'C');
        foreach ($diasProf as $dia) {
            $texto = '';
            if (isset($dataMatrix[$dia][$a])) {
                $texto = implode(", ", $dataMatrix[$dia][$a]);
            }
            // sanitize content
            $texto = preg_replace('/[[:cntrl:]]+/', ' ', (string)$texto);
            $pdf->Cell($colDiaW, 10, iconv('UTF-8','ISO-8859-1',$texto), 1, 0, 'C');
        }
        $pdf->Ln();
    }
}

// Finaliza e envia o PDF
$pdf->Output('I','RelatorioProfessoresAulas.pdf');
exit;
?>
