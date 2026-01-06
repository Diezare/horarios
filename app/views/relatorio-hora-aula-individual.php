<?php
// /horarios/app/views/horarios-treino-geral.php 
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

/**
 * Log de segurança. Garante tag [SEGURANCA].
 */
function seg_log(string $msg, array $meta = []) {
    $SEG_LOG = __DIR__ . '/../../logs/seguranca.log';
    $entry = '[' . date('d-M-Y H:i:s T') . '] [SEGURANCA] ' . $msg . ' | META=' . json_encode($meta, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    @file_put_contents($SEG_LOG, $entry, FILE_APPEND | LOCK_EX);
}

/**
 * Testa existência de um registro na tabela.
 * Retorna true se existir, false caso contrário.
 */
function exists_record(PDO $pdo, string $table, string $field, $value): bool {
    if (!$value) return false;
    $sql = "SELECT 1 FROM {$table} WHERE {$field} = :v LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([':v' => $value]);
    return (bool) $st->fetchColumn();
}

// ---------------- Parâmetros obrigatórios e opcionais ----------------
$id_ano_letivo   = isset($_GET['id_ano_letivo'])   ? (int)$_GET['id_ano_letivo']   : 0;
$id_turno        = isset($_GET['id_turno'])        ? (int)$_GET['id_turno']        : 0;
$id_modalidade   = isset($_GET['id_modalidade'])   ? (int)$_GET['id_modalidade']   : 0;
$id_categoria    = isset($_GET['id_categoria'])    ? (int)$_GET['id_categoria']    : 0;
$id_professor    = isset($_GET['id_professor'])    ? (int)$_GET['id_professor']    : 0;
$id_nivel_ensino = isset($_GET['id_nivel_ensino']) ? (int)$_GET['id_nivel_ensino'] : 0;

// Orientação de página (opcional): 'r' => Retrato, 'p' => Paisagem
$orientation = isset($_GET['orient']) ? $_GET['orient'] : 'r';
$orientation = ($orientation === 'p') ? 'L' : 'P'; // 'L' landscape, 'P' portrait

// Usuário da sessão (para logs)
$idUsuarioSess = $_SESSION['id_usuario'] ?? 0;

// id_ano_letivo obrigatório (presença sintática)
if (!$id_ano_letivo) {
    seg_log('Gerar horarios-treino-geral sem id_ano_letivo', ['usuario'=>$idUsuarioSess]);
    header('HTTP/1.1 400 Bad Request');
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Parâmetro ausente</title></head><body>';
    echo '<h2>Parâmetro obrigatório ausente: id_ano_letivo</h2>';
    echo '</body></html>';
    exit;
}

// Se algum filtro informado não existir, apenas ignora o filtro e registra no log.
// Isso permite "trocar" parâmetros sem bloquear a execução.
$invalidFilters = [];
if ($id_turno && !exists_record($pdo, 'turno', 'id_turno', $id_turno)) {
    $invalidFilters['id_turno'] = $id_turno;
    $id_turno = 0;
}
if ($id_modalidade && !exists_record($pdo, 'modalidade', 'id_modalidade', $id_modalidade)) {
    $invalidFilters['id_modalidade'] = $id_modalidade;
    $id_modalidade = 0;
}
if ($id_categoria && !exists_record($pdo, 'categoria', 'id_categoria', $id_categoria)) {
    $invalidFilters['id_categoria'] = $id_categoria;
    $id_categoria = 0;
}
if ($id_professor && !exists_record($pdo, 'professor', 'id_professor', $id_professor)) {
    $invalidFilters['id_professor'] = $id_professor;
    $id_professor = 0;
}
if ($id_nivel_ensino && !exists_record($pdo, 'nivel_ensino', 'id_nivel_ensino', $id_nivel_ensino)) {
    $invalidFilters['id_nivel_ensino'] = $id_nivel_ensino;
    $id_nivel_ensino = 0;
}
if (!empty($invalidFilters)) {
    seg_log('Filtros ignorados por não existirem no BD em horarios-treino-geral', ['usuario'=>$idUsuarioSess,'ignorados'=>$invalidFilters]);
}

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
        // todos os parâmetros são inteiros no WHERE
        $stmt->bindValue($key, (int)$value, PDO::PARAM_INT);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    seg_log('Erro ao consultar horarios-treino-geral: '.$e->getMessage(), ['usuario'=>$idUsuarioSess]);
    header('HTTP/1.1 500 Internal Server Error');
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Erro</title></head><body>';
    echo '<h2>Erro ao buscar dados.</h2>';
    echo '<p>Contate o administrador.</p>';
    echo '</body></html>';
    exit;
}

// Descobrir nome do turno e ano para exibir no cabeçalho
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
    // tentar preencher nomes para cabeçalho a partir do BD (se os filtros originais eram válidos)
    $stm = $pdo->prepare("SELECT ano FROM ano_letivo WHERE id_ano_letivo=? LIMIT 1");
    $stm->execute([$id_ano_letivo]);
    if ($rA = $stm->fetch(PDO::FETCH_ASSOC)) $anoLetivo = $rA['ano'];

    if ($id_turno) {
        $stm = $pdo->prepare("SELECT nome_turno FROM turno WHERE id_turno=? LIMIT 1");
        $stm->execute([$id_turno]);
        if ($r = $stm->fetch(PDO::FETCH_ASSOC)) $nomeTurno = $r['nome_turno'];
    }
    if ($id_modalidade) {
        $stm = $pdo->prepare("SELECT nome_modalidade FROM modalidade WHERE id_modalidade=? LIMIT 1");
        $stm->execute([$id_modalidade]);
        if ($r = $stm->fetch(PDO::FETCH_ASSOC)) $nomeModalidade = $r['nome_modalidade'];
    }
    if ($id_categoria) {
        $stm = $pdo->prepare("SELECT nome_categoria FROM categoria WHERE id_categoria=? LIMIT 1");
        $stm->execute([$id_categoria]);
        if ($r = $stm->fetch(PDO::FETCH_ASSOC)) $nomeCategoria = $r['nome_categoria'];
    }
    if ($id_professor) {
        $stm = $pdo->prepare("SELECT COALESCE(nome_exibicao,nome_completo) AS nome_professor FROM professor WHERE id_professor=? LIMIT 1");
        $stm->execute([$id_professor]);
        if ($r = $stm->fetch(PDO::FETCH_ASSOC)) $nomeProfessor = $r['nome_professor'];
    }
}

// Verificar se os filtros específicos estão ativos (modalidade, categoria ou professor)
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

// Agrupar os horários por dia_semana
$grupoPorDia = [];
foreach ($rows as $r) {
    $grupoPorDia[$r['dia_semana']][] = $r;
}

// Se não há registros, mostra página com mensagem (sem gerar PDF)
if (empty($rows)) {
    seg_log('Consulta sem resultados em horarios-treino-geral (nenhum registro).', ['usuario'=>$idUsuarioSess,'filtros'=>[
        'id_ano_letivo'=>$id_ano_letivo,'id_turno'=>$id_turno,'id_modalidade'=>$id_modalidade,
        'id_categoria'=>$id_categoria,'id_professor'=>$id_professor,'id_nivel_ensino'=>$id_nivel_ensino
    ]]);
    header('HTTP/1.1 200 OK');
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Nenhum horário encontrado</title></head><body>';
    echo '<h2>Nenhum horário encontrado.</h2>';
    echo '<p>Não há registros para os filtros selecionados.</p>';
    echo '</body></html>';
    exit;
}

// ---------- Classe FPDF (mantive a sua estrutura) ----------
class PDFHorariosTreinoGeral extends FPDF
{
    protected $isPrimeiraPageina = true;
    private $nomeTurno = '';
    private $anoLetivo = '';
    private $nomeModalidade = '';
    private $nomeCategoria = '';
    private $nomeProfessor = '';
    
    private $LOGO_SIZE_MM = 15;
    private $LOGO_GAP_MM  = 5;
    
    public function __construct($orientation, $unit, $size, $nomeTurno, $anoLetivo, $nomeModalidade = '', $nomeCategoria = '', $nomeProfessor = '') 
    {
        parent::__construct($orientation, $unit, $size);
        $this->nomeTurno = $nomeTurno;
        $this->anoLetivo = $anoLetivo;
        $this->nomeModalidade = $nomeModalidade;
        $this->nomeCategoria = $nomeCategoria;
        $this->nomeProfessor = $nomeProfessor;
    }
    
    public function Header()
    {
        global $pdo;

        if ($this->isPrimeiraPageina) {
            $stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
            $inst = $stmtInst->fetch(PDO::FETCH_ASSOC);

            $nomeInst = $inst ? ($inst['nome_instituicao'] ?? '') : '';
            $logoPath = ($inst && !empty($inst['imagem_instituicao'])) ? LOGO_PATH . '/' . basename($inst['imagem_instituicao']) : null;

            $this->SetY(12);
            $this->SetFont('Arial','B',14);
            $txt  = iconv('UTF-8','ISO-8859-1', $nomeInst);
            $txtW = $this->GetStringWidth($txt);
            $hasLogo = ($logoPath && file_exists($logoPath));
            $totalW  = $hasLogo ? ($this->LOGO_SIZE_MM + ($nomeInst ? $this->LOGO_GAP_MM : 0) + $txtW) : $txtW;
            $pageW   = $this->GetPageWidth();
            $x       = ($pageW - $totalW) / 2;
            $y       = $this->GetY();

            if ($hasLogo) {
                $this->Image($logoPath, $x, $y - 2, $this->LOGO_SIZE_MM, $this->LOGO_SIZE_MM);
                $x += $this->LOGO_SIZE_MM + ($nomeInst ? $this->LOGO_GAP_MM : 0);
            }

            if ($nomeInst) {
                $this->SetXY($x, $y);
                $this->Cell($txtW, $this->LOGO_SIZE_MM, $txt, 0, 1, 'L');
            }

            $this->Ln(3);
            $this->SetFont('Arial','B',13);
            $this->Cell(0, 7, iconv('UTF-8','ISO-8859-1', 'Horários de Treino Individual'), 0, 1, 'C');
            $this->Ln(1);

            $txtHeader = '';
            if ($this->anoLetivo) $txtHeader .= 'Ano Letivo ' . $this->anoLetivo;
            if ($this->nomeTurno) $txtHeader .= ($txtHeader ? ' | ' : '') . 'Turno: ' . $this->nomeTurno;

            $this->SetFont('Arial','B',14);
            $this->Cell(0, 8, iconv('UTF-8','ISO-8859-1', $txtHeader), 0, 1, 'C');

            $filtrosExtras = '';
            if ($this->nomeModalidade) $filtrosExtras .= 'Modalidade: ' . $this->nomeModalidade;
            if ($this->nomeCategoria)  $filtrosExtras .= ($filtrosExtras ? ' | ' : '') . 'Categoria: ' . $this->nomeCategoria;
            if ($this->nomeProfessor)  $filtrosExtras .= ($filtrosExtras ? ' | ' : '') . 'Professor: ' . $this->nomeProfessor;
            if ($filtrosExtras) {
                $this->SetFont('Arial','B',11);
                $this->Cell(0, 6, iconv('UTF-8','ISO-8859-1', $filtrosExtras), 0, 1, 'C');
            }

            $this->Ln(5);
            $this->isPrimeiraPageina = false;
        }
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10, iconv('UTF-8','ISO-8859-1','Página ' . $this->PageNo()), 0, 0, 'L');
        $this->Cell(0,10, iconv('UTF-8','ISO-8859-1','Impresso em: ' . date('d/m/Y H:i:s')), 0, 0, 'R');
    }

    public function checkPageBreak($height)
    {
        return ($this->GetY() + $height > $this->GetPageHeight() - 20);
    }
}

// Instancia e gera PDF
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
$pdf->SetTitle(mb_convert_encoding('Horários de Treino Individual','ISO-8859-1','UTF-8'));
$pdf->AddPage();
$pdf->SetFont('Arial','',9);

// Verificar se deve usar o layout por dia da semana (quando modalidade, categoria ou professor estão filtrados)
if ($filtrosEspecificosAtivos) {
    $primeiroDia = true;
    foreach ($grupoPorDia as $diaSemana => $registrosDia) {
        if (!$primeiroDia) $pdf->Ln(10);
        $primeiroDia = false;

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 8, mb_convert_encoding($diaNormal[$diaSemana], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');

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
    // Layout vertical original
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
    
        $pdf->Cell($colDiaW,        $lineH, mb_convert_encoding('Dia','ISO-8859-1','UTF-8'), 1,0,'C',true);
        $pdf->Cell($colHorarioW,    $lineH, mb_convert_encoding('Horário','ISO-8859-1','UTF-8'), 1,0,'C',true);
        $pdf->Cell($colModalidadeW, $lineH, mb_convert_encoding('Modalidade','ISO-8859-1','UTF-8'), 1,0,'C',true);
        $pdf->Cell($colCategoriaW,  $lineH, mb_convert_encoding('Categoria','ISO-8859-1','UTF-8'), 1,0,'C',true);
        $pdf->Cell($colProfessorW,  $lineH, mb_convert_encoding('Professor','ISO-8859-1','UTF-8'), 1,1,'C',true);
    
        $pdf->SetFont('Arial','',9);
    }

    foreach ($grupoPorDia as $diaSemana => $registrosDia) {
        $numRegDia = count($registrosDia);
        $boxHeight = ($numRegDia * $lineH) + $lineH;
        $altura_total = $boxHeight + $espaco_entre_dias;
        
        if ($pdf->checkPageBreak($altura_total)) $pdf->AddPage();

        imprimirCabecalhoColunas($pdf, $colDiaW, $colHorarioW, $colModalidadeW, $colCategoriaW, $colProfessorW, $lineH);

        $boxHeight = $numRegDia * $lineH;
        $x0 = $pdf->GetX();
        $y0 = $pdf->GetY();

        $pdf->Rect($x0, $y0, $colDiaW, $boxHeight);

        $pdf->SetFont('Arial','',8);
        $verticalText = $diaVertical[$diaSemana] ?? $diaSemana;
        $pdf->SetXY($x0, $y0);

        $numLines   = count(explode("\n", $verticalText));
        $textHeight = $numLines * 3.5;
        $startY     = $y0 + ($boxHeight/2) - ($textHeight/2);

        $pdf->SetXY($x0, $startY);
        $pdf->MultiCell($colDiaW, 3.5, mb_convert_encoding($verticalText,'ISO-8859-1','UTF-8'), 0, 'C');

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

$pdf->Output();
exit;
?>
