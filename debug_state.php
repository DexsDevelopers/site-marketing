<?php
require 'includes/db_connect.php';

$campanhas = fetchData($pdo, "SELECT * FROM marketing_campanhas");
$mensagens = fetchData($pdo, "SELECT id, campanha_id, ordem, tipo, conteudo, ativo FROM marketing_mensagens");
$membros = fetchData($pdo, "SELECT id, status, ultimo_passo_id FROM marketing_membros LIMIT 5");

echo json_encode([
    'campanhas' => $campanhas,
    'mensagens' => $mensagens,
    'membros_sample' => $membros
], JSON_PRETTY_PRINT);
