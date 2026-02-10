<?php
require_once 'includes/db_connect.php';
header('Content-Type: text/plain');

try {
    echo "INICIANDO CORREÇÃO DE CAMPANHAS...\n";

    // 1. Mover mensagens da campanha 2 para campanha 1
    $pdo->exec("UPDATE marketing_mensagens SET campanha_id = 1 WHERE campanha_id = 2");
    echo "- Mensagens movidas para Campanha 1.\n";

    // 2. Garantir que Campanha 1 está ativa
    $pdo->exec("UPDATE marketing_campanhas SET ativo = 1 WHERE id = 1");
    echo "- Campanha 1 Ativada.\n";

    // 3. Garantir que Membros estão na Campanha 1
    $pdo->exec("UPDATE marketing_membros SET campanha_atual_id = 1");
    echo "- Membros vinculados à Campanha 1.\n";

    // 4. Resetar data_proximo_envio para quem está em_progresso para disparar AGORA 
    // (Apenas para teste, já que o usuário clicou em Executar Agora)
    $pdo->exec("UPDATE marketing_membros SET data_proximo_envio = NOW() WHERE status = 'em_progresso'");
    echo "- Timer de envios resetado para AGORA.\n";

    echo "\nCONCLUÍDO COM SUCESSO!\n";
}
catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}