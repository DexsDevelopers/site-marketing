<?php
require_once 'includes/db_connect.php';
header('Content-Type: text/plain');

$dbTime = $pdo->query("SELECT NOW()")->fetchColumn();
$phpTime = date('Y-m-d H:i:s');

echo "Database Time: $dbTime\n";
echo "PHP Time:      $phpTime\n";

$pending = $pdo->query("SELECT COUNT(*) FROM marketing_membros WHERE status = 'em_progresso' AND data_proximo_envio <= NOW()")->fetchColumn();
echo "Pending (DB NOW): $pending\n";

$pendingPHP = $pdo->query("SELECT COUNT(*) FROM marketing_membros WHERE status = 'em_progresso' AND data_proximo_envio <= '$phpTime'")->fetchColumn();
echo "Pending (PHP TIME): $pendingPHP\n";

$membros = $pdo->query("SELECT id, data_proximo_envio FROM marketing_membros WHERE status = 'em_progresso' LIMIT 5")->fetchAll();
echo "\nSample Members:\n";
print_r($membros);