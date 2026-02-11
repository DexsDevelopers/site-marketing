<?php
require_once 'includes/db_connect.php';
$stmt = $pdo->query("DESCRIBE wa_instancias");
echo json_encode($stmt->fetchAll(), JSON_PRETTY_PRINT);
?>