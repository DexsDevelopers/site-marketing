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
            $enviosHoje = fetchOne($pdo, "SELECT COUNT(*) as c FROM bot_automation_logs WHERE tipo_automacao = 'marketing' AND DATE(criado_em) = CURDATE()")['c'] ?? 0;

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
            // Config do bot de marketing (Porta 3002)
            $token = 'lucastav8012';
            $baseUrl = 'https://cyan-spoonbill-539092.hostingersite.com';

            $ch = curl_init($baseUrl . '/status');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['x-api-token: ' . $token],
                CURLOPT_TIMEOUT => 3,
                CURLOPT_SSL_VERIFYPEER => false
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
            $token = 'lucastav8012';
            $baseUrl = 'https://cyan-spoonbill-539092.hostingersite.com';

            $ch = curl_init($baseUrl . '/qr');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['x-api-token: ' . $token],
                CURLOPT_TIMEOUT => 3,
                CURLOPT_SSL_VERIFYPEER => false
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
            $logs = fetchData($pdo, "SELECT criado_em, numero_origem, resposta_enviada FROM bot_automation_logs WHERE tipo_automacao = 'marketing' ORDER BY criado_em DESC LIMIT 10");
            $response = ['success' => true, 'data' => $logs];
            break;
    }
}
catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
exit;