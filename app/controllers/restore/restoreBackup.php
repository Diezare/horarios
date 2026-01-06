<?php
// app/controllers/backup/restoreBackup.php
declare(strict_types=1);
require_once __DIR__ . '/../../../configs/init.php';

header('Content-Type: application/json; charset=utf-8');
set_time_limit(0);

// 1) Checagem do upload
if (
	!isset($_FILES['restoreFile']) ||
	$_FILES['restoreFile']['error'] !== UPLOAD_ERR_OK ||
	!is_uploaded_file($_FILES['restoreFile']['tmp_name'])
) {
	echo json_encode(['status' => 'error', 'message' => 'Erro no upload do arquivo.']);
	exit;
}

$uploadedFile = $_FILES['restoreFile']['tmp_name'];
$origName		 = $_FILES['restoreFile']['name'] ?? '';
$ext					= strtolower(pathinfo($origName, PATHINFO_EXTENSION));

// Aceita apenas .sql (ajuste se for suportar .gz)
if ($ext !== 'sql') {
	echo json_encode(['status' => 'error', 'message' => 'Apenas arquivos .sql são aceitos.']);
	exit;
}

// 2) Dados de conexão
$dbhost		= DB_HOST;
$dbname		= DB_NAME;
$dbuser		= DB_USER;
$dbpass		= DB_PASSWORD;
$dbcharset = DB_CHARSET;

// 3) Local do binário mysql (Windows/Linux)
$mysqlPath = (defined('MYSQL_PATH') && file_exists(MYSQL_PATH))
	? MYSQL_PATH
	: 'mysql';

// 4) Montagem segura do comando (sem redirecionamento '<')
$parts = [
	$mysqlPath,
	'--host=' . escapeshellarg($dbhost),
	'--user=' . escapeshellarg($dbuser),
	'--password=' . escapeshellarg($dbpass),
	'--default-character-set=' . escapeshellarg($dbcharset),
	escapeshellarg($dbname),
];
$cmd = implode(' ', $parts);

// 5) Abre processo e envia o .sql via STDIN
$descriptorspec = [
	0 => ['pipe', 'r'], // STDIN
	1 => ['pipe', 'w'], // STDOUT
	2 => ['pipe', 'w'], // STDERR
];

$proc = proc_open($cmd, $descriptorspec, $pipes, null, null);

// Falha ao iniciar o processo
if (!is_resource($proc)) {
	echo json_encode(['status' => 'error', 'message' => 'Não foi possível iniciar o cliente mysql.']);
	exit;
}

$fh = fopen($uploadedFile, 'rb');
if ($fh === false) {
	// Garante fechar pipes/processo
	fclose($pipes[0]); fclose($pipes[1]); fclose($pipes[2]);
	proc_close($proc);
	echo json_encode(['status' => 'error', 'message' => 'Falha ao ler o arquivo enviado.']);
	exit;
}

// Copia o conteúdo do .sql para o STDIN do mysql
stream_copy_to_stream($fh, $pipes[0]);
fclose($fh);
fclose($pipes[0]);

// Captura saídas
$stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
$stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);

$exitCode = proc_close($proc);

// 6) Trata retorno
if ($exitCode !== 0) {
	// Opcional: exponha só um resumo do erro
	$msg = 'Erro ao restaurar o backup.';
	if ($stderr) {
		// Sanitiza mensagem longa
		$trimmed = mb_substr(trim($stderr), 0, 800);
		$msg .= ' Detalhes: ' . $trimmed;
	}
	echo json_encode(['status' => 'error', 'message' => $msg]);
	exit;
}

// 7) Log de restauração
$logDir = __DIR__ . '/../../../restore';
if (!is_dir($logDir)) {
	@mkdir($logDir, 0755, true);
}
$logFile	= $logDir . '/restore_log.txt';
$logEntry = date('Y-m-d H:i:s') . ' - ' . $origName . " restaurado\n";
@file_put_contents($logFile, $logEntry, FILE_APPEND);

// 8) Sucesso
echo json_encode(['status' => 'success', 'message' => 'Backup restaurado com sucesso.']);
exit;
?>