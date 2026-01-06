<?php
// File: app/models/header.php

// Carrega configurações de caminhos
require_once __DIR__ . '/../../configs/paths.php';

// Inicializa variáveis do usuário
$idUsuario     = $_SESSION['id_usuario'] ?? 0;
$nomeUsuario   = 'Convidado';
$fotoPerfilBD  = '';
$fotoPerfil    = '';

// Verifica se o usuário está logado e busca dados
if ($idUsuario > 0) {
    $stmtUser = $pdo->prepare("
        SELECT nome_usuario, imagem_usuario
        FROM usuario
        WHERE id_usuario = ?
        LIMIT 1
    ");
    $stmtUser->execute([$idUsuario]);

    if ($rowUser = $stmtUser->fetch(PDO::FETCH_ASSOC)) {
        $nomeUsuario  = $rowUser['nome_usuario'];
        $fotoPerfilBD = $rowUser['imagem_usuario'] ?? '';
    }
}

/* Detecta ambiente
$isLocalhost   = ($_SERVER['HTTP_HOST'] === 'localhost');
$isHttps       = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$isProduction  = $isHttps && !$isLocalhost;

if (empty($fotoPerfilBD)) {
	$fotoPerfil = IMG_PATH . '/perfil/sem-foto.jpg';
} else {
	if (preg_match('#^https?://#', $fotoPerfilBD)) {
		// Ajusta host e força HTTPS
		$url = str_replace('localhost', $_SERVER['HTTP_HOST'], $fotoPerfilBD);
		$fotoPerfil = preg_replace('#^http://#', 'https://', $url);
	} else {
		// Caminho relativo -> monta e força HTTPS se IMG_PATH for absoluto http
		$fotoPerfil = IMG_PATH . '/perfil/' . ltrim($fotoPerfilBD, '/');
		$fotoPerfil = preg_replace('#^http://#', 'https://', $fotoPerfil);
	}
}*/

// Detecta ambiente
$host         = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocalhost  = ($host === 'localhost' || $host === '127.0.0.1' || filter_var($host, FILTER_VALIDATE_IP));
$isHttps      = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$isProduction = ($isHttps && !$isLocalhost);

// Monta URL final da foto
if (empty($fotoPerfilBD)) {
    // padrão
    $fotoPerfil = rtrim(IMG_PATH, '/') . '/perfil/sem-foto.jpg';
} else if (preg_match('#^https?://#', $fotoPerfilBD)) {
    // valor já absoluto
    // troca host "localhost" pelo atual somente se NÃO for localhost
    if (!$isLocalhost) {
        $fotoPerfilBD = preg_replace('#^https?://localhost#', ($isProduction ? 'https://' : 'http://') . $host, $fotoPerfilBD);
    }
    // só força https em produção
    $fotoPerfil = $isProduction ? preg_replace('#^http://#', 'https://', $fotoPerfilBD) : $fotoPerfilBD;
} else {
    // valor relativo vindo do BD
    $fotoPerfil = rtrim(IMG_PATH, '/') . '/perfil/' . ltrim($fotoPerfilBD, '/');
    // NÃO tocar no esquema em dev; em prod, IMG_PATH já deve estar com https via BASE_URL
}


/* ================================
   Helpers para menu ativo/accordion
=================================== */
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$currentFile = strtolower(basename($currentPath));

$grpAdmin      = ['evento.php', 'instituicao.php', 'usuario.php'];
$grpCadastros  = ['ano-letivo.php', 'disciplina.php', 'nivel-ensino.php', 'professor.php', 'sala.php', 'serie.php', 'turma.php', 'turno.php'];
$grpEsportes   = ['categoria.php', 'hora-aula-escolinha.php', 'modalidade.php'];
$grpPainel     = ['horarios.php', 'horarios-treino.php'];
$grpRelatorios = ['horario-aulas.php', 'horarios-professores.php', 'horarios-treino-geral.php', 'relatorio-hora-aula-escolinha.php', 'prof-aulas.php'];
$grpHistorico  = ['historico-horarios.php'];
$grpSeguranca  = ['backup.php', 'restore.php'];

function isActive(string $file): string {
    global $currentFile;
    return ($currentFile === strtolower($file)) ? ' class="active-sub"' : '';
}

function isSectionActive(array $files): bool {
    global $currentFile;
    return in_array($currentFile, array_map('strtolower', $files), true);
}

function dropdownClasses(array $files): string {
    return isSectionActive($files) ? ' active' : '';
}

function dropdownShow(array $files): string {
    return isSectionActive($files) ? ' show' : '';
}

// Saudação com base no horário
$hora = date('H');
if ($hora >= 5 && $hora < 12) {
    $saudacao = 'Bom dia!';
} elseif ($hora >= 12 && $hora < 18) {
    $saudacao = 'Boa tarde!';
} else {
    $saudacao = 'Boa noite!';
}
?>

<!DOCTYPE html>
<html lang="pt-br" data-sw-path="<?= SW_PATH ?>">
<head><meta charset="utf-8">
	
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!-- verificacao -->
	<meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
	<!-- libs -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
	<!-- seu CSS -->
	<link rel="stylesheet" href="<?php echo CSS_PATH; ?>/style.css">
	<link rel="stylesheet" href="<?= SW_PATH ?>/sweetalert2.css">
	<link rel="icon" type="image/x-icon" href="<?php echo FAVICON_PATH; ?>">
	<title>Class Hours - Simples e Fácil de Usar!</title>
</head>
<body>
	<!-- SIDEBAR -->
	<section id="sidebar">
		<a href="#" class="brand"><i class="fa-solid fa-clock icon"></i>Class Hours</a> 
		<ul class="side-menu">
			<!-- Dashboard: ativo só quando estiver em dashboard.php -->
			<li>
				<a href="<?php echo PAGES_PATH; ?>/dashboard.php" <?php echo ($currentFile === 'dashboard.php' ? 'class="active"' : ''); ?>>
					<i class="fa-brands fa-microsoft icon"></i>Dashboard
				</a>
			</li> 
			<li class="divider" data-text="menu">Menu</li>
			
			<!-- Administração -->
			<li>
				<a href="#" class="has-dropdown<?php echo dropdownClasses($grpAdmin); ?>">
					<i class="fa-solid fa-lock icon"></i> Administração
					<i class="fa-solid fa-angle-right icon-right"></i>
				</a>
				<ul class="side-dropdown<?php echo dropdownShow($grpAdmin); ?>">
					<li><a href="<?php echo PAGES_PATH; ?>/evento.php"		<?php echo isActive('evento.php'); ?>>Eventos</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/instituicao.php"   <?php echo isActive('instituicao.php'); ?>>Instituição</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/usuario.php"	   <?php echo isActive('usuario.php'); ?>>Usuários</a></li>
				</ul>
			</li>
			
			<!-- Cadastros Gerais -->
			<li>
				<a href="#" class="has-dropdown<?php echo dropdownClasses($grpCadastros); ?>">
					<i class="fa-solid fa-folder-plus icon"></i> Cadastros Gerais
					<i class="fa-solid fa-angle-right icon-right"></i>
				</a>
				<ul class="side-dropdown<?php echo dropdownShow($grpCadastros); ?>">
					<li><a href="<?php echo PAGES_PATH; ?>/ano-letivo.php"	<?php echo isActive('ano-letivo.php'); ?>>Anos Letivos</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/disciplina.php"	<?php echo isActive('disciplina.php'); ?>>Disciplinas</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/nivel-ensino.php"  <?php echo isActive('nivel-ensino.php'); ?>>Níveis de Ensino</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/professor.php"	 <?php echo isActive('professor.php'); ?>>Professores</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/sala.php"		  <?php echo isActive('sala.php'); ?>>Salas</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/serie.php"		 <?php echo isActive('serie.php'); ?>>Séries</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/turma.php"		 <?php echo isActive('turma.php'); ?>>Turmas</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/turno.php"		 <?php echo isActive('turno.php'); ?>>Turnos</a></li>
				</ul>
			</li>
			
			<!-- Gestão Esportiva -->
			<li>
				<a href="#" class="has-dropdown<?php echo dropdownClasses($grpEsportes); ?>">
					<i class="fa-solid fa-medal icon"></i> Gestão Esportiva
					<i class="fa-solid fa-angle-right icon-right"></i>
				</a>
				<ul class="side-dropdown<?php echo dropdownShow($grpEsportes); ?>">
					<li><a href="<?php echo PAGES_PATH; ?>/categoria.php"			<?php echo isActive('categoria.php'); ?>>Categorias</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/hora-aula-escolinha.php"  <?php echo isActive('hora-aula-escolinha.php'); ?>>Controle de Hora-Aula</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/modalidade.php"		   <?php echo isActive('modalidade.php'); ?>>Modalidades</a></li>
				</ul>
			</li>	
			
			<!-- Painel de Horários -->
			<li>
				<a href="#" class="has-dropdown<?php echo dropdownClasses($grpPainel); ?>">
					<i class="fa-solid fa-clock icon"></i> Painel de Horários 
					<i class="fa-solid fa-angle-right icon-right"></i>
				</a>
				<ul class="side-dropdown<?php echo dropdownShow($grpPainel); ?>">
					<li><a href="<?php echo PAGES_PATH; ?>/horarios.php"		<?php echo isActive('horarios.php'); ?>>Horários de Aulas</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/horarios-treino.php" <?php echo isActive('horarios-treino.php'); ?>>Horários de Treinos</a></li>
				</ul>
			</li>
			
			<!-- Relatórios -->
			<li>
				<a href="#" class="has-dropdown<?php echo dropdownClasses($grpRelatorios); ?>">
					<i class="fa-solid fa-print icon"></i> Relatórios 
					<i class="fa-solid fa-angle-right icon-right"></i>
				</a>
				<ul class="side-dropdown<?php echo dropdownShow($grpRelatorios); ?>">
					<li><a href="<?php echo PAGES_PATH; ?>/horario-aulas.php"				 <?php echo isActive('horario-aulas.php'); ?>>Horário de Aulas</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/horarios-professores.php"		  <?php echo isActive('horarios-professores.php'); ?>>Horário dos Professores</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/horarios-treino-geral.php"		 <?php echo isActive('horarios-treino-geral.php'); ?>>Horário de Treinos</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/relatorio-hora-aula-escolinha.php" <?php echo isActive('relatorio-hora-aula-escolinha.php'); ?>>Hora/Aula de Treino</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/prof-aulas.php"					<?php echo isActive('prof-aulas.php'); ?>>Professores Aulas</a></li>
				</ul>
			</li>
			
			<!-- Histórico -->
			<li>
				<a href="#" class="has-dropdown<?php echo dropdownClasses($grpHistorico); ?>">
					<i class="fa-solid fa-clock-rotate-left icon"></i> Histórico 
					<i class="fa-solid fa-angle-right icon-right"></i>
				</a>
				<ul class="side-dropdown<?php echo dropdownShow($grpHistorico); ?>">
					<li><a href="<?php echo PAGES_PATH; ?>/historico-horarios.php" <?php echo isActive('historico-horarios.php'); ?>>Histórico de Horários</a></li>
				</ul>
			</li>
			
			<!-- Segurança -->
			<li>
				<a href="#" class="has-dropdown<?php echo dropdownClasses($grpSeguranca); ?>">
					<i class="fa-solid fa-shield icon"></i> Segurança 
					<i class="fa-solid fa-angle-right icon-right"></i>
				</a>
				<ul class="side-dropdown<?php echo dropdownShow($grpSeguranca); ?>">
					<li><a href="<?php echo PAGES_PATH; ?>/backup.php"  <?php echo isActive('backup.php'); ?>>Backup</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/restore.php" <?php echo isActive('restore.php'); ?>>Restaurar</a></li>
				</ul>
			</li>

			<!-- Sistema -->
			<li> 
				<a href="#" class="has-dropdown">
					<i class="fa-solid fa-laptop icon"></i> Sistema
					<i class="fa-solid fa-angle-right icon-right"></i>
				</a>
				<ul class="side-dropdown">
					<li><a href="<?php echo ARCHIVE_PATH; ?>" target="_blank">Ajuda</a></li>
					<li>
						<a href="#" data-logout-url="<?php echo BASE_URL; ?>/app/login/logout.php" class="logout-link">
							Sair
						</a>
					</li>
				</ul>
			</li>
		</ul>
	</section>
	<!-- SIDEBAR -->

	<!-- NAVBAR e CONTENT -->
	<section id="content">
	<!-- NAVBAR -->
		<nav>
			<i class="fa-solid fa-bars toggle-sidebar"></i>

			<form action="#">
				<div class="user-greeting">
					Olá, <?php echo htmlspecialchars($nomeUsuario); ?>!
				</div>
			</form>

			<div class="user-greeting">
				<?= $saudacao ?>
			</div>

			<span class="divider"></span>
			
			<div class="profile">
				<img src="<?php echo $fotoPerfil; ?>" alt="Profile" class="avatar">
				<ul class="profile-link">
					<li>
						<a href="#" data-logout-url="<?php echo BASE_URL; ?>/app/login/logout.php" class="logout-link">
							<i class="fa-solid fa-arrow-right-from-bracket"></i> Sair
						</a>
					</li>
				</ul>
			</div>
		</nav>
	<!-- NAVBAR -->

<!-- O restante da página segue... -->