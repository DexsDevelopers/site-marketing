<?php
require_once 'includes/db_connect.php';
header('Content-Type: application/json');

$msgs = fetchData($pdo, "SELECT id, ordem, conteudo FROM marketing_mensagens WHERE campanha_id = 1 ORDER BY ordem");
$membros = fetchData($pdo, "SELECT status, COUNT(*) as c FROM marketing_membros GROUP BY status");

echo json_encode([
    'mensagens' => $msgs,
    'membros_status' => $membros
]);