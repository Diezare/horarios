<?php
// File: app/models/header.php

// Certifique-se de que as configurações de paths foram carregadas
require_once __DIR__ . '/../../configs/paths.php';

$idUsuario    = $_SESSION['id_usuario'] ?? 0;
$nomeUsuario  = 'Convidado';
$fotoPerfilBD = ''; // valor vindo do BD
$fotoPerfil   = ''; // valor final para exibir no <img src="...">

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

// Se não houver foto definida, usar 'sem-foto.jpg'
if (empty($fotoPerfilBD)) {
    // Usa IMG_PATH para montar o caminho relativo da imagem padrão
    $fotoPerfil = IMG_PATH . '/perfil/sem-foto.jpg';
} else {
    // Se o BD armazenar uma URL absoluta, substitua "localhost" pelo host atual
    if (preg_match('#^https?://#', $fotoPerfilBD)) {
        $fotoPerfil = str_replace('localhost', $_SERVER['HTTP_HOST'], $fotoPerfilBD);
    } else {
        // Caso contrário, monta o caminho relativo com IMG_PATH
        $fotoPerfil = IMG_PATH . '/perfil/' . $fotoPerfilBD;
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>/style.css">
    <link rel="icon" type="image/x-icon" href="<?php echo FAVICON_PATH; ?>">
    <title>Class Hours - Simples e Fácil de Usar!</title>
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand"><i class="fa-solid fa-clock icon"></i>Class Hours</a> 
        <ul class="side-menu">
            <li><a href="<?php echo PAGES_PATH; ?>/dashboard.php" class="active"><i class="fa-brands fa-microsoft icon"></i>Dashboard</a></li> 
            <li class="divider" data-text="menu">Menu</li>
			
			<li>
                <a href="#"><i class="fa-solid fa-lock icon"></i> Administração<i class="fa-solid fa-angle-right icon-right"></i></a>
                <ul class="side-dropdown">
					<li><a href="<?php echo PAGES_PATH; ?>/evento.php">Eventos</a></li>
                    <li><a href="<?php echo PAGES_PATH; ?>/instituicao.php">Instituição</a></li>
                    <li><a href="<?php echo PAGES_PATH; ?>/usuario.php">Usuários</a></li>
                </ul>
            </li>
			
			
            <li>
                <a href="#"><i class="fa-solid fa-folder-plus icon"></i> Cadastros Gerais<i class="fa-solid fa-angle-right icon-right"></i></a>
                <ul class="side-dropdown">
                    <li><a href="<?php echo PAGES_PATH; ?>/ano-letivo.php">Anos Letivos</a></li>
                    <li><a href="<?php echo PAGES_PATH; ?>/disciplina.php">Disciplinas</a></li>
                    <li><a href="<?php echo PAGES_PATH; ?>/nivel-ensino.php">Níveis de Ensino</a></li>
                    <li><a href="<?php echo PAGES_PATH; ?>/professor.php">Professores</a></li>
                    <li><a href="<?php echo PAGES_PATH; ?>/sala.php">Salas</a></li>
                    <li><a href="<?php echo PAGES_PATH; ?>/serie.php">Séries</a></li>
                    <li><a href="<?php echo PAGES_PATH; ?>/turma.php">Turmas</a></li>
                    <li><a href="<?php echo PAGES_PATH; ?>/turno.php">Turnos</a></li>
                </ul>
            </li>
			
			<li>
                <a href="#"><i class="fa-solid fa-medal icon"></i> Gestão Esportiva<i class="fa-solid fa-angle-right icon-right"></i></a>
                <ul class="side-dropdown">

                    <li><a href="<?php echo PAGES_PATH; ?>/categoria.php">Categorias</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/hora-aula-escolinha.php">Controle de Hora-Aula</a></li>
                    <li><a href="<?php echo PAGES_PATH; ?>/modalidade.php">Modalidades</a></li>
                </ul>
            </li>	
			
            <li>
                <a href="#"><i class="fa-solid fa-clock icon"></i> Painel de Horários <i class="fa-solid fa-angle-right icon-right"></i></a>
                <ul class="side-dropdown">
                    <li><a href="<?php echo PAGES_PATH; ?>/horarios.php">Horários de Aulas</a></li>
                    <li><a href="<?php echo PAGES_PATH; ?>/horarios-treino.php">Horários de Treinos</a></li>
                </ul>
            </li>
			
            <li>
                <a href="#"><i class="fa-solid fa-print icon"></i> Relatórios <i class="fa-solid fa-angle-right icon-right"></i></a>
                <ul class="side-dropdown">
                    <li><a href="<?php echo PAGES_PATH; ?>/horario-aulas.php">Horário de Aulas</a></li>
                    <li><a href="<?php echo PAGES_PATH; ?>/horarios-professores.php">Horário dos Professores</a></li>
                    <li><a href="<?php echo PAGES_PATH; ?>/horarios-treino-geral.php">Horário de Treinos</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/relatorio-hora-aula-escolinha.php">Hora/Aula de Treino</a></li>
					<li><a href="<?php echo PAGES_PATH; ?>/prof-aulas.php">Professores Aulas</a></li>
                </ul>
            </li>
			
			<li>
                <a href="#"><i class="fa-solid fa-clock-rotate-left icon"></i> Histórico <i class="fa-solid fa-angle-right icon-right"></i></a>
                <ul class="side-dropdown">
                    <li><a href="<?php echo PAGES_PATH; ?>/historico-horarios.php">Histórico de Horários</a></li>
                </ul>
            </li>
			
            <li>
                <a href="#"><i class="fa-solid fa-shield icon"></i> Segurança <i class="fa-solid fa-angle-right icon-right"></i></a>
                <ul class="side-dropdown">
                    <li><a href="<?php echo PAGES_PATH; ?>/backup.php">Backup</a></li>
                    <li><a href="<?php echo PAGES_PATH; ?>/restore.php">Restaurar</a></li>
                </ul>
            </li>
            <li> 
                <a href="#"><i class="fa-solid fa-laptop icon"></i> Sistema <i class="fa-solid fa-angle-right icon-right"></i></a>
                <ul class="side-dropdown">
                    <li><a href="<?php echo ARCHIVE_PATH; ?>" target="_blank">Ajuda</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/app/login/logout.php">Sair</a></li>
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

            <form action="#" style="margin-right: auto;">
                <div class="user-greeting" style="margin-right: 20px; font-weight: 500;">
                    Olá, <?php echo htmlspecialchars($nomeUsuario); ?>!
                </div>
            </form>

            <div class="user-greeting" style="margin-right: 20px; font-weight: 500;">
                Aproveite ao máximo do sistema.
            </div>

            <span class="divider"></span>

            <div class="profile">
                <img 
                    src="<?php echo $fotoPerfil; ?>" 
                    alt="Profile"
                    style="object-fit: cover; width:36px; height:36px; border-radius: 50%;"
                >
                <ul class="profile-link">
                    <li>
                        <a href="<?php echo BASE_URL; ?>/app/login/logout.php">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i> Sair
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    <!-- NAVBAR -->

<!-- O restante da página segue... -->
