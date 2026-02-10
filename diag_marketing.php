<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';

echo "<h3>Diagnóstico de Marketing</h3>";

// Campanha
$campanha = fetchOne($pdo, "SELECT * FROM marketing_campanhas WHERE id = 1");
echo "Campanha 1 ativa: " . ($campanha['ativo'] ? 'SIM' : 'NÃO') . " (Limite: " . $campanha['membros_por_dia_grupo'] . ")<br>";

// Mensagens
$msgs = fetchData($pdo, "SELECT id, ordem, tipo FROM marketing_mensagens WHERE campanha_id = 1");
echo "Mensagens no Funil: " . count($msgs) . "<br>";
foreach ($msgs as $m)
    echo "- Passo {$m['ordem']} (ID: {$m['id']})<br>";

// Membros
$stats = fetchData($pdo, "SELECT status, COUNT(*) as c FROM marketing_membros GROUP BY status");
echo "<h4>Status dos Membros:</h4>";
foreach ($stats as $s)
    echo "- {$s['status']}: {$s['c']}<br>";

// Pendentes
$sqlTasks = "
    SELECT COUNT(*) as total
    FROM marketing_membros m
    JOIN marketing_mensagens msg ON (m.ultimo_passo_id + 1) = msg.ordem AND msg.campanha_id = 1
    WHERE m.status = 'em_progresso' 
    AND m.data_proximo_envio <= NOW()
";
$total = fetchOne($pdo, $sqlTasks)['total'];
echo "<h4>Mensagens Pendentes para Envio AGORA: $total</h4>";

// Se for 0, listar quem está em_progresso e por que não bate
if ($total == 0) {
    echo "<h4>Detalhamento de quem está 'em_progresso':</h4>";
    $membros = fetchData($pdo, "SELECT id, ultimo_passo_id, data_proximo_envio FROM marketing_membros WHERE status = 'em_progresso' LIMIT 10");
    foreach ($membros as $m) {
        $nextOrdem = $m['ultimo_passo_id'] + 1;
        $msgExists = fetchOne($pdo, "SELECT COUNT(*) as c FROM marketing_mensagens WHERE ordem = ? AND campanha_id = 1", [$nextOrdem])['c'];
        echo "- ID {$m['id']}: data_envio={$m['data_proximo_envio']}, próximo_passo={$nextOrdem}, msg_existe=" . ($msgExists ? 'SIM' : 'NÃO') . "<br>";
    }
}
?>