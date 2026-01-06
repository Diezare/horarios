<?php
// app/controllers/backup/listRestore.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

$logFile = __DIR__ . '/../../../restore/restore_log.txt';

if (!file_exists($logFile)) {
	echo json_encode(['status' => 'success', 'data' => []]);
	exit;
}

$lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$entries = [];
foreach ($lines as $line) {
	// Formato esperado: "YYYY-MM-DD HH:ii:ss - nome_do_arquivo restaurado"
	$parts = explode(" - ", $line);
	if (count($parts) === 2) {
		$entries[] = [
			'data_restore' => $parts[0],
			'arquivo' => $parts[1]
		];
	}
}
$entries = array_reverse($entries);
echo json_encode(['status' => 'success', 'data' => $entries]);
exit;
?>