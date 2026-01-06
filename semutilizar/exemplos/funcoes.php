<?php

require_once __DIR__ . '/database.php'; // Certifique-se de que o caminho está correto

date_default_timezone_set('America/Sao_Paulo');

/*function registrarLogAtividade($pdo, $idUsuario, $nomeUsuario, $statusAtividade) {
	$statusValido = ['sucesso_login', 'falha_login', 'acesso_negado_login', 'saida_sistema'];
	if (!in_array($statusAtividade, $statusValido)) {
		error_log("[registrarLogAtividade] Status inválido: $statusAtividade");
		return;
	}
	
	$ipUsuario = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
	
	$sql = "INSERT INTO log_atividade (id_usuario, nome_usuario, ip_usuario, status_atividade, data_hora_atividade)
			VALUES (:id_usuario, :nome_usuario, :ip_usuario, :status_atividade, NOW())";
	
	$stmt = $pdo->prepare($sql);
	$stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
	$stmt->bindValue(':nome_usuario', $nomeUsuario, PDO::PARAM_STR);
	$stmt->bindValue(':ip_usuario', $ipUsuario, PDO::PARAM_STR);
	$stmt->bindValue(':status_atividade', $statusAtividade, PDO::PARAM_STR);
	$stmt->execute();
}*/

/*descomentar após inserir usuário*/
/*function registrarLogAtividade($pdo, $idUsuario = null, $nomeUsuario = 'Desconhecido', $statusAtividade) {
	$statusValido = ['sucesso_login', 'falha_login', 'acesso_negado_login', 'saida_sistema'];
	if (!in_array($statusAtividade, $statusValido)) {
		error_log("[registrarLogAtividade] Status inválido: $statusAtividade");
		return;
	}

	// Usar 0 como id_usuario padrão se for NULL
	$idUsuario = $idUsuario ?? 0;

	$ipUsuario = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

	$sql = "INSERT INTO log_atividade (id_usuario, nome_usuario, ip_usuario, status_atividade, data_hora_atividade)
			VALUES (:id_usuario, :nome_usuario, :ip_usuario, :status_atividade, NOW())";

	$stmt = $pdo->prepare($sql);
	$stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
	$stmt->bindValue(':nome_usuario', $nomeUsuario, PDO::PARAM_STR);
	$stmt->bindValue(':ip_usuario', $ipUsuario, PDO::PARAM_STR);
	$stmt->bindValue(':status_atividade', $statusAtividade, PDO::PARAM_STR);
	$stmt->execute();
}

*/

function registrarLogAtividade($pdo, $statusAtividade, $idUsuario = 99999, $nomeUsuario = 'teste')
{
	$statusValido = ['sucesso_login', 'falha_login', 'acesso_negado_login', 'saida_sistema'];
	if (!in_array($statusAtividade, $statusValido)) {
		error_log("[registrarLogAtividade] Status inválido: $statusAtividade");
		return;
	}

	$ipUsuario = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

	$sql = "INSERT INTO log_atividade (
				id_usuario, nome_usuario, ip_usuario, status_atividade, data_hora_atividade
			) VALUES (
				:id_usuario, :nome_usuario, :ip_usuario, :status_atividade, NOW()
			)";

	$stmt = $pdo->prepare($sql);
	$stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
	$stmt->bindValue(':nome_usuario', $nomeUsuario, PDO::PARAM_STR);
	$stmt->bindValue(':ip_usuario', $ipUsuario, PDO::PARAM_STR);
	$stmt->bindValue(':status_atividade', $statusAtividade, PDO::PARAM_STR);
	$stmt->execute();
}

// horarios/configs/funcoes.php (ou se você já tem um funcoes.php)

// Exemplo de função genérica para registrar logs:
function registrarLogAtividade($pdo, $status, $idUsuario, $nomeUsuario)
{
	// $status pode ser: 'sucesso_login', 'falha_login', 'acesso_negado_login', 'saida_sistema'
	// Convertemos para mensagem amigável:
	switch ($status) {
		case 'sucesso_login':
			$mensagemStatus = 'Sucesso no login.';
			break;
		case 'falha_login':
			$mensagemStatus = 'Login falhou. Usuário ou senha errada.';
			break;
		case 'acesso_negado_login':
			$mensagemStatus = 'Acesso negado! Usuário inexistente ou inativo!';
			break;
		case 'saida_sistema':
			$mensagemStatus = 'Saiu do sistema e/ou tempo se encerrou.';
			break;
		default:
			$mensagemStatus = 'Status desconhecido.';
			break;
	}
 
	$ipUsuario = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
	// Se $idUsuario for null, podemos definir como 0
	$idUsuario = $idUsuario ?? 0;
	$nomeUsuario = $nomeUsuario ?? 'Desconhecido';

	// 1) Insere no banco
	try {
		$sql = "INSERT INTO log_atividade 
				(id_usuario, nome_usuario, ip_usuario, status_atividade, data_hora_atividade)
				VALUES (?, ?, ?, ?, NOW())";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$idUsuario, $nomeUsuario, $ipUsuario, $status]);
	} catch (Exception $e) {
		error_log("Erro ao registrar log no banco: " . $e->getMessage());
	}

	// 2) Registra no arquivo de texto: horarios/acessos/log_acessos.log
	$logFile = ROOT_PATH . '/acessos/log_acessos.log'; // Ajuste se quiser outro nome/pasta
	$dataHora = date('Y-m-d H:i:s');
	$linhaLog = sprintf(
		"[%s] Usuário: %s | Nome Usuário: %s | IP: %s | Status: %s\n",
		$dataHora,
		$idUsuario,
		$nomeUsuario,
		$ipUsuario,
		$mensagemStatus
	);

	file_put_contents($logFile, $linhaLog, FILE_APPEND);
}
