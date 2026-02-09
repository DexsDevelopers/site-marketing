<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/auth_helper.php';
requireLogin();

header('Content-Type: application/json');

// Verificar configuração de delays
$mensagens = fetchData($pdo, "SELECT ordem, delay_apos_anterior_minutos FROM marketing_mensagens WHERE campanha_id = 1 ORDER BY ordem ASC");

echo json_encode([
    'success' => true,
    'mensagens' => $mensagens
], JSON_PRETTY_PRINT);