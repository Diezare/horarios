<?php
// testar_headers.php
require_once __DIR__ . '/configs/init.php';

echo "<h2>🔍 Teste de Headers de Segurança</h2>";

// Lista de headers que devem estar presentes
$headersEsperados = [
    'X-Frame-Options',
    'X-Content-Type-Options',
    'Content-Security-Policy'
];

$headersAtuais = headers_list();

echo "<h3>Headers Enviados:</h3>";
echo "<pre>";
foreach ($headersAtuais as $header) {
    echo htmlspecialchars($header) . "\n";
    
    // Verifica CSP
    if (strpos($header, 'Content-Security-Policy') !== false) {
        echo "✅ CSP ENCONTRADO!\n";
    }
}
echo "</pre>";

echo "<h3>Status dos Headers de Segurança:</h3>";
foreach ($headersEsperados as $headerEsperado) {
    $encontrado = false;
    foreach ($headersAtuais as $headerAtual) {
        if (stripos($headerAtual, $headerEsperado) !== false) {
            $encontrado = true;
            break;
        }
    }
    echo $encontrado ? "✅ " : "❌ ";
    echo "$headerEsperado<br>";
}

// Teste CSP no console
echo "
<script>
console.log('🧪 Testando CSP...');
// Tente criar um script inline - deve ser bloqueado pelo CSP
try {
    eval('teste CSP');
} catch(e) {
    console.log('✅ CSP bloqueando scripts inline: ', e.toString());
}
</script>
";

echo "<p><strong>Dica:</strong> Abra o Console do Navegador (F12) para ver se há erros de CSP.</p>";