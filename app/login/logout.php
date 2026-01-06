<?php
// horarios/app/login/logout.php
require_once __DIR__ . '/../../configs/init.php';

// ID e nome do usuário da sessão
$idUsuario   = $_SESSION['id_usuario']   ?? null;
$nomeUsuario = $_SESSION['nome_usuario'] ?? null;

// Registra "saida_sistema"
registrarLogAtividade($pdo, 'saida_sistema', $idUsuario, $nomeUsuario);

// Elimina a sessão
session_unset();
session_destroy();

// Remove cookie "rememberMe", se existir
if (isset($_COOKIE['rememberMe'])) {
	setcookie('rememberMe', '', time() - 3600, '/'); 
}

// Impedir cache
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redireciona ao login
header('Location: ' . BASE_URL . '/app/login/index.php');
exit;
?>