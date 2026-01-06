<?php
// Exemplo: require_once init.php (já inicia sessão e tem $pdo)
$idUsuario    = $_SESSION['id_usuario']   ?? 0;
$nomeUsuario  = 'Convidado';
$fotoPerfilBD = '';

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

// Se não tiver foto, usar sem-foto
if (empty($fotoPerfilBD)) {
    // Ajuste se tiver caminho absoluto
    $fotoPerfil = 'http://localhost/horarios/app/assets/imgs/perfil/sem-foto.jpg';
} else {
    // Já vem do BD o caminho completo (ex: 'http://localhost/horarios/app/assets/imgs/perfil/xxx.jpg')
    $fotoPerfil = $fotoPerfilBD;
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
	<link rel="stylesheet" href="<?php echo CSS_PATH; ?>/style.css">
	<link rel="icon" type="image/x-icon" href="<?php echo IMG_PATH; ?>/fav/favicon.png">
	<title>Class Hours - Simples e Fácil de Usar!</title>
</head>
<body>
	<!-- SIDEBAR -->
	<section id="sidebar">
		<a href="#" class="brand"><i class="fa-solid fa-clock icon"></i></i>Class Hours</a> 
		<ul class="side-menu">
			<li><a href="#" class="active"><i class="fa-brands fa-microsoft icon"></i></i>Dashboard</a></li> 
			<li class="divider" data-text="menu">Menu</li>
			<li>
				<a href="#"><i class="fa-solid fa-folder-plus icon"></i> Cadastros <i class="fa-solid fa-angle-right icon-right"></i></a>
				<ul class="side-dropdown">
					<li><a href="<?php echo PAGES_PATH; ?>/ano-letivo.php">Ano Letivo</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/disciplina.php">Disciplina</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/instituicao.php">Instituição</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/nivel-ensino.php">Nivel de Ensino</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/professor.php">Professor</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/serie.php">Série</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/turma.php">Turma</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/turno.php">Turno</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/usuario.php">Usuário</a></li>
				</ul>
			</li>
			<li>
				<a href="#"><i class="fa-solid fa-clock icon"></i> Gerar Horários <i class="fa-solid fa-angle-right icon-right"></i></a>
				<ul class="side-dropdown">
					<li><a href="<?php echo PAGES_PATH; ?>/horarios.php">Horários</a></li>
				</ul>
			</li>
			<li>
				<a href="#"><i class="fa-solid fa-print icon"></i> Relatórios <i class="fa-solid fa-angle-right icon-right"></i></a>
				<ul class="side-dropdown">
					<li><a href="<?php echo PAGES_PATH; ?>/historico-horarios.php">Histórico de Horários</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/relatorio-horarios.php">Horários de Aulas</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/relatorio-professores-aulas.php">Professores Aulas</a></li>
				</ul>
			</li>
			<li>
				<a href="#"><i class="fa-solid fa-shield icon"></i> Segurança <i class="fa-solid fa-angle-right icon-right"></i></a>
				<ul class="side-dropdown">
					<li><a href="<?php echo PAGES_PATH; ?>/backup.php">Backup</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/restore.php">Restarurar</a></li>
				</ul>
			</li>
			<li> 
				<a href="#"><i class="fa-solid fa-laptop icon"></i> Sistema <i class="fa-solid fa-angle-right icon-right"></i></a>
				<ul class="side-dropdown">
					<li><a href="<?php echo PAGES_PATH; ?>/ajuda.php">Ajuda</a></li>
				</ul>
			</li>
		</ul>
		<!-- <div class="ads">
			<div class="wrapper">
				<a href="#" class="btn-upgrade">Upgrade</a>
				<p>Become a <span>PRO</span> member and enjoy <span>All Features</span></p>
			</div>
		</div> -->
	</section>
	<!-- SIDEBAR -->

	<!-- NAVBAR e CONTENT -->
<section id="content">
    <!-- NAVBAR -->
    <nav>
        <!-- Ícone para abrir/fechar sidebar -->
        <i class="fa-solid fa-bars toggle-sidebar"></i>
        
        <!-- Campo de busca permanece do lado esquerdo -->
        <form action="#" style="margin-right: auto;">
            <!--<div class="form-group">
                <input type="text" placeholder="Pesquisar no sistema...">
                <i class='bx bx-search icon'></i>
            </div>-->
        </form>

        <!-- Saudação do usuário, com algum espaçamento -->
        <div class="user-greeting" style="margin-right: 20px; font-weight: 500;">
            Olá, <?php echo htmlspecialchars($nomeUsuario); ?>!
        </div>

        <!-- Ícones de notificação 
        <a href="#" class="nav-link">
            <i class="fa-solid fa-bell icon"></i>
            <span class="badge">5</span>
        </a>
        <a href="#" class="nav-link">
            <i class="fa-solid fa-message icon"></i>
            <span class="badge">8</span>
        </a> -->

        <span class="divider"></span>

        <!-- Perfil no canto direito -->
        <div class="profile">
            <img 
                src="<?php echo $fotoPerfil; ?>" 
                alt="Profile"
                style="object-fit: cover; width:36px; height:36px; border-radius: 50%;"
            >
            <ul class="profile-link">
                <!-- Caso queira Perfil/Configurações, descomente:
                <li><a href="#"><i class="fa-solid fa-circle-user"></i> Perfil</a></li>
                <li><a href="#"><i class="fa-solid fa-gear"></i> Configurações</a></li>
                -->
                <li>
                    <a href="<?php echo BASE_URL; ?>/app/login/logout.php">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i> Sair
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    <!-- NAVBAR -->
