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

            // Buscar envios reais do marketing hoje (atualizações de status para o passo final ou qualquer passo)
            // Como não temos logs dedicados ainda, vamos contar quantos entraram hoje ou quantos foram atualizados hoje
            // Para ser preciso, depois vamos implementar log real. Por agora:
            $enviosHoje = fetchOne($pdo, "SELECT COUNT(*) as c FROM marketing_membros WHERE DATE(data_proximo_envio) = CURDATE() AND ultimo_passo_id > 0")['c'] ?? 0;

            $response = [
                'success' => true,
                'data' => [
                    'leads_total' => (int)$totalLeads,
                    'leads_ativo' => (int)$emProgresso,
                    'leads_concluido' => (int)$concluidos,
                    'envios_hoje' => (int)$enviosHoje
                ]
            ];
            break;

        case 'get_bot_status':
            $apiConfig = whatsappApiConfig();
            $baseUrl = $apiConfig['base_url'];

            $ch = curl_init($baseUrl . '/instance/list');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            $result = curl_exec($ch);
            curl_close($ch);

            $statusData = ['online' => false, 'instances' => []];
            if ($result) {
                $decoded = json_decode($result, true);
                if (isset($decoded['instances'])) {
                    $statusData['online'] = true;
                    $statusData['instances'] = $decoded['instances'];
                }
            }
            $response = ['success' => true, 'data' => $statusData];
            break;

        case 'get_qr':
            $apiConfig = whatsappApiConfig();
            $baseUrl = $apiConfig['base_url'];
            $sessionId = $_GET['session_id'] ?? 'admin_session'; // Default fallback but dynamic now

            $ch = curl_init($baseUrl . '/instance/qr/' . urlencode($sessionId));
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            $result = curl_exec($ch);
            curl_close($ch);

            if ($result) {
                $decoded = json_decode($result, true);
                if ($decoded) {
                    if (isset($decoded['status']) && $decoded['status'] === 'connected') {
                        $response = ['success' => true, 'qr' => null, 'ready' => true, 'message' => 'Bot já está conectado'];
                    }
                    elseif (isset($decoded['qr'])) {
                        $response = ['success' => true, 'qr' => $decoded['qr'], 'ready' => false];
                    }
                    else {
                        $response = ['success' => false, 'message' => 'QR não disponível ainda'];
                    }
                }
                else {
                    $response = ['success' => false, 'message' => 'Resposta inválida do bot'];
                }
            }
            else {
                $response = ['success' => false, 'message' => 'Bot offline ou inacessível'];
            }
            break;

        case 'generate_pairing':
            $apiConfig = whatsappApiConfig();
            $baseUrl = $apiConfig['base_url'];
            $phone = $_POST['phone'] ?? '';
            $sessionId = $_POST['session_id'] ?? 'admin_session';

            if (!$phone) {
                $response = ['success' => false, 'message' => 'Telefone não informado'];
                break;
            }

            $payload = json_encode(['sessionId' => $sessionId, 'phone' => $phone]);
            $ch = curl_init($baseUrl . '/instance/pairing-code');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 25,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            $result = curl_exec($ch);
            curl_close($ch);

            if ($result) {
                $decoded = json_decode($result, true);
                if ($decoded && isset($decoded['code'])) {
                    $response = ['success' => true, 'code' => $decoded['code']];
                }
                else {
                    $response = ['success' => false, 'message' => $decoded['message'] ?? 'Erro desconhecido ao gerar código'];
                }
            }
            else {
                $response = ['success' => false, 'message' => 'Falha de conexão com o bot'];
            }
            break;

        case 'remove_instance':
            $apiConfig = whatsappApiConfig();
            $baseUrl = $apiConfig['base_url'];
            $sessionId = $_POST['session_id'] ?? '';

            if (!$sessionId) {
                $response = ['success' => false, 'message' => 'Session ID não carregada'];
                break;
            }

            $ch = curl_init($baseUrl . '/instance/reset/' . urlencode($sessionId));
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            $result = curl_exec($ch);
            curl_close($ch);

            $response = ['success' => true, 'message' => 'Chip desconectado com sucesso'];
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