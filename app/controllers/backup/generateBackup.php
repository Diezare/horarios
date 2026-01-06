<?php 
// app/controllers/backup/generateBackup.php
require_once __DIR__ . '/../../../configs/init.php';

// Utilize as constantes definidas no seu database.php
$dbhost	= DB_HOST;
$dbname	= DB_NAME;
$dbuser	= DB_USER;
$dbpass	= DB_PASSWORD;
$dbcharset = DB_CHARSET; // ex: 'utf8mb4'

// Utilize a constante MYSQLDUMP_PATH definida em paths.php
$mysqldumpPath = '"' . MYSQLDUMP_PATH . '"';

// Verifica se o caminho para o mysqldump foi definido corretamente
if (!file_exists(MYSQLDUMP_PATH)) {
	die("mysqldump não encontrado. Verifique o caminho em MYSQLDUMP_PATH.");
}

// Define o nome do arquivo de backup com a data/hora atual (ex.: backup_20250304_143012.sql)
$timestamp = date('Ymd_His');
$backupFilename = "backup_{$timestamp}.sql";

// Define um caminho temporário para gerar o backup (o arquivo será removido após o download)
$tempBackupPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $backupFilename;

// Monta o comando mysqldump garantindo a codificação UTF-8
$command = $mysqldumpPath 
		. " --host=" . escapeshellarg($dbhost)
		. " --user=" . escapeshellarg($dbuser)
		. " --password=" . escapeshellarg($dbpass)
		. " --default-character-set=" . escapeshellarg($dbcharset)
		. " " . escapeshellarg($dbname)
		. " > " . escapeshellarg($tempBackupPath);

exec($command, $output, $return_var);
if ($return_var !== 0) {
	die("Erro ao gerar backup.");
}

// Preparar o log (mas somente registraremos se a transferência for concluída)
$logEntry = date('Y-m-d H:i:s') . " - " . $backupFilename . "\n";
$logDir = __DIR__ . '/../../../backup';
if (!is_dir($logDir)) {
	mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/backup_log.txt';

// Preparação para o download
ignore_user_abort(false); // Permite detectar se o usuário abortou a conexão

// Envia os headers para download
header('Content-Description: File Transfer');
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $backupFilename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($tempBackupPath));

// Em vez de usar readfile(), lemos o arquivo em blocos
$fp = fopen($tempBackupPath, 'rb');
$transferCompleted = true;
if ($fp) {
	while (!feof($fp)) {
		// Lê um bloco (8KB)
		$buffer = fread($fp, 8192);
		echo $buffer;
		flush();
		// Se o usuário abortou a conexão, marca como não concluída e sai do loop
		if (connection_aborted()) {
			$transferCompleted = false;
			break;
		}
	}
	fclose($fp);
} else {
	$transferCompleted = false;
}
unlink($tempBackupPath);

// Registra o log somente se a transferência foi concluída
if ($transferCompleted && connection_status() === CONNECTION_NORMAL) {
	file_put_contents($logFile, $logEntry, FILE_APPEND);
}

exit;
