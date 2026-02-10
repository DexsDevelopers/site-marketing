<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
header('Content-Type: text/plain');

try {
    // Forçar Timezone para -03:00 (igual ao api_marketing.php)
    $pdo->exec("SET time_zone = '-03:00'");
    echo "Timezone definido para -03:00 no MySQL.\n";

    $dbNow = $pdo->query("SELECT NOW()")->fetchColumn();
    echo "Database NOW (-03:00): $dbNow\n";

    // Resetar todos os timers de quem está 'em_progresso' para NOW()
    // Isso garante que o api_marketing.php (que também usa -03:00) consiga vê-los
    $sql = "UPDATE marketing_membros SET data_proximo_envio = '$dbNow' WHERE status = 'em_progresso'";
    $affected = $pdo->exec($sql);
    echo "Timer de $affected membros resetado para $dbNow.\n";

    echo "CONCLUÍDO!\n";
}
catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}