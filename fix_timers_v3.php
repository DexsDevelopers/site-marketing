<?php
require_once 'includes/db_connect.php';
header('Content-Type: text/plain');

try {
    echo "SINCRONIZANDO TIMERS COM O BANCO (PROGRESIVO)...\n";

    // Obter a hora atual do banco de dados (que está em UTC/Futuro)
    $dbNow = $pdo->query("SELECT NOW()")->fetchColumn();

    // Resetar timers para a hora atual do banco para quem está em_progresso
    // Isso garante que o NOW() do MySQL na query de cron_process sempre os encontre
    $sql = "UPDATE marketing_membros 
            SET data_proximo_envio = '$dbNow' 
            WHERE status = 'em_progresso'";

    $affected = $pdo->exec($sql);
    echo "- Timer de $affected membros sincronizados com $dbNow.\n";

    echo "CONCLUÍDO! O funil deve começar a disparar nos logs do bot em instantes.\n";
}
catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}