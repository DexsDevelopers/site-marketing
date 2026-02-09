<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/auth_helper.php';
requireLogin();

header('Content-Type: application/json');

try {
    // Buscar automation de marketing
    $autoMarketing = $pdo->query("SELECT id FROM bot_automations WHERE nome = 'Campanha Marketing' LIMIT 1")->fetch();
    $autoMarketingId = $autoMarketing ? $autoMarketing['id'] : 0;

    // Contar disparos de HOJE usando criado_em (coluna correta)
    $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM bot_automation_logs WHERE automation_id = ? AND DATE(criado_em) = CURDATE()");
    $stmt->execute([$autoMarketingId]);
    $disparosHoje = $stmt->fetch()['c'] ?? 0;

    // Contar total de logs de marketing
    $stmt2 = $pdo->prepare("SELECT COUNT(*) as c FROM bot_automation_logs WHERE automation_id = ?");
    $stmt2->execute([$autoMarketingId]);
    $totalMarketing = $stmt2->fetch()['c'] ?? 0;

    // Buscar últimos 5 disparos
    $stmt3 = $pdo->prepare("SELECT criado_em, numero_origem, LEFT(resposta_enviada, 50) as preview FROM bot_automation_logs WHERE automation_id = ? ORDER BY criado_em DESC LIMIT 5");
    $stmt3->execute([$autoMarketingId]);
    $ultimos = $stmt3->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'automation_id' => $autoMarketingId,
        'disparos_hoje' => $disparosHoje,
        'total_disparos_marketing' => $totalMarketing,
        'data_atual' => date('Y-m-d'),
        'ultimos_5_disparos' => $ultimos,
        'query_usada' => "SELECT COUNT(*) as c FROM bot_automation_logs WHERE automation_id = $autoMarketingId AND DATE(criado_em) = CURDATE()"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

}
catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'erro' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}