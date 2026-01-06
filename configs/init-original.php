<?php
/**
 * init.php
 * Inicialização geral da aplicação.
 */

// 1. Carrega as constantes de caminho e funções auxiliares
require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/funcoes.php';

// Se necessário, inclua a conexão com o banco de dados (PDO)
require_once ROOT_PATH . '/configs/database.php';

// 2. Define timezone
date_default_timezone_set('America/Sao_Paulo');

// 3. Define tempo de vida da sessão em 4 horas (14400 segundos)
if (!defined('SESSION_LIFETIME')) {
	define('SESSION_LIFETIME', 14400); // 4 horas
}
if (!defined('SESSION_REGENERATE_TIME')) {
	define('SESSION_REGENERATE_TIME', 300); // 5 minutos
}

// 4. Iniciar sessão com cookie seguro, apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
	session_set_cookie_params([
		'lifetime' => 0,
		'path'	 => '/',
		'domain'   => $_SERVER['HTTP_HOST'],
		'secure'   => !empty($_SERVER['HTTPS']),
		'httponly' => true,
		'samesite' => 'Strict'
	]);
	session_start();
}

class Sessao
{
	public static function verificarSessao($pdo)
	{
		self::verificarIdUsuario($pdo);
		self::verificarTempoInatividade($pdo);
		self::verificarRegeneracaoId();
		self::verificarUserAgent();
		self::verificarEnderecoIP();
	}

	private static function verificarIdUsuario($pdo)
	{
		// Se não houver id_usuario na sessão, considera que não está logado
		if (!isset($_SESSION['id_usuario'])) {
			registrarLogAtividade($pdo, 'acesso_negado_login', null, null);
			self::redirecionarParaLogin();
			exit;
		}
	}

	private static function verificarTempoInatividade($pdo)
	{
		if (isset($_SESSION['last_activity']) 
			&& (time() - $_SESSION['last_activity']) > SESSION_LIFETIME
		) {
			// Sessão expirou => loga 'saida_sistema'
			registrarLogAtividade(
				$pdo, 
				'saida_sistema', 
				$_SESSION['id_usuario'] ?? null, 
				$_SESSION['nome_usuario'] ?? null
			);

			session_unset();
			session_destroy();
			self::redirecionarParaLogin();
		}
		$_SESSION['last_activity'] = time();
	}

	private static function verificarRegeneracaoId()
	{
		if (!isset($_SESSION['created'])) {
			$_SESSION['created'] = time();
		} elseif (time() - $_SESSION['created'] > SESSION_REGENERATE_TIME) {
			session_regenerate_id(true);
			$_SESSION['created'] = time();
		}
	}

	private static function verificarUserAgent()
	{
		if (!isset($_SESSION['user_agent'])) {
			$_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
		} elseif ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
			session_unset();
			session_destroy();
			self::redirecionarParaLogin();
		}
	}

	private static function verificarEnderecoIP()
	{
		if (!isset($_SESSION['ip_address'])) {
			$_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
		} elseif ($_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')) {
			session_unset();
			session_destroy();
			self::redirecionarParaLogin();
		}
	}

	private static function redirecionarParaLogin()
	{
		header('Location: ' . BASE_URL . '/app/login/index.php');
		exit();
	}
}

// Certifique-se de que a variável $pdo foi definida.
if (!isset($pdo)) {
	die("Erro: conexão com o banco de dados não estabelecida.");
}

/**
 * 5) Verificação GLOBAL, exceto nas rotas de login
 * Se a URL atual contém "/app/login/index.php", "/loginCheck.php" ou "/app/login/logout.php",
 * pulamos a verificação. Em todas as outras páginas, exigimos login.
 */
$currentUri = $_SERVER['REQUEST_URI'] ?? '';

// Rotas que não exigem login:
$excecoes = [
    '/app/login/index.php',
    '/app/controllers/login/loginCheck.php',
    '/app/login/logout.php'
];

$precisaVerificar = true;
foreach ($excecoes as $rotaIgnorada) {
    if (strpos($currentUri, $rotaIgnorada) !== false) {
        $precisaVerificar = false;
        break;
    }
}

if ($precisaVerificar) {
	Sessao::verificarSessao($pdo);
}
?>