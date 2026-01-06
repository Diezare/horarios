<?php
require_once __DIR__ . '/../../configs/init.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
	<meta charset="utf-8">
	<title>Login - Class Hours</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;300;400;600;700;900&display=swap">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
	<link rel="stylesheet" href="<?php echo CSS_PATH; ?>/login.css" />
	<link rel="stylesheet" href="<?php echo SW_PATH ?>/sweetalert2.css">
	<link rel="icon" type="image/x-icon" href="<?php echo IMG_PATH; ?>/fav/favicon.png" />
</head>
<body>
  <div class="wrapper">
    <h1>Login</h1>
    <p id="error-message" aria-live="polite"></p>

    <form id="loginForm" method="POST" action="/horarios/app/controllers/login/loginCheck.php" autocomplete="off" novalidate>
      <!-- usuário -->
      <div>
        <label for="username">
          <span>@</span>
        </label>
        <input type="text" name="username" id="username" placeholder="Nome de usuário" required>
      </div>

      <!-- senha -->
      <div>
        <label for="password">
          <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="M240-80q-33 0-56.5-23.5T160-160v-400q0-33 23.5-56.5T240-640h40v-80q0-83 58.5-141.5T480-920q83 0 141.5 58.5T680-720v80h40q33 0 56.5 23.5T800-560v400q0 33-23.5 56.5T720-80H240Zm240-200q33 0 56.5-23.5T560-360q0-33-23.5-56.5T480-440q-33 0-56.5 23.5T400-360q0 33 23.5 56.5T480-280ZM360-640h240v-80q0-50-35-85t-85-35q-50 0-85 35t-35 85v80Z"/></svg>
        </label>
        <input type="password" name="password" id="password" placeholder="Senha" required>
      </div>

      <!-- lembrar-me (markup limpo; estilo no login.css) -->
		<div class="lembrar-me">
		  <input type="checkbox" id="rememberMe" name="rememberMe" />
		  <label for="rememberMe">Lembrar-me</label>
		</div>

      <!-- botão do mesmo estilo do template -->
      <button type="submit">Login</button>

      <!-- texto de recuperar senha (visível abaixo do botão) -->
      <p class="esqueceu-senha">
        <span id="linkEsqueceuSenha" role="button" tabindex="0">Esqueceu sua senha?</span>
      </p>
    </form>
  </div>

  <script src="<?php echo SW_PATH; ?>/sweetalert2.all.min.js"></script>
  <!--<script src="<?php echo JS_PATH; ?>/validation.js" defer></script>-->
  <script src="<?php echo JS_PATH; ?>/login.js"></script>
</body>
</html>
