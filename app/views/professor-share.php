<?php
// app/views/professor-share.php
require_once __DIR__ . '/../../configs/init.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

class PDF extends FPDF {
	public function Footer() {
		$this->SetY(-15);
		$this->SetFont('Arial','I',8);
		$this->Cell(
			0,
			10,
			iconv('UTF-8','ISO-8859-1','Impresso em: ' . date('d/m/Y H:i:s')),
			0,
			0,
			'R'
		);
	}
}

// ----------------------------------------------------------------
// 1) Parâmetros GET
// ----------------------------------------------------------------
$id_professor = isset($_GET['id_professor']) ? intval($_GET['id_professor']) : 0;
$id_ano	   = isset($_GET['id_ano'])	   ? intval($_GET['id_ano']) : 0;

// Checkboxes para exibição de seções
$exibeDisciplinas = isset($_GET['disciplina']) ? true : false;
$exibeRestricoes  = isset($_GET['restricoes'])  ? true : false;
$exibeTurnos	  = isset($_GET['turnos'])	  ? true : false;
$exibeTurmas	  = isset($_GET['turmas'])	  ? true : false;
$exibeHorarios	= isset($_GET['horarios'])	? true : false;

// Parâmetro obrigatório
if (!$id_professor) {
	header("Content-Type: text/plain; charset=utf-8");
	echo "Parâmetro id_professor é obrigatório.";
	exit;
}

// ----------------------------------------------------------------
// 2) Inicia PDF
// ----------------------------------------------------------------
$pdf = new PDF('P','mm','A4');
$pdf->SetTitle(iconv('UTF-8','ISO-8859-1','Relatório de Compartilhamento de Professor'));
$pdf->AddPage();

// ----------------------------------------------------------------
// 3) Cabeçalho – Logo e Nome da Instituição
// ----------------------------------------------------------------
$stmtInst = $pdo->query("SELECT nome_instituicao, imagem_instituicao FROM instituicao LIMIT 1");
$inst = $stmtInst->fetch(PDO::FETCH_ASSOC);
if ($inst && !empty($inst['imagem_instituicao'])) {
	$logoPath = LOGO_PATH . '/' . basename($inst['imagem_instituicao']);
	if (file_exists($logoPath)) {
		$desiredSize = 90 * 25.4 / 96; // Ajuste conforme sua necessidade
		$pageWidth = $pdf->GetPageWidth();
		$x = ($pageWidth - $desiredSize) / 2;
		$pdf->Image($logoPath, $x, 10, $desiredSize, $desiredSize);
	}
}
$pdf->Ln(25);
$pdf->SetFont('Arial','B',16);
if ($inst) {
	$pdf->Cell(0, 10, iconv('UTF-8','ISO-8859-1',$inst['nome_instituicao'] ?? ''), 0, 1, 'C');
}
$pdf->Ln(5);
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0, 10, iconv('UTF-8','ISO-8859-1','Relatório de Compartilhamento de Professor'), 0, 1, 'L');
$pdf->Ln(2);

// ----------------------------------------------------------------
// 4) Dados do Professor
// ----------------------------------------------------------------
$stmtProf = $pdo->prepare("SELECT * FROM professor WHERE id_professor = :id");
$stmtProf->execute([':id'=>$id_professor]);
$prof = $stmtProf->fetch(PDO::FETCH_ASSOC);
if (!$prof) {
	$pdf->SetFont('Arial','I',12);
	$pdf->Cell(0,8, iconv('UTF-8','ISO-8859-1','Professor não encontrado.'), 0, 1, 'C');
	$pdf->Output();
	exit;
}

$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(200,200,200);
$pdf->Cell(80, 10, iconv('UTF-8','ISO-8859-1','Nome Completo'), 1, 0, 'C', true);
$pdf->Cell(35, 10, iconv('UTF-8','ISO-8859-1','Nome Exibição'), 1, 0, 'C', true);
$pdf->Cell(45, 10, iconv('UTF-8','ISO-8859-1','Telefone'), 1, 0, 'C', true);
$pdf->Cell(30, 10, iconv('UTF-8','ISO-8859-1','Sexo'), 1, 1, 'C', true);

$pdf->SetFont('Arial','',12);
$pdf->Cell(80, 10, iconv('UTF-8','ISO-8859-1',$prof['nome_completo'] ?? ''), 1, 0, 'C');
$pdf->Cell(35, 10, iconv('UTF-8','ISO-8859-1',$prof['nome_exibicao'] ?? ''), 1, 0, 'C');
$pdf->Cell(45, 10, iconv('UTF-8','ISO-8859-1',$prof['telefone'] ?? ''), 1, 0, 'C');
$pdf->Cell(30, 10, iconv('UTF-8','ISO-8859-1',$prof['sexo'] ?? ''), 1, 1, 'C');

// ----------------------------------------------------------------
// 5) Restrições (se marcado) e se tiver id_ano
// ----------------------------------------------------------------
if ($exibeRestricoes && $id_ano) {
	$stmtRes = $pdo->prepare("
		SELECT dia_semana, numero_aula 
		  FROM professor_restricoes 
		 WHERE id_professor = :id_prof 
		   AND id_ano_letivo = :id_ano
	");
	$stmtRes->execute([':id_prof'=>$id_professor, ':id_ano'=>$id_ano]);
	$restricoesData = $stmtRes->fetchAll(PDO::FETCH_ASSOC);
	
	$restricoesMap = [];
	foreach ($restricoesData as $r) {
		$dia = $r['dia_semana'];
		if (!isset($restricoesMap[$dia])) {
			$restricoesMap[$dia] = [];
		}
		$restricoesMap[$dia][] = $r['numero_aula'];
	}
	
	$pdf->Ln(10);
	$pdf->SetFont('Arial','B',14);
	$pdf->Cell(190, 8, iconv('UTF-8','ISO-8859-1','Restrições'), 1, 1, 'C');
	
	$pdf->SetFont('Arial','B',10);
	$pdf->SetFillColor(200,200,200);
	$pdf->Cell(30, 8, iconv('UTF-8','ISO-8859-1','Aula/ Dia'), 1, 0, 'C', true);
	$diasUteis = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta'];
	foreach ($diasUteis as $dia) {
		$pdf->Cell(32, 8, iconv('UTF-8','ISO-8859-1',$dia), 1, 0, 'C', true);
	}
	$pdf->Ln();
	
	$maxAulas = 6; // Ajuste caso precise
	$pdf->SetFont('Arial','',10);
	for ($aula = 1; $aula <= $maxAulas; $aula++) {
		$pdf->Cell(30, 8, $aula . iconv('UTF-8','ISO-8859-1','ª Aula'), 1, 0, 'C');
		foreach ($diasUteis as $dia) {
			if (isset($restricoesMap[$dia]) && in_array($aula, $restricoesMap[$dia])) {
				// Aula restrita
				$pdf->SetFillColor(255,0,0);
				$pdf->SetTextColor(255,255,255);
				$pdf->Cell(32, 8, 'X', 1, 0, 'C', true);
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFillColor(255,255,255);
			} else {
				// Aula liberada
				$pdf->SetTextColor(0,128,0);
				$pdf->Cell(32, 8, 'V', 1, 0, 'C');
				$pdf->SetTextColor(0,0,0);
			}
		}
		$pdf->Ln();
	}
}

// ----------------------------------------------------------------
// 6) Turmas (se marcado) e se tiver id_ano
// ----------------------------------------------------------------
if ($exibeTurmas && $id_ano) {
	$pdf->Ln(10);
	$pdf->SetFont('Arial','B',14);
	$pdf->Cell(0, 10, iconv('UTF-8','ISO-8859-1','Turmas'), 0, 1, 'L');
	
	$stmtTurmasInfo = $pdo->prepare("
		SELECT a.ano, 
			   n.nome_nivel_ensino, 
			   s.nome_serie, 
			   GROUP_CONCAT(t.nome_turma ORDER BY t.nome_turma SEPARATOR ', ') AS turmas
		  FROM turma t
		  JOIN ano_letivo a ON t.id_ano_letivo = a.id_ano_letivo
		  JOIN serie s ON t.id_serie = s.id_serie
		  JOIN nivel_ensino n ON s.id_nivel_ensino = n.id_nivel_ensino
		  JOIN professor_disciplinas_turmas pdt ON t.id_turma = pdt.id_turma
		 WHERE pdt.id_professor = :id 
		   AND a.id_ano_letivo = :id_ano
		 GROUP BY s.id_serie
		 ORDER BY a.ano, n.nome_nivel_ensino, s.nome_serie
	");
	$stmtTurmasInfo->execute([':id'=>$id_professor, ':id_ano'=>$id_ano]);
	$rows = $stmtTurmasInfo->fetchAll(PDO::FETCH_ASSOC);
	
	if ($rows) {
		$agrupado = [];
		foreach ($rows as $r) {
			$key = $r['ano'].'__'.$r['nome_nivel_ensino'];
			if (!isset($agrupado[$key])) {
				$agrupado[$key] = [
					'ano' => $r['ano'],
					'nivel' => $r['nome_nivel_ensino'],
					'series' => []
				];
			}
			$agrupado[$key]['series'][] = [
				'nome_serie' => $r['nome_serie'],
				'turmas'	 => $r['turmas']
			];
		}
		
		$pdf->SetFont('Arial','B',10);
		$pdf->SetFillColor(200,200,200);
		$pdf->Cell(30, 8, 'Ano Letivo', 1, 0, 'C', true);
		$pdf->Cell(60, 8, iconv('UTF-8','ISO-8859-1','Nível de Ensino'), 1, 0, 'C', true);
		$pdf->Cell(100, 8, iconv('UTF-8','ISO-8859-1','Série e Turmas'), 1, 1, 'C', true);
		
		$pdf->SetFont('Arial','',10);
		foreach ($agrupado as $group) {
			$ano   = $group['ano'];
			$nivel = $group['nivel'];
			$series = $group['series'];
			
			$stText = '';
			foreach ($series as $serieData) {
				$stText .= iconv('UTF-8','ISO-8859-1', $serieData['nome_serie']) 
						 . ' - ' . iconv('UTF-8','ISO-8859-1', $serieData['turmas']) . "\n";
			}
			$stText = rtrim($stText, "\n");
			
			$lineCount = substr_count($stText, "\n") + 1;
			$rowHeight = 8 * $lineCount;
			
			$pdf->Cell(30, $rowHeight, iconv('UTF-8','ISO-8859-1',$ano), 1, 0, 'C');
			$pdf->Cell(60, $rowHeight, iconv('UTF-8','ISO-8859-1',$nivel), 1, 0, 'C');
			$x = $pdf->GetX();
			$y = $pdf->GetY();
			$pdf->MultiCell(100, 8, $stText, 1, 'C');
		}
	} else {
		$pdf->SetFont('Arial','I',12);
		$pdf->Cell(0, 10, 'Nenhuma turma encontrada para o ano selecionado.', 0, 1, 'C');
	}
}

// ----------------------------------------------------------------
// 7) Disciplinas (se marcado) e se tiver id_ano
// ----------------------------------------------------------------
if ($exibeDisciplinas && $id_ano) {
	$pdf->Ln(10);
	$pdf->SetFont('Arial','B',14);
	$pdf->Cell(0, 10, iconv('UTF-8','ISO-8859-1','Disciplinas'), 0, 1, 'L');
	
	$stmtDisciplina = $pdo->prepare("
		SELECT a.ano, 
			   n.nome_nivel_ensino, 
			   s.nome_serie, 
			   t.nome_turma, 
			   d.nome_disciplina
		  FROM professor_disciplinas_turmas pdt
		  JOIN turma t ON pdt.id_turma = t.id_turma
		  JOIN serie s ON t.id_serie = s.id_serie
		  JOIN ano_letivo a ON t.id_ano_letivo = a.id_ano_letivo
		  JOIN nivel_ensino n ON s.id_nivel_ensino = n.id_nivel_ensino 
		  JOIN professor_disciplinas pd 
			   ON pdt.id_professor = pd.id_professor 
			  AND pd.id_disciplina = pdt.id_disciplina
		  JOIN disciplina d ON pd.id_disciplina = d.id_disciplina
		 WHERE pdt.id_professor = :id 
		   AND a.id_ano_letivo = :id_ano
		 ORDER BY a.ano, n.nome_nivel_ensino, s.nome_serie, t.nome_turma, d.nome_disciplina
	");
	$stmtDisciplina->execute([':id'=>$id_professor, ':id_ano'=>$id_ano]);
	$discRows = $stmtDisciplina->fetchAll(PDO::FETCH_ASSOC);
	
	$agrupado = [];
	foreach ($discRows as $row) {
		$key = $row['ano'].'__'.$row['nome_nivel_ensino'].'__'.$row['nome_disciplina'];
		if (!isset($agrupado[$key])) {
			$agrupado[$key] = [
				'ano' => $row['ano'],
				'nivel' => $row['nome_nivel_ensino'],
				'disciplina' => $row['nome_disciplina'],
				'seriesTurmas' => []
			];
		}
		$agrupado[$key]['seriesTurmas'][] = [
			'serie' => $row['nome_serie'],
			'turma' => $row['nome_turma']
		];
	}
	
	if ($agrupado) {
		$pdf->SetFont('Arial','B',10);
		$pdf->SetFillColor(200,200,200);
		$pdf->Cell(30, 8, 'Ano Letivo', 1, 0, 'C', true);
		$pdf->Cell(50, 8, iconv('UTF-8','ISO-8859-1','Nível de Ensino'), 1, 0, 'C', true);
		$pdf->Cell(60, 8, iconv('UTF-8','ISO-8859-1','Série e Turmas'), 1, 0, 'C', true);
		$pdf->Cell(50, 8, iconv('UTF-8','ISO-8859-1','Disciplina'), 1, 1, 'C', true);
		
		$pdf->SetFont('Arial','',10);
		foreach ($agrupado as $grp) {
			$ano		= $grp['ano'];
			$nivel	  = $grp['nivel'];
			$disciplina = $grp['disciplina'];
			$listaST	= $grp['seriesTurmas'];
			
			// Agrupamos as turmas por série
			$serieTurmaMap = [];
			foreach ($listaST as $st) {
				$nome_serie = $st['serie'];
				if (!isset($serieTurmaMap[$nome_serie])) {
					$serieTurmaMap[$nome_serie] = [];
				}
				$serieTurmaMap[$nome_serie][] = $st['turma'];
			}
			
			$stText = '';
			foreach ($serieTurmaMap as $serie => $turmasArr) {
				$stText .= iconv('UTF-8','ISO-8859-1',$serie) 
						. ' ' . implode(', ', $turmasArr) . "\n";
			}
			$stText = rtrim($stText, "\n");
			
			$lineCount = substr_count($stText, "\n") + 1;
			$cellHeight = 8 * $lineCount;
			
			$x = $pdf->GetX();
			$y = $pdf->GetY();
			
			$pdf->Cell(30, $cellHeight, iconv('UTF-8','ISO-8859-1',$ano), 1, 0, 'C');
			$pdf->Cell(50, $cellHeight, iconv('UTF-8','ISO-8859-1',$nivel), 1, 0, 'C');
			$pdf->SetXY($x + 30 + 50, $y);
			$pdf->MultiCell(60, 8, $stText, 1, 'C');
			$pdf->SetXY($x + 30 + 50 + 60, $y);
			$pdf->Cell(50, $cellHeight, iconv('UTF-8','ISO-8859-1',$disciplina), 1, 1, 'C');
		}
	} else {
		$pdf->SetFont('Arial','I',12);
		$pdf->Cell(0,10, 'Nenhuma disciplina encontrada.', 0,1,'C');
	}
}

// ----------------------------------------------------------------
// 8) Turnos (se marcado)
// ----------------------------------------------------------------
if ($exibeTurnos) {
	$pdf->Ln(10);
	$pdf->SetFont('Arial','B',14);
	$pdf->Cell(0,10, 'Turnos', 0, 1, 'L');
	
	$stmtTurnos = $pdo->prepare("
		SELECT t.nome_turno 
		  FROM professor_turnos pt 
		  JOIN turno t ON pt.id_turno = t.id_turno 
		 WHERE pt.id_professor = :id
	");
	$stmtTurnos->execute([':id'=>$id_professor]);
	$turnosList = $stmtTurnos->fetchAll(PDO::FETCH_ASSOC);
	
	if ($turnosList) {
		$totalWidth = 190; 
		$countTurnos = count($turnosList);
		$cellWidth = ($countTurnos > 0) ? floor($totalWidth / $countTurnos) : 0;
		$cellHeight = 10;
		$pdf->SetFont('Arial','',12);
		
		foreach ($turnosList as $t) {
			$pdf->Cell($cellWidth, $cellHeight, iconv('UTF-8','ISO-8859-1',$t['nome_turno'] ?? ''), 1, 0, 'C');
		}
		$pdf->Ln();
	} else {
		$pdf->SetFont('Arial','I',12);
		$pdf->Cell(0,10, 'Nenhum turno vinculado.', 1, 1, 'C');
	}
}

// ----------------------------------------------------------------
// 9) Horários (se marcado) e se tiver id_ano
//	 Exibe "Ano Letivo de XXXX" + tabela
// ----------------------------------------------------------------
if ($exibeHorarios && $id_ano) {

	// 1) Descobrir qual "ano" estamos usando para exibir no cabeçalho
	$stmtAno = $pdo->prepare("SELECT ano FROM ano_letivo WHERE id_ano_letivo = :id_ano LIMIT 1");
	$stmtAno->execute([':id_ano' => $id_ano]);
	$anoLetivoRow = $stmtAno->fetch(PDO::FETCH_ASSOC);
	$anoLetivo = $anoLetivoRow ? $anoLetivoRow['ano'] : '';

	// 2) Buscar horários do professor para esse ano letivo
	$stmtHorarios = $pdo->prepare("
	   SELECT h.dia_semana, 
			  h.numero_aula, 
			  s.nome_serie, 
			  t.nome_turma, 
			  d.nome_disciplina
		 FROM horario h
		 JOIN turma t ON h.id_turma = t.id_turma
		 JOIN serie s ON t.id_serie = s.id_serie
		 JOIN disciplina d ON h.id_disciplina = d.id_disciplina
		 JOIN ano_letivo a ON t.id_ano_letivo = a.id_ano_letivo
		WHERE h.id_professor = :id_prof
		  AND a.id_ano_letivo = :id_ano
		ORDER BY 
		  FIELD(h.dia_semana,'Segunda','Terca','Quarta','Quinta','Sexta','Sabado','Domingo'),
		  h.numero_aula
	");
	$stmtHorarios->execute([':id_prof'=>$id_professor, ':id_ano'=>$id_ano]);
	$horariosData = $stmtHorarios->fetchAll(PDO::FETCH_ASSOC);
	
	// Ajuste se quiser exibir Sabado e Domingo:
	$dias = ['Segunda','Terca','Quarta','Quinta','Sexta']; // Ajuste se incluir Sábado, Domingo etc.
	$maxAulas = 6; // Ajuste se tiver mais aulas
	
	// Montamos uma matriz vazia
	$matrix = [];
	for ($aula = 1; $aula <= $maxAulas; $aula++) {
		foreach ($dias as $dia) {
			$matrix[$aula][$dia] = "";
		}
	}
	
	// Preenche com a (série/turma + disciplina)
	foreach ($horariosData as $row) {
		$dia   = $row['dia_semana'];
		$aula  = $row['numero_aula'];
		$serieTurma = $row['nome_serie'] . ' ' . $row['nome_turma'];
		$disciplina = $row['nome_disciplina'];
		
		// Formato:
		// 6º Ano A
		// Inglês
		$text = $serieTurma . "\n" . $disciplina;
		
		if (isset($matrix[$aula][$dia]) && $matrix[$aula][$dia] != "") {
			// Se por acaso houver + de uma disciplina nesse mesmo horário
			$matrix[$aula][$dia] .= "\n---\n" . $text;
		} else {
			$matrix[$aula][$dia] = $text;
		}
	}
	
	// Exibir no PDF
	$pdf->Ln(10);
	$pdf->SetFont('Arial','B',14);

	// Linha com "Ano Letivo de XXXX"
	$tituloAno = $anoLetivo ? "Ano Letivo de $anoLetivo" : "Horários";
	$pdf->Cell(190, 10, iconv('UTF-8','ISO-8859-1',$tituloAno), 1, 1, 'C');

	// Cabeçalho da tabela (Aula/ Dia...):
	$pdf->SetFont('Arial','B',10);
	$pdf->Cell(30, 8, iconv('UTF-8','ISO-8859-1','Aula/ Dia'), 1, 0, 'C', true);
	foreach ($dias as $dia) {
		// Ajusta 'Terca' para 'Terça'
		$diaLabel = ($dia == 'Terca') ? 'Terça' : $dia;
		$pdf->Cell(32, 8, iconv('UTF-8','ISO-8859-1',$diaLabel), 1, 0, 'C', true);
	}
	$pdf->Ln();
	
	// Linhas das aulas
	$pdf->SetFont('Arial','',9);
	for ($aula = 1; $aula <= $maxAulas; $aula++) {
		// Antes de gerar as colunas, descobrimos a altura max de cada linha:
		$maxRowHeight = 8;
		foreach ($dias as $dia) {
			$txt = $matrix[$aula][$dia];
			$lines = substr_count($txt, "\n") + ($txt ? 1 : 0);
			$height = 5 * $lines; // ou 6, depende do seu gosto
			if ($height > $maxRowHeight) {
				$maxRowHeight = $height;
			}
		}
		
		// Primeira célula: "1ª Aula"
		$pdf->Cell(30, $maxRowHeight, $aula . iconv('UTF-8','ISO-8859-1','ª Aula'), 1, 0, 'C');
		
		// Agora as colunas de cada dia
		foreach ($dias as $dia) {
			$x = $pdf->GetX();
			$y = $pdf->GetY();
			$cellText = iconv('UTF-8','ISO-8859-1',$matrix[$aula][$dia]);
			
			// Faz MultiCell para permitir "quebras" de linha
			// mas depois precisamos reposicionar o cursor manualmente
			$pdf->MultiCell(32, 5, $cellText, 1, 'C');
			
			// Retorna para a "linha" antes de avançar
			$pdf->SetXY($x + 32, $y);
		}
		$pdf->Ln($maxRowHeight);
	}
}

// ----------------------------------------------------------------
// 10) Finaliza e envia ao browser
// ----------------------------------------------------------------
$pdf->Output();
exit;
