<?php
// app/controllers/nivel-ensino/deleteNivelEnsino.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
	echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
	exit;
}

try {
	// Tabelas que apontam para nivel_ensino
	$dependencias = [
		'serie'         => 'id_nivel_ensino',
		'usuario_niveis'=> 'id_nivel_ensino'
	];

	foreach ($dependencias as $tabela => $coluna) {
		$sqlCheck = "SELECT COUNT(*) AS qtd FROM {$tabela} WHERE {$coluna} = ?";
		$stmtCheck = $pdo->prepare($sqlCheck);
		$stmtCheck->execute([$id]);
		$qtd = (int)($stmtCheck->fetchColumn() ?? 0);

		if ($qtd > 0) {
			echo json_encode([
				'status'  => 'error',
				'message' => 'Não é possível excluir: existem informações vinculadas a este nível de ensino.'
			]);
			exit;
		}
	}

	$stmt = $pdo->prepare("DELETE FROM nivel_ensino WHERE id_nivel_ensino = ?");
	$stmt->execute([$id]);

	if ($stmt->rowCount() > 0) {
		echo json_encode(['status' => 'success', 'message' => 'Excluído com sucesso.']);
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Registro não encontrado.']);
	}

} catch (PDOException $e) {
	// Tratamento amigável para erro de integridade (FK)
	$code = $e->getCode();                 // geralmente "23000"
	$info = $e->errorInfo[1] ?? null;      // geralmente 1451 no MySQL

	if ($code === '23000' || (int)$info === 1451) {
		echo json_encode([
			'status'  => 'error',
			'message' => 'Não é possível excluir: existem informações vinculadas a este nível de ensino.'
		]);
		exit;
	}

	// Outros erros (sem expor detalhes)
	echo json_encode([
		'status'  => 'error',
		'message' => 'Erro ao excluir. Tente novamente.'
	]);
}
exit;
?>