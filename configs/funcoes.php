<?php
// horarios/configs/funcoes.php

require_once __DIR__ . '/database.php';

// Define timezone para América/Sao_Paulo
date_default_timezone_set('America/Sao_Paulo');

define('LOG_FILE_PATH', ROOT_PATH . '/acessos/log_acessos.log');

/**
 * Obtém a mensagem amigável para o status do log.
 * @param string $status
 * @return string
 */
function getMensagemStatus(string $status): string
{
    $mapaStatus = [
        'sucesso_login'       => 'Sucesso no login.',
        'falha_login'         => 'Login falhou. Usuário ou senha errada.',
        'acesso_negado_login' => 'Acesso negado! Usuário inexistente ou inativo!',
        'saida_sistema'       => 'Saiu do sistema e/ou tempo se encerrou.'
    ];

    return $mapaStatus[$status] ?? 'Status desconhecido.';
}

/**
 * Valida parâmetros simples para evitar quebras ou conteúdo inseguro.
 * @param string|null $nomeUsuario
 * @return string
 */
function validarNomeUsuario(?string $nomeUsuario): string
{
    if (empty($nomeUsuario)) {
        return 'Desconhecido';
    }
    // Remove caracteres não imprimíveis e limita tamanho a 50 chars
    return substr(preg_replace('/[^\P{C}\n]+/u', '', $nomeUsuario), 0, 50);
}

/**
 * Registra log no banco de dados.
 */
function registrarLogBD(PDO $pdo, ?int $idUsuario, string $nomeUsuario, string $status): void
{
    $sql = "INSERT INTO log_atividade (id_usuario, nome_usuario, ip_usuario, status_atividade, data_hora_atividade)
            VALUES (:idUsuario, :nomeUsuario, :ipUsuario, :status, NOW())";
    $stmt = $pdo->prepare($sql);

    $ipUsuario = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (is_null($idUsuario)) {
        $stmt->bindValue(':idUsuario', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':idUsuario', $idUsuario, PDO::PARAM_INT);
    }
    $stmt->bindValue(':nomeUsuario', $nomeUsuario, PDO::PARAM_STR);
    $stmt->bindValue(':ipUsuario', $ipUsuario, PDO::PARAM_STR);
    $stmt->bindValue(':status', $status, PDO::PARAM_STR);

    try {
        $stmt->execute();
    } catch (Exception $e) {
        error_log("[registrarLogBD] Erro ao registrar log no BD: " . $e->getMessage());
    }
}

/**
 * Grava log em arquivo, no formato padronizado texto simples.
 */
function registrarLogArquivo(int|string $idUsuario, string $nomeUsuario, string $ipUsuario, string $mensagemStatus): void
{
    $dataHora = date('Y-m-d H:i:s');
    $linhaLog = sprintf(
        "[%s] Usuário: %s | Nome Usuário: %s | IP: %s | Status: %s\n",
        $dataHora,
        $idUsuario,
        $nomeUsuario,
        $ipUsuario,
        $mensagemStatus
    );

    try {
        file_put_contents(LOG_FILE_PATH, $linhaLog, FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {
        error_log("[registrarLogArquivo] Erro ao gravar log em arquivo: " . $e->getMessage());
    }
}

/**
 * Função principal para registrar log de atividade.
 * @param PDO $pdo
 * @param string $status
 * @param int|null $idUsuario
 * @param string|null $nomeUsuario
 */
function registrarLogAtividade(PDO $pdo, string $status, ?int $idUsuario = null, ?string $nomeUsuario = null): void
{
    $mensagemStatus = getMensagemStatus($status);
    $nomeUsuarioValido = validarNomeUsuario($nomeUsuario);
    $idUsuarioLog = $idUsuario ?? 0;
    $ipUsuario = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    registrarLogBD($pdo, $idUsuario, $nomeUsuarioValido, $status);
    registrarLogArquivo($idUsuarioLog, $nomeUsuarioValido, $ipUsuario, $mensagemStatus);
}
