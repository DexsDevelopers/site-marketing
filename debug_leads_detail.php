<?php
require_once 'includes/db_connect.php';
header('Content-Type: application/json');

$leads = fetchData($pdo, "SELECT id, telefone, ultimo_passo_id, data_proximo_envio, status FROM marketing_membros WHERE status = 'em_progresso' LIMIT 20");
$now = fetchOne($pdo, "SELECT NOW() as n")['n'];

echo json_encode([
    'db_now' => $now,
    'php_now' => date('Y-m-d H:i:s'),
    'leads' => $leads
]);