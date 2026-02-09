<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/auth_helper.php';
requireLogin();

header('Content-Type: application/json');

try {
    $diagnostico = [];

    // 1. Verificar leads em progresso
    $emProgresso = fetchData($pdo, "
        SELECT id, telefone, status, ultimo_passo_id, data_proximo_envio, 
               TIMESTAMPDIFF(MINUTE, data_proximo_envio, NOW()) as minutos_atrasado
        FROM marketing_membros 
        WHERE status = 'em_progresso' 
        ORDER BY data_proximo_envio ASC 
        LIMIT 10
    ");

    $diagnostico['leads_em_progresso'] = [
        'total' => count($emProgresso),
        'detalhes' => $emProgresso
    ];

    // 2. Verificar tarefas pendentes (prontas para envio)
    $tarefasPendentes = fetchData($pdo, "
        SELECT m.id, m.telefone, m.ultimo_passo_id, m.data_proximo_envio,
               msg.ordem, msg.conteudo, msg.tipo
        FROM marketing_membros m
        JOIN marketing_mensagens msg ON (m.ultimo_passo_id + 1) = msg.ordem
        WHERE m.status = 'em_progresso' 
        AND m.data_proximo_envio <= NOW()
        ORDER BY m.data_proximo_envio ASC
        LIMIT 10
    ");

    $diagnostico['tarefas_prontas_para_envio'] = [
        'total' => count($tarefasPendentes),
        'detalhes' => $tarefasPendentes
    ];

    // 3. Verificar campanha ativa
    $campanha = fetchOne($pdo, "SELECT * FROM marketing_campanhas WHERE id = 1");
    $diagnostico['campanha'] = $campanha;

    // 4. Verificar mensagens do funil
    $mensagens = fetchData($pdo, "SELECT * FROM marketing_mensagens WHERE campanha_id = 1 ORDER BY ordem ASC");
    $diagnostico['mensagens_funil'] = $mensagens;

    // 5. Verificar últimos logs de envio
    $autoMarketing = fetchOne($pdo, "SELECT id FROM bot_automations WHERE nome = 'Campanha Marketing' LIMIT 1");
    $autoMarketingId = $autoMarketing ? $autoMarketing['id'] : 0;

    $ultimosLogs = fetchData($pdo, "
        SELECT criado_em, numero_origem, LEFT(mensagem_recebida, 100) as status_envio
        FROM bot_automation_logs 
        WHERE automation_id = ?
        ORDER BY criado_em DESC 
        LIMIT 5
    ", [$autoMarketingId]);

    $diagnostico['ultimos_logs'] = $ultimosLogs;

    // 6. Hora atual do servidor
    $diagnostico['hora_servidor'] = date('Y-m-d H:i:s');

    echo json_encode($diagnostico, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

}
catch (Exception $e) {
    echo json_encode([
        'erro' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}