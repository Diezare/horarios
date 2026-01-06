<?php
// app/controllers/hora-aula-escolinha/updateHoraAulaEscolinha.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$idConfiguracao = intval($_POST['id_configuracao'] ?? 0);
	$idAnoLetivo = intval($_POST['id_ano_letivo'] ?? 0);
	$duracaoAulaMinutos = intval($_POST['duracao_aula_minutos'] ?? 50);
	$toleranciaQuebra = isset($_POST['tolerancia_quebra']) ? (bool)$_POST['tolerancia_quebra'] : true;
	$ativo = isset($_POST['ativo']) ? (bool)$_POST['ativo'] : true;
	
	$categoriasJson = $_POST['categorias'] ?? '';
	$categorias = json_decode($categoriasJson, true);

	if ($idConfiguracao <= 0 || $idAnoLetivo <= 0) {
		echo json_encode(['status' => 'error', 'message' => 'ID da configuração e ano letivo são obrigatórios.']);
		exit;
	}

	if (!$categorias || !is_array($categorias) || count($categorias) != 1) {
		echo json_encode(['status' => 'error', 'message' => 'Selecione exatamente uma categoria para edição.']);
		exit;
	}

	if ($duracaoAulaMinutos <= 0) {
		echo json_encode(['status' => 'error', 'message' => 'Duração da aula deve ser maior que zero.']);
		exit;
	}

	$idCategoria = intval($categorias[0]);

	try {
		// Busca a modalidade diretamente da tabela categoria
		$stmtModalidade = $pdo->prepare("
			SELECT c.id_modalidade
			FROM categoria c
			WHERE c.id_categoria = ?
		");
		$stmtModalidade->execute([$idCategoria]);
		$modalidadeInfo = $stmtModalidade->fetch();

		if (!$modalidadeInfo) {
			echo json_encode([
				'status' => 'error', 
				'message' => 'Categoria não encontrada.'
			]);
			exit;
		}

		$idModalidade = $modalidadeInfo['id_modalidade'];

		// Verifica se já existe outra configuração para esse ano letivo e categoria (não apenas modalidade!)
		$stmtCheck = $pdo->prepare("
			SELECT id_configuracao 
			FROM configuracao_hora_aula_escolinha 
			WHERE id_ano_letivo = ? AND id_categoria = ? AND id_configuracao != ?
		");
		$stmtCheck->execute([$idAnoLetivo, $idCategoria, $idConfiguracao]);

		if ($stmtCheck->fetch()) {
			echo json_encode([
				'status' => 'error', 
				'message' => 'Já existe uma configuração para este ano letivo e categoria.'
			]);
			exit;
		}

		$stmt = $pdo->prepare("
			UPDATE configuracao_hora_aula_escolinha
			SET id_ano_letivo = ?, 
				id_modalidade = ?, 
				id_categoria = ?,
				duracao_aula_minutos = ?, 
				tolerancia_quebra = ?, 
				ativo = ?
			WHERE id_configuracao = ?
		");
		$stmt->execute([
			$idAnoLetivo,
			$idModalidade,
			$idCategoria,
			$duracaoAulaMinutos,
			$toleranciaQuebra,
			$ativo,
			$idConfiguracao
		]);

		if ($stmt->rowCount() > 0) {
			echo json_encode(['status' => 'success', 'message' => 'Configuração atualizada com sucesso.']);
		} else {
			echo json_encode(['status' => 'error', 'message' => 'Nenhuma alteração realizada ou registro não encontrado.']);
		}
	} catch (PDOException $e) {
		echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
	}
} else {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
}
?>
