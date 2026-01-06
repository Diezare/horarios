<?php
// /horarios/app/views/relatorio-hora-aula-geral.php
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

// ---------- WHITELIST de parâmetros GET ----------
$permitidos = [
    'id_ano_letivo',
    'id_turno',
    'tipo_relatorio',
    'id_modalidade',
    'id_categoria',
    'id_professor',
    'orient'
];
$recebidos = array_keys($_GET);
$naoPermitidos = array_diff($recebidos, $permitidos);
if (!empty($naoPermitidos)) {
    if (function_exists('logSecurityEvent')) {
        logSecurityEvent("Parâmetros não permitidos em relatorio-hora-aula-geral: " . implode(', ', $naoPermitidos));
    }
    header('HTTP/1.1 400 Bad Request');
    die('Parâmetros inválidos');
}

// ---------- Sanitização / Validação ----------
$id_ano_letivo  = filter_input(INPUT_GET, 'id_ano_letivo', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
$id_turno       = filter_input(INPUT_GET, 'id_turno', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
$id_modalidade  = filter_input(INPUT_GET, 'id_modalidade', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
$id_categoria   = filter_input(INPUT_GET, 'id_categoria', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
$id_professor   = filter_input(INPUT_GET, 'id_professor', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
$tipo_relatorio = isset($_GET['tipo_relatorio']) ? trim($_GET['tipo_relatorio']) : 'tudo';
$orient_param   = isset($_GET['orient']) ? trim($_GET['orient']) : 'r';

// Valida tipo_relatorio
$tipos_validos = ['dia','semana','mes','semestre','ano','tudo'];
if (!in_array($tipo_relatorio, $tipos_validos, true)) {
    if (function_exists('logSecurityEvent')) {
        logSecurityEvent("tipo_relatorio inválido em relatorio-hora-aula-geral: " . $tipo_relatorio);
    }
    header('HTTP/1.1 400 Bad Request');
    die('Parâmetros inválidos');
}

// Orientação
$orientation = ($orient_param === 'p') ? 'L' : 'P';

// Ano letivo é obrigatório
if (!$id_ano_letivo) {
    header('HTTP/1.1 400 Bad Request');
    die('Parâmetro ano letivo é obrigatório para gerar PDF.');
}

// ---------- Funções utilitárias (mantidas com leves proteções) ----------
function buscarConfiguracaoHoraAula($pdo, $id_ano_letivo, $id_modalidade, $id_categoria) {
    try {
        $sql = "
            SELECT duracao_aula_minutos
            FROM configuracao_hora_aula_escolinha
            WHERE id_ano_letivo = :id_ano_letivo
              AND id_modalidade = :id_modalidade
              AND id_categoria  = :id_categoria
              AND ativo = 1
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        if (!$stmt) return 50;
        $stmt->execute([
            ':id_ano_letivo' => (int)$id_ano_letivo,
            ':id_modalidade' => (int)$id_modalidade,
            ':id_categoria'  => (int)$id_categoria
        ]);
        $cfg = $stmt->fetch(PDO::FETCH_ASSOC);
        return $cfg ? (int)$cfg['duracao_aula_minutos'] : 50;
    } catch (Exception $e) {
        if (function_exists('logSecurityEvent')) logSecurityEvent("Erro buscarConfiguracaoHoraAula: " . $e->getMessage());
        return 50;
    }
}

function formatarHorasMinutos($total) {
    $total = (int)$total;
    if ($total <= 0) return "0min";
    $h = floor($total / 60); $m = $total % 60;
    $txt = $h ? ($h . "h") : "";
    if ($m) $txt .= ($txt ? " " : "") . $m . "min";
    return $txt ?: "0min";
}

function isDataEmEvento($data, $eventos) {
    $d = $data->format('Y-m-d');
    foreach ($eventos as $ev) {
        if (!empty($ev['data_inicio']) && !empty($ev['data_fim'])) {
            if ($d >= $ev['data_inicio'] && $d <= $ev['data_fim']) return true;
        }
    }
    return false;
}

function contarOcorrenciasDiaSemana($ini, $fim, $diaSemana, $eventos) {
    $map = ['Domingo'=>0,'Segunda'=>1,'Terca'=>2,'Quarta'=>3,'Quinta'=>4,'Sexta'=>5,'Sabado'=>6];
    $target = $map[$diaSemana] ?? 1;
    $oc = 0;
    try {
        $d = clone $ini;
        // avança até o primeiro dia alvo
        while ($d->format('w') != $target && $d <= $fim) $d->add(new DateInterval('P1D'));
        while ($d <= $fim) {
            if (!isDataEmEvento($d, $eventos)) $oc++;
            $d->add(new DateInterval('P7D'));
        }
    } catch (Exception $e) {
        if (function_exists('logSecurityEvent')) logSecurityEvent("Erro contarOcorrenciasDiaSemana: " . $e->getMessage());
    }
    return $oc;
}

function calcularTotalAulas($pdo, $horarios, $anoLetivo, $eventos, $tipo) {
    $tot = 0;
    try {
        $ini = new DateTime($anoLetivo['data_inicio']);
        $fim = new DateTime($anoLetivo['data_fim']);
        foreach ($horarios as $h) {
            $dia = $h['dia_semana'] ?? '';
            if (!$dia) continue;
            switch ($tipo) {
                case 'dia':      $tot += 1;  break;
                case 'semana':   $tot += 1;  break;
                case 'mes':      $tot += 4;  break;
                case 'semestre': $tot += 24; break;
                case 'ano':
                default:
                    $tot += contarOcorrenciasDiaSemana($ini, $fim, $dia, $eventos);
            }
        }
    } catch (Exception $e) {
        if (function_exists('logSecurityEvent')) logSecurityEvent("Erro calcularTotalAulas: " . $e->getMessage());
    }
    return $tot;
}

// ---------- Consulta segura de dados ----------
try {
    // Monta WHERE e parâmetros com tipos
    $where  = "he.id_ano_letivo = :ano";
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

    $sql = "
        SELECT 
            p.id_professor,
            COALESCE(p.nome_exibicao, p.nome_completo) AS nome_professor_completo,
            m.id_modalidade,   m.nome_modalidade,
            c.id_categoria,    c.nome_categoria,
            t.nome_turno,
            a.ano,
            GROUP_CONCAT(CONCAT(he.dia_semana,'|',he.hora_inicio,'|',he.hora_fim) SEPARATOR ';') AS horarios_detalhes
        FROM horario_escolinha he
        INNER JOIN professor  p ON he.id_professor  = p.id_professor
        INNER JOIN modalidade m ON he.id_modalidade = m.id_modalidade
        INNER JOIN categoria  c ON he.id_categoria  = c.id_categoria
        INNER JOIN turno      t ON he.id_turno      = t.id_turno
        INNER JOIN ano_letivo a ON he.id_ano_letivo = a.id_ano_letivo
        WHERE $where
        GROUP BY p.id_professor, m.id_modalidade, c.id_categoria
        ORDER BY nome_professor_completo, m.nome_modalidade, c.nome_categoria
    ";

    $stmt = $pdo->prepare($sql);
    // bind conforme tipo
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, (int)$v, PDO::PARAM_INT);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ano letivo (datas)
    $stmtAno = $pdo->prepare("SELECT * FROM ano_letivo WHERE id_ano_letivo = :id LIMIT 1");
    $stmtAno->execute([':id' => $id_ano_letivo]);
    $anoLetivoData = $stmtAno->fetch(PDO::FETCH_ASSOC);

    if (!$anoLetivoData) {
        header('HTTP/1.1 400 Bad Request');
        die('Parâmetros inválidos');
    }

    // Eventos que excluem hora-aula
    $stmtEv = $pdo->prepare("
        SELECT data_inicio, data_fim
        FROM eventos_calendario_escolar
        WHERE id_ano_letivo = :id
          AND tipo_evento IN ('Feriado','Recesso','Férias')
    ");
    $stmtEv->execute([':id' => $id_ano_letivo]);
    $eventos = $stmtEv->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    if (function_exists('logSecurityEvent')) logSecurityEvent("Erro buscar dados relatorio-hora-aula-geral: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    die('Erro ao buscar dados: ' . $e->getMessage());
}

// ---------- Obtém nomes legíveis para filtros (somente se ids foram fornecidos) ----------
$nomeTurno = '';
$anoLetivo = $anoLetivoData['ano'] ?? '';

if ($id_turno) {
    $stmTurno = $pdo->prepare("SELECT nome_turno FROM turno WHERE id_turno = :id LIMIT 1");
    $stmTurno->execute([':id' => $id_turno]);
    $rT = $stmTurno->fetch(PDO::FETCH_ASSOC);
    $nomeTurno = $rT['nome_turno'] ?? '';
}

$nomeModalidade = $nomeCategoria = $nomeProfessor = '';
if ($id_modalidade) {
    $stm = $pdo->prepare("SELECT nome_modalidade FROM modalidade WHERE id_modalidade = :id LIMIT 1");
    $stm->execute([':id' => $id_modalidade]);
    $r = $stm->fetch(PDO::FETCH_ASSOC);
    $nomeModalidade = $r['nome_modalidade'] ?? '';
}
if ($id_categoria) {
    $stm = $pdo->prepare("SELECT nome_categoria FROM categoria WHERE id_categoria = :id LIMIT 1");
    $stm->execute([':id' => $id_categoria]);
    $r = $stm->fetch(PDO::FETCH_ASSOC);
    $nomeCategoria = $r['nome_categoria'] ?? '';
}
if ($id_professor) {
    $stm = $pdo->prepare("SELECT COALESCE(nome_exibicao,nome_completo) AS nome_professor FROM professor WHERE id_professor = :id LIMIT 1");
    $stm->execute([':id' => $id_professor]);
    $r = $stm->fetch(PDO::FETCH_ASSOC);
    $nomeProfessor = $r['nome_professor'] ?? '';
}

// ---------- Agrupa por professor e calcula totais ----------
$dadosPorProfessor = [];
foreach ($rows as $row) {
    $pid = (int)$row['id_professor'];
    if (!isset($dadosPorProfessor[$pid])) {
        $dadosPorProfessor[$pid] = [
            'nome'        => $row['nome_professor_completo'],
            'modalidades' => []
        ];
    }

    // explode horários
    $horariosDetalhes = [];
    if (!empty($row['horarios_detalhes'])) {
        foreach (explode(';', $row['horarios_detalhes']) as $h) {
            $p = explode('|', $h);
            if (count($p) === 3) {
                $horariosDetalhes[] = ['dia_semana'=>$p[0],'hora_inicio'=>$p[1],'hora_fim'=>$p[2]];
            }
        }
    }

    $totAulas = calcularTotalAulas($pdo, $horariosDetalhes, $anoLetivoData, $eventos, $tipo_relatorio);
    $durAula  = buscarConfiguracaoHoraAula($pdo, $id_ano_letivo, $row['id_modalidade'], $row['id_categoria']);
    $totMin   = $totAulas * $durAula;

    $dadosPorProfessor[$pid]['modalidades'][] = [
        'ano'                   => $row['ano'],
        'modalidade'            => $row['nome_modalidade'],
        'categoria'             => $row['nome_categoria'],
        'qtde_aulas'            => $totAulas,
        'duracao_aula'          => $durAula,
        'total_minutos'         => $totMin,
        'total_horas_formatado' => formatarHorasMinutos($totMin),
    ];
}

// ---------- Classe PDF (mantive layout do seu original) ----------
class PDFHoraAulaGeral extends FPDF
{
    protected $isPrimeiraPagina = true;
    private $nomeTurno = '';
    private $anoLetivo = '';
    private $linhaFiltros = '';
    private $LOGO_SIZE_MM = 15;
    private $LOGO_GAP_MM  = 5;

    public function __construct($orientation, $unit, $size, $nomeTurno, $anoLetivo, $linhaFiltros = '')
    {
        parent::__construct($orientation, $unit, $size);
        $this->nomeTurno    = $nomeTurno;
        $this->anoLetivo    = $anoLetivo;
        $this->linhaFiltros = $linhaFiltros;
    }

    public function Header()
    {
        global $pdo;

        if ($this->isPrimeiraPagina) {
            $stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
            $inst     = $stmtInst->fetch(PDO::FETCH_ASSOC);

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

            // Título
            $this->Ln(3);
            $this->SetFont('Arial','B',13);
            $this->Cell(0, 7, iconv('UTF-8','ISO-8859-1', 'Relatório Geral de Hora/Aula da Escolinha'), 0, 1, 'C');

            // Linha 2
            $this->Ln(1);
            $this->SetFont('Arial','B',14);
            $linha2 = '';
            if (!empty($this->anoLetivo)) $linha2 .= 'Ano Letivo ' . $this->anoLetivo;
            if (!empty($this->nomeTurno)) $linha2 .= ($linha2 ? ' | ' : '') . 'Turno: ' . $this->nomeTurno;
            $this->Cell(0, 8, iconv('UTF-8','ISO-8859-1', $linha2), 0, 1, 'C');

            // Linha 3: filtros
            if (!empty($this->linhaFiltros)) {
                $this->SetFont('Arial','B',11);
                $this->Cell(0, 6, iconv('UTF-8','ISO-8859-1', $this->linhaFiltros), 0, 1, 'C');
            }

            $this->Ln(5);
            $this->isPrimeiraPagina = false;
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

// ---------- Linha de filtros para cabeçalho ----------
$mapTipos = [
    'dia' => 'Por Dia', 'semana' => 'Por Semana',
    'mes' => 'Por Mês', 'semestre' => 'Por Semestre',
    'ano' => 'Anual',   'tudo' => 'Geral'
];
$parts = [];
if ($nomeModalidade) $parts[] = "Modalidade: $nomeModalidade";
if ($nomeCategoria)  $parts[] = "Categoria: $nomeCategoria";
if ($nomeProfessor)  $parts[] = "Professor: $nomeProfessor";
$parts[] = "Tipo: " . ($mapTipos[$tipo_relatorio] ?? ucfirst($tipo_relatorio));
$linhaFiltros = implode(' | ', $parts);

// ---------- Geração do PDF (mantendo layout) ----------
$pdf = new PDFHoraAulaGeral($orientation, 'mm', 'A4', $nomeTurno, $anoLetivo, $linhaFiltros);
$pdf->SetTitle(iconv('UTF-8','ISO-8859-1','Relatório de Hora Aula'));
$pdf->AddPage();
$pdf->SetFont('Arial','',9);

if (empty($dadosPorProfessor)) {
    $pdf->Cell(0, 8, iconv('UTF-8','ISO-8859-1','Nenhum horário encontrado.'), 0, 1, 'C');
    $pdf->Output();
    exit;
}

$totalGeralAulas = 0;
$totalGeralMin   = 0;

foreach ($dadosPorProfessor as $profId => $dadosProfessor) {
    $linhas = count($dadosProfessor['modalidades']) + 4;
    $altura = $linhas * 8;
    if ($pdf->checkPageBreak($altura)) $pdf->AddPage();

    // Nome do professor
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0, 8, mb_convert_encoding($dadosProfessor['nome'], 'ISO-8859-1','UTF-8'), 0, 1, 'L');

    // Linha separadora
    $pageWidth = $pdf->GetPageWidth() - 20;
    $yLine = $pdf->GetY();
    $pdf->Line(10, $yLine, 10 + $pageWidth, $yLine);
    $pdf->Ln(2);

    // Cabeçalho da tabela
    $pdf->SetFont('Arial','B',9);
    $pdf->SetFillColor(220,220,220);
    $pdf->Cell($pageWidth * 0.12, 8, mb_convert_encoding('Ano', 'ISO-8859-1','UTF-8'), 1, 0, 'C', true);
    $pdf->Cell($pageWidth * 0.45, 8, mb_convert_encoding('Modalidade e Categoria', 'ISO-8859-1','UTF-8'), 1, 0, 'C', true);
    $pdf->Cell($pageWidth * 0.15, 8, mb_convert_encoding('Qtde. de Aulas', 'ISO-8859-1','UTF-8'), 1, 0, 'C', true);
    $pdf->Cell($pageWidth * 0.15, 8, mb_convert_encoding('Hora Aula', 'ISO-8859-1','UTF-8'), 1, 0, 'C', true);
    $pdf->Cell($pageWidth * 0.13, 8, mb_convert_encoding('Total Hora', 'ISO-8859-1','UTF-8'), 1, 1, 'C', true);
    $pdf->SetFont('Arial','',9);

    $totProfAulas = 0; $totProfMin = 0;
    foreach ($dadosProfessor['modalidades'] as $m) {
        $mc = $m['modalidade'].' - '.$m['categoria'];
        $horaAulaTxt = $m['duracao_aula'].'min Cada';

        $pdf->Cell($pageWidth * 0.12, 8, mb_convert_encoding($m['ano'], 'ISO-8859-1','UTF-8'), 1, 0, 'C');
        $pdf->Cell($pageWidth * 0.45, 8, mb_convert_encoding($mc, 'ISO-8859-1','UTF-8'), 1, 0, 'L');
        $pdf->Cell($pageWidth * 0.15, 8, mb_convert_encoding($m['qtde_aulas'], 'ISO-8859-1','UTF-8'), 1, 0, 'C');
        $pdf->Cell($pageWidth * 0.15, 8, mb_convert_encoding($horaAulaTxt, 'ISO-8859-1','UTF-8'), 1, 0, 'C');
        $pdf->Cell($pageWidth * 0.13, 8, mb_convert_encoding($m['total_horas_formatado'], 'ISO-8859-1','UTF-8'), 1, 1, 'C');

        $totProfAulas += (int)$m['qtde_aulas'];
        $totProfMin   += (int)$m['total_minutos'];
    }

    // Totais do professor
    $pdf->SetFont('Arial','B',9);
    $pdf->SetFillColor(240,240,240);
    $pdf->Cell($pageWidth * 0.57, 8, mb_convert_encoding('Total de hora/aula dada', 'ISO-8859-1','UTF-8'), 1, 0, 'L', true);
    $pdf->Cell($pageWidth * 0.15, 8, mb_convert_encoding($totProfAulas, 'ISO-8859-1','UTF-8'), 1, 0, 'C', true);
    $pdf->Cell($pageWidth * 0.28, 8, mb_convert_encoding(formatarHorasMinutos($totProfMin), 'ISO-8859-1','UTF-8'), 1, 1, 'C', true);

    // Separador
    $pdf->Ln(2);
    $yLine2 = $pdf->GetY();
    $pdf->Line(10, $yLine2, 10 + $pageWidth, $yLine2);
    $pdf->Ln(8);

    $totalGeralAulas += $totProfAulas;
    $totalGeralMin   += $totProfMin;
}

// Resumo geral
if (count($dadosPorProfessor) > 1) {
    if ($pdf->checkPageBreak(20)) $pdf->AddPage();
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0, 8, mb_convert_encoding('RESUMO GERAL', 'ISO-8859-1','UTF-8'), 0, 1, 'C');
    $pdf->Ln(2);

    $pdf->SetFont('Arial','B',10);
    $pdf->SetFillColor(200,200,200);
    $pageWidth = $pdf->GetPageWidth() - 20;
    $pdf->Cell($pageWidth * 0.57, 10, mb_convert_encoding('TOTAL GERAL DE HORA/AULA', 'ISO-8859-1','UTF-8'), 1, 0, 'L', true);
    $pdf->Cell($pageWidth * 0.15, 10, mb_convert_encoding($totalGeralAulas, 'ISO-8859-1','UTF-8'), 1, 0, 'C', true);
    $pdf->Cell($pageWidth * 0.28, 10, mb_convert_encoding(formatarHorasMinutos($totalGeralMin), 'ISO-8859-1','UTF-8'), 1, 1, 'C', true);
}

$pdf->Output();
exit;
?>
