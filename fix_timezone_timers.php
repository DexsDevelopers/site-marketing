<?php
require_once 'includes/db_connect.php';
header('Content-Type: text/plain');

try {
    echo "CORRIGINDO TIMER DEVIDO A TIMEZONE...\n";

    // Subtrair 3 horas de quem está com timer no futuro (provável UTC)
    $sql = "UPDATE marketing_membros 
            SET data_proximo_envio = DATE_SUB(data_proximo_envio, INTERVAL 3 HOUR) 
            WHERE status = 'em_progresso' 
            AND data_proximo_envio > NOW()";

    $affected = $pdo->exec($sql);
    echo "- Timer de $affected membros corrigidos.\n";

    // Forçar data_proximo_envio para AGORA para quem ainda estiver no futuro (garantir disparo)
    $sql2 = "UPDATE marketing_membros SET data_proximo_envio = NOW() WHERE status = 'em_progresso'";
    $pdo->exec($sql2);
    echo "- Timer resetado para NOW() para todos em progresso.\n";

    echo "\nCONCLUÍDO!\n";
}
catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}