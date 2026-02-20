<?php
require 'includes/db_connect.php';

try {
    $sqlLeads = "
        SELECT m.id, m.telefone, m.ultimo_passo_id
        FROM marketing_membros m
        WHERE m.status = 'em_progresso' 
        AND m.data_proximo_envio <= NOW()
        ORDER BY m.data_proximo_envio ASC
        LIMIT 50
    ";
    $leads = fetchData($pdo, $sqlLeads);
    echo "Pendentes: " . count($leads) . "\n";
}
catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
