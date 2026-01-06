<?php
/**
 * Controller para listagem do relatório de hora/aula da escolinha
 * 
 * Este controller processa os dados de horários da escolinha e conta
 * o total de aulas semanais por professor/modalidade/categoria.
 */

// Ativar exibição de erros para debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
	require_once __DIR__ . '/../../../configs/init.php';
	header('Content-Type: application/json');
	
	if (!isset($pdo) || !($pdo instanceof PDO)) {
		echo json_encode([
			'status' => 'error',
			'message' => 'Erro interno do servidor: Conexão com o banco de dados não estabelecida.',
			'data' => []
		]);
		exit();
	}

	/**
	 * Busca a configuração de duração da aula para uma modalidade/categoria específica
	 */
	function buscarConfiguracaoHoraAula($pdo, $id_ano_letivo, $id_modalidade, $id_categoria) {
		try {
			$sql = "
				SELECT duracao_aula_minutos
				FROM configuracao_hora_aula_escolinha 
				WHERE id_ano_letivo = :id_ano_letivo
				AND id_modalidade = :id_modalidade
				AND id_categoria = :id_categoria
				AND ativo = 1
				LIMIT 1
			";
			
			$stmt = $pdo->prepare($sql);
			if (!$stmt) {
				return 50; // Valor padrão de 50 minutos
			}
			
			$result = $stmt->execute([
				'id_ano_letivo' => $id_ano_letivo,
				'id_modalidade' => $id_modalidade,
				'id_categoria' => $id_categoria
			]);
			
			if (!$result) {
				return 50; // Valor padrão de 50 minutos
			}
			
			$config = $stmt->fetch(PDO::FETCH_ASSOC);
			return $config ? intval($config['duracao_aula_minutos']) : 50;
			
		} catch (Exception $e) {
			return 50; // Valor padrão de 50 minutos em caso de erro
		}
	}

	/**
	 * Conta o total de aulas semanais para um professor/modalidade/categoria específico
	 */
	function contarAulasSemana($pdo, $id_professor, $id_modalidade, $id_categoria, $id_ano_letivo, $id_turno = '') {
		try {
			// Conta quantos horários o professor tem para esta modalidade/categoria durante a semana
			$sql = "
				SELECT COUNT(*) as total_aulas_semana
				FROM horario_escolinha he
				WHERE he.id_professor = :id_professor
				AND he.id_modalidade = :id_modalidade
				AND he.id_categoria = :id_categoria
				AND he.id_ano_letivo = :id_ano_letivo
			";
			
			$params = [
				'id_professor' => $id_professor,
				'id_modalidade' => $id_modalidade,
				'id_categoria' => $id_categoria,
				'id_ano_letivo' => $id_ano_letivo
			];
			
			if (!empty($id_turno)) {
				$sql .= " AND he.id_turno = :id_turno";
				$params['id_turno'] = $id_turno;
			}
			
			$stmt = $pdo->prepare($sql);
			if (!$stmt) {
				return 0;
			}
			
			$result = $stmt->execute($params);
			if (!$result) {
				return 0;
			}
			
			$resultado = $stmt->fetch(PDO::FETCH_ASSOC);
			return intval($resultado['total_aulas_semana'] ?? 0);
			
		} catch (Exception $e) {
			return 0;
		}
	}

	/**
	 * Formata minutos em formato de horas e minutos (ex: "2h 30min")
	 */
	function formatarHorasMinutos($totalMinutos) {
		if ($totalMinutos <= 0) {
			return "0min";
		}
		
		$horas = floor($totalMinutos / 60);
		$minutos = $totalMinutos % 60;
		
		$resultado = "";
		if ($horas > 0) {
			$resultado .= $horas . "h";
		}
		if ($minutos > 0) {
			if ($horas > 0) $resultado .= " ";
			$resultado .= $minutos . "min";
		}
		
		return $resultado;
	}

	/**
	 * Calcula as horas-aula para um professor/modalidade/categoria específico
	 * considerando os eventos do calendário escolar
	 */
	function calcularHorasAula($pdo, $id_professor, $id_modalidade, $id_categoria, $id_ano_letivo, $id_turno = '', $tipo_relatorio = 'tudo') {
		try {
			// Busca os horários do professor/modalidade/categoria
			$sql = "
				SELECT 
					he.*,
					TIMESTAMPDIFF(MINUTE, he.hora_inicio, he.hora_fim) as duracao_minutos
				FROM horario_escolinha he
				WHERE he.id_professor = :id_professor
				AND he.id_modalidade = :id_modalidade
				AND he.id_categoria = :id_categoria
				AND he.id_ano_letivo = :id_ano_letivo
			";
			
			$params = [
				'id_professor' => $id_professor,
				'id_modalidade' => $id_modalidade,
				'id_categoria' => $id_categoria,
				'id_ano_letivo' => $id_ano_letivo
			];
			
			if (!empty($id_turno)) {
				$sql .= " AND he.id_turno = :id_turno";
				$params['id_turno'] = $id_turno;
			}
			
			$stmt = $pdo->prepare($sql);
			if (!$stmt) {
				return 0;
			}
			
			$result = $stmt->execute($params);
			if (!$result) {
				return 0;
			}
			
			$horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			if (empty($horarios)) {
				return 0;
			}
			
			// Busca o período do ano letivo
			$sqlAno = "SELECT data_inicio, data_fim FROM ano_letivo WHERE id_ano_letivo = :id_ano_letivo";
			$stmtAno = $pdo->prepare($sqlAno);
			if (!$stmtAno) {
				return 0;
			}
			
			$resultAno = $stmtAno->execute(['id_ano_letivo' => $id_ano_letivo]);
			if (!$resultAno) {
				return 0;
			}
			
			$anoLetivo = $stmtAno->fetch(PDO::FETCH_ASSOC);
			
			if (!$anoLetivo) {
				return 0;
			}
			
			// Busca eventos que não contam como hora-aula
			$sqlEventos = "
				SELECT data_inicio, data_fim 
				FROM eventos_calendario_escolar 
				WHERE id_ano_letivo = :id_ano_letivo
			";
			$stmtEventos = $pdo->prepare($sqlEventos);
			if (!$stmtEventos) {
				$eventos = [];
			} else {
				$resultEventos = $stmtEventos->execute(['id_ano_letivo' => $id_ano_letivo]);
				if (!$resultEventos) {
					$eventos = [];
				} else {
					$eventos = $stmtEventos->fetchAll(PDO::FETCH_ASSOC);
				}
			}
			
			// Calcula total de minutos baseado no tipo de relatório
			$totalMinutos = 0;
			
			switch ($tipo_relatorio) {
				case 'dia':
					$totalMinutos = calcularMinutosPorPeriodo($horarios, $anoLetivo, $eventos, 'dia');
					break;
				case 'semana':
					$totalMinutos = calcularMinutosPorPeriodo($horarios, $anoLetivo, $eventos, 'semana');
					break;
				case 'mes':
					$totalMinutos = calcularMinutosPorPeriodo($horarios, $anoLetivo, $eventos, 'mes');
					break;
				case 'semestre':
					$totalMinutos = calcularMinutosPorPeriodo($horarios, $anoLetivo, $eventos, 'semestre');
					break;
				case 'ano':
					$totalMinutos = calcularMinutosPorPeriodo($horarios, $anoLetivo, $eventos, 'ano');
					break;
				default: // 'tudo'
					$totalMinutos = calcularMinutosTotais($horarios, $anoLetivo, $eventos);
					break;
			}
			
			return $totalMinutos;
			
		} catch (Exception $e) {
			return 0;
		}
	}

	/**
	 * Calcula minutos totais considerando todos os horários
	 */
	function calcularMinutosTotais($horarios, $anoLetivo, $eventos) {
		$totalMinutos = 0;
		
		try {
			$dataInicio = new DateTime($anoLetivo['data_inicio']);
			$dataFim = new DateTime($anoLetivo['data_fim']);
			
			// Para cada horário, conta quantas vezes ele ocorre no período
			foreach ($horarios as $horario) {
				$diaSemana = $horario['dia_semana'] ?? '';
				$duracaoMinutos = intval($horario['duracao_minutos'] ?? 0);
				
				if (empty($diaSemana) || $duracaoMinutos <= 0) {
					continue;
				}
				
				// Conta quantas vezes este dia da semana ocorre no período
				$ocorrencias = contarOcorrenciasDiaSemana($dataInicio, $dataFim, $diaSemana, $eventos);
				
				$totalMinutos += $ocorrencias * $duracaoMinutos;
			}
		} catch (Exception $e) {
			// Em caso de erro, retorna 0
		}
		
		return $totalMinutos;
	}

	/**
	 * Calcula minutos por período específico (dia, semana, mês, etc.)
	 */
	function calcularMinutosPorPeriodo($horarios, $anoLetivo, $eventos, $periodo) {
		$totalMinutos = 0;
		
		try {
			foreach ($horarios as $horario) {
				$duracaoMinutos = intval($horario['duracao_minutos'] ?? 0);
				
				if ($duracaoMinutos <= 0) {
					continue;
				}
				
				switch ($periodo) {
					case 'dia':
						// Retorna a duração de um dia de aula
						$totalMinutos += $duracaoMinutos;
						break;
					case 'semana':
						// Conta quantas aulas por semana
						$totalMinutos += $duracaoMinutos; // Uma aula por semana para este horário
						break;
					case 'mes':
						// Aproximadamente 4 semanas por mês
						$totalMinutos += $duracaoMinutos * 4;
						break;
					case 'semestre':
						// Aproximadamente 6 meses por semestre
						$totalMinutos += $duracaoMinutos * 24; // 4 semanas * 6 meses
						break;
					case 'ano':
						// Calcula para o ano todo
						$dataInicio = new DateTime($anoLetivo['data_inicio']);
						$dataFim = new DateTime($anoLetivo['data_fim']);
						$ocorrencias = contarOcorrenciasDiaSemana($dataInicio, $dataFim, $horario['dia_semana'] ?? '', $eventos);
						$totalMinutos += $ocorrencias * $duracaoMinutos;
						break;
				}
			}
		} catch (Exception $e) {
			// Em caso de erro, retorna 0
		}
		
		return $totalMinutos;
	}

	/**
	 * Conta quantas vezes um dia da semana ocorre em um período,
	 * excluindo os eventos que não contam como hora-aula
	 */
	function contarOcorrenciasDiaSemana($dataInicio, $dataFim, $diaSemana, $eventos) {
		$diasSemana = [
			'Domingo' => 0,
			'Segunda' => 1,
			'Terca' => 2,
			'Quarta' => 3,
			'Quinta' => 4,
			'Sexta' => 5,
			'Sabado' => 6
		];
		
		$diaSemanaNum = $diasSemana[$diaSemana] ?? 1;
		$ocorrencias = 0;
		
		try {
			$dataAtual = clone $dataInicio;
			
			// Encontra a primeira ocorrência do dia da semana
			while ($dataAtual->format('w') != $diaSemanaNum && $dataAtual <= $dataFim) {
				$dataAtual->add(new DateInterval('P1D'));
			}
			
			// Conta as ocorrências, pulando uma semana a cada iteração
			while ($dataAtual <= $dataFim) {
				// Verifica se esta data não está em um evento que não conta como hora-aula
				if (!isDataEmEvento($dataAtual, $eventos)) {
					$ocorrencias++;
				}
				
				$dataAtual->add(new DateInterval('P7D')); // Próxima semana
			}
		} catch (Exception $e) {
			// Em caso de erro, retorna 0
		}
		
		return $ocorrencias;
	}

	/**
	 * Verifica se uma data está dentro de um evento que não conta como hora-aula
	 */
	function isDataEmEvento($data, $eventos) {
		$dataStr = $data->format('Y-m-d');
		
		foreach ($eventos as $evento) {
			if (isset($evento['data_inicio']) && isset($evento['data_fim'])) {
				if ($dataStr >= $evento['data_inicio'] && $dataStr <= $evento['data_fim']) {
					return true;
				}
			}
		}
		return false;
	}

	// Bloco principal
	
	// Recebe os parâmetros da requisição
	$id_ano_letivo = $_GET['id_ano_letivo'] ?? '';
	$id_turno = $_GET['id_turno'] ?? '';
	$tipo_relatorio = $_GET['tipo_relatorio'] ?? 'tudo';
	
	// Valida parâmetros obrigatórios
	if (empty($id_ano_letivo)) {
		throw new Exception('Ano letivo é obrigatório');
	}
	
	// Inicializa os parâmetros da query principal
	$params = ['id_ano_letivo' => $id_ano_letivo];
	
	// Monta a query base
	$sql = "
		SELECT DISTINCT
			p.id_professor,
			p.nome_exibicao as nome_professor,
			m.id_modalidade,
			m.nome_modalidade,
			c.id_categoria,
			c.nome_categoria,
			t.nome_turno
		FROM horario_escolinha he
		INNER JOIN professor p ON he.id_professor = p.id_professor
		INNER JOIN modalidade m ON he.id_modalidade = m.id_modalidade
		INNER JOIN categoria c ON he.id_categoria = c.id_categoria
		INNER JOIN turno t ON he.id_turno = t.id_turno
		WHERE he.id_ano_letivo = :id_ano_letivo
	";

	// Adiciona filtro de turno se especificado
	if (!empty($id_turno)) {
		$sql .= " AND he.id_turno = :id_turno";
		$params['id_turno'] = $id_turno;
	}

	// ORDER BY corrigido - usando o alias
	$sql .= " ORDER BY nome_professor, m.nome_modalidade, c.nome_categoria";
	
	$stmt = $pdo->prepare($sql);
	if (!$stmt) {
		throw new Exception('Erro ao preparar consulta principal: ' . implode(', ', $pdo->errorInfo()));
	}
	
	$result = $stmt->execute($params);
	if (!$result) {
		throw new Exception('Erro ao executar consulta principal: ' . implode(', ', $stmt->errorInfo()));
	}
	
	$professores = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	$resultado = [];
	
	foreach ($professores as $professor) {
		// Conta quantas aulas por semana o professor tem nesta modalidade/categoria
		$totalAulasSemana = contarAulasSemana(
			$pdo,
			$professor['id_professor'],
			$professor['id_modalidade'],
			$professor['id_categoria'],
			$id_ano_letivo,
			$id_turno
		);
		
		// Busca a configuração de duração da aula
		$duracaoAulaMinutos = buscarConfiguracaoHoraAula(
			$pdo,
			$id_ano_letivo,
			$professor['id_modalidade'],
			$professor['id_categoria']
		);
		
		// Calcula o total de minutos por semana
		$totalMinutosSemana = $totalAulasSemana * $duracaoAulaMinutos;
		
		// Formata as horas
		$horasFormatadas = formatarHorasMinutos($totalMinutosSemana);
		
		// Calcula as horas-aula para relatórios mais detalhados (se necessário)
		$horasAula = calcularHorasAula(
			$pdo,
			$professor['id_professor'],
			$professor['id_modalidade'],
			$professor['id_categoria'],
			$id_ano_letivo,
			$id_turno,
			$tipo_relatorio
		);
		
		$resultado[] = [
			'id_professor' => $professor['id_professor'],
			'nome_professor' => $professor['nome_professor'],
			'id_modalidade' => $professor['id_modalidade'],
			'nome_modalidade' => $professor['nome_modalidade'],
			'id_categoria' => $professor['id_categoria'],
			'nome_categoria' => $professor['nome_categoria'],
			'nome_turno' => $professor['nome_turno'],
			'total_aulas' => $totalAulasSemana,
			'duracao_aula_minutos' => $duracaoAulaMinutos,
			'total_horas_semana' => $horasFormatadas,
			'aulas_e_horas_texto' => $totalAulasSemana . ' Aulas (' . $horasFormatadas . ')',
			'horas_aula_calculadas' => $horasAula // Mantém o cálculo de horas se necessário
		];
	}
	
	echo json_encode([
		'status' => 'success',
		'data' => $resultado,
		'message' => 'Dados carregados com sucesso'
	]);
	
} catch (Exception $e) {
	echo json_encode([
		'status' => 'error',
		'message' => 'Erro: ' . $e->getMessage(),
		'data' => []
	]);
} catch (Error $e) {
	echo json_encode([
		'status' => 'error',
		'message' => 'Erro interno: ' . $e->getMessage(),
		'data' => []
	]);
}
?>