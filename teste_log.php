<?php
// Teste de criação de log
$logFile = __DIR__ . '/horarios_debug.log';

echo "<h1>Teste de Log</h1>";
echo "<p>Tentando criar log em: <strong>$logFile</strong></p>";

// Tenta escrever
$resultado = file_put_contents($logFile, "Teste de escrita: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

if ($resultado !== false) {
    echo "<p style='color:green;'>✅ SUCESSO! Arquivo criado/atualizado.</p>";
    echo "<p>Conteúdo do arquivo:</p>";
    echo "<pre>" . file_get_contents($logFile) . "</pre>";
} else {
    echo "<p style='color:red;'>❌ ERRO! Não conseguiu criar o arquivo.</p>";
    echo "<p>Verifique as permissões da pasta.</p>";
}

// Mostra o caminho completo
echo "<p>__DIR__ = " . __DIR__ . "</p>";
?>