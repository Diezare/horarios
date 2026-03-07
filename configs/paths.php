<?php
// File: configs/paths.php

// 1. Diretório Raiz do Projeto (absoluto)
define('ROOT_PATH', __DIR__ . '/../');

// 2. Detecção de Ambiente (Desenvolvimento ou Produção)
$isDev = (
    strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
    strpos($_SERVER['HTTP_HOST'], '192.168.0.91') !== false ||
    strpos($_SERVER['HTTP_HOST'], '10.147.20.215') !== false
);
define('ENVIRONMENT', $isDev ? 'development' : 'production');

// 3. Definição do Caminho Base (URL) do Projeto
if ($isDev) {
    $baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/horarios';
} else {
    // Ajuste para seu domínio real em produção
    //$baseUrl = 'https://estrategiaslegais.com.br/horarios';
}
define('BASE_URL', rtrim($baseUrl, '/'));

// 4. Caminhos para Recursos Públicos
define('ASSETS_PATH', BASE_URL . '/app/assets');
define('CSS_PATH', ASSETS_PATH . '/css');
define('JS_PATH', ASSETS_PATH . '/js');
define('IMG_PATH', ASSETS_PATH . '/imgs');
define('FAVICON_PATH', IMG_PATH . '/fav/favicon.png');
define('LOGO_PATH', ROOT_PATH . 'app/assets/imgs/logo');
define('LOGO_URL', BASE_URL . '/app/assets/imgs/logo');
define('ARCHIVE_PATH', BASE_URL . '/app/help/ajuda.pdf');
define('SW_PATH', ASSETS_PATH . '/sw2');

// 5. Caminho para as páginas (views)
define('PAGES_PATH', BASE_URL . '/app/pages');

// 6. Caminhos Internos no Servidor
define('APP_SERVER_PATH', ROOT_PATH . 'app');

// 7. Caminhos para Uploads
define('UPLOADS_PATH', ROOT_PATH . 'uploads');
define('UPLOADS_URL', BASE_URL . '/uploads');

// 8. Caminho para Logs
define('LOGS_PATH', ROOT_PATH . 'logs');

// Função auxiliar para obter caminho absoluto no sistema de arquivos
function absolute_path($path) {
    return rtrim(ROOT_PATH, '/\\') . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
}

// 9. Caminhos do MySQL / MySQLDump
if (PHP_OS_FAMILY === 'Windows') {
    define('MYSQLDUMP_PATH', 'C:\\xampp\\mysql\\bin\\mysqldump.exe');
    define('MYSQL_PATH', 'C:\\xampp\\mysql\\bin\\mysql.exe');
} else {
    define('MYSQLDUMP_PATH', '/usr/bin/mysqldump');
    define('MYSQL_PATH', '/usr/bin/mysql');
}
?>