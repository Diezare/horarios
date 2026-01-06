<?php

// 1. Configurações do Banco de Dados
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'horario_aulas');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'Horarios@2025!;');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

// 2. Configurações de Erro e Logs
define('DB_ERRMODE', PDO::ERRMODE_EXCEPTION);
define('DISPLAY_ERRORS', false);

// 3. Função para configurar a conexão com o Banco de Dados
function configurarConexaoBanco() {
	$dsn = sprintf(
		"mysql:host=%s;dbname=%s;charset=%s",
		DB_HOST,
		DB_NAME,
		DB_CHARSET
	);

	try {
		$pdo = new PDO($dsn, DB_USER, DB_PASSWORD);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, DB_ERRMODE);
		$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		return $pdo;
	} catch (PDOException $e) {
		tratarErroConexao($e);
	}
}

// 4. Função para tratar erros de conexão
function tratarErroConexao($e) {
	if (DISPLAY_ERRORS) {
		echo "Erro de conexão: " . $e->getMessage();
	} else {
		error_log("Erro de conexão com o banco: " . $e->getMessage());
		die("Erro de conexão. Contate o administrador.");
	}
}

// 5. Função para configurar os relatórios de erro do PHP
function configurarRelatoriosErro() {
	error_reporting(E_ALL);
	ini_set('display_errors', DISPLAY_ERRORS ? '1' : '0');
}

// 6. Inicialização
configurarRelatoriosErro();
$pdo = configurarConexaoBanco();
?>