<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/auth_helper.php';
requireLogin();

header('Content-Type: application/json');

try {
    // Testar a query exata que está sendo usada
    $result = [];

    // 1. Estatísticas básicas
    $result['marketing_membros'] = [
        'total' => $pdo->query("SELECT COUNT(*) as c FROM marketing_membros")->fetch()['c'] ?? 0,
        'em_progresso' => $pdo->query("SELECT COUNT(*) as c FROM marketing_membros WHERE status = 'em_progresso'")->fetch()['c'] ?? 0,
        'concluido' => $pdo->query("SELECT COUNT(*) as c FROM marketing_membros WHERE status = 'concluido'")->fetch()['c'] ?? 0,
        'novo' => $pdo->query("SELECT COUNT(*) as c FROM marketing_membros WHERE status = 'novo'")->fetch()['c'] ?? 0,
    ];

    // 2. Verificar se a tabela bot_automation_logs existe
    try {
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'bot_automation_logs'")->fetch();
        $result['tabela_existe'] = !empty($tableCheck);
    }
    catch (Exception $e) {
        $result['tabela_existe'] = false;
        $result['erro_tabela'] = $e->getMessage();
    }

    // 3. Se existir, verificar colunas
    if ($result['tabela_existe']) {
        $columns = $pdo->query("DESCRIBE bot_automation_logs")->fetchAll(PDO::FETCH_COLUMN);
        $result['colunas'] = $columns;

        // 4. Contar registros
        $result['total_logs'] = $pdo->query("SELECT COUNT(*) as c FROM bot_automation_logs")->fetch()['c'] ?? 0;

        // 5. Verificar se existe automation de marketing
        $autoCheck = $pdo->query("SELECT id, nome FROM bot_automations")->fetchAll(PDO::FETCH_ASSOC);
        $result['automations'] = $autoCheck;

        // 6. Tentar contar disparos de hoje (se a coluna created_at existir)
        if (in_array('created_at', $columns)) {
            $result['disparos_hoje_total'] = $pdo->query("SELECT COUNT(*) as c FROM bot_automation_logs WHERE DATE(created_at) = CURDATE()")->fetch()['c'] ?? 0;

            // Se houver automation de marketing
            $autoMarketing = null;
            foreach ($autoCheck as $auto) {
                if (stripos($auto['nome'], 'marketing') !== false || stripos($auto['nome'], 'campanha') !== false) {
                    $autoMarketing = $auto;
                    break;
                }
            }

            if ($autoMarketing) {
                $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM bot_automation_logs WHERE automation_id = ? AND DATE(created_at) = CURDATE()");
                $stmt->execute([$autoMarketing['id']]);
                $result['disparos_hoje_marketing'] = $stmt->fetch()['c'] ?? 0;
                $result['automation_usado'] = $autoMarketing;
            }
            else {
                $result['disparos_hoje_marketing'] = 0;
                $result['automation_usado'] = null;
                $result['aviso'] = 'Nenhuma automation com nome contendo "marketing" ou "campanha" foi encontrada';
            }
        }
        else {
            $result['erro_coluna'] = 'Coluna created_at não existe na tabela';
        }

        // 7. Mostrar últimos 3 logs
        if ($result['total_logs'] > 0) {
            $result['ultimos_logs'] = $pdo->query("SELECT * FROM bot_automation_logs ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

}
catch (Exception $e) {
    echo json_encode([
        'erro' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}