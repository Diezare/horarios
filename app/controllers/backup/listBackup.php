<?php
// app/controllers/backup/listBackup.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$logFile = __DIR__ . '/../../../backup/backup_log.txt';

if (!file_exists($logFile)) {
	echo json_encode(['status' => 'success', 'data' => []]);
	exit;
}

$lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$entries = [];
foreach ($lines as $line) {
	// Formato: "YYYY-MM-DD HH:ii:ss - backup_YYYYmmdd_HHMMSS.sql"
	$parts = explode(" - ", $line);
	if (count($parts) === 2) {
		$entries[] = [
			'data_backup' => $parts[0],
			'arquivo'	 => $parts[1]
		];
	}
}
// Reverte para que os backups mais recentes fiquem primeiro
$entries = array_reverse($entries);

echo json_encode(['status' => 'success', 'data' => $entries]);
exit;
