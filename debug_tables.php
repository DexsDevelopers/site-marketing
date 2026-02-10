<?php
require_once 'includes/db_connect.php';
header('Content-Type: application/json');

try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $counts = [];
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        $counts[$table] = $count;
    }
    echo json_encode(['success' => true, 'tables' => $counts]);
}
catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}