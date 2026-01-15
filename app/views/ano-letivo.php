<?php
// app/views/ano-letivo.php
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

class PDF extends FPDF
{
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);

        $this->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1', 'Página ' . $this->PageNo()), 0, 0, 'L');
        $this->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1', 'Impresso em: ' . date('d/m/Y H:i:s')), 0, 0, 'R');
    }
}

/* ---------------- helpers ---------------- */

function enc($s) { return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$s); }

function fmtDateBR($d): string
{
    $ts = strtotime((string)$d);
    return $ts ? date('d/m/Y', $ts) : '';
}

function normalizeDiaKey($diaRaw): string
{
    $d = trim((string)$diaRaw);
    if ($d === '') return '';

    // numérico (1..7) se existir esse padrão
    if (ctype_digit($d)) {
        $n = (int)$d;
        $mapN = [1=>'Domingo',2=>'Segunda',3=>'Terca',4=>'Quarta',5=>'Quinta',6=>'Sexta',7=>'Sabado'];
        return $mapN[$n] ?? '';
    }

    $x = mb_strtolower($d, 'UTF-8');
    $x = strtr($x, [
        'á'=>'a','à'=>'a','â'=>'a','ã'=>'a',
        'é'=>'e','ê'=>'e',
        'í'=>'i',
        'ó'=>'o','ô'=>'o','õ'=>'o',
        'ú'=>'u',
        'ç'=>'c'
    ]);
    $x = preg_replace('/[^a-z]/', '', $x);

    if ($x === 'domingo') return 'Domingo';
    if ($x === 'segunda' || $x === 'segundafeira') return 'Segunda';
    if ($x === 'terca'   || $x === 'tercafeira')   return 'Terca';
    if ($x === 'quarta'  || $x === 'quartafeira')  return 'Quarta';
    if ($x === 'quinta'  || $x === 'quintafeira')  return 'Quinta';
    if ($x === 'sexta'   || $x === 'sextafeira')   return 'Sexta';
    if ($x === 'sabado') return 'Sabado';

    return '';
}

function diaLabel($key): string
{
    $labels = [
        'Domingo' => 'Domingo',
        'Segunda' => 'Segunda',
        'Terca'   => 'Terça',
        'Quarta'  => 'Quarta',
        'Quinta'  => 'Quinta',
        'Sexta'   => 'Sexta',
        'Sabado'  => 'Sábado',
    ];
    return $labels[$key] ?? $key;
}

function parseProfFilter($raw)
{
    if ($raw === null) return null;
    $s = trim((string)$raw);
    $low = mb_strtolower($s, 'UTF-8');

    // checkbox padrão: on / vazio / 1 => todas
    if ($s === '' || $low === 'on' || $s === '1' || $low === 'true') return 'todas';
    if ($low === 'todas') return 'todas';
    if (ctype_digit($s) && (int)$s >= 1) return (int)$s;

    // inválido => não quebra relatório
    return 'todas';
}

/*
 Resolve id_ano de forma robusta:
 - se vazio/0 => null (geral)
 - se numérico:
    1) tenta como id_ano_letivo
    2) se não existir, tenta como ano (ex.: 2026) e pega id_ano_letivo
*/
function resolveIdAnoLetivo(PDO $pdo, $raw): ?int
{
    $s = trim((string)$raw);
    if ($s === '' || $s === '0') return null;
    if (!ctype_digit($s)) return null;

    $v = (int)$s;

    $st = $pdo->prepare("SELECT id_ano_letivo FROM ano_letivo WHERE id_ano_letivo = :v LIMIT 1");
    $st->execute([':v' => $v]);
    $id = $st->fetchColumn();
    if ($id) return (int)$id;

    $st2 = $pdo->prepare("SELECT id_ano_letivo FROM ano_letivo WHERE ano = :v ORDER BY id_ano_letivo DESC LIMIT 1");
    $st2->execute([':v' => $v]);
    $id2 = $st2->fetchColumn();
    if ($id2) return (int)$id2;

    return null;
}

/* ---------------- 1) Parâmetros ---------------- */

$id_ano_raw     = $_GET['id_ano'] ?? 0;
$id_ano         = resolveIdAnoLetivo($pdo, $id_ano_raw); // null=geral; int=específico
$turma_enabled  = isset($_GET['turma']);                 // checkbox: só existir já ativa
$prof_enabled   = isset($_GET['prof_restricao']);        // idem
$prof_filter    = $prof_enabled ? parseProfFilter($_GET['prof_restricao'] ?? null) : null;

/* ---------------- 2) PDF ---------------- */

$pdf = new PDF('P', 'mm', 'A4');
$pdf->SetTitle(enc('Relatório de Ano Letivo'));
$pdf->AddPage();

/* ---------------- 3) Cabeçalho (IGUAL AO ORIGINAL) ---------------- */

$LOGO_SIZE_MM = 15;
$LOGO_GAP_MM  = 5;

$stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
$inst = $stmtInst->fetch(PDO::FETCH_ASSOC);

$topY = 12;
if ($inst) {
    $nomeInst = $inst['nome_instituicao'] ?? '';

    $pdf->SetFont('Arial','B',14);
    $text = iconv('UTF-8','ISO-8859-1',$nomeInst);
    $textW = $pdf->GetStringWidth($text);

    $totalW = $LOGO_SIZE_MM + $LOGO_GAP_MM + $textW;
    $pageW  = $pdf->GetPageWidth();
    $xStart = ($pageW - $totalW) / 2;

    if (!empty($inst['imagem_instituicao'])) {
        $logoPath = LOGO_PATH . '/' . basename($inst['imagem_instituicao']);
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, $xStart, $topY, $LOGO_SIZE_MM, $LOGO_SIZE_MM);
        }
    }

    $textY = $topY + ($LOGO_SIZE_MM / 2) - 5;
    if ($textY < $topY) $textY = $topY;

    $pdf->SetXY($xStart + $LOGO_SIZE_MM + $LOGO_GAP_MM, $textY);
    $pdf->Cell($textW + 1, 10, $text, 0, 1, 'L');
}

$pdf->SetY($topY + $LOGO_SIZE_MM + 6);

/* ---------------- Título ---------------- */

$pdf->SetFont('Arial','B',14);
$pdf->Cell(0, 8, enc('Relatório de Ano Letivo'), 0, 1, 'L');
$pdf->Ln(1);

/* ---------------- 4) Tabela: Ano Letivo ---------------- */

$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(220,220,220);
$pdf->Cell(40, 8, enc('Ano Letivo'), 1, 0, 'C', true);
$pdf->Cell(75, 8, enc('Data Início'), 1, 0, 'C', true);
$pdf->Cell(75, 8, enc('Data Fim'),    1, 1, 'C', true);

$pdf->SetFont('Arial','',11);

if ($id_ano !== null) {
    $stmtAno = $pdo->prepare("SELECT * FROM ano_letivo WHERE id_ano_letivo = :id LIMIT 1");
    $stmtAno->execute([':id' => $id_ano]);
} else {
    $stmtAno = $pdo->query("SELECT * FROM ano_letivo ORDER BY ano");
}
$anos = $stmtAno->fetchAll(PDO::FETCH_ASSOC);

if ($id_ano !== null && empty($anos)) {
    $pdf->Cell(190, 8, enc('Ano letivo selecionado não foi encontrado.'), 1, 1, 'C');
    $pdf->Output();
    exit;
}

foreach ($anos as $a) {
    $dataIni = fmtDateBR($a['data_inicio'] ?? '');
    $dataFim = fmtDateBR($a['data_fim'] ?? '');
    $pdf->Cell(40, 8, enc($a['ano'] ?? ''), 1, 0, 'C');
    $pdf->Cell(75, 8, enc($dataIni),       1, 0, 'C');
    $pdf->Cell(75, 8, enc($dataFim),       1, 1, 'C');
}

/* ---------------- 5) Turmas (opcional) ---------------- */

if ($turma_enabled) {
    $pdf->Ln(6);
    $pdf->SetFont('Arial','B',13);
    $pdf->Cell(0,8, enc('Turmas'), 0,1,'L');

    $renderTabelaTurmas = function($idAnoLetivo) use ($pdo, $pdf) {
        $sqlTurmas = "
            SELECT
                n.nome_nivel_ensino AS nivel,
                s.nome_serie AS serie,
                GROUP_CONCAT(DISTINCT t.nome_turma ORDER BY t.nome_turma SEPARATOR ', ') AS turmas
            FROM turma t
            JOIN serie s ON t.id_serie = s.id_serie
            JOIN nivel_ensino n ON s.id_nivel_ensino = n.id_nivel_ensino
            WHERE t.id_ano_letivo = :id_ano
            GROUP BY n.id_nivel_ensino, s.id_serie
            ORDER BY n.nome_nivel_ensino, s.nome_serie
        ";
        $stT = $pdo->prepare($sqlTurmas);
        $stT->bindValue(':id_ano', (int)$idAnoLetivo, PDO::PARAM_INT);
        $stT->execute();
        $turmas = $stT->fetchAll(PDO::FETCH_ASSOC);

        $pdf->SetFont('Arial','B',11);
        $pdf->SetFillColor(200,200,200);
        $pdf->Cell(60, 8, enc('Nível de Ensino'), 1, 0, 'C', true);
        $pdf->Cell(40, 8, enc('Série'),          1, 0, 'C', true);
        $pdf->Cell(90, 8, enc('Turmas'),         1, 1, 'C', true);

        $pdf->SetFont('Arial','',10);
        if (empty($turmas)) {
            $pdf->Cell(190, 8, enc('Nenhuma turma encontrada.'), 1, 1, 'C');
        } else {
            foreach ($turmas as $t) {
                $pdf->Cell(60, 8, enc($t['nivel'] ?? ''),  1, 0, 'C');
                $pdf->Cell(40, 8, enc($t['serie'] ?? ''),  1, 0, 'C');
                $pdf->Cell(90, 8, enc($t['turmas'] ?? ''), 1, 1, 'C');
            }
        }
    };

    if ($id_ano === null) {
        foreach ($anos as $a) {
            $pdf->SetFont('Arial','B',11);
            $pdf->Cell(0,7, enc('Ano: ' . ($a['ano'] ?? '')), 0, 1, 'L');
            $pdf->Ln(1);
            $renderTabelaTurmas((int)$a['id_ano_letivo']);
            $pdf->Ln(4);
        }
    } else {
        $renderTabelaTurmas($id_ano);
    }
}

/* ---------------- 6) Professor Restrição (opcional) ---------------- */

if ($prof_enabled) {
    $pdf->Ln(6);
    $pdf->SetFont('Arial','B',13);
    $pdf->Cell(0,8, enc('Professor Restrição'), 0,1,'L');

    $diasOrdemBase = ['Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado'];

    // Consulta (filtra por ano quando selecionado)
    $sqlProf = "
        SELECT pr.id_ano_letivo,
               a.ano AS ano_label,
               pr.id_professor,
               p.nome_completo AS nome_professor,
               pr.dia_semana,
               pr.numero_aula
          FROM professor_restricoes pr
          JOIN professor p  ON pr.id_professor = p.id_professor
          JOIN ano_letivo a ON pr.id_ano_letivo = a.id_ano_letivo
         WHERE 1=1
    ";
    $params = [];
    if ($id_ano !== null) {
        $sqlProf .= " AND pr.id_ano_letivo = :id_ano ";
        $params[':id_ano'] = $id_ano;
    }
    if ($prof_filter !== null && $prof_filter !== 'todas') {
        $sqlProf .= " AND pr.id_professor = :p ";
        $params[':p'] = (int)$prof_filter;
    }
    $sqlProf .= " ORDER BY a.ano, p.nome_completo, pr.numero_aula";

    $stP = $pdo->prepare($sqlProf);
    $stP->execute($params);
    $rows = $stP->fetchAll(PDO::FETCH_ASSOC);

    // Agrupa: ano -> professor -> dia -> aulas
    $porAno = [];
    $diasPresentesPorAno = []; // para decidir se Domingo/Sábado aparecem (só se existir dado)
    foreach ($rows as $r) {
        $anoKey = (string)($r['ano_label'] ?? '');
        $idProf = (int)($r['id_professor'] ?? 0);
        if ($idProf <= 0) continue;

        $diaKey = normalizeDiaKey($r['dia_semana'] ?? '');
        if ($diaKey === '') continue;

        $numAula = (int)($r['numero_aula'] ?? 0);
        if ($numAula <= 0) continue;

        $diasPresentesPorAno[$anoKey][$diaKey] = true;

        if (!isset($porAno[$anoKey])) $porAno[$anoKey] = [];
        if (!isset($porAno[$anoKey][$idProf])) {
            $porAno[$anoKey][$idProf] = ['nome' => ($r['nome_professor'] ?? ''), 'restricoes' => []];
        }
        if (!isset($porAno[$anoKey][$idProf]['restricoes'][$diaKey])) {
            $porAno[$anoKey][$idProf]['restricoes'][$diaKey] = [];
        }
        $porAno[$anoKey][$idProf]['restricoes'][$diaKey][] = $numAula;
    }

    // Normaliza arrays (unique + sort)
    foreach ($porAno as $anoK => &$profs) {
        foreach ($profs as &$info) {
            foreach ($info['restricoes'] as &$arr) {
                $arr = array_values(array_unique(array_map('intval', $arr)));
                sort($arr, SORT_NUMERIC);
            }
            unset($arr);
        }
        unset($info);
    }
    unset($profs);

    // Renderizadores para repetir em quebra de página
    $renderTabelaHeader = function() use ($pdf) {
        $pdf->SetFont('Arial','B',11);
        $pdf->SetFillColor(200,200,200);
        $pdf->Cell(70, 8, enc('Nome Professor'), 1, 0, 'C', true);
        $pdf->Cell(60, 8, enc('Dia da Semana'),  1, 0, 'C', true);
        $pdf->Cell(60, 8, enc('Aulas'),          1, 1, 'C', true);
        $pdf->SetFont('Arial','',10);
    };

    $renderAnoLabel = function($anoLabel) use ($pdf) {
        $pdf->Ln(3);
        $pdf->SetFont('Arial','B',11);
        $pdf->Cell(0,7, enc('Ano: ' . $anoLabel), 0, 1, 'L');
        $pdf->SetFont('Arial','',10);
    };

    $ensureSpace = function(float $neededHeight, ?string $anoLabelForRepeat) use ($pdf, $renderTabelaHeader, $renderAnoLabel, $id_ano) {
        // margem segura inferior (não mexe no seu cabeçalho)
        $bottomSafe = 20;
        $limit = $pdf->GetPageHeight() - $bottomSafe;

        if (($pdf->GetY() + $neededHeight) > $limit) {
            $pdf->AddPage();
            // se é relatório geral (todos os anos), repetir o "Ano: X" no topo da página nova
            if ($id_ano === null && $anoLabelForRepeat !== null && $anoLabelForRepeat !== '') {
                $renderAnoLabel($anoLabelForRepeat);
            }
            // repetir cabeçalho da tabela sempre que trocar de página
            $renderTabelaHeader();
        }
    };

    if (empty($porAno)) {
        $pdf->SetFont('Arial','I',11);
        $pdf->Cell(0,8, enc('Nenhuma restrição encontrada para o filtro aplicado.'), 0, 1, 'L');
    } else {
        // cabeçalho da tabela na primeira vez
        $renderTabelaHeader();

        $rowH = 8;

        foreach ($porAno as $anoLabel => $professores) {

            // Dias efetivamente existentes nesse ano (se não existir Sáb/Dom, nem entra na checagem)
            $diasOrdem = [];
            foreach ($diasOrdemBase as $d) {
                if (!empty($diasPresentesPorAno[$anoLabel][$d])) $diasOrdem[] = $d;
            }
            if (empty($diasOrdem)) $diasOrdem = $diasOrdemBase;

            // Se geral (todos anos), separa por ano (e evita quebrar logo depois do label)
            if ($id_ano === null) {
                $ensureSpace(12, null);
                $renderAnoLabel($anoLabel);
                $renderTabelaHeader();
            }

            foreach ($professores as $dataP) {
                $nomeProf = $dataP['nome'] ?? '';

                // Dias que realmente têm restrição (DOM/SÁB só entram se houver restrição desse professor)
                $diasDoProfessor = [];
                foreach ($diasOrdem as $diaKey) {
                    if (!empty($dataP['restricoes'][$diaKey])) {
                        $diasDoProfessor[] = $diaKey;
                    }
                }
                if (empty($diasDoProfessor)) continue;

                $linhas = count($diasDoProfessor);
                $nameHeight = $rowH * $linhas;

                // ---- FIX PRINCIPAL: não deixa começar um professor no fim da página e quebrar o bloco
                $ensureSpace($nameHeight + 3, $anoLabel);

                $x = $pdf->GetX();
                $y = $pdf->GetY();

                // célula mesclada do nome
                $pdf->Cell(70, $nameHeight, enc($nomeProf), 1, 0, 'C');

                $colDiaW  = 60;
                $colAulaW = 60;

                foreach ($diasDoProfessor as $i => $diaKey) {
                    $lista = $dataP['restricoes'][$diaKey];
                    $aulasFormatadas = array_map(function($n){ return $n.'ª'; }, $lista);
                    $aulasStr = implode(', ', $aulasFormatadas);

                    $pdf->SetXY($x + 70, $y + ($i * $rowH));
                    $pdf->Cell($colDiaW,  $rowH, enc(diaLabel($diaKey)), 1, 0, 'C');
                    $pdf->Cell($colAulaW, $rowH, enc($aulasStr),         1, 0, 'C');
                }

                $pdf->SetXY($x, $y + $nameHeight);
                $pdf->Ln(3);
            }
        }
    }
}

/* ---------------- 7) Saída ---------------- */

$pdf->Output();
exit;
?>
