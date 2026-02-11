<?php
require_once 'includes/db_connect.php';
header('Content-Type: application/json');

$res = [
    'users' => fetchData($pdo, "SELECT id, username, role FROM users"),
    'instancias' => fetchData($pdo, "SELECT * FROM wa_instancias")
];
echo json_encode($res, JSON_PRETTY_PRINT);
?>