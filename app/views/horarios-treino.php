<?php
// app/views/horarios-treino.php
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

/**
 * Logger de segurança com tag [SEGURANCA]
 */
function seg_log(string $msg, array $meta = []) {
    $SEG_LOG = __DIR__ . '/../../logs/seguranca.log';
    $entry = '[' . date('d-M-Y H:i:s T') . '] [SEGURANCA] ' . $msg . ' | META=' . json_encode($meta, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    @file_put_contents($SEG_LOG, $entry, FILE_APPEND | LOCK_EX);
}

/**
 * Verifica se registro existe na tabela (campo = value).
 */
function exists_record(PDO $pdo, string $table, string $field, $value): bool {
    if (!$value) return false;
    $sql = "SELECT 1 FROM {$table} WHERE {$field} = :v LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([':v' => $value]);
    return (bool)$st->fetchColumn();
}

/* ---------------- 1) Parâmetros GET obrigatórios ---------------- */
$id_ano_letivo   = isset($_GET['id_ano_letivo'])   ? (int)$_GET['id_ano_letivo']   : 0;
$id_nivel_ensino = isset($_GET['id_nivel_ensino']) ? (int)$_GET['id_nivel_ensino'] : 0;
$id_turno        = isset($_GET['id_turno'])        ? (int)$_GET['id_turno']        : 0;

if (!$id_ano_letivo || !$id_nivel_ensino || !$id_turno) {
    seg_log('Tentativa de gerar horarios-treino sem parâmetros obrigatórios', [
        'id_ano_letivo'=>$id_ano_letivo,
        'id_nivel_ensino'=>$id_nivel_ensino,
        'id_turno'=>$id_turno,
        'usuario'=> $_SESSION['id_usuario'] ?? 0
    ]);
    header('HTTP/1.1 400 Bad Request');
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Parâmetros inválidos</title></head><body>';
    echo '<h2>Parâmetros inválidos</h2>';
    echo '<p>Verifique os parâmetros informados e tente novamente.</p>';
    echo '</body></html>';
    exit;
}

/* ---------------- 2) Parâmetros GET opcionais ---------------- */
$filterModalidade = isset($_GET['modalidade']) ? (int)$_GET['modalidade'] : 0;
$filterCategoria  = isset($_GET['categoria'])  ? (int)$_GET['categoria']  : 0;
$filterProfessor  = isset($_GET['professor'])  ? (int)$_GET['professor']  : 0;

/* ---------------- 3) Validar filtros ---------------- */
$invalidFilters = [];
if ($filterModalidade && !exists_record($pdo, 'modalidade', 'id_modalidade', $filterModalidade)) {
    $invalidFilters['modalidade'] = $filterModalidade;
    $filterModalidade = 0;
}
if ($filterCategoria && !exists_record($pdo, 'categoria', 'id_categoria', $filterCategoria)) {
    $invalidFilters['categoria'] = $filterCategoria;
    $filterCategoria = 0;
}
if ($filterProfessor && !exists_record($pdo, 'professor', 'id_professor', $filterProfessor)) {
    $invalidFilters['professor'] = $filterProfessor;
    $filterProfessor = 0;
}
if (!empty($invalidFilters)) {
    seg_log('Filtros ignorados por não existirem no BD ao gerar horarios-treino', [
        'ignorados' => $invalidFilters,
        'usuario' => $_SESSION['id_usuario'] ?? 0,
        'id_ano_letivo' => $id_ano_letivo,
        'id_nivel_ensino' => $id_nivel_ensino,
        'id_turno' => $id_turno
    ]);
}

/* ---------------- 4) Consulta ---------------- */
try {
    $where = "
        he.id_ano_letivo = :ano
        AND he.id_nivel_ensino = :nivel
        AND he.id_turno = :turno
    ";
    if ($filterModalidade) $where .= " AND he.id_modalidade = :modalidade ";
    if ($filterCategoria)  $where .= " AND he.id_categoria  = :categoria ";
    if ($filterProfessor)  $where .= " AND he.id_professor  = :professor ";

    $sql = "
        SELECT he.*,
               a.ano,
               ne.nome_nivel_ensino,
               mo.nome_modalidade,
               c.nome_categoria,
               COALESCE(p.nome_exibicao, p.nome_completo) AS nome_professor,
               t.nome_turno
          FROM horario_escolinha he
          JOIN ano_letivo       a  ON he.id_ano_letivo   = a.id_ano_letivo
          JOIN nivel_ensino     ne ON he.id_nivel_ensino = ne.id_nivel_ensino
          JOIN modalidade       mo ON he.id_modalidade   = mo.id_modalidade
          JOIN categoria        c  ON he.id_categoria    = c.id_categoria
          JOIN professor        p  ON he.id_professor    = p.id_professor
          JOIN turno            t  ON he.id_turno        = t.id_turno
         WHERE $where
         ORDER BY
           FIELD(he.dia_semana,'Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado'),
           he.hora_inicio
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':ano',   $id_ano_letivo, PDO::PARAM_INT);
    $stmt->bindValue(':nivel', $id_nivel_ensino, PDO::PARAM_INT);
    $stmt->bindValue(':turno', $id_turno, PDO::PARAM_INT);
    if ($filterModalidade) $stmt->bindValue(':modalidade', $filterModalidade, PDO::PARAM_INT);
    if ($filterCategoria)  $stmt->bindValue(':categoria',  $filterCategoria,  PDO::PARAM_INT);
    if ($filterProfessor)  $stmt->bindValue(':professor',  $filterProfessor,  PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    seg_log('Erro na consulta de horarios-treino: '.$e->getMessage(), [
        'usuario' => $_SESSION['id_usuario'] ?? 0,
        'query_params' => [
            'id_ano_letivo'=>$id_ano_letivo,
            'id_nivel_ensino'=>$id_nivel_ensino,
            'id_turno'=>$id_turno,
            'modalidade'=>$filterModalidade,
            'categoria'=>$filterCategoria,
            'professor'=>$filterProfessor
        ]
    ]);
    header('HTTP/1.1 500 Internal Server Error');
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Erro</title></head><body>';
    echo '<h2>Erro ao buscar dados.</h2>';
    echo '</body></html>';
    exit;
}

/* ---------------- 5) Cabeçalho (ano/turno) ---------------- */
$nomeTurno = '';
$anoLetivo = '';
if (!empty($rows)) {
    $nomeTurno = $rows[0]['nome_turno'] ?? '';
    $anoLetivo = $rows[0]['ano'] ?? '';
} else {
    $stm = $pdo->prepare("SELECT nome_turno FROM turno WHERE id_turno=? LIMIT 1");
    $stm->execute([$id_turno]);
    if ($r = $stm->fetch(PDO::FETCH_ASSOC)) $nomeTurno = $r['nome_turno'];

    $stm = $pdo->prepare("SELECT ano FROM ano_letivo WHERE id_ano_letivo=? LIMIT 1");
    $stm->execute([$id_ano_letivo]);
    if ($r = $stm->fetch(PDO::FETCH_ASSOC)) $anoLetivo = $r['ano'];
}

/* ---------------- 6) Dias verticais (completos como antes) ---------------- */
$diaVertical = [
    'Domingo' => "D\nO\nM\nI\nN\nG\nO",
    'Segunda' => "S\nE\nG\nU\nN\nD\nA",
    'Terca'   => "T\nE\nR\nÇ\nA",
    'Quarta'  => "Q\nU\nA\nR\nT\nA",
    'Quinta'  => "Q\nU\nI\nN\nT\nA",
    'Sexta'   => "S\nE\nX\nT\nA",
    'Sabado'  => "S\nA\nB\nA\nD\nO"
];

/* ---------------- 7) Sem registros ---------------- */
if (empty($rows)) {
    seg_log('Nenhum horário de treino encontrado para os filtros (exibição HTML).', [
        'usuario'=> $_SESSION['id_usuario'] ?? 0,
        'id_ano_letivo'=>$id_ano_letivo,
        'id_nivel_ensino'=>$id_nivel_ensino,
        'id_turno'=>$id_turno,
        'modalidade'=>$filterModalidade,
        'categoria'=>$filterCategoria,
        'professor'=>$filterProfessor
    ]);
    header('HTTP/1.1 200 OK');
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Nenhum horário encontrado</title></head><body>';
    echo '<h2>Nenhum horário de treino encontrado para os parâmetros informados.</h2>';
    echo '<p>Verifique os filtros e tente novamente.</p>';
    echo '</body></html>';
    exit;
}

/* ---------------- 8) PDF ---------------- */
$LOGO_SIZE_MM = 15;
$LOGO_GAP_MM  = 5;

class PDFHorariosTreino extends FPDF
{
    public function Header()
    {
        global $pdo, $anoLetivo, $nomeTurno, $LOGO_SIZE_MM, $LOGO_GAP_MM;

        // 2ª página em diante: sem logo/nome/título
        if ($this->PageNo() > 1) {
            $this->SetY(8);
            return;
        }

        $stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
        $inst = $stmtInst->fetch(PDO::FETCH_ASSOC);

        $nomeInst = $inst['nome_instituicao'] ?? '';
        $logoPath = (!empty($inst['imagem_instituicao'])) ? LOGO_PATH . '/' . basename($inst['imagem_instituicao']) : null;

        $this->SetY(10);
        $this->SetFont('Arial','B',13);

        $txt  = mb_convert_encoding($nomeInst, 'ISO-8859-1','UTF-8');
        $txtW = $this->GetStringWidth($txt);
        $hasLogo = ($logoPath && file_exists($logoPath));
        $totalW  = $hasLogo ? ($LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0) + $txtW) : $txtW;
        $pageW   = $this->GetPageWidth();
        $x       = ($pageW - $totalW) / 2;
        $y       = $this->GetY();

        if ($hasLogo) {
            $this->Image($logoPath, $x, $y-2, $LOGO_SIZE_MM, $LOGO_SIZE_MM);
            $x += $LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0);
        }

        if ($nomeInst) {
            $this->SetXY($x, $y);
            $this->Cell($txtW, $LOGO_SIZE_MM, $txt, 0, 1, 'L');
        }

        $this->Ln(2);
        $this->SetFont('Arial','B',9);
        $linha = 'Ano Letivo ' . ($anoLetivo ?: '—') . ' | Horários de Treino ' . trim($nomeTurno ?: '');
        $this->Cell(0, 6, mb_convert_encoding($linha, 'ISO-8859-1','UTF-8'), 0, 1, 'C');
        $this->Ln(2);
    }

    public function Footer()
    {
        $this->SetY(-12);
        $this->SetFont('Arial','I',7);
        $this->Cell(0, 5, mb_convert_encoding('Página '.$this->PageNo(),'ISO-8859-1','UTF-8'), 0, 0, 'L');
        $this->Cell(0, 5, mb_convert_encoding('Impresso em: '.date('d/m/Y H:i:s'),'ISO-8859-1','UTF-8'), 0, 0, 'R');
    }

    public function checkPageBreak($height)
    {
        return ($this->GetY() + $height > $this->GetPageHeight() - 14);
    }
}

/* Suprime notice específico do iconv() */
$previousErrorHandler = set_error_handler(function($errno, $errstr) {
    if ($errno === E_NOTICE && strpos($errstr, 'iconv(): Detected an illegal character in input string') !== false) {
        return true;
    }
    return false;
});

$pdf = new PDFHorariosTreino('P','mm','A4'); // RETRATO
$pdf->SetMargins(6, 8, 6);                   // margens menores para aproveitar folha
$pdf->SetAutoPageBreak(true, 12);
$pdf->SetTitle(mb_convert_encoding('Horários de Treino','ISO-8859-1','UTF-8'));
$pdf->AddPage();
$pdf->SetFont('Arial','',8.5);

/* Colunas em retrato (A4 útil ~198mm com margens 6) */
$colDiaW       = 10;
$colHorarioW   = 24;
$colModCatW    = 82; // Modalidade + Categoria juntas
$colProfessorW = 82; // Professor
$lineH         = 7; // compacta

function imprimirCabecalhoColunas($pdf, $colDiaW, $colHorarioW, $colModCatW, $colProfessorW, $lineH)
{
    $pdf->SetFont('Arial','B',8);
    $pdf->SetFillColor(220,220,220);

    $pdf->Cell($colDiaW,       $lineH, mb_convert_encoding('Dia','ISO-8859-1','UTF-8'), 1, 0, 'C', true);
    $pdf->Cell($colHorarioW,   $lineH, mb_convert_encoding('Horário','ISO-8859-1','UTF-8'), 1, 0, 'C', true);
    $pdf->Cell($colModCatW,    $lineH, mb_convert_encoding('Modalidade / Categoria','ISO-8859-1','UTF-8'), 1, 0, 'C', true);
    $pdf->Cell($colProfessorW, $lineH, mb_convert_encoding('Professor','ISO-8859-1','UTF-8'), 1, 1, 'C', true);

    $pdf->SetFont('Arial','',8.5);
}

function fitText(FPDF $pdf, string $text, float $maxW): string
{
    if ($pdf->GetStringWidth($text) <= $maxW) return $text;
    $txt = $text;
    while (mb_strlen($txt, 'UTF-8') > 1 && $pdf->GetStringWidth($txt . '...') > $maxW) {
        $txt = mb_substr($txt, 0, mb_strlen($txt, 'UTF-8') - 1, 'UTF-8');
    }
    return $txt . '...';
}

/* Agrupa por dia */
$grupoPorDia = [];
foreach ($rows as $r) {
    $grupoPorDia[$r['dia_semana']][] = $r;
}

$espaco_entre_dias = 2;

foreach ($grupoPorDia as $diaSemana => $registrosDia) {
    $numRegDia = count($registrosDia);
    $alturaGrupo = $lineH + ($numRegDia * $lineH) + $espaco_entre_dias;

    if ($pdf->checkPageBreak($alturaGrupo)) {
        $pdf->AddPage();
    }

    imprimirCabecalhoColunas($pdf, $colDiaW, $colHorarioW, $colModCatW, $colProfessorW, $lineH);

    $boxHeight = $numRegDia * $lineH;
    $x0 = $pdf->GetX();
    $y0 = $pdf->GetY();

    // Coluna Dia (vertical)
    $pdf->Rect($x0, $y0, $colDiaW, $boxHeight);

    $pdf->SetFont('Arial','',7);
    $verticalText = $diaVertical[$diaSemana] ?? $diaSemana;
    $pdf->SetXY($x0, $y0);

    $numLines   = count(explode("\n", $verticalText));
    $textHeight = $numLines * 3.0;
    $startY     = $y0 + ($boxHeight/2) - ($textHeight/2);

    $pdf->SetXY($x0, $startY);
    $pdf->MultiCell($colDiaW, 3.0, mb_convert_encoding($verticalText,'ISO-8859-1','UTF-8'), 0, 'C');

    // Linhas
    $pdf->SetFont('Arial','',8.5);
    $pdf->SetXY($x0 + $colDiaW, $y0);

    foreach ($registrosDia as $r) {
        $horaI = substr($r['hora_inicio'], 0, 5);
        $horaF = substr($r['hora_fim'],   0, 5);
        $horario = $horaI.' - '.$horaF;

        $modCatRaw = trim(($r['nome_modalidade'] ?? '') . ' ' . ($r['nome_categoria'] ?? ''));
        $profRaw   = trim($r['nome_professor'] ?? '');

        $modCatIso = mb_convert_encoding($modCatRaw, 'ISO-8859-1','UTF-8');
        $profIso   = mb_convert_encoding($profRaw,   'ISO-8859-1','UTF-8');

        $modCat = fitText($pdf, $modCatIso, $colModCatW - 2);
        $prof   = fitText($pdf, $profIso,   $colProfessorW - 2);

        $pdf->Cell($colHorarioW,   $lineH, mb_convert_encoding($horario, 'ISO-8859-1','UTF-8'), 1, 0, 'C');
        $pdf->Cell($colModCatW,    $lineH, $modCat, 1, 0, 'L');
        $pdf->Cell($colProfessorW, $lineH, $prof,   1, 1, 'L');

        $pdf->SetX($x0 + $colDiaW);
    }

    $pdf->Ln($espaco_entre_dias);
}

/* Restaura handler */
if ($previousErrorHandler !== null) {
    set_error_handler($previousErrorHandler);
} else {
    restore_error_handler();
}

$pdf->Output('I','horarios-treino.pdf');
exit;
?>