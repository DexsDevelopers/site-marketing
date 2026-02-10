<?php
require_once 'includes/db_connect.php';
header('Content-Type: text/plain');
$msgs = fetchData($pdo, "SELECT id, campanha_id, ordem, conteudo FROM marketing_mensagens");
echo "MESSAGES:\n";
print_r($msgs);

$membros = fetchData($pdo, "SELECT DISTINCT campanha_atual_id FROM marketing_membros");
echo "\nMEMBERS CAMPAIGN IDs:\n";
print_r($membros);