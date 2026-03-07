<?php
// app/controllers/restore/restoreBackup.php
declare(strict_types=1);

require_once __DIR__ . '/../../../configs/init.php';

header('Content-Type: application/json; charset=utf-8');
set_time_limit(0);

function jsonResponse(string $status, string $message, array $extra = []): void
{
	echo json_encode(array_merge([
		'status'  => $status,
		'message' => $message
	], $extra), JSON_UNESCAPED_UNICODE);
	exit;
}

if (
	!isset($_FILES['restoreFile']) ||
	$_FILES['restoreFile']['error'] !== UPLOAD_ERR_OK ||
	!is_uploaded_file($_FILES['restoreFile']['tmp_name'])
) {
	jsonResponse('error', 'Erro no upload do arquivo.');
}

$uploadedFile = $_FILES['restoreFile']['tmp_name'];
$origName     = $_FILES['restoreFile']['name'] ?? '';
$ext          = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

if ($ext !== 'sql') {
	jsonResponse('error', 'Apenas arquivos .sql são aceitos.');
}

$dbhost    = DB_HOST;
$dbname    = DB_NAME;
$dbuser    = DB_USER;
$dbpass    = DB_PASSWORD;
$dbcharset = defined('DB_CHARSET') && DB_CHARSET ? DB_CHARSET : 'utf8mb4';

if (!defined('MYSQL_PATH') || !file_exists(MYSQL_PATH)) {
	jsonResponse('error', 'mysql.exe não encontrado. Verifique a constante MYSQL_PATH em configs/paths.php.');
}

$mysqlPath = '"' . MYSQL_PATH . '"';

$tempSqlPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'restore_' . date('Ymd_His') . '_' . uniqid('', true) . '.sql';

if (!move_uploaded_file($uploadedFile, $tempSqlPath)) {
	jsonResponse('error', 'Não foi possível preparar o arquivo para restauração.');
}

/*
 * Corrige dump do MariaDB que vem com:
 * /*M!999999\- enable the sandbox mode *\/
 * Essa linha quebra no mysql.exe do Windows.
 */
$sqlContent = file_get_contents($tempSqlPath);
if ($sqlContent === false) {
	@unlink($tempSqlPath);
	jsonResponse('error', 'Não foi possível ler o arquivo temporário do restore.');
}

// Remove BOM UTF-8, se existir
$sqlContent = preg_replace('/^\xEF\xBB\xBF/', '', $sqlContent);

// Remove a linha problemática do sandbox mode
$sqlContent = preg_replace('/^\/\*M!999999\\\\- enable the sandbox mode \*\/\R?/m', '', $sqlContent);

// Também remove a versão sem barra, se vier em outro dump
$sqlContent = preg_replace('/^\/\*M!999999- enable the sandbox mode \*\/\R?/m', '', $sqlContent);

if (file_put_contents($tempSqlPath, $sqlContent) === false) {
	@unlink($tempSqlPath);
	jsonResponse('error', 'Não foi possível ajustar o arquivo SQL para restauração.');
}

$command = $mysqlPath
	. " --host=" . escapeshellarg($dbhost)
	. " --user=" . escapeshellarg($dbuser)
	. " --password=" . escapeshellarg($dbpass)
	. " --default-character-set=" . escapeshellarg($dbcharset)
	. " --binary-mode=1"
	. " " . escapeshellarg($dbname)
	. " < " . escapeshellarg($tempSqlPath)
	. " 2>&1";

$output = [];
$returnVar = 0;

exec($command, $output, $returnVar);

@unlink($tempSqlPath);

if ($returnVar !== 0) {
	$details = trim(implode("\n", $output));
	$message = 'Erro ao restaurar o backup.';

	if ($details !== '') {
		$message .= ' Detalhes: ' . mb_substr($details, 0, 1200);
	}

	$logDir = __DIR__ . '/../../../restore';
	if (!is_dir($logDir)) {
		@mkdir($logDir, 0755, true);
	}

	$errorLog = $logDir . '/restore_error.log';
	@file_put_contents(
		$errorLog,
		'[' . date('Y-m-d H:i:s') . '] ' . $origName . ' | retorno=' . $returnVar . ' | detalhes=' . $details . PHP_EOL,
		FILE_APPEND
	);

	jsonResponse('error', $message, [
		'return_code' => $returnVar
	]);
}

$logDir = __DIR__ . '/../../../restore';
if (!is_dir($logDir)) {
	@mkdir($logDir, 0755, true);
}

$logFile  = $logDir . '/restore_log.txt';
$logEntry = date('Y-m-d H:i:s') . ' - ' . $origName . " restaurado\n";
@file_put_contents($logFile, $logEntry, FILE_APPEND);

jsonResponse('success', 'Backup restaurado com sucesso.');