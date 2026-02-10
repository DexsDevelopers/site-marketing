<?php
require_once 'includes/db_connect.php';
header('Content-Type: text/plain');

try {
    // 1. Aumentar limite para 10000 para ignorar a trava por hoje
    $pdo->exec("UPDATE marketing_campanhas SET membros_por_dia_grupo = 10000 WHERE id = 1");
    echo "Limite diário aumentado para 10000.\n";

    // 2. Resetar timers de quem está 'em_progresso' (garantia)
    $pdo->exec("UPDATE marketing_membros SET data_proximo_envio = NOW() WHERE status = 'em_progresso'");

    echo "\nSISTEMA DESTRANCADO PARA 10.000 ENVIOS. Verifique os logs do bot agora.\n";
}
catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}