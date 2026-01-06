<?php
// /horarios/app/views/horarios-treino-individual.php
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
 * Aborta a requisição e retorna mensagem de erro em texto puro.
 * Evita qualquer saída de PDF.
 */
function abortClient($msg = 'Parâmetros inválidos') {
    while (ob_get_level() > 0) @ob_end_clean();
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit;
}

/**
 * Converte UTF-8 -> ISO-8859-1 de forma resiliente.
 * Repara entradas não-UTF8, tenta TRANSLIT e, se necessário, IGNORE.
 */
function safe_utf8_to_iso(string $s): string {
    if ($s === '') return '';
    $s = (string)$s;
    // normaliza para UTF-8 (remove/replace bytes inválidos)
    $s = @mb_convert_encoding($s, 'UTF-8', 'UTF-8');
    // tenta transliteração primeiro (silenciando warnings)
    $out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s);
    if ($out !== false) return $out;
    // fallback: ignora caracteres não representáveis
    $out = @iconv('UTF-8', 'ISO-8859-1//IGNORE', $s);
    if ($out !== false) return $out;
    return '';
}

// ---------------- Validação rígida do parâmetro id_horario ----------------
$id_horario_raw = $_GET['id_horario_escolinha'] ?? null;

if (!is_scalar($id_horario_raw) || !preg_match('/^[1-9]\d*$/', (string)$id_horario_raw)) {
    logSecurity('id_horario_escolinha inválido ou ausente em horarios-treino-individual: raw=' . var_export($id_horario_raw, true) . ' | qs=' . ($_SERVER['QUERY_STRING'] ?? ''));
    abortClient();
}
$id_horario = (int)$id_horario_raw;

// Orientação opcional: 'p' para paisagem, outro para retrato
$orientation_raw = isset($_GET['orient']) ? (string)$_GET['orient'] : 'r';
$orientation = ($orientation_raw === 'p') ? 'L' : 'P';

// ---------------- Busca de dados ----------------
try {
    // Busca informações básicas do horário
    $sqlBasic = "
        SELECT he.*,
               a.ano,
               ne.nome_nivel_ensino,
               mo.nome_modalidade,
               c.nome_categoria,
               COALESCE(p.nome_exibicao, p.nome_completo) AS nome_professor,
               p.id_professor,
               t.nome_turno
          FROM horario_escolinha he
          JOIN ano_letivo       a  ON he.id_ano_letivo   = a.id_ano_letivo
          JOIN nivel_ensino     ne ON he.id_nivel_ensino = ne.id_nivel_ensino
          JOIN modalidade       mo ON he.id_modalidade   = mo.id_modalidade
          JOIN categoria        c  ON he.id_categoria    = c.id_categoria
          JOIN professor        p  ON he.id_professor    = p.id_professor
          JOIN turno            t  ON he.id_turno        = t.id_turno
         WHERE he.id_horario_escolinha = :id
    ";
    $stmtBasic = $pdo->prepare($sqlBasic);
    $stmtBasic->bindValue(':id', $id_horario, PDO::PARAM_INT);
    $stmtBasic->execute();
    $basicInfo = $stmtBasic->fetch(PDO::FETCH_ASSOC);

    if (!$basicInfo) {
        logSecurity("Nenhum basicInfo para id_horario_escolinha={$id_horario} em horarios-treino-individual");
        abortClient();
    }

    // Busca todos os horários do professor no mesmo ano/turno (prepared)
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
         WHERE p.id_professor   = :id_professor
           AND he.id_ano_letivo = :id_ano_letivo
           AND he.id_turno      = :id_turno
         ORDER BY 
           FIELD(he.dia_semana,'Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado'),
           he.hora_inicio
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id_professor', $basicInfo['id_professor'], PDO::PARAM_INT);
    $stmt->bindValue(':id_ano_letivo', $basicInfo['id_ano_letivo'], PDO::PARAM_INT);
    $stmt->bindValue(':id_turno', $basicInfo['id_turno'], PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        logSecurity("Consulta retornou vazia para professor={$basicInfo['id_professor']} ano={$basicInfo['id_ano_letivo']} turno={$basicInfo['id_turno']} em horarios-treino-individual");
        abortClient();
    }

} catch (Throwable $e) {
    logSecurity("Erro SQL/Exception em horarios-treino-individual: " . $e->getMessage());
    abortClient();
}

// Dados para cabeçalho (mantemos em UTF-8 internamente)
$nomeTurno     = $basicInfo['nome_turno'] ?? '';
$anoLetivo     = $basicInfo['ano'] ?? '';
$nomeProfessor = $basicInfo['nome_professor'] ?? '';

// Corrigir nomes dos dias
function corrigirDiaSemana($dia) {
    $mapaCorrecao = [
        'Domingo' => 'Domingo',
        'Segunda' => 'Segunda',
        'Terca'   => 'Terça',
        'Quarta'  => 'Quarta',
        'Quinta'  => 'Quinta',
        'Sexta'   => 'Sexta',
        'Sabado'  => 'Sábado'
    ];
    return $mapaCorrecao[$dia] ?? $dia;
}

// Agrupar por dia (corrigindo nomes)
$treinosPorDia = [];
foreach ($rows as $row) {
    $diaSemana = corrigirDiaSemana($row['dia_semana']);
    $treinosPorDia[$diaSemana][] = $row;
}

// ---------------- Parâmetros do cabeçalho padronizado ----------------
$LOGO_SIZE_MM = 15; // tamanho da logo (mm)
$LOGO_GAP_MM  = 5;  // espaço entre logo e texto (mm)

// Classe PDF
class PDFHorariosTreinoIndividual extends FPDF {
    private $nomeProfessor;
    private $nomeTurno;
    private $anoLetivo;

    public function __construct($orientation, $unit, $size, $nomeProfessor, $nomeTurno, $anoLetivo) {
        parent::__construct($orientation, $unit, $size);
        $this->nomeProfessor = $nomeProfessor;
        $this->nomeTurno     = $nomeTurno;
        $this->anoLetivo     = $anoLetivo;
    }

    public function Header() {
        global $pdo, $LOGO_SIZE_MM, $LOGO_GAP_MM;

        // Busca instituição
        $stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
        $inst = $stmtInst->fetch(PDO::FETCH_ASSOC);

        $nomeInst = $inst ? ($inst['nome_instituicao'] ?? '') : '';
        $logoPath = ($inst && !empty($inst['imagem_instituicao'])) ? LOGO_PATH.'/'.basename($inst['imagem_instituicao']) : null;

        // Proteção: garante que logo esteja dentro do diretório e exista
        if ($logoPath && (!file_exists($logoPath) || !is_file($logoPath) || strpos(realpath($logoPath), realpath(LOGO_PATH)) !== 0)) {
            $logoPath = null;
            logSecurity("Logo inválido/inacessível em Header horarios-treino-individual: " . ($inst['imagem_instituicao'] ?? ''));
        }

        // Logo + nome da instituição na mesma linha (centralizado)
        $this->SetY(12);
        $this->SetFont('Arial','B',14);
        $txt  = safe_utf8_to_iso($nomeInst);
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

        // Linha de informações: "Ano Letivo 2025 | Horários de Treino Vespertino"
        $this->Ln(3);
        $this->SetFont('Arial','B',11);

        $linhaInfo = 'Ano Letivo';
        if (!empty($this->anoLetivo))  $linhaInfo .= ' ' . $this->anoLetivo;
        $linhaInfo .= ' | Horários de Treino';
        if (!empty($this->nomeTurno))  $linhaInfo .= ' ' . $this->nomeTurno;

        $this->Cell(0, 7, safe_utf8_to_iso($linhaInfo), 0, 1, 'C');

        // Professor (centralizado)
        if (!empty($this->nomeProfessor)) {
            $this->SetFont('Arial','B',10);
            $this->Cell(0, 6, safe_utf8_to_iso('Professor: ' . $this->nomeProfessor), 0, 1, 'C');
        }

        $this->Ln(6); // espaço antes do conteúdo
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        // Página à esquerda
        $this->Cell(0, 10, safe_utf8_to_iso('Página '.$this->PageNo()), 0, 0, 'L');
        // Data/hora à direita
        $txt = 'Impresso em: '.date('d/m/Y H:i:s');
        $this->Cell(0, 10, safe_utf8_to_iso($txt), 0, 0, 'R');
    }
}

// ----------------- Suppress specific iconv notices from FPDF -----------------
// FPDF internals sometimes call iconv with bytes that trigger a notice.
// We install a temporary error handler that swallows that specific notice
// only when it originates from the fpdf.php file and contains the iconv message.
$prevErrorHandler = set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (stripos($errstr, 'iconv(): Detected an illegal character') !== false
        && stripos($errfile, 'fpdf.php') !== false) {
        // mark as handled (no further processing)
        return true;
    }
    // defer to PHP internal handler for other errors
    return false;
});

// ----------------- Gerar PDF (uso protegido) -----------------
$pdf = new PDFHorariosTreinoIndividual($orientation, 'mm', 'A4', $nomeProfessor, $nomeTurno, $anoLetivo);
//$pdf->SetTitle(safe_utf8_to_iso('Horário de Treinos - '.$nomeProfessor), true);
$pdf->SetTitle('Relatório de Horários de Treinos Individuais', true);
$pdf->AddPage();
$pdf->SetFont('Arial', '', 9);

// Conteúdo
$primeiroDia = true;
foreach ($treinosPorDia as $diaSemana => $treinosDoDia) {
    if (!$primeiroDia) $pdf->Ln(10);
    $primeiroDia = false;

    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 8, safe_utf8_to_iso($diaSemana), 0, 1, 'L');

    // Cabeçalho da tabela
    $pageWidth = $pdf->GetPageWidth() - 20;
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(220);
    $pdf->Cell($pageWidth * 0.20, 8, safe_utf8_to_iso('Horário'),    1, 0, 'C', true);
    $pdf->Cell($pageWidth * 0.40, 8, safe_utf8_to_iso('Modalidade'), 1, 0, 'C', true);
    $pdf->Cell($pageWidth * 0.40, 8, safe_utf8_to_iso('Categoria'),  1, 1, 'C', true);
    $pdf->SetFont('Arial', '', 9);

    // Dados
    foreach ($treinosDoDia as $treino) {
        $horaI = isset($treino['hora_inicio']) ? substr($treino['hora_inicio'], 0, 5) : '';
        $horaF = isset($treino['hora_fim'])    ? substr($treino['hora_fim'], 0, 5)    : '';
        $modalidade = $treino['nome_modalidade'] ?? '';
        $categoria  = $treino['nome_categoria']  ?? '';

        $pdf->Cell($pageWidth * 0.20, 8, safe_utf8_to_iso("$horaI - $horaF"), 1, 0, 'C');
        $pdf->Cell($pageWidth * 0.40, 8, safe_utf8_to_iso($modalidade), 1, 0, 'C');
        $pdf->Cell($pageWidth * 0.40, 8, safe_utf8_to_iso($categoria),  1, 1, 'C');
    }
}

// Output
$pdf->Output('I', 'RelatorioHorariosTreinosIndividuais.pdf');

restore_error_handler(); // volta ao handler anterior
exit;
