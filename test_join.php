<?php
require_once 'includes/db_connect.php';
header('Content-Type: text/plain');

$sql = "
    SELECT m.id as member_id, m.telefone, m.ultimo_passo_id, msg.id as msg_id, msg.ordem, msg.campanha_id
    FROM marketing_membros m
    JOIN marketing_mensagens msg ON (m.ultimo_passo_id + 1) = msg.ordem AND msg.campanha_id = 1
    WHERE m.status = 'em_progresso' 
    AND m.data_proximo_envio <= NOW()
    LIMIT 5
";

$results = fetchData($pdo, $sql);
echo "JOIN RESULTS:\n";
print_r($results);

if (empty($results)) {
    echo "\nDebugging Join failure:\n";
    $m = fetchOne($pdo, "SELECT * FROM marketing_membros WHERE status = 'em_progresso' LIMIT 1");
    echo "Member Sample: " . json_encode($m) . "\n";

    $targetOrdem = $m['ultimo_passo_id'] + 1;
    echo "Looking for msg with ordem = $targetOrdem and campanha_id = 1\n";

    $msg = fetchOne($pdo, "SELECT * FROM marketing_mensagens WHERE ordem = ? AND campanha_id = 1", [$targetOrdem]);
    echo "Message Found: " . json_encode($msg) . "\n";
}