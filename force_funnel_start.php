<?php
require_once 'includes/db_connect.php';
header('Content-Type: text/plain');

try {
    echo "CORREÇÃO DEFINITIVA DE DATA_PROXIMO_ENVIO\n";

    // Forçar data_proximo_envio para um valor bem antigo (Ontem)
    // Isso garante que QUALQUER timezone (UTC ou BRT) identifique como passado
    $ontem = date('Y-m-d H:i:s', strtotime('-1 day'));

    $sql = "UPDATE marketing_membros SET data_proximo_envio = '$ontem' WHERE status = 'em_progresso'";
    $affected = $pdo->exec($sql);

    echo "- $affected membros alterados para responderem ao funil imediatamente.\n";

    // Verificar se as mensagens existem para a campanha 1
    $msgs = fetchData($pdo, "SELECT id, ordem FROM marketing_mensagens WHERE campanha_id = 1");
    echo "- Mensagens encontradas para Campanha 1: " . count($msgs) . "\n";

    echo "\nCONCLUÍDO. Aguarde o próximo ciclo do bot (60s).\n";
}
catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}