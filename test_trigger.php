<?php
/**
 * Script de teste simples para verificar o endpoint trigger_disparos
 */

// Habilitar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TESTE DE TRIGGER DE DISPAROS ===\n\n";

// Iniciar sessão para simular login
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_username'] = 'test';
$_SESSION['login_time'] = time();

echo "1. Sessão iniciada\n";
echo "   - admin_logged_in: " . ($_SESSION['admin_logged_in'] ? 'true' : 'false') . "\n";
echo "   - admin_username: " . $_SESSION['admin_username'] . "\n\n";

// Fazer requisição para o endpoint
$url = 'http://localhost/api_marketing_ajax.php?action=trigger_disparos';
echo "2. Fazendo requisição para: $url\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

echo "3. Resposta recebida:\n";
echo "   - HTTP Code: $httpCode\n";
echo "   - Headers:\n";
echo "     " . str_replace("\n", "\n     ", trim($headers)) . "\n\n";
echo "   - Body:\n";
echo "     " . $body . "\n\n";

// Tentar fazer parse do JSON
try {
    $data = json_decode($body, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "4. JSON parseado com sucesso:\n";
        echo "   " . print_r($data, true) . "\n";
    }
    else {
        echo "4. ERRO ao fazer parse do JSON: " . json_last_error_msg() . "\n";
    }
}
catch (Exception $e) {
    echo "4. EXCEÇÃO ao fazer parse do JSON: " . $e->getMessage() . "\n";
}