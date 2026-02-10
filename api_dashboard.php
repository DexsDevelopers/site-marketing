<?php
/**
 * API Backend para o Dashboard Moderno
 */
ob_start();

require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/whatsapp_helper.php';
require_once 'includes/auth_helper.php';

// Verificar autenticação
requireLogin();

if (ob_get_length())
    ob_clean();
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$response = ['success' => false, 'message' => 'Ação não reconhecida'];

try {
    switch ($action) {
        case 'get_stats':
            // Estatísticas consolidadas
            $totalLeads = fetchOne($pdo, "SELECT COUNT(*) as c FROM marketing_membros")['c'] ?? 0;
            $emProgresso = fetchOne($pdo, "SELECT COUNT(*) as c FROM marketing_membros WHERE status = 'em_progresso'")['c'] ?? 0;
            $concluidos = fetchOne($pdo, "SELECT COUNT(*) as c FROM marketing_membros WHERE status = 'concluido'")['c'] ?? 0;

            // Buscar disparos de marketing pelo automation_id
            $autoMarketing = fetchOne($pdo, "SELECT id FROM bot_automations WHERE nome = 'Campanha Marketing' LIMIT 1");
            $autoMarketingId = $autoMarketing ? $autoMarketing['id'] : 0;
            $enviosHoje = fetchOne($pdo, "SELECT COUNT(*) as c FROM bot_automation_logs WHERE automation_id = ? AND DATE(criado_em) = CURDATE()", [$autoMarketingId])['c'] ?? 0;

            // Próximo envio
            $proxEnvio = fetchOne($pdo, "SELECT data_proximo_envio FROM marketing_membros WHERE status = 'em_progresso' AND data_proximo_envio IS NOT NULL ORDER BY data_proximo_envio ASC LIMIT 1");

            $response = [
                'success' => true,
                'data' => [
                    'leads_total' => $totalLeads,
                    'leads_ativo' => $emProgresso,
                    'leads_concluido' => $concluidos,
                    'envios_hoje' => $enviosHoje,
                    'proximo_envio' => $proxEnvio ? $proxEnvio['data_proximo_envio'] : 'Nenhum agendado'
                ]
            ];
            break;

        case 'get_bot_status':
            $apiConfig = whatsappApiConfig();
            $token = $apiConfig['token'];
            $baseUrl = $apiConfig['base_url'];

            $ch = curl_init($baseUrl . '/status');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['x-api-token: ' . $token, 'ngrok-skip-browser-warning: true'],
                CURLOPT_TIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_FOLLOWLOCATION => true
            ]);
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $statusData = ['online' => false, 'ready' => false];
            if ($httpCode === 200 && $result) {
                $decoded = json_decode($result, true);
                $statusData = [
                    'online' => true,
                    'ready' => $decoded['ready'] ?? false,
                    'uptime' => $decoded['uptimeFormatted'] ?? 'N/A',
                    'reconnects' => $decoded['reconnectAttempts'] ?? 0
                ];
            }

            $response = ['success' => true, 'data' => $statusData];
            break;

        case 'get_qr':
            $apiConfig = whatsappApiConfig();
            $token = $apiConfig['token'];
            $baseUrl = $apiConfig['base_url'];

            $ch = curl_init($baseUrl . '/qr');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['x-api-token: ' . $token, 'ngrok-skip-browser-warning: true'],
                CURLOPT_TIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_FOLLOWLOCATION => true
            ]);
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $result) {
                $decoded = json_decode($result, true);
                $response = ['success' => true, 'qr' => $decoded['qr'] ?? null];
            }
            else {
                $response = ['success' => false, 'message' => 'Bot offline ou QR não disponível'];
            }
            break;

        case 'get_recent_activity':
            // Buscar atividades de marketing pelo automation_id
            $autoMarketing = fetchOne($pdo, "SELECT id FROM bot_automations WHERE nome = 'Campanha Marketing' LIMIT 1");
            $autoMarketingId = $autoMarketing ? $autoMarketing['id'] : 0;
            $logs = fetchData($pdo, "SELECT criado_em, numero_origem, resposta_enviada FROM bot_automation_logs WHERE automation_id = ? ORDER BY criado_em DESC LIMIT 10", [$autoMarketingId]);
            $response = ['success' => true, 'data' => $logs];
            break;
    }
}
catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
exit;