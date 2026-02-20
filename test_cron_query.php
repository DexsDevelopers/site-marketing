<?php
require 'includes/db_connect.php';

$activeC = fetchOne($pdo, "SELECT * FROM marketing_campanhas WHERE ativo = 1 ORDER BY id ASC LIMIT 1");
$activeCId = $activeC['id'];

echo "Active C ID: $activeCId\n";

$lead_ultimo_passo = 0;
$msg = fetchOne($pdo, "
    SELECT conteudo, tipo, ordem, midia_url, tipo_midia 
    FROM marketing_mensagens 
    WHERE campanha_id = ? AND ordem > ? AND ativo = 1
    ORDER BY ordem ASC LIMIT 1
", [$activeCId, $lead_ultimo_passo]);

print_r(['msg' => $msg]);
