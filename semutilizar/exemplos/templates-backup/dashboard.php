<?php
// horarios/app/pages/dashboard.php
require_once __DIR__ . '/../../configs/init.php';
include_once APP_SERVER_PATH . '/models/header.php';

// Agora habilite a verificação da sessão (se estiver comentada no init, pode chamar manualmente):
// Sessao::verificarSessao($pdo);

Sessao::verificarSessao($pdo); // forçando a verificação

// Se não estiver logado, a função redireciona para /login/login.php
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
	<meta charset="UTF-8">
	<title>Backup do Sistema</title>
	<link rel="stylesheet" href="<?php echo CSS_PATH; ?>/style.css">
</head>
<body>
<main>
	<div class="page-header">
		<h1>Dashboard</h1>
	</div>

	<div class="data">
		<div class="content-data">

		</div>
	</div>

	<script src="<?php echo JS_PATH; ?>/script.js"></script>
</body>
</html>