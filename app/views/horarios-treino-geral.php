<?php
// /horarios/app/views/horarios-treino-geral.php
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
$allowed = [
    'id_ano_letivo',
    'id_turno',
    'id_modalidade',
    'id_categoria',
    'id_professor',
    'id_nivel_ensino',
    'orient'
];
$rawQuery = $_SERVER['QUERY_STRING'] ?? '';
parse_str($rawQuery, $receivedParams);

// Rejeita parâmetros inesperados
$receivedKeys = array_keys($receivedParams);
$extra = array_diff($receivedKeys, $allowed);
if (!empty($extra)) {
    logSecurity('Parâmetros inesperados em horarios-treino-geral: ' . implode(', ', $extra) . ' | raw_qs=' . $rawQuery);
    abortClient();
}

// Normalizadores e validadores
$canonical = [];

// inteiros não-negativos
$intParams = ['id_ano_letivo','id_turno','id_modalidade','id_categoria','id_professor','id_nivel_ensino'];
foreach ($intParams as $p) {
    if (array_key_exists($p, $receivedParams)) {
        $raw = $receivedParams[$p];
        if (!is_scalar($raw) || !preg_match('/^\d+$/', (string)$raw)) {
            logSecurity("$p inválido em horarios-treino-geral: raw=" . var_export($raw, true) . " | raw_qs={$rawQuery}");
            abortClient();
        }
        $ival = (int)$raw;
        if ($ival < 0) {
            logSecurity("$p negativo em horarios-treino-geral: raw=" . var_export($raw, true) . " | raw_qs={$rawQuery}");
            abortClient();
        }
        $canonical[$p] = $ival;
    }
}

// orient: opcional, aceitar apenas 'r' ou 'p'
if (array_key_exists('orient', $receivedParams)) {
    $raw = $receivedParams['orient'];
    if (!is_scalar($raw) || !preg_match('/^[pr]$/i', (string)$raw)) {
        logSecurity("orient inválido em horarios-treino-geral: raw=" . var_export($raw, true) . " | raw_qs={$rawQuery}");
        abortClient();
    }
    $canonical['orient'] = strtolower((string)$raw) === 'p' ? 'p' : 'r';
}

// Ordenar para comparação canônica e comparar
ksort($canonical);
$normalized_received_array = [];
foreach ($receivedParams as $k => $v) {
    if (in_array($k, $intParams, true)) {
        $normalized_received_array[$k] = (int)$v;
    } elseif ($k === 'orient') {
        $normalized_received_array[$k] = strtolower((string)$v);
    } else {
        // não deveria ocorrer, mas defensivo
        $normalized_received_array[$k] = $v;
    }
}
ksort($normalized_received_array);

if ($canonical !== $normalized_received_array) {
    logSecurity("Query string não canônica em horarios-treino-geral: expected_array=" . json_encode($canonical) . " got_array=" . json_encode($normalized_received_array) . " | full_raw={$rawQuery}");
    abortClient();
}

// id_ano_letivo obrigatório
if (!isset($canonical['id_ano_letivo']) || $canonical['id_ano_letivo'] <= 0) {
    logSecurity("id_ano_letivo ausente em horarios-treino-geral | raw_qs={$rawQuery}");
    abortClient('Parâmetro ano letivo é obrigatório para gerar PDF.');
}

// ---------------- Verificações de existência no banco ----------------
try {
    // ano letivo
    $st = $pdo->prepare("SELECT 1 FROM ano_letivo WHERE id_ano_letivo = :id LIMIT 1");
    $st->execute([':id' => $canonical['id_ano_letivo']]);
    if (!$st->fetchColumn()) {
        logSecurity("id_ano_letivo inexistente em horarios-treino-geral: id=" . $canonical['id_ano_letivo'] . " | raw_qs={$rawQuery}");
        abortClient();
    }

    // checar opcionais se informados
    $checkMap = [
        'id_turno' => ['table'=>'turno','col'=>'id_turno'],
        'id_modalidade' => ['table'=>'modalidade','col'=>'id_modalidade'],
        'id_categoria' => ['table'=>'categoria','col'=>'id_categoria'],
        'id_professor' => ['table'=>'professor','col'=>'id_professor'],
        'id_nivel_ensino' => ['table'=>'nivel_ensino','col'=>'id_nivel_ensino']
    ];
    foreach ($checkMap as $param => $meta) {
        if (isset($canonical[$param]) && $canonical[$param] > 0) {
            $st = $pdo->prepare("SELECT 1 FROM {$meta['table']} WHERE {$meta['col']} = :id LIMIT 1");
            $st->execute([':id' => $canonical[$param]]);
            if (!$st->fetchColumn()) {
                logSecurity("$param inexistente em horarios-treino-geral: id=" . $canonical[$param] . " tabela={$meta['table']} | raw_qs={$rawQuery}");
                abortClient();
            }
        }
    }
} catch (Throwable $e) {
    logSecurity("Erro SQL validando IDs em horarios-treino-geral: " . $e->getMessage() . " | raw_qs={$rawQuery}");
    abortClient();
}

// ---------------- FIM camada de segurança ----------------

// Atribuir variáveis seguras (com defaults)
$id_ano_letivo   = $canonical['id_ano_letivo'];
$id_turno        = $canonical['id_turno'] ?? 0;
$id_modalidade   = $canonical['id_modalidade'] ?? 0;
$id_categoria    = $canonical['id_categoria'] ?? 0;
$id_professor    = $canonical['id_professor'] ?? 0;
$id_nivel_ensino = $canonical['id_nivel_ensino'] ?? 0;
$orient_raw      = $canonical['orient'] ?? 'r';
$orientation     = ($orient_raw === 'p') ? 'L' : 'P';

// ------------------- continua sua lógica original -------------------
// Now proceed with building query and producing PDF as before

try {
    // Monta WHERE
    $where = "he.id_ano_letivo = :ano";
    $params = [':ano' => $id_ano_letivo];

    if ($id_turno) {
        $where .= " AND he.id_turno = :turno";
        $params[':turno'] = $id_turno;
    }
    if ($id_modalidade) {
        $where .= " AND he.id_modalidade = :modalidade";
        $params[':modalidade'] = $id_modalidade;
    }
    if ($id_categoria) {
        $where .= " AND he.id_categoria = :categoria";
        $params[':categoria'] = $id_categoria;
    }
    if ($id_professor) {
        $where .= " AND he.id_professor = :professor";
        $params[':professor'] = $id_professor;
    }
    if ($id_nivel_ensino) {
        $where .= " AND he.id_nivel_ensino = :nivel";
        $params[':nivel'] = $id_nivel_ensino;
    }

    $sql = "
        SELECT he.*,
               a.ano,
               ne.nome_nivel_ensino,
               mo.nome_modalidade,
               c.nome_categoria,
               COALESCE(p.nome_exibicao,p.nome_completo) as nome_professor,
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
    foreach ($params as $key => $value) {
        // todos os valores aqui são inteiros validados
        $stmt->bindValue($key, (int)$value, PDO::PARAM_INT);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    logSecurity("Erro ao buscar dados em horarios-treino-geral: " . $e->getMessage() . " | raw_qs={$rawQuery}");
    abortClient('Erro ao buscar dados.');
}

// Descobrir nomes para o cabeçalho
$nomeTurno = '';
$anoLetivo = '';
$nomeModalidade = '';
$nomeCategoria = '';
$nomeProfessor = '';

if (!empty($rows)) {
    $nomeTurno = $rows[0]['nome_turno'] ?? '';
    $anoLetivo = $rows[0]['ano'] ?? '';
    if ($id_modalidade) $nomeModalidade = $rows[0]['nome_modalidade'] ?? '';
    if ($id_categoria)  $nomeCategoria  = $rows[0]['nome_categoria']  ?? '';
    if ($id_professor)  $nomeProfessor  = $rows[0]['nome_professor']  ?? '';
} else {
    if ($id_ano_letivo) {
        $stmAno = $pdo->prepare("SELECT ano FROM ano_letivo WHERE id_ano_letivo=? LIMIT 1");
        $stmAno->execute([$id_ano_letivo]);
        if ($rA = $stmAno->fetch(PDO::FETCH_ASSOC)) $anoLetivo = $rA['ano'];
    }
    if ($id_turno) {
        $stmTurno = $pdo->prepare("SELECT nome_turno FROM turno WHERE id_turno=? LIMIT 1");
        $stmTurno->execute([$id_turno]);
        if ($rT = $stmTurno->fetch(PDO::FETCH_ASSOC)) $nomeTurno = $rT['nome_turno'];
    }
    if ($id_modalidade) {
        $stmModalidade = $pdo->prepare("SELECT nome_modalidade FROM modalidade WHERE id_modalidade=? LIMIT 1");
        $stmModalidade->execute([$id_modalidade]);
        if ($rM = $stmModalidade->fetch(PDO::FETCH_ASSOC)) $nomeModalidade = $rM['nome_modalidade'];
    }
    if ($id_categoria) {
        $stmCategoria = $pdo->prepare("SELECT nome_categoria FROM categoria WHERE id_categoria=? LIMIT 1");
        $stmCategoria->execute([$id_categoria]);
        if ($rC = $stmCategoria->fetch(PDO::FETCH_ASSOC)) $nomeCategoria = $rC['nome_categoria'];
    }
    if ($id_professor) {
        $stmProfessor = $pdo->prepare("SELECT COALESCE(nome_exibicao,nome_completo) as nome_professor FROM professor WHERE id_professor=? LIMIT 1");
        $stmProfessor->execute([$id_professor]);
        if ($rP = $stmProfessor->fetch(PDO::FETCH_ASSOC)) $nomeProfessor = $rP['nome_professor'];
    }
}

// Verifica se há filtros específicos
$filtrosEspecificosAtivos = ($id_modalidade || $id_categoria || $id_professor);

// Mapeamentos de dias
$diaNormal = [
    'Domingo' => "Domingo",
    'Segunda' => "Segunda",
    'Terca'   => "Terça",
    'Quarta'  => "Quarta",
    'Quinta'  => "Quinta",
    'Sexta'   => "Sexta",
    'Sabado'  => "Sábado"
];
$diaVertical = [
    'Domingo' => "D\nO\nM\nI\nN\nG\nO",
    'Segunda' => "S\nE\nG\nU\nN\nD\nA",
    'Terca'   => "T\nE\nR\nÇ\nA",
    'Quarta'  => "Q\nU\nA\nR\nT\nA",
    'Quinta'  => "Q\nU\nI\nN\nT\nA",
    'Sexta'   => "S\nE\nX\nT\nA",
    'Sabado'  => "S\nA\nB\nA\nD\nO"
];

// Agrupa por dia
$grupoPorDia = [];
foreach ($rows as $r) {
    $dia = (string)$r['dia_semana'];
    $grupoPorDia[$dia][] = $r;
}

// ---------------- Parâmetros do cabeçalho padronizado ----------------
$LOGO_SIZE_MM = 15; // tamanho da logo (mm)
$LOGO_GAP_MM  = 5;  // espaço entre logo e texto (mm)

// Classe PDF com cabeçalho padronizado (mantida igual)
class PDFHorariosTreinoGeral extends FPDF
{
    protected $isPrimeiraPageina = true;
    private $nomeTurno = '';
    private $anoLetivo = '';
    private $nomeModalidade = '';
    private $nomeCategoria = '';
    private $nomeProfessor = '';

    public function __construct($orientation, $unit, $size, $nomeTurno, $anoLetivo, $nomeModalidade = '', $nomeCategoria = '', $nomeProfessor = '')
    {
        parent::__construct($orientation, $unit, $size);
        $this->nomeTurno     = $nomeTurno;
        $this->anoLetivo     = $anoLetivo;
        $this->nomeModalidade= $nomeModalidade;
        $this->nomeCategoria = $nomeCategoria;
        $this->nomeProfessor = $nomeProfessor;
    }

    public function Header()
    {
        global $pdo, $LOGO_SIZE_MM, $LOGO_GAP_MM;

        // Busca instituição
        $stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
        $inst = $stmtInst->fetch(PDO::FETCH_ASSOC);

        $nomeInst = $inst ? ($inst['nome_instituicao'] ?? '') : '';
        $logoPath = ($inst && !empty($inst['imagem_instituicao'])) ? LOGO_PATH.'/'.basename($inst['imagem_instituicao']) : null;

        // Linha única: logo + nome centralizados
        $this->SetY(12);
        $this->SetFont('Arial','B',14);
        $txt  = iconv('UTF-8','ISO-8859-1', $nomeInst);
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

        // Linha de informações
        $this->Ln(3);
        $this->SetFont('Arial','B',11);

        $info = 'Ano Letivo';
        if (!empty($this->anoLetivo)) {
            $info .= ' ' . $this->anoLetivo;
        }
        $info .= ' | Horários de Treino';
        if (!empty($this->nomeTurno)) {
            $info .= ' ' . $this->nomeTurno;
        }

        $this->Cell(0, 7, iconv('UTF-8','ISO-8859-1', $info), 0, 1, 'C');
        $this->Ln(6);

        // filtros extras
        $filtrosExtras = [];
        if (!empty($this->nomeModalidade)) $filtrosExtras[] = 'Modalidade: ' . $this->nomeModalidade;
        if (!empty($this->nomeCategoria))  $filtrosExtras[] = 'Categoria: '  . $this->nomeCategoria;
        if (!empty($this->nomeProfessor))  $filtrosExtras[] = 'Professor: '  . $this->nomeProfessor;

        if (!empty($filtrosExtras)) {
            $this->SetFont('Arial','B',10);
            $this->Cell(0, 6, iconv('UTF-8','ISO-8859-1', implode(' | ', $filtrosExtras)), 0, 1, 'C');
            $this->Ln(4);
        }
    }

    public function Footer()
    {
        // Rodapé
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10, iconv('UTF-8','ISO-8859-1','Página ' . $this->PageNo()), 0, 0, 'L');
        $this->Cell(0,10, iconv('UTF-8','ISO-8859-1','Impresso em: ' . date('d/m/Y H:i:s')), 0, 0, 'R');
    }

    // Método para verificar se um bloco cabe na página
    public function checkPageBreak($height)
    {
        if ($this->GetY() + $height > $this->GetPageHeight() - 20) {
            return true;
        }
        return false;
    }
}

// Instancia o PDF
$pdf = new PDFHorariosTreinoGeral(
    $orientation,
    'mm',
    'A4',
    $nomeTurno,
    $anoLetivo,
    $nomeModalidade,
    $nomeCategoria,
    $nomeProfessor
);
$pdf->SetTitle(mb_convert_encoding('Horários de Treino Geral','ISO-8859-1','UTF-8'));
$pdf->AddPage();
$pdf->SetFont('Arial','',9);

if (empty($rows)) {
    $pdf->Cell(0, 8, mb_convert_encoding('Nenhum horário encontrado.','ISO-8859-1','UTF-8'), 0, 1, 'C');
    $pdf->Output();
    exit;
}

// Se filtros específicos (modalidade/categoria/professor) ativos: layout por dia
if ($filtrosEspecificosAtivos) {
    $primeiroDia = true;
    foreach ($grupoPorDia as $diaSemana => $registrosDia) {
        if (!$primeiroDia) $pdf->Ln(10);
        $primeiroDia = false;

        // Título do dia
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 8, mb_convert_encoding($diaNormal[$diaSemana] ?? $diaSemana, 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');

        // Cabeçalho tabela
        $pageWidth = $pdf->GetPageWidth() - 20;
        $pdf->SetFont('Arial','B',9);
        $pdf->SetFillColor(220,220,220);
        $pdf->Cell($pageWidth * 0.15, 8, mb_convert_encoding('Horário','ISO-8859-1','UTF-8'), 1, 0, 'C', true);
        $pdf->Cell($pageWidth * 0.30, 8, mb_convert_encoding('Modalidade','ISO-8859-1','UTF-8'), 1, 0, 'C', true);
        $pdf->Cell($pageWidth * 0.30, 8, mb_convert_encoding('Categoria','ISO-8859-1','UTF-8'), 1, 0, 'C', true);
        $pdf->Cell($pageWidth * 0.25, 8, mb_convert_encoding('Professor','ISO-8859-1','UTF-8'), 1, 1, 'C', true);

        $pdf->SetFont('Arial','',9);
        foreach ($registrosDia as $r) {
            $horaI = substr($r['hora_inicio'], 0, 5);
            $horaF = substr($r['hora_fim'], 0, 5);
            $horario = "$horaI - $horaF";
            $mod  = mb_convert_encoding($r['nome_modalidade'], 'ISO-8859-1','UTF-8');
            $cat  = mb_convert_encoding($r['nome_categoria'],  'ISO-8859-1','UTF-8');
            $prof = mb_convert_encoding($r['nome_professor'],  'ISO-8859-1','UTF-8');

            $pdf->Cell($pageWidth * 0.15, 8, $horario, 1, 0, 'C');
            $pdf->Cell($pageWidth * 0.30, 8, $mod,     1, 0, 'C');
            $pdf->Cell($pageWidth * 0.30, 8, $cat,     1, 0, 'C');
            $pdf->Cell($pageWidth * 0.25, 8, $prof,    1, 1, 'C');
        }
    }

} else {
    // Layout vertical original (sem filtros específicos)
    $colDiaW        = 10;
    $colHorarioW    = 30;
    $colModalidadeW = 35;
    $colCategoriaW  = 35;
    $colProfessorW  = 80;
    $lineH          = 8;
    $espaco_entre_dias = 5;

    function imprimirCabecalhoColunas($pdf, $colDiaW, $colHorarioW, $colModalidadeW, $colCategoriaW, $colProfessorW, $lineH)
    {
        $pdf->SetFont('Arial','B',9);
        $pdf->SetFillColor(220,220,220);
        $pdf->Cell($colDiaW,        $lineH, mb_convert_encoding('Dia','ISO-8859-1','UTF-8'),       1,0,'C',true);
        $pdf->Cell($colHorarioW,    $lineH, mb_convert_encoding('Horário','ISO-8859-1','UTF-8'),   1,0,'C',true);
        $pdf->Cell($colModalidadeW, $lineH, mb_convert_encoding('Modalidade','ISO-8859-1','UTF-8'),1,0,'C',true);
        $pdf->Cell($colCategoriaW,  $lineH, mb_convert_encoding('Categoria','ISO-8859-1','UTF-8'), 1,0,'C',true);
        $pdf->Cell($colProfessorW,  $lineH, mb_convert_encoding('Professor','ISO-8859-1','UTF-8'), 1,1,'C',true);
        $pdf->SetFont('Arial','',9);
    }

    foreach ($grupoPorDia as $diaSemana => $registrosDia) {
        $numRegDia   = count($registrosDia);
        $boxHeight   = ($numRegDia * $lineH) + $lineH;
        $alturaTotal = $boxHeight + $espaco_entre_dias;

        if ($pdf->checkPageBreak($alturaTotal)) {
            $pdf->AddPage();
        }

        imprimirCabecalhoColunas($pdf, $colDiaW, $colHorarioW, $colModalidadeW, $colCategoriaW, $colProfessorW, $lineH);

        $boxHeight = $numRegDia * $lineH;
        $x0 = $pdf->GetX();
        $y0 = $pdf->GetY();

        // coluna do dia (vertical)
        $pdf->Rect($x0, $y0, $colDiaW, $boxHeight);
        $pdf->SetFont('Arial','',8);
        $verticalText = $diaVertical[$diaSemana] ?? $diaSemana;
        $pdf->SetXY($x0, $y0);
        $numLines   = count(explode("\n", $verticalText));
        $textHeight = $numLines * 3.5;
        $startY     = $y0 + ($boxHeight/2) - ($textHeight/2);
        $pdf->SetXY($x0, $startY);
        $pdf->MultiCell($colDiaW, 3.5, mb_convert_encoding($verticalText,'ISO-8859-1','UTF-8'), 0, 'C');

        // linhas do dia
        $pdf->SetFont('Arial','',9);
        $pdf->SetXY($x0 + $colDiaW, $y0);
        foreach ($registrosDia as $r) {
            $horaI = substr($r['hora_inicio'],0,5);
            $horaF = substr($r['hora_fim'],   0,5);
            $horario = $horaI.' - '.$horaF;

            $mod  = mb_convert_encoding($r['nome_modalidade'], 'ISO-8859-1','UTF-8');
            $cat  = mb_convert_encoding($r['nome_categoria'],  'ISO-8859-1','UTF-8');
            $prof = mb_convert_encoding($r['nome_professor'],  'ISO-8859-1','UTF-8');

            $pdf->Cell($colHorarioW,    $lineH, $horario, 1, 0, 'C');
            $pdf->Cell($colModalidadeW, $lineH, $mod,     1, 0, 'C');
            $pdf->Cell($colCategoriaW,  $lineH, $cat,     1, 0, 'C');
            $pdf->Cell($colProfessorW,  $lineH, $prof,    1, 1, 'C');

            $pdf->SetX($x0 + $colDiaW);
        }
        $pdf->Ln($espaco_entre_dias);
    }
}

// Emite o PDF
$pdf->Output();
exit;
?>
