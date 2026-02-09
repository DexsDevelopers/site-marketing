<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/auth_helper.php';

requireLogin();

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

try {
    if ($action === 'get_active_leads') {
        // Buscar leads em progresso ordenados por próximo envio
        $leads = fetchData($pdo, "
            SELECT 
                id,
                telefone,
                status,
                ultimo_passo_id,
                data_proximo_envio,
                grupo_origem_jid,
                data_entrada_fluxo
            FROM marketing_membros 
            WHERE status = 'em_progresso'
            ORDER BY data_proximo_envio ASC
            LIMIT 50
        ");

        echo json_encode([
            'success' => true,
            'leads' => $leads,
            'count' => count($leads)
        ]);
    }
    elseif ($action === 'get_recent_sends') {
        // Buscar últimos envios (logs)
        $autoMarketing = fetchOne($pdo, "SELECT id FROM bot_automations WHERE nome = 'Campanha Marketing' LIMIT 1");
        $autoMarketingId = $autoMarketing ? $autoMarketing['id'] : 0;

        $logs = fetchData($pdo, "
            SELECT 
                criado_em,
                numero_origem,
                LEFT(resposta_enviada, 100) as preview,
                mensagem_recebida
            FROM bot_automation_logs 
            WHERE automation_id = ?
            ORDER BY criado_em DESC 
            LIMIT 20
        ", [$autoMarketingId]);

        echo json_encode([
            'success' => true,
            'logs' => $logs,
            'count' => count($logs)
        ]);
    }
    else {
        echo json_encode([
            'success' => false,
            'message' => 'Ação não reconhecida'
        ]);
    }
}
catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}