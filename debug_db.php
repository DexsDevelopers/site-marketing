<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== DEBUG DO BANCO DE DADOS ===\n\n";

// 1. Verificar estrutura da tabela bot_automation_logs
echo "1. ESTRUTURA DA TABELA bot_automation_logs:\n";
echo str_repeat("-", 60) . "\n";
try {
    $stmt = $pdo->query("DESCRIBE bot_automation_logs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo sprintf("%-30s %-20s %s\n", $col['Field'], $col['Type'], $col['Null']);
    }
}
catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}

echo "\n2. VERIFICAR TABELA bot_automations:\n";
echo str_repeat("-", 60) . "\n";
try {
    $automations = $pdo->query("SELECT id, nome FROM bot_automations")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($automations)) {
        echo "⚠️ TABELA VAZIA - Nenhuma automação cadastrada!\n";
    }
    else {
        foreach ($automations as $auto) {
            echo "ID: {$auto['id']} | Nome: {$auto['nome']}\n";
        }
    }
}
catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}

echo "\n3. CONTAR REGISTROS EM bot_automation_logs:\n";
echo str_repeat("-", 60) . "\n";
try {
    $total = $pdo->query("SELECT COUNT(*) as c FROM bot_automation_logs")->fetch(PDO::FETCH_ASSOC);
    echo "Total de registros: " . $total['c'] . "\n";

    if ($total['c'] > 0) {
        echo "\nÚltimos 5 registros:\n";
        $logs = $pdo->query("SELECT * FROM bot_automation_logs ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($logs as $log) {
            echo "\nID: {$log['id']}\n";
            foreach ($log as $key => $value) {
                echo "  $key: " . (is_null($value) ? 'NULL' : substr($value, 0, 100)) . "\n";
            }
        }
    }
}
catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}

echo "\n4. ESTATÍSTICAS DE MARKETING:\n";
echo str_repeat("-", 60) . "\n";
try {
    $stats = [
        'Total Leads' => $pdo->query("SELECT COUNT(*) as c FROM marketing_membros")->fetch()['c'],
        'Em Progresso' => $pdo->query("SELECT COUNT(*) as c FROM marketing_membros WHERE status = 'em_progresso'")->fetch()['c'],
        'Concluídos' => $pdo->query("SELECT COUNT(*) as c FROM marketing_membros WHERE status = 'concluido'")->fetch()['c'],
        'Novos' => $pdo->query("SELECT COUNT(*) as c FROM marketing_membros WHERE status = 'novo'")->fetch()['c'],
    ];

    foreach ($stats as $label => $value) {
        echo sprintf("%-20s: %d\n", $label, $value);
    }
}
catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}

echo "\n5. VERIFICAR DISPAROS DE HOJE:\n";
echo str_repeat("-", 60) . "\n";
try {
    // Tentar diferentes queries para ver qual funciona
    echo "Tentativa 1 - Contar todos os logs de hoje:\n";
    $result1 = $pdo->query("SELECT COUNT(*) as c FROM bot_automation_logs WHERE DATE(created_at) = CURDATE()")->fetch();
    echo "  Total: " . ($result1['c'] ?? 0) . "\n";

    echo "\nTentativa 2 - Buscar automation 'Campanha Marketing':\n";
    $auto = $pdo->query("SELECT id FROM bot_automations WHERE nome = 'Campanha Marketing' LIMIT 1")->fetch();
    if ($auto) {
        echo "  Automation ID encontrado: " . $auto['id'] . "\n";
        $result2 = $pdo->prepare("SELECT COUNT(*) as c FROM bot_automation_logs WHERE automation_id = ? AND DATE(created_at) = CURDATE()");
        $result2->execute([$auto['id']]);
        echo "  Disparos de hoje: " . $result2->fetch()['c'] . "\n";
    }
    else {
        echo "  ⚠️ Automation 'Campanha Marketing' NÃO ENCONTRADA!\n";
    }

}
catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Debug concluído!\n";