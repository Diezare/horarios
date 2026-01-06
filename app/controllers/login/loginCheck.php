<?php
// horarios/app/controllers/login/loginCheck.php 
header('Content-Type: application/json');

require_once __DIR__ . '/../../../configs/init.php';

// Função para gerar token aleatório seguro
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Função para limpar tokens expirados (por exemplo com validade de 30 dias)
function clearExpiredTokens($pdo) {
    $stmt = $pdo->prepare("DELETE FROM remember_me_tokens WHERE expires_at < NOW()");
    $stmt->execute();
}

// Função para salvar token no banco (selector + hashed token)
function saveRememberMeToken($pdo, $userId, $selector, $tokenHash, $expiresAt) {
    // Limpa tokens expirados antes
    clearExpiredTokens($pdo);

    // Insere novo token
    $stmt = $pdo->prepare("
        INSERT INTO remember_me_tokens(user_id, selector, token_hash, expires_at)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $selector, $tokenHash, $expiresAt]);
}

try {
    // 1) Obtem credenciais
    $nomeUsuario   = trim($_POST['username'] ?? '');
    $senhaDigitada = $_POST['password'] ?? '';
    $rememberMe    = isset($_POST['rememberMe']);

    if (empty($nomeUsuario) || empty($senhaDigitada)) {
        registrarLogAtividade($pdo, 'falha_login', null, 'SemUsuario');
        echo json_encode(['status' => 'error', 'message' => 'Usuário ou senha vazios.']);
        exit;
    }

    // 2) Busca o usuário, incluindo nivel_usuario
    $stmt = $pdo->prepare("
        SELECT id_usuario,
               nome_usuario,
               senha_usuario,
               situacao_usuario,
               nivel_usuario,
               imagem_usuario
          FROM usuario
         WHERE BINARY nome_usuario = ?
         LIMIT 1
    ");
    $stmt->execute([$nomeUsuario]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3) Se não encontrou => 'acesso_negado_login'
    if (!$row) {
        registrarLogAtividade($pdo, 'acesso_negado_login', null, $nomeUsuario);
        echo json_encode(['status' => 'error', 'message' => 'Credenciais inválidas.']);
        exit;
    }

    // 4) Verifica a senha
    if (!password_verify($senhaDigitada, $row['senha_usuario'])) {
        registrarLogAtividade($pdo, 'falha_login', $row['id_usuario'] ?? null, $row['nome_usuario']);
        echo json_encode(['status' => 'error', 'message' => 'Credenciais inválidas.']);
        exit;
    }

    // 5) Verifica se está ativo
    if ($row['situacao_usuario'] !== 'Ativo') {
        registrarLogAtividade($pdo, 'acesso_negado_login', $row['id_usuario'], $row['nome_usuario']);
        echo json_encode(['status' => 'error', 'message' => 'Usuário inativo.']);
        exit;
    }

    // 6) Login OK: define as variáveis de sessão
    $_SESSION['id_usuario']    = $row['id_usuario'];
    $_SESSION['nome_usuario']  = $row['nome_usuario'];
    $_SESSION['nivel_usuario'] = $row['nivel_usuario'];  // IMPORTANTE
    $_SESSION['last_activity'] = time();
    $_SESSION['user_agent']    = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $_SESSION['ip_address']    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $_SESSION['imagem_usuario'] = $row['imagem_usuario'];

    registrarLogAtividade($pdo, 'sucesso_login', $row['id_usuario'], $row['nome_usuario']);

    // 7) Se marcou "Lembrar-me", crie o token e o cookie
    if ($rememberMe) {
        $selector = generateToken(12);  // identificador público
        $token = generateToken(24);     // token secreto
        $tokenHash = password_hash($token, PASSWORD_DEFAULT);

        // Expira em 30 dias
        $expiresAt = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30);

        // Salva no banco o selector + hash(token) + validade
        saveRememberMeToken($pdo, $row['id_usuario'], $selector, $tokenHash, $expiresAt);

        // Cria cookie seguro com selector e token (não armazene o token puro no banco, só o hash)
        setcookie('rememberme_selector', $selector, time() + 60 * 60 * 24 * 30, '/', '', true, true);
        setcookie('rememberme_token', $token, time() + 60 * 60 * 24 * 30, '/', '', true, true);
    } else {
        // Limpa o cookie caso não esteja marcado
        setcookie('rememberme_selector', '', time() - 3600, '/', '', true, true);
        setcookie('rememberme_token', '', time() - 3600, '/', '', true, true);
    }

    echo json_encode(['status' => 'success']);
    exit;

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro interno: ' . $e->getMessage()]);
    exit;
}
?>