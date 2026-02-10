<?php
require_once 'includes/db_connect.php';
header('Content-Type: text/plain');

echo "DATABASE CHECK\n";
echo "Host: $host, DB: $db, User: $user\n\n";

try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found: " . implode(", ", $tables) . "\n\n";

    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "Table: $table - Count: $count\n";
        if ($count > 0 && strpos($table, 'marketing') !== false) {
            $data = $pdo->query("SELECT * FROM $table LIMIT 1")->fetch();
            echo "Sample: " . json_encode($data) . "\n";
        }
    }
}
catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}