<?php
// app/views/turno-geral.php (VERSÃO COM VALIDAÇÕES RÍGIDAS)
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

// ---------------- security / logging ----------------
$SEG_LOG = __DIR__ . '/../../logs/seguranca.log';
function logSecurity($msg){
    global $SEG_LOG;
    $meta = [
        'ts'=>date('c'),
        'ip'=>$_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'ua'=>$_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'qs'=>$_SERVER['QUERY_STRING'] ?? '',
        'script'=>basename($_SERVER['SCRIPT_NAME'] ?? '')
    ];
    $entry = '['.date('d-M-Y H:i:s T').'] [SEGURANCA] '.$msg.' | META='.json_encode($meta, JSON_UNESCAPED_UNICODE).PHP_EOL;
    @file_put_contents($SEG_LOG, $entry, FILE_APPEND | LOCK_EX);
}
function abortClient(){ http_response_code(400); die('Parâmetros inválidos'); }

// ---------------- whitelist + validação de GET ----------------
$allowed = ['prof','nivel'];
$received = array_keys($_GET);
$extra = array_diff($received, $allowed);
if (!empty($extra)) {
    logSecurity('Parâmetros inesperados em turno-geral: '.implode(', ',$extra));
    abortClient();
}

// prof só aceita exatamente "1"
$profOpt = false;
if (array_key_exists('prof', $_GET)) {
    if ((string)$_GET['prof'] !== '1') {
        logSecurity("Valor inválido para 'prof' em turno-geral: ".($_GET['prof'] ?? ''));
        abortClient();
    }
    $profOpt = true;
}

// nivel se presente deve ser 'todos' ou inteiro positivo
$nivelOpt = null;
if (array_key_exists('nivel', $_GET)) {
    $raw = $_GET['nivel'];
    if ($raw === 'todos') {
        $nivelOpt = 'todos';
    } else {
        $n = filter_var($raw, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if ($n === false) {
            logSecurity("Valor inválido para 'nivel' em turno-geral: ".($raw ?? ''));
            abortClient();
        }
        $nivelOpt = $n;
    }
}

// obrigar sessão ativa (init.php deve iniciar sessão)
if (empty($_SESSION['id_usuario'])) {
    logSecurity("Acesso sem sessão a turno-geral");
    abortClient();
}

// ---------------- Cabeçalho padrão (mantido) ----------------
$LOGO_SIZE_MM = 15;
$LOGO_GAP_MM  = 5;

class PDF extends FPDF {
    public function Footer(){
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,iconv('UTF-8','ISO-8859-1','Página '.$this->PageNo()),0,0,'L');
        $this->Cell(0,10,iconv('UTF-8','ISO-8859-1','Impresso em: '.date('d/m/Y H:i:s')),0,0,'R');
    }
}

// ---------------- Carrega turnos (prepared) ----------------
try {
    $sqlTurnos = "
        SELECT id_turno, nome_turno,
               TIME_FORMAT(horario_inicio_turno, '%H:%i') AS h_inicio,
               TIME_FORMAT(horario_fim_turno,    '%H:%i') AS h_fim
        FROM turno
        ORDER BY nome_turno ASC
    ";
    $stT = $pdo->prepare($sqlTurnos);
    $stT->execute();
    $allTurnos = $stT->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logSecurity("Erro SQL listar turnos: ".$e->getMessage());
    $pdf = new PDF('P','mm','A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial','I',12);
    $pdf->Cell(0,10,iconv('UTF-8','ISO-8859-1','Erro interno ao carregar turnos.'),0,1,'C');
    $pdf->Output();
    exit;
}

$pdf = new PDF('P','mm','A4');
$pdf->SetTitle(iconv('UTF-8','ISO-8859-1','Relatório Geral de Turnos'));

if (!$allTurnos) {
    $pdf->AddPage();
    $pdf->SetFont('Arial','I',12);
    $pdf->Cell(0,10,iconv('UTF-8','ISO-8859-1','Nenhum turno cadastrado.'),0,1,'C');
    $pdf->Output();
    exit;
}

// ---------------- Dados instituição (mantidos) ----------------
$inst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$nomeInst = $inst ? ($inst['nome_instituicao'] ?? '') : '';
$logoPath = ($inst && !empty($inst['imagem_instituicao'])) ? LOGO_PATH.'/'.basename($inst['imagem_instituicao']) : null;
$hasLogo  = ($logoPath && file_exists($logoPath));

// ---------------- Loop por turno (mantido layout) ----------------
foreach ($allTurnos as $turno) {
    $pdf->AddPage();

    // cabeçalho visual (mantido)
    $pdf->SetY(12);
    $pdf->SetFont('Arial','B',12);
    $txt   = iconv('UTF-8','ISO-8859-1',$nomeInst);
    $txtW  = $pdf->GetStringWidth($txt);
    $totalW = $hasLogo ? ($LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0) + $txtW) : $txtW;
    $pageW  = $pdf->GetPageWidth();
    $x      = ($pageW - $totalW)/2;
    $y      = $pdf->GetY();

    if ($hasLogo) {
        $pdf->Image($logoPath, $x, $y-2, $LOGO_SIZE_MM, $LOGO_SIZE_MM);
        $x += $LOGO_SIZE_MM + ($nomeInst ? $LOGO_GAP_MM : 0);
    }
    if ($nomeInst) {
        $pdf->SetXY($x,$y);
        $pdf->Cell($txtW, $LOGO_SIZE_MM, $txt, 0, 1, 'L');
    }

    $pdf->Ln(3);
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,8,iconv('UTF-8','ISO-8859-1','Relatório Geral de Turnos'),0,1,'L');
    $pdf->Ln(1);

    // Turno nome/horário (mantido)
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,8,iconv('UTF-8','ISO-8859-1','Turno: '.$turno['nome_turno']),0,1);
    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(220,220,220);
    $pdf->Cell(80,10, iconv('UTF-8','ISO-8859-1','Nome do Turno'), 1,0,'C',true);
    $pdf->Cell(110,10, iconv('UTF-8','ISO-8859-1','Horário (Início - Fim)'),1,1,'C',true);
    $pdf->SetFont('Arial','',11);
    $pdf->Cell(80,10, iconv('UTF-8','ISO-8859-1',$turno['nome_turno']),1,0,'C');
    $pdf->Cell(110,10, iconv('UTF-8','ISO-8859-1',$turno['h_inicio'].' - '.$turno['h_fim']),1,1,'C');

    // Aulas por dia (prepared)
    $pdf->Ln(4);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,8,iconv('UTF-8','ISO-8859-1','Quantidade de Aulas por Dia da Semana'),0,1);

    try {
        $stDias = $pdo->prepare("SELECT dia_semana, aulas_no_dia FROM turno_dias WHERE id_turno = :id");
        $stDias->execute([':id'=>$turno['id_turno']]);
        $rowsDias = $stDias->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        logSecurity("Erro SQL buscar turno_dias (id_turno=".$turno['id_turno']."): ".$e->getMessage());
        $rowsDias = [];
    }

    $daysPt = ['Domingo'=>'Domingo','Segunda'=>'Segunda','Terca'=>'Terça','Quarta'=>'Quarta','Quinta'=>'Quinta','Sexta'=>'Sexta','Sabado'=>'Sábado'];
    $aulas = ['Domingo'=>0,'Segunda'=>0,'Terca'=>0,'Quarta'=>0,'Quinta'=>0,'Sexta'=>0,'Sabado'=>0];
    foreach ($rowsDias as $r) { if (isset($aulas[$r['dia_semana']])) $aulas[$r['dia_semana']] = (int)$r['aulas_no_dia']; }

    $colW = [27,27,27,27,27,27,28]; $keys = array_keys($daysPt);
    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(220,220,220);
    for($i=0;$i<7;$i++) $pdf->Cell($colW[$i],10,iconv('UTF-8','ISO-8859-1',$daysPt[$keys[$i]]),1,0,'C',true);
    $pdf->Ln();
    $pdf->SetFont('Arial','',11);
    for($i=0;$i<7;$i++) $pdf->Cell($colW[$i],10,$aulas[$keys[$i]],1,0,'C');
    $pdf->Ln();

    // Professores vinculados (se solicitado) - validações já feitas
    if ($profOpt) {
        $pdf->Ln(4);
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(0,8, iconv('UTF-8','ISO-8859-1','Professores Vinculados'), 0,1);

        $sqlProf = "
            SELECT DISTINCT p.nome_completo
            FROM professor_disciplinas_turmas pdt
            JOIN professor p ON pdt.id_professor = p.id_professor
            JOIN turma t ON pdt.id_turma = t.id_turma
            JOIN serie s ON t.id_serie = s.id_serie
            JOIN nivel_ensino n ON s.id_nivel_ensino = n.id_nivel_ensino
            WHERE t.id_turno = :idTurno
        ";
        if (!is_null($nivelOpt) && $nivelOpt !== 'todos') {
            $sqlProf .= " AND n.id_nivel_ensino = :idNivel ";
        }
        $sqlProf .= " ORDER BY p.nome_completo ";

        try {
            $stmtProf = $pdo->prepare($sqlProf);
            $stmtProf->bindValue(':idTurno', $turno['id_turno'], PDO::PARAM_INT);
            if (!is_null($nivelOpt) && $nivelOpt !== 'todos') $stmtProf->bindValue(':idNivel', intval($nivelOpt), PDO::PARAM_INT);
            $stmtProf->execute();
            $professores = $stmtProf->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            logSecurity("Erro SQL buscar professores vinculados (turno=".$turno['id_turno']."): ".$e->getMessage());
            $professores = [];
        }

        if (!$professores) {
            $pdf->SetFont('Arial','I',12);
            $pdf->Cell(0, 8, iconv('UTF-8','ISO-8859-1','Nenhum professor vinculado a turmas deste turno.'), 0,1);
        } else {
            $pdf->SetFont('Arial','B',12);
            $pdf->SetFillColor(220,220,220);
            $colW = 85; $spaceW = 20;
            $pdf->Cell($colW,10,iconv('UTF-8','ISO-8859-1','Nome do Professor(a)'),1,0,'C',true);
            $pdf->Cell($spaceW,10,'',0,0);
            $pdf->Cell($colW,10,iconv('UTF-8','ISO-8859-1','Nome do Professor(a)'),1,1,'C',true);

            $pdf->SetFont('Arial','',11);
            for ($i=0;$i<count($professores);$i+=2) {
                $p1 = $professores[$i];
                $p2 = ($i+1 < count($professores)) ? $professores[$i+1] : '';
                $pdf->Cell($colW,10,iconv('UTF-8','ISO-8859-1',$p1),1,0,'C');
                $pdf->Cell($spaceW,10,'',0,0);
                $pdf->Cell($colW,10,iconv('UTF-8','ISO-8859-1',$p2),1,1,'C');
            }
        }
    }

    // Professores das escolinhas (mantido, com prepared)
    $pdf->Ln(4);
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,8, iconv('UTF-8','ISO-8859-1','Professores das escolinhas de treino'), 0,1);

    $sqlEscolProf = "
        SELECT DISTINCT p.nome_completo
        FROM professor_turnos pt
        JOIN professor p ON pt.id_professor = p.id_professor
        WHERE pt.id_turno = :idTurno
          AND NOT EXISTS (
              SELECT 1
              FROM professor_disciplinas_turmas pdt
              JOIN turma t ON pdt.id_turma = t.id_turma
              WHERE pdt.id_professor = pt.id_professor
                AND t.id_turno = pt.id_turno
          )
        ORDER BY p.nome_completo
    ";
    try {
        $stEP = $pdo->prepare($sqlEscolProf);
        $stEP->bindValue(':idTurno', $turno['id_turno'], PDO::PARAM_INT);
        $stEP->execute();
        $profsEscolinha = $stEP->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        logSecurity("Erro SQL buscar profs escolinha (turno=".$turno['id_turno']."): ".$e->getMessage());
        $profsEscolinha = [];
    }

    if (!$profsEscolinha) {
        $pdf->SetFont('Arial','I',12);
        $pdf->Cell(0,8, iconv('UTF-8','ISO-8859-1','Nenhum professor de escolinha vinculado a este turno.'), 0,1);
    } else {
        $pdf->SetFont('Arial','B',12);
        $pdf->SetFillColor(220,220,220);
        $colW = 85; $spaceW = 20;
        $pdf->Cell($colW,10,iconv('UTF-8','ISO-8859-1','Nome do Professor(a)'),1,0,'C',true);
        $pdf->Cell($spaceW,10,'',0,0);
        $pdf->Cell($colW,10,iconv('UTF-8','ISO-8859-1','Nome do Professor(a)'),1,1,'C',true);

        $pdf->SetFont('Arial','',11);
        for ($i=0;$i<count($profsEscolinha);$i+=2) {
            $p1 = $profsEscolinha[$i];
            $p2 = ($i+1 < count($profsEscolinha)) ? $profsEscolinha[$i+1] : '';
            $pdf->Cell($colW,10,iconv('UTF-8','ISO-8859-1',$p1),1,0,'C');
            $pdf->Cell($spaceW,10,'',0,0);
            $pdf->Cell($colW,10,iconv('UTF-8','ISO-8859-1',$p2),1,1,'C');
        }
    }

    // Turmas filtradas por nível se nivelOpt estiver setado (mantido)
    if (!is_null($nivelOpt)) {
        $pdf->Ln(4);
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(0,8,iconv('UTF-8','ISO-8859-1','Turmas'),0,1);

        $sqlTur = "
            SELECT ne.id_nivel_ensino, ne.nome_nivel_ensino,
                   s.id_serie, s.nome_serie,
                   t.id_turma, t.nome_turma
            FROM turma t
            JOIN serie s ON t.id_serie = s.id_serie
            JOIN nivel_ensino ne ON s.id_nivel_ensino = ne.id_nivel_ensino
            WHERE t.id_turno = :idTurno
        ";
        if ($nivelOpt !== 'todos') $sqlTur .= " AND ne.id_nivel_ensino = :idNivel ";
        $sqlTur .= " ORDER BY ne.nome_nivel_ensino, s.nome_serie, t.nome_turma ";

        try {
            $stmtTur = $pdo->prepare($sqlTur);
            $stmtTur->bindValue(':idTurno', $turno['id_turno'], PDO::PARAM_INT);
            if ($nivelOpt !== 'todos') $stmtTur->bindValue(':idNivel', intval($nivelOpt), PDO::PARAM_INT);
            $stmtTur->execute();
            $rowsTur = $stmtTur->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            logSecurity("Erro SQL listar turmas por turno (turno=".$turno['id_turno']."): ".$e->getMessage());
            $rowsTur = [];
        }

        if (!$rowsTur) {
            $pdf->SetFont('Arial','I',12);
            $pdf->Cell(0, 8, iconv('UTF-8','ISO-8859-1','Não há turmas registradas para este Turno (ou Nível selecionado).'), 0,1);
        } else {
            // agrupamento e render mantidos (igual ao original)
            $group = [];
            foreach ($rowsTur as $r) {
                $idN = $r['id_nivel_ensino'];
                if (!isset($group[$idN])) $group[$idN] = ['nome'=>$r['nome_nivel_ensino'],'series'=>[]];
                $idS = $r['id_serie'];
                if (!isset($group[$idN]['series'][$idS])) $group[$idN]['series'][$idS] = ['serie'=>$r['nome_serie'],'turmas'=>[]];
                $group[$idN]['series'][$idS]['turmas'][] = $r['nome_turma'];
            }

            $pdf->SetFont('Arial','B',12);
            $pdf->SetFillColor(220,220,220);
            $pdf->Cell(60,10,iconv('UTF-8','ISO-8859-1','Nível de Ensino'),1,0,'C',true);
            $pdf->Cell(60,10,iconv('UTF-8','ISO-8859-1','Série'),1,0,'C',true);
            $pdf->Cell(70,10,iconv('UTF-8','ISO-8859-1','Turmas'),1,1,'C',true);

            $pdf->SetFont('Arial','',11);
            $rowH = 10;
            foreach ($group as $nivel) {
                $xLeft = $pdf->GetX();
                $yTop  = $pdf->GetY();
                $series = array_values($nivel['series']);
                $rowsN  = count($series);
                if ($rowsN === 0) continue;
                for ($i=0;$i<$rowsN;$i++) {
                    $pdf->SetX($xLeft + 60);
                    $serie = $series[$i]['serie'];
                    $turmasStr = implode(', ', $series[$i]['turmas']);
                    $pdf->Cell(60,$rowH,iconv('UTF-8','ISO-8859-1',$serie),1,0,'C');
                    $pdf->Cell(70,$rowH,iconv('UTF-8','ISO-8859-1',$turmasStr),1,1,'C');
                }
                $nivelCellHeight = $rowH * $rowsN;
                $pdf->SetXY($xLeft, $yTop);
                $pdf->Cell(60, $nivelCellHeight, iconv('UTF-8','ISO-8859-1',$nivel['nome']), 1, 0, 'C');
                $pdf->SetY($yTop + $nivelCellHeight);
                $pdf->Ln(2);
            }
        }
    }
}

$pdf->Output();
exit;
?>
