<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/auth_helper.php';
requireLogin();

header('Content-Type: application/json');

try {
    // Testar a query exata que o bot usa
    $sqlTasks = "
        SELECT m.id, m.telefone, m.ultimo_passo_id, msg.conteudo, msg.tipo, msg.ordem
        FROM marketing_membros m
        JOIN marketing_mensagens msg ON (m.ultimo_passo_id + 1) = msg.ordem AND msg.campanha_id = 1
        WHERE m.status = 'em_progresso' 
        AND m.data_proximo_envio <= NOW()
        ORDER BY m.data_proximo_envio ASC
        LIMIT 5
    ";

    $tasks = fetchData($pdo, $sqlTasks);

    // Verificar também sem o JOIN para debug
    $membros = fetchData($pdo, "
        SELECT id, telefone, ultimo_passo_id, data_proximo_envio, status
        FROM marketing_membros 
        WHERE status = 'em_progresso' 
        AND data_proximo_envio <= NOW()
        LIMIT 5
    ");

    $mensagens = fetchData($pdo, "
        SELECT ordem, tipo, LEFT(conteudo, 50) as preview, campanha_id
        FROM marketing_mensagens 
        WHERE campanha_id = 1
        ORDER BY ordem ASC
    ");

    echo json_encode([
        'tasks_com_join' => $tasks,
        'total_tasks' => count($tasks),
        'membros_sem_join' => $membros,
        'mensagens_disponiveis' => $mensagens,
        'hora_servidor' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

}
catch (Exception $e) {
    echo json_encode([
        'erro' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}