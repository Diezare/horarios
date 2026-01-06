<?php
// horarios/configs/funcoes.php

require_once __DIR__ . '/database.php'; // Se $pdo vem de outro lugar, ajuste

date_default_timezone_set('America/Sao_Paulo');

/**
 * Registra uma atividade de log no banco (log_atividade) e num arquivo texto (log_acessos.log).
 * @param PDO $pdo
 * @param string $status Ex: 'sucesso_login','falha_login','acesso_negado_login','saida_sistema'
 * @param int|null $idUsuario
 * @param string|null $nomeUsuario
 */
function registrarLogAtividade($pdo, $status, $idUsuario = null, $nomeUsuario = null)
{
	// Converte status para mensagem amigável no log de texto
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

	// Se for null => insere 0 para log de texto, mas usaremos null no BD
	$logIdUser  = $idUsuario ?? 0;
	$logName	= $nomeUsuario ?? 'Desconhecido';
	$ipUsuario  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

	// 1) Insere no BD, id_usuario pode ser null
	try {
		$sql = "INSERT INTO log_atividade 
				(id_usuario, nome_usuario, ip_usuario, status_atividade, data_hora_atividade)
				VALUES (:idUsuario, :nomeUsuario, :ipUsuario, :status, NOW())";
		$stmt = $pdo->prepare($sql);

		// Se $idUsuario é null, bind como PDO::PARAM_NULL
		if (is_null($idUsuario)) {
			$stmt->bindValue(':idUsuario', null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue(':idUsuario', $idUsuario, PDO::PARAM_INT);
		}

		$stmt->bindValue(':nomeUsuario', $logName, \PDO::PARAM_STR);
		$stmt->bindValue(':ipUsuario',   $ipUsuario, \PDO::PARAM_STR);
		$stmt->bindValue(':status',	  $status, \PDO::PARAM_STR);

		$stmt->execute();
	} catch (\Exception $e) {
		error_log("[registrarLogAtividade] Erro BD: " . $e->getMessage());
	}

	// 2) Escreve em arquivo texto: /acessos/log_acessos.log
	$logFile  = ROOT_PATH . '/acessos/log_acessos.log';
	$dataHora = date('Y-m-d H:i:s');
	$linhaLog = sprintf(
		"[%s] Usuário: %s | Nome Usuário: %s | IP: %s | Status: %s\n",
		$dataHora,
		$logIdUser,
		$logName,
		$ipUsuario,
		$mensagemStatus
	);

	file_put_contents($logFile, $linhaLog, FILE_APPEND);
}
?>