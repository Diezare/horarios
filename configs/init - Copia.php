<?php
/**
 * init.php — bootstrap seguro da aplicação
 * Requer: configs/paths.php define ROOT_PATH e BASE_URL
 */

///////////////////////////////////////////////////////////
// 0) Cabeçalhos de segurança e CSP
///////////////////////////////////////////////////////////
$allowedImgHosts = "http://localhost http://192.168.0.91 http://10.147.20.215 https://cdnjs.cloudflare.com https://fonts.gstatic.com";

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

/*$csp = "
	default-src 'self';
	base-uri 'self';
	object-src 'none';
	frame-ancestors 'self';
	img-src 'self' data: $allowedImgHosts https://www.gstatic.com;
	font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com data:;
	style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com https://www.gstatic.com;
	style-src-elem 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com https://www.gstatic.com;
	script-src 'self' https://www.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net;
	connect-src 'self';
";*/

$csp = "
    default-src 'self';
    base-uri 'self';
    object-src 'none';
    frame-ancestors 'self';
    img-src 'self' data: $allowedImgHosts https://www.gstatic.com;
    font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com data:;
    style-src 'self' https://cdnjs.cloudflare.com https://fonts.googleapis.com https://www.gstatic.com; // REMOVIDO 'unsafe-inline'
    style-src-elem 'self' https://cdnjs.cloudflare.com https://fonts.googleapis.com https://www.gstatic.com; // REMOVIDO 'unsafe-inline'
    script-src 'self' https://www.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net;
    connect-src 'self';
";


header_remove('Content-Security-Policy');
header('Content-Security-Policy: ' . preg_replace('/\s+/', ' ', trim($csp)));

if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
	header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}

///////////////////////////////////////////////////////////
// 1) Paths, DB, funções
///////////////////////////////////////////////////////////
require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/funcoes.php';

if (!isset($pdo)) {
	die("Erro: conexão com o banco não estabelecida.");
}

///////////////////////////////////////////////////////////
// 2) Timezone e constantes de sessão
///////////////////////////////////////////////////////////
date_default_timezone_set('America/Sao_Paulo');

if (!defined('SESSION_LIFETIME')) define('SESSION_LIFETIME', 14400);			 // 4h
if (!defined('SESSION_REGENERATE_TIME')) define('SESSION_REGENERATE_TIME', 3600); // 1h
ini_set('session.use_strict_mode', 1);

///////////////////////////////////////////////////////////
// 3) Logs: acessos e segurança
///////////////////////////////////////////////////////////
define('ACCESS_LOG_DIR', dirname(__DIR__) . '/acessos');						// C:\xampp\htdocs\horarios\acessos
define('ACCESS_LOG_FILE', ACCESS_LOG_DIR . '/log_acessos.log');

define('SECURITY_LOG_DIR', dirname(__DIR__) . '/logs');						 // C:\xampp\htdocs\horarios\logs
define('SECURITY_LOG_FILE', SECURITY_LOG_DIR . '/seguranca.log');

if (!is_dir(ACCESS_LOG_DIR)) @mkdir(ACCESS_LOG_DIR, 0775, true);
if (!is_dir(SECURITY_LOG_DIR)) @mkdir(SECURITY_LOG_DIR, 0775, true);

function logSecurityEvent(string $message): void {
	$date = date('Y-m-d H:i:s');
	@file_put_contents(SECURITY_LOG_FILE, "[$date] $message\n", FILE_APPEND | LOCK_EX);
}

// Opcional: se suas funções de auditoria quiserem um fallback simples
if (!function_exists('registrarLogAtividadeArquivo')) {
	function registrarLogAtividadeArquivo(string $linha): void {
		$date = date('Y-m-d H:i:s');
		@file_put_contents(ACCESS_LOG_FILE, "[$date] $linha\n", FILE_APPEND | LOCK_EX);
	}
}

///////////////////////////////////////////////////////////
// 4) Iniciar sessão antes de qualquer uso de $_SESSION
///////////////////////////////////////////////////////////
if (session_status() === PHP_SESSION_NONE) {
	$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
	// Em localhost/IP, deixe domain vazio para não quebrar o cookie
	$cookieDomain = ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) ? '' : $host;

	session_set_cookie_params([
		'lifetime' => 0,
		'path'		 => '/',
		'domain'	 => $cookieDomain,
		'secure'	 => !empty($_SERVER['HTTPS']),
		'httponly' => true,
		'samesite' => 'Strict'
	]);
	session_start();
}

///////////////////////////////////////////////////////////
// 5) “Remember me” — só agora que $pdo e sessão existem
///////////////////////////////////////////////////////////
if (!isset($_SESSION['id_usuario']) && isset($_COOKIE['rememberme_selector'], $_COOKIE['rememberme_token'])) {
	$selector = $_COOKIE['rememberme_selector'];
	$token		= $_COOKIE['rememberme_token'];

	$stmt = $pdo->prepare("SELECT user_id, token_hash, expires_at FROM remember_me_tokens WHERE selector = ?");
	$stmt->execute([$selector]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);

	if ($row && strtotime($row['expires_at']) > time() && password_verify($token, $row['token_hash'])) {
		$stmtUser = $pdo->prepare("SELECT id_usuario, nome_usuario, nivel_usuario, imagem_usuario FROM usuario WHERE id_usuario = ?");
		$stmtUser->execute([$row['user_id']]);
		if ($user = $stmtUser->fetch(PDO::FETCH_ASSOC)) {
			$_SESSION['id_usuario']		 = $user['id_usuario'];
			$_SESSION['nome_usuario']	 = $user['nome_usuario'];
			$_SESSION['nivel_usuario']	= $user['nivel_usuario'];
			$_SESSION['last_activity']	= time();
			$_SESSION['user_agent']		 = $_SERVER['HTTP_USER_AGENT'] ?? '';
			$_SESSION['ip_address']		 = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
			$_SESSION['imagem_usuario'] = $user['imagem_usuario'];
			// opcional: log de acesso
			registrarLogAtividadeArquivo("remember_me_ok user_id={$user['id_usuario']}");
		}
	} else {
		$secure = !empty($_SERVER['HTTPS']);
		setcookie('rememberme_selector', '', time() - 3600, '/', '', $secure, true);
		setcookie('rememberme_token',	 '', time() - 3600, '/', '', $secure, true);
		registrarLogAtividadeArquivo("remember_me_expirado_ou_invalido selector=$selector");
	}
}

///////////////////////////////////////////////////////////
// 6) Classe de verificação de sessão
///////////////////////////////////////////////////////////
class Sessao
{
	public static function verificarSessao(PDO $pdo): void {
		self::verificarIdUsuario($pdo);
		self::verificarTempoInatividade($pdo);
		self::verificarRegeneracaoId();
		self::verificarUserAgent();
		self::verificarEnderecoIP();
	}

	private static function verificarIdUsuario(PDO $pdo): void {
		if (!isset($_SESSION['id_usuario'])) {
			registrarLogAtividadeArquivo('acesso_negado_login');
			self::redirLogin();
		}
	}

	private static function verificarTempoInatividade(PDO $pdo): void {
		if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
			// Se existir sua função de auditoria no BD, chame-a; aqui fazemos arquivo como fallback
			registrarLogAtividadeArquivo('saida_sistema_por_inatividade user_id=' . ($_SESSION['id_usuario'] ?? 'null'));
			session_unset();
			session_destroy();
			self::redirLogin();
		}
		$_SESSION['last_activity'] = time();
	}

	private static function verificarRegeneracaoId(): void {
		if (!isset($_SESSION['created'])) {
			$_SESSION['created'] = time();
		} elseif (time() - $_SESSION['created'] > SESSION_REGENERATE_TIME) {
			if (!@session_regenerate_id(true)) {
				logSecurityEvent("Falha na regeneração do ID de sessão user_id=" . ($_SESSION['id_usuario'] ?? 'null'));
			}
			$_SESSION['created'] = time();
		}
	}

	private static function verificarUserAgent(): void {
		$currentUA = $_SERVER['HTTP_USER_AGENT'] ?? '';
		if (!isset($_SESSION['user_agent'])) {
			$_SESSION['user_agent'] = $currentUA;
			$_SESSION['user_agent_discrepancies'] = 0;
			$_SESSION['user_agent_last_change']	 = time();
			return;
		}
		if ($_SESSION['user_agent'] !== $currentUA) {
			$recent = (time() - ($_SESSION['user_agent_last_change'] ?? 0)) < 300;
			$_SESSION['user_agent_discrepancies'] = ($recent ? ($_SESSION['user_agent_discrepancies'] ?? 0) + 1 : 1);
			$_SESSION['user_agent_last_change']	 = time();

			if ($_SESSION['user_agent_discrepancies'] >= 3) {
				logSecurityEvent("User-Agent múltiplas discrepâncias. Forçando logout. user_id=" . ($_SESSION['id_usuario'] ?? 'null'));
				session_unset();
				session_destroy();
				self::redirLogin();
			} else {
				logSecurityEvent("User-Agent discrepante. Sessão='{$_SESSION['user_agent']}' Atual='{$currentUA}' user_id=" . ($_SESSION['id_usuario'] ?? 'null'));
				$_SESSION['user_agent'] = $currentUA;
			}
		}
	}

	private static function verificarEnderecoIP(): void {
		$currentIP = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
		if (!isset($_SESSION['ip_address'])) {
			$_SESSION['ip_address'] = $currentIP;
			$_SESSION['ip_address_discrepancies'] = 0;
			$_SESSION['ip_address_last_change']	 = time();
			return;
		}
		if ($_SESSION['ip_address'] !== $currentIP) {
			$recent = (time() - ($_SESSION['ip_address_last_change'] ?? 0)) < 300;
			$_SESSION['ip_address_discrepancies'] = ($recent ? ($_SESSION['ip_address_discrepancies'] ?? 0) + 1 : 1);
			$_SESSION['ip_address_last_change']	 = time();

			if ($_SESSION['ip_address_discrepancies'] >= 3) {
				logSecurityEvent("IP múltiplas discrepâncias. Forçando logout. user_id=" . ($_SESSION['id_usuario'] ?? 'null'));
				session_unset();
				session_destroy();
				self::redirLogin();
			} else {
				logSecurityEvent("IP discrepante. Sessão='{$_SESSION['ip_address']}' Atual='{$currentIP}' user_id=" . ($_SESSION['id_usuario'] ?? 'null'));
				$_SESSION['ip_address'] = $currentIP;
			}
		}
	}

	private static function redirLogin(): void {
		header('Location: ' . BASE_URL . '/app/login/index.php');
		exit;
	}
}

///////////////////////////////////////////////////////////
// 7) Gate global: pula rotas de login
///////////////////////////////////////////////////////////
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$excecoes = [
	'/app/login/index.php',
	'/app/controllers/login/loginCheck.php',
	'/app/login/logout.php'
];

$precisaVerificar = true;
foreach ($excecoes as $rota) {
	if (strpos($currentUri, $rota) !== false) { $precisaVerificar = false; break; }
}
if ($precisaVerificar) {
	Sessao::verificarSessao($pdo);
}
