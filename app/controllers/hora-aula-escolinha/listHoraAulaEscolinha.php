<?php
// app/controllers/hora-aula-escolinha/listHoraAulaEscolinha.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

try {
	$whereClause = "WHERE c.ativo = 1";
	$params = [];
	
	// Filtro por ano letivo (se fornecido)
	if (isset($_GET['id_ano_letivo']) && !empty($_GET['id_ano_letivo'])) {
		$whereClause .= " AND c.id_ano_letivo = ?";
		$params[] = intval($_GET['id_ano_letivo']);
	}
	
	// CORREÇÃO: Agora busca também as informações de categoria
	$stmt = $pdo->prepare("
		SELECT 
			c.*,
			a.ano as ano_letivo,
			m.nome_modalidade,
			cat.id_categoria,
			cat.nome_categoria,
			CONCAT(m.nome_modalidade, ' - ', cat.nome_categoria) as nome_modalidade_categoria
		FROM configuracao_hora_aula_escolinha c
		LEFT JOIN ano_letivo a ON c.id_ano_letivo = a.id_ano_letivo
		LEFT JOIN modalidade m ON c.id_modalidade = m.id_modalidade
		LEFT JOIN categoria cat ON c.id_categoria = cat.id_categoria
		$whereClause
		ORDER BY a.ano DESC, m.nome_modalidade, cat.nome_categoria
	");
	
	$stmt->execute($params);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>