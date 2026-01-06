<?php
// app/controllers/hora-aula-escolinha/insertHoraAulaEscolinha.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$idAnoLetivo = intval($_POST['id_ano_letivo'] ?? 0);
	$duracaoAulaMinutos = intval($_POST['duracao_aula_minutos'] ?? 50);
	$toleranciaQuebra = isset($_POST['tolerancia_quebra']) ? (bool)$_POST['tolerancia_quebra'] : true;
	$ativo = isset($_POST['ativo']) ? (bool)$_POST['ativo'] : true;

	$categoriasJson = $_POST['categorias'] ?? '';
	$categorias = json_decode($categoriasJson, true);

	if ($idAnoLetivo <= 0) {
		echo json_encode(['status' => 'error', 'message' => 'Ano letivo é obrigatório.']);
		exit;
	}

	if (!$categorias || !is_array($categorias) || count($categorias) == 0) {
		echo json_encode(['status' => 'error', 'message' => 'Selecione pelo menos uma categoria.']);
		exit;
	}

	if ($duracaoAulaMinutos <= 0) {
		echo json_encode(['status' => 'error', 'message' => 'Duração da aula deve ser maior que zero.']);
		exit;
	}

	try {
		$pdo->beginTransaction();
		
		$sucessos = 0;
		$erros = [];

		foreach ($categorias as $idCategoria) {
			$idCategoria = intval($idCategoria);
			if ($idCategoria <= 0) continue;

			// Busca a modalidade diretamente da tabela categoria
			$stmtModalidade = $pdo->prepare("
				SELECT c.id_categoria, c.id_modalidade, m.nome_modalidade, c.nome_categoria
				FROM categoria c
				JOIN modalidade m ON c.id_modalidade = m.id_modalidade
				WHERE c.id_categoria = ?
			");
			$stmtModalidade->execute([$idCategoria]);
			$modalidadeInfo = $stmtModalidade->fetch();

			if (!$modalidadeInfo) {
				$erros[] = "Categoria ID $idCategoria não encontrada";
				continue;
			}

			$idModalidade = $modalidadeInfo['id_modalidade'];

			// Verifica se já existe configuração para este ano letivo e categoria
			$stmtCheck = $pdo->prepare("
				SELECT id_configuracao 
				FROM configuracao_hora_aula_escolinha 
				WHERE id_ano_letivo = ? AND id_categoria = ?
			");
			$stmtCheck->execute([$idAnoLetivo, $idCategoria]);

			if ($stmtCheck->fetch()) {
				$nomeCompleto = $modalidadeInfo['nome_modalidade'] . ' - ' . $modalidadeInfo['nome_categoria'];
				$erros[] = "Já existe configuração para: $nomeCompleto";
				continue;
			}

			// Insere a nova configuração
			$stmt = $pdo->prepare("
				INSERT INTO configuracao_hora_aula_escolinha 
				(id_ano_letivo, id_modalidade, id_categoria, duracao_aula_minutos, tolerancia_quebra, ativo)
				VALUES (?, ?, ?, ?, ?, ?)
			");
			$stmt->execute([
				$idAnoLetivo,
				$idModalidade,
				$idCategoria,
				$duracaoAulaMinutos,
				$toleranciaQuebra,
				$ativo
			]);

			$sucessos++;
		}

		$pdo->commit();

		if ($sucessos > 0 && count($erros) == 0) {
			echo json_encode([
				'status'  => 'success',
				'message' => "Configuração salva com sucesso para $sucessos categoria(s)!"
			]);
		} elseif ($sucessos > 0 && count($erros) > 0) {
			echo json_encode([
				'status'  => 'warning',
				'message' => "Salvo $sucessos categoria(s). Avisos: " . implode('; ', $erros)
			]);
		} else {
			echo json_encode([
				'status'  => 'error',
				'message' => 'Nenhuma categoria foi salva. Erros: ' . implode('; ', $erros)
			]);
		}
		
	} catch (PDOException $e) {
		$pdo->rollBack();
		echo json_encode([
			'status'  => 'error',
			'message' => 'Erro ao inserir: ' . $e->getMessage()
		]);
	}
} else {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
}

?>