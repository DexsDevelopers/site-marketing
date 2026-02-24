<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';

$stats = [];
$stats['campanhas'] = fetchData($pdo, "SELECT id, nome, ativo, membros_por_dia_grupo FROM marketing_campanhas");
$stats['membros_summary'] = fetchData($pdo, "SELECT status, COUNT(*) as c FROM marketing_membros GROUP BY status");
$stats['hoje_fluxo'] = fetchData($pdo, "SELECT COUNT(*) as c FROM marketing_membros WHERE DATE(data_entrada_fluxo) = CURDATE()");
$stats['ready_tasks'] = fetchData($pdo, "SELECT COUNT(*) as c FROM marketing_membros WHERE status = 'em_progresso' AND (data_proximo_envio <= NOW() OR data_proximo_envio IS NULL)");

header('Content-Type: application/json');
echo json_encode($stats, JSON_PRETTY_PRINT);
