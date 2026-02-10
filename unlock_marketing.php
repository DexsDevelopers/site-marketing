<?php
require_once 'includes/db_connect.php';
header('Content-Type: text/plain');

try {
    // 1. Aumentar limite diário para 500
    $pdo->exec("UPDATE marketing_campanhas SET membros_por_dia_grupo = 500 WHERE id = 1");
    echo "Limite diário aumentado para 500.\n";

    // 2. Garantir que as mensagens estão na campanha 1 (reforço)
    $pdo->exec("UPDATE marketing_mensagens SET campanha_id = 1 WHERE campanha_id != 1");
    echo "Mensagens unificadas na Campanha 1.\n";

    // 3. Resetar timers para disparar AGORA
    $pdo->exec("UPDATE marketing_membros SET data_proximo_envio = NOW() WHERE status = 'em_progresso'");
    echo "Timers resetados para NOW().\n";

    echo "\nSISTEMA DESTRANCADO! Verifique os logs do bot em 1 minuto.\n";
}
catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}