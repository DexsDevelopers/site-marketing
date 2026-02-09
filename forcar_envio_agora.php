<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/auth_helper.php';
requireLogin();

header('Content-Type: application/json');

try {
    // Forçar TODOS os leads em progresso para envio IMEDIATO
    $updated = executeQuery($pdo, "
        UPDATE marketing_membros 
        SET data_proximo_envio = DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
        WHERE status = 'em_progresso'
    ");

    // Contar quantos foram atualizados
    $count = fetchOne($pdo, "
        SELECT COUNT(*) as total 
        FROM marketing_membros 
        WHERE status = 'em_progresso' 
        AND data_proximo_envio <= NOW()
    ");

    echo json_encode([
        'success' => true,
        'message' => 'Envios forçados para AGORA!',
        'leads_atualizados' => $count['total'],
        'proxima_execucao' => 'O bot processará em até 60 segundos'
    ], JSON_PRETTY_PRINT);

}
catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'erro' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}