<?php
// app/views/turno.php
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

// ---------------- Parâmetros do cabeçalho (ajuste aqui) ----------------
$LOGO_SIZE_MM = 15; // tamanho (largura=altura) da logo em mm
$LOGO_GAP_MM  = 5;  // espaço entre a logo e o nome em mm

class PDF extends FPDF
{
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        // Esquerda: Página X (sem "/N")
        $this->Cell(0, 10, iconv('UTF-8','ISO-8859-1','Página ' . $this->PageNo()), 0, 0, 'L');
        // Direita: data/hora
        $this->Cell(0, 10, iconv('UTF-8','ISO-8859-1','Impresso em: ' . date('d/m/Y H:i:s')), 0, 0, 'R');
    }
}

// ----------------------------------------------------------------
// 1) Recupera parâmetros GET
// ----------------------------------------------------------------
$idTurno  = isset($_GET['id_turno']) ? intval($_GET['id_turno']) : 0;
$profOpt  = isset($_GET['prof']) ? true : false;                 // &prof=1
$nivelOpt = isset($_GET['nivel']) ? $_GET['nivel'] : null;       // 'todos' ou id_nivel

// ----------------------------------------------------------------
// 2) Inicia PDF
// ----------------------------------------------------------------
$pdf = new PDF('P','mm','A4');
$pdf->SetTitle(iconv('UTF-8','ISO-8859-1','Relatório de Turno'));
$pdf->AddPage();

// ----------------------------------------------------------------
// 3) Cabeçalho (logo + nome da instituição) - padrão
// ----------------------------------------------------------------
$stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
$inst     = $stmtInst->fetch(PDO::FETCH_ASSOC);
$nomeInst = $inst ? ($inst['nome_instituicao'] ?? '') : '';
$logoPath = ($inst && !empty($inst['imagem_instituicao'])) ? LOGO_PATH . '/' . basename($inst['imagem_instituicao']) : null;

// Cabeçalho (logo + nome na mesma linha, centralizados)
$pdf->SetY(12);
$pdf->SetFont('Arial','B',14);
$txt     = iconv('UTF-8','ISO-8859-1', $nomeInst);
$txtW    = $pdf->GetStringWidth($txt);
$hasLogo = ($logoPath && file_exists($logoPath));
$totalW  = $hasLogo ? ($LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0) + $txtW) : $txtW;
$pageW   = $pdf->GetPageWidth();
$x       = ($pageW - $totalW) / 2;
$y       = $pdf->GetY();

if ($hasLogo) {
    $pdf->Image($logoPath, $x, $y - 2, $LOGO_SIZE_MM, $LOGO_SIZE_MM);
    $x += $LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0);
}
if ($nomeInst) {
    $pdf->SetXY($x, $y);
    $pdf->Cell($txtW, $LOGO_SIZE_MM, $txt, 0, 1, 'L');
}

$pdf->Ln(3);
$pdf->SetFont('Arial','B',13);
$pdf->Cell(0, 7, iconv('UTF-8','ISO-8859-1','Relatório de Turno'), 0, 1, 'L');
$pdf->Ln(1);

// ----------------------------------------------------------------
// 4) Dados do Turno (sem descrição)
// ----------------------------------------------------------------
$stmtT = $pdo->prepare("
    SELECT id_turno, nome_turno,
           TIME_FORMAT(horario_inicio_turno, '%H:%i') AS h_inicio,
           TIME_FORMAT(horario_fim_turno,   '%H:%i') AS h_fim
    FROM turno
    WHERE id_turno = :id
    LIMIT 1
");
$stmtT->execute([':id' => $idTurno]);
$turno = $stmtT->fetch(PDO::FETCH_ASSOC);

// ---- Ajuste solicitado: quando um id for fornecido e não existir,
// ---- retornar 400 + "Parâmetros inválidos" em vez de "Turno não encontrado."
if (!$turno) {
    header('HTTP/1.1 400 Bad Request');
    // mensagem pequena e clara esperada pelo usuário
    die('Parâmetros inválidos');
}

$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(220,220,220);
// Cabeçalho: Nome (80) | Horário (110) => total 190
$pdf->Cell(80, 10, iconv('UTF-8','ISO-8859-1','Nome do Turno'),      1, 0, 'C', true);
$pdf->Cell(110,10, iconv('UTF-8','ISO-8859-1','Horário (Início - Fim)'), 1, 1, 'C', true);

$pdf->SetFont('Arial','',12);
$pdf->Cell(80, 10, iconv('UTF-8','ISO-8859-1',$turno['nome_turno']),                 1, 0, 'C');
$pdf->Cell(110,10, iconv('UTF-8','ISO-8859-1',$turno['h_inicio'].' - '.$turno['h_fim']), 1, 1, 'C');

// ----------------------------------------------------------------
// 5) Quantidade de aulas por dia da semana (turno_dias)
// ----------------------------------------------------------------
$pdf->Ln(8);
$pdf->SetFont('Arial','B',13);
$pdf->Cell(0, 8, iconv('UTF-8','ISO-8859-1','Quantidade de Aulas por Dia da Semana'), 0, 1, 'L');

// Labels PT com acentos
$daysPt = [
    'Domingo' => 'Domingo',
    'Segunda' => 'Segunda',
    'Terca'   => 'Terça',
    'Quarta'  => 'Quarta',
    'Quinta'  => 'Quinta',
    'Sexta'   => 'Sexta',
    'Sabado'  => 'Sábado'
];

$stmtDias = $pdo->prepare("
    SELECT dia_semana, aulas_no_dia
    FROM turno_dias
    WHERE id_turno = :id
");
$stmtDias->execute([':id' => $idTurno]);
$rowsDias = $stmtDias->fetchAll(PDO::FETCH_ASSOC);

// Base zerada
$aulasPorDia = ['Domingo'=>0,'Segunda'=>0,'Terca'=>0,'Quarta'=>0,'Quinta'=>0,'Sexta'=>0,'Sabado'=>0];
foreach ($rowsDias as $r) {
    $d = $r['dia_semana'];
    if (isset($aulasPorDia[$d])) $aulasPorDia[$d] = (int)$r['aulas_no_dia'];
}

// 7 colunas totalizando 190 mm
$colWidths = [27, 27, 27, 27, 27, 27, 28];
$dayKeys   = array_keys($daysPt);

$pdf->SetFont('Arial','B',11);
$pdf->SetFillColor(220,220,220);
// Cabeçalho
for ($i=0; $i<7; $i++) {
    $pdf->Cell($colWidths[$i], 10, iconv('UTF-8','ISO-8859-1',$daysPt[$dayKeys[$i]]), 1, 0, 'C', true);
}
$pdf->Ln();

// Valores
$pdf->SetFont('Arial','',11);
for ($i=0; $i<7; $i++) {
    $pdf->Cell($colWidths[$i], 10, $aulasPorDia[$dayKeys[$i]], 1, 0, 'C');
}
$pdf->Ln();

// ----------------------------------------------------------------
// 6) Professores Vinculados (opcional &prof=1) – 2 colunas com espaço
// ----------------------------------------------------------------
if ($profOpt) {
    $pdf->Ln(8);
    $pdf->SetFont('Arial','B',13);
    $pdf->Cell(0, 8, iconv('UTF-8','ISO-8859-1','Professores Vinculados'), 0, 1, 'L');

    $stmtProf = $pdo->prepare("
        SELECT p.nome_completo
        FROM professor_turnos pt
        JOIN professor p ON pt.id_professor = p.id_professor
        WHERE pt.id_turno = :id
        ORDER BY p.nome_completo
    ");
    $stmtProf->execute([':id' => $idTurno]);
    $professores = $stmtProf->fetchAll(PDO::FETCH_COLUMN);

    if (!$professores) {
        $pdf->SetFont('Arial','I',12);
        $pdf->Cell(0, 8, iconv('UTF-8','ISO-8859-1','Nenhum professor vinculado a este turno.'), 0, 1);
    } else {
        $pdf->SetFont('Arial','B',12);
        $pdf->SetFillColor(220,220,220);
        $colW   = 85; // 85 + 20 (espaço) + 85 = 190
        $spaceW = 20;

        // Cabeçalho das duas colunas
        $pdf->Cell($colW,   10, iconv('UTF-8','ISO-8859-1','Nome do Professor(a)'), 1, 0, 'C', true);
        $pdf->Cell($spaceW, 10, '', 0, 0);
        $pdf->Cell($colW,   10, iconv('UTF-8','ISO-8859-1','Nome do Professor(a)'), 1, 1, 'C', true);

        $pdf->SetFont('Arial','',12);
        $i = 0; $tot = count($professores);
        while ($i < $tot) {
            $p1 = $professores[$i];
            $p2 = ($i+1 < $tot) ? $professores[$i+1] : '';
            $pdf->Cell($colW,   10, iconv('UTF-8','ISO-8859-1',$p1), 1, 0, 'C');
            $pdf->Cell($spaceW, 10, '', 0, 0);
            $pdf->Cell($colW,   10, iconv('UTF-8','ISO-8859-1',$p2), 1, 1, 'C');
            $i += 2;
        }
    }
}

// ----------------------------------------------------------------
// 7) Turmas (se ?nivel=...) — filtra por Nível
// ----------------------------------------------------------------
if (!is_null($nivelOpt)) {
    $pdf->Ln(8);
    $pdf->SetFont('Arial','B',13);
    $pdf->Cell(0, 8, iconv('UTF-8','ISO-8859-1','Turmas'), 0, 1, 'L');

    $sqlTur = "
        SELECT ne.id_nivel_ensino, ne.nome_nivel_ensino,
               s.id_serie, s.nome_serie,
               t.id_turma, t.nome_turma
        FROM turma t
        JOIN serie s      ON t.id_serie = s.id_serie
        JOIN nivel_ensino ne ON s.id_nivel_ensino = ne.id_nivel_ensino
        WHERE t.id_turno = :idTurno
    ";
    if ($nivelOpt !== 'todos') {
        $sqlTur .= " AND ne.id_nivel_ensino = :idNivel ";
    }
    $sqlTur .= " ORDER BY ne.nome_nivel_ensino, s.nome_serie, t.nome_turma ";

    $stmtTur = $pdo->prepare($sqlTur);
    $stmtTur->bindValue(':idTurno', $idTurno, PDO::PARAM_INT);
    if ($nivelOpt !== 'todos') {
        $stmtTur->bindValue(':idNivel', intval($nivelOpt), PDO::PARAM_INT);
    }
    $stmtTur->execute();
    $rowsTur = $stmtTur->fetchAll(PDO::FETCH_ASSOC);

    if (!$rowsTur) {
        $pdf->SetFont('Arial','I',12);
        $pdf->Cell(0, 8, iconv('UTF-8','ISO-8859-1','Não há turmas registradas para este Turno (ou Nível selecionado).'), 0, 1);
    } else {
        // Agrupa: nível -> série -> [turmas...]
        $dataGroup = [];
        foreach ($rowsTur as $r) {
            $idNivel = $r['id_nivel_ensino'];
            if (!isset($dataGroup[$idNivel])) {
                $dataGroup[$idNivel] = [
                    'nome_nivel' => $r['nome_nivel_ensino'],
                    'series'     => []
                ];
            }
            $idSer = $r['id_serie'];
            if (!isset($dataGroup[$idNivel]['series'][$idSer])) {
                $dataGroup[$idNivel]['series'][$idSer] = [
                    'nome_serie' => $r['nome_serie'],
                    'turmas'     => []
                ];
            }
            $dataGroup[$idNivel]['series'][$idSer]['turmas'][] = $r['nome_turma'];
        }

        // Cabeçalho 190mm: 60 (Nível) | 60 (Série) | 70 (Turmas)
        $pdf->SetFont('Arial','B',12);
        $pdf->SetFillColor(220,220,220);
        $pdf->Cell(60,10, iconv('UTF-8','ISO-8859-1','Nível de Ensino'), 1, 0, 'C', true);
        $pdf->Cell(60,10, iconv('UTF-8','ISO-8859-1','Série'),          1, 0, 'C', true);
        $pdf->Cell(70,10, iconv('UTF-8','ISO-8859-1','Turmas'),         1, 1, 'C', true);

        $pdf->SetFont('Arial','',12);
        foreach ($dataGroup as $nv) {
            $nomeNivel = $nv['nome_nivel'];
            $series    = $nv['series'];
            $firstSerie = true;

            foreach ($series as $ser) {
                $nomeSerie = $ser['nome_serie'];
                $turmasStr = implode(', ', $ser['turmas']);

                // imprime nível apenas na primeira linha do bloco
                if ($firstSerie) {
                    $pdf->Cell(60,10, iconv('UTF-8','ISO-8859-1',$nomeNivel), 1, 0, 'C');
                    $firstSerie = false;
                } else {
                    $pdf->Cell(60,10, '', 1, 0, 'C');
                }
                $pdf->Cell(60,10, iconv('UTF-8','ISO-8859-1',$nomeSerie), 1, 0, 'C');
                $pdf->Cell(70,10, iconv('UTF-8','ISO-8859-1',$turmasStr), 1, 1, 'C');
            }
            $pdf->Ln(2);
        }
    }
}

// ----------------------------------------------------------------
// 8) Finaliza PDF
// ----------------------------------------------------------------
$pdf->Output();
exit;
?>
