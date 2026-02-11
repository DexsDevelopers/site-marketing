<?php
echo "<h1>Testando Conexão com o Bot (Localhost:3002)</h1>";

$ch = curl_init("http://127.0.0.1:3002/instance/list");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($response) {
    echo "<p>Sucesso! Código HTTP: $httpCode</p>";
    echo "<pre>$response</pre>";
}
else {
    echo "<p>Falha na conexão.</p>";
    echo "<p>Erro Curl: $err</p>";
    echo "<p>Código HTTP: $httpCode</p>";

    echo "<h2>Tentando via Localhost (sem 127.0.0.1)</h2>";
    $ch = curl_init("http://localhost:3002/instance/list");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    echo "<pre>$response</pre>";
    curl_close($ch);
}
?>