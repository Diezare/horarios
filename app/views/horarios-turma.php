<?php
// app/views/horarios-turma.php - VERSÃO SIMPLIFICADA
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

$PARAMS_ESPERADOS = ['id_turma', 'orient'];
$LOG_PATH = __DIR__ . '/../../logs/seguranca.log';

function logSecurity(string $msg): void {
    global $LOG_PATH;
    $meta = [
        'ts' => date('c'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'qs' => $_SERVER['QUERY_STRING'] ?? '',
        'script' => basename($_SERVER['SCRIPT_NAME'] ?? '')
    ];
    $entry = '[' . date('d-M-Y H:i:s T') . '] [SEGURANCA] ' . $msg . ' | META=' . json_encode($meta, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    $dir = dirname($LOG_PATH);
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    @file_put_contents($LOG_PATH, $entry, FILE_APPEND | LOCK_EX);
}

function abortInvalid(): void {
    http_response_code(400);
    die('Parâmetros inválidos');
}

function abortNotFound(): void {
    http_response_code(404);
    die('Recurso não encontrado');
}

function abortServer(): void {
    http_response_code(500);
    die('Erro interno');
}

// ✅ FUNÇÃO IDÊNTICA À DO OUTRO RELATÓRIO
function enc($s) {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$s);
}

/* -------------- WHITELIST --------------- */
$extras = array_diff(array_keys($_GET), $PARAMS_ESPERADOS);
if (!empty($extras)) {
    logSecurity('Parâmetros inesperados em horarios-turma: ' . implode(', ', $extras));
    abortInvalid();
}

/* -------------- LER E VALIDAR ------------- */
$id_turma = filter_input(INPUT_GET, 'id_turma', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id_turma) {
    logSecurity('id_turma inválido/ausente (horarios-turma)');
    abortInvalid();
}

$orient = 'P';
if (isset($_GET['orient']) && strtolower($_GET['orient']) === 'landscape') {
    $orient = 'L';
}

/* -------------- CONSULTAS -------------- */
try {
    // Informações da instituição
    $stmtInst = $pdo->prepare("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
    $stmtInst->execute();
    $inst = $stmtInst->fetch(PDO::FETCH_ASSOC) ?: [];

    // Informações da turma
    $sqlTurma = "
        SELECT 
            t.*,
            s.nome_serie,
            a.ano,
            n.nome_nivel_ensino,
            turn.nome_turno,
            t.intervalos_positions
        FROM turma t
        JOIN serie s ON t.id_serie = s.id_serie
        JOIN nivel_ensino n ON s.id_nivel_ensino = n.id_nivel_ensino
        JOIN ano_letivo a ON t.id_ano_letivo = a.id_ano_letivo
        JOIN turno turn ON t.id_turno = turn.id_turno
        WHERE t.id_turma = ?
        LIMIT 1
    ";
    $stmtT = $pdo->prepare($sqlTurma);
    $stmtT->execute([$id_turma]);
    $turma = $stmtT->fetch(PDO::FETCH_ASSOC);

    if (!$turma) {
        logSecurity("Turma não encontrada: id_turma={$id_turma}");
        abortNotFound();
    }

    // Horários da turma
    $sqlHorarios = "
        SELECT 
            h.dia_semana,
            h.numero_aula,
            d.nome_disciplina,
            COALESCE(p.nome_exibicao, p.nome_completo) AS professor
        FROM horario h
        JOIN disciplina d ON h.id_disciplina = d.id_disciplina
        JOIN professor p ON h.id_professor = p.id_professor
        WHERE h.id_turma = ?
        ORDER BY 
            FIELD(h.dia_semana, 'Segunda','Terca','Quarta','Quinta','Sexta','Sabado','Domingo'),
            h.numero_aula
    ";
    $stmtH = $pdo->prepare($sqlHorarios);
    $stmtH->execute([$id_turma]);
    $horarios = $stmtH->fetchAll(PDO::FETCH_ASSOC);

    // Dias do turno
    $sqlDiasTurno = "
        SELECT dia_semana, aulas_no_dia
        FROM turno_dias
        WHERE id_turno = ?
        ORDER BY FIELD(dia_semana, 'Segunda','Terca','Quarta','Quinta','Sexta','Sabado','Domingo')
    ";
    $stmtDias = $pdo->prepare($sqlDiasTurno);
    $stmtDias->execute([$turma['id_turno']]);
    $turnoDias = $stmtDias->fetchAll(PDO::FETCH_ASSOC);

    if (!$turnoDias) {
        logSecurity("Turno sem dias configurados: id_turno={$turma['id_turno']}");
        abortServer();
    }

} catch (Exception $e) {
    logSecurity("Erro SQL horarios-turma: " . $e->getMessage());
    abortServer();
}

/* -------------- PDF CLASS -------------- */
class PDF_Horarios extends FPDF
{
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, enc('Página ' . $this->PageNo()), 0, 0, 'L');
        $this->Cell(0, 10, enc('Impresso em: ' . date('d/m/Y H:i:s')), 0, 0, 'R');
    }
}

/* -------------- HEADER HELPER -------------- */
function headerRender(PDF_Horarios $pdf, string $nome, ?string $logo, float $size = 15, float $gap = 5)
{
    $topY = 12;
    $pdf->SetFont('Arial', 'B', 14);
    
    $txt = enc($nome);
    $txtW = $pdf->GetStringWidth($txt);
    
    $hasLogo = ($logo && file_exists($logo));
    $totalW = $hasLogo ? ($size + $gap + $txtW) : $txtW;
    $xStart = ($pdf->GetPageWidth() - $totalW) / 2;
    
    if ($hasLogo) {
        // Verifica segurança do caminho
        $realLogo = realpath($logo);
        $realLogoDir = realpath(LOGO_PATH);
        if ($realLogo && $realLogoDir && strpos($realLogo, $realLogoDir) === 0) {
            $pdf->Image($realLogo, $xStart, $topY, $size, $size);
            $pdf->SetXY($xStart + $size + $gap, $topY + 3);
        } else {
            $hasLogo = false;
            $pdf->SetXY($xStart, $topY);
        }
    } else {
        $pdf->SetXY($xStart, $topY);
    }
    
    if ($txt !== '') {
        $pdf->Cell($txtW + 1, 8, $txt, 0, 1, 'L');
    }
    
    $pdf->SetY(max($topY + ($hasLogo ? $size : 0), $topY + 8) + 6);
}

/* -------------- GERA PDF -------------- */
$pdf = new PDF_Horarios($orient, 'mm', 'A4');
$pdf->SetTitle(enc('Relatório de Horários da Turma'));
$pdf->AddPage();

// Nome da instituição e logo
$nomeInstituicao = $inst['nome_instituicao'] ?? '';
$logoRaw = $inst['imagem_instituicao'] ?? null;
$logoPath = null;

if ($logoRaw) {
    $safeLogoName = basename($logoRaw);
    $logoPath = LOGO_PATH . '/' . $safeLogoName;
    
    // Verificação de segurança
    $realLogo = realpath($logoPath);
    $realLogoDir = realpath(LOGO_PATH);
    
    if (!$realLogo || !$realLogoDir || strpos($realLogo, $realLogoDir) !== 0 || !file_exists($realLogo)) {
        $logoPath = null;
    }
}

headerRender($pdf, $nomeInstituicao, $logoPath);

// Informações da turma
$pdf->SetFont('Arial', 'B', 12);
$infoLinha = enc('Ano Letivo ' . ($turma['ano'] ?? '') . ' | Horário da Turma: ' . 
                 ($turma['nome_serie'] ?? '') . ' ' . ($turma['nome_turma'] ?? ''));
$pdf->Cell(0, 7, $infoLinha, 0, 1, 'C');
$pdf->Ln(8);

/* -------------- MONTAR MATRIZ DE HORÁRIOS -------------- */
$diasRelatorio = [];
$matrix = [];
$maxPorDia = [];

foreach ($turnoDias as $td) {
    $aulasNoDia = (int)$td['aulas_no_dia'];
    if ($aulasNoDia <= 0) continue;
    $dia = $td['dia_semana'];
    $diasRelatorio[] = $dia;
    $matrix[$dia] = [];
    $maxPorDia[$dia] = $aulasNoDia;
}

foreach ($horarios as $row) {
    $dia = $row['dia_semana'];
    if (!isset($matrix[$dia])) continue;

    $aula = (int)$row['numero_aula'];
    $disciplina = $row['nome_disciplina'] ?? '';
    $professor = $row['professor'] ?? '';
    
    // ✅ USA enc() PARA CONVERTER CADA PARTE
    $texto = enc($disciplina);
    if ($professor) {
        $texto .= "\n" . enc($professor);
    }
    
    $matrix[$dia][$aula] = $texto;

    if ($aula > ($maxPorDia[$dia] ?? 0)) {
        $maxPorDia[$dia] = $aula;
    }
}

$maxAulasGlobal = !empty($maxPorDia) ? max($maxPorDia) : 0;
if ($maxAulasGlobal == 0) {
    $pdf->SetFont('Arial', 'I', 12);
    $pdf->Cell(0, 10, enc('Nenhum horário cadastrado para esta turma.'), 0, 1, 'C');
    $pdf->Output();
    exit;
}

/* -------------- INTERVALOS -------------- */
$intervals = [];
if (!empty($turma['intervalos_positions'])) {
    $intervals = array_map('intval', 
        array_filter(
            array_map('trim', explode(',', $turma['intervalos_positions']))
        )
    );
    sort($intervals);
}

/* -------------- TABELA DE HORÁRIOS -------------- */
$margin = 10;
$pageWidth = $pdf->GetPageWidth();
$usableWidth = $pageWidth - (2 * $margin);
$colWidthAula = 30;
$daysCount = count($diasRelatorio);
$colWidthDay = ($usableWidth - $colWidthAula) / $daysCount;

// ✅ Mapeia nomes dos dias (com acentos)
$diasPortugues = [
    'Segunda' => 'Segunda-feira',
    'Terca' => 'Terça-feira',
    'Quarta' => 'Quarta-feira',
    'Quinta' => 'Quinta-feira',
    'Sexta' => 'Sexta-feira',
    'Sabado' => 'Sábado',
    'Domingo' => 'Domingo'
];

// Cabeçalho da tabela
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(200, 200, 200);
$pdf->Cell($colWidthAula, 12, enc('Aula / Dia'), 1, 0, 'C', true);

foreach ($diasRelatorio as $d) {
    $diaNome = $diasPortugues[$d] ?? $d;
    $pdf->Cell($colWidthDay, 12, enc($diaNome), 1, 0, 'C', true);
}
$pdf->Ln();

// Linhas da tabela
for ($a = 1; $a <= $maxAulasGlobal; $a++) {
    $rowTexts = [];
    foreach ($diasRelatorio as $dia) {
        $rowTexts[] = ($a <= ($maxPorDia[$dia] ?? 0)) ? ($matrix[$dia][$a] ?? '') : '';
    }

    // Calcula altura da linha
    $maxLines = 1;
    foreach ($rowTexts as $t) {
        if ($t !== '') {
            $num = count(explode("\n", $t));
            if ($num > $maxLines) $maxLines = $num;
        }
    }

    $lineHeight = 7;
    $rowHeight = max(14, $maxLines * $lineHeight);

    // Célula da aula
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell($colWidthAula, $rowHeight, enc($a . 'ª Aula'), 1, 0, 'C', true);

    // Células dos dias
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetFillColor(255, 255, 255);
    
    foreach ($rowTexts as $texto) {
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        
        $pdf->Cell($colWidthDay, $rowHeight, '', 1, 0, 'C', true);
        
        if ($texto !== '') {
            $linhas = explode("\n", $texto);
            $numLinhas = count($linhas);
            
            // Centraliza verticalmente
            $alturaTexto = $numLinhas * $lineHeight;
            $yStart = $y + ($rowHeight - $alturaTexto) / 2;
            
            $pdf->SetXY($x, $yStart);
            foreach ($linhas as $linha) {
                // Centraliza horizontalmente
                $textoWidth = $pdf->GetStringWidth($linha);
                $xStart = $x + ($colWidthDay - $textoWidth) / 2;
                $pdf->SetX($xStart);
                $pdf->Cell($textoWidth, $lineHeight, $linha, 0, 2, 'L');
            }
            
            $pdf->SetXY($x + $colWidthDay, $y);
        }
    }
    $pdf->Ln($rowHeight);

    // Linha de intervalo
    if (in_array($a, $intervals, true)) {
        $intervalHeight = 10;
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetFillColor(220, 220, 220);
        
        $pdf->Cell($colWidthAula, $intervalHeight, enc('Intervalo'), 1, 0, 'C', true);
        
        foreach ($diasRelatorio as $dia) {
            $pdf->Cell($colWidthDay, $intervalHeight, enc('Intervalo'), 1, 0, 'C', true);
        }
        
        $pdf->Ln($intervalHeight);
    }
}

// ✅ REMOVIDO: As linhas de informações extras foram removidas
// Não há mais "Total de aulas por semana", "Turno" ou "Nível de Ensino"

$pdf->Output();
exit;
?>