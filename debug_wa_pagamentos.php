<?php
require_once 'includes/db_connect.php';
header('Content-Type: application/json');
try {
    $schema = $pdo->query("DESCRIBE wa_pagamentos")->fetchAll();
    echo json_encode(['success' => true, 'schema' => $schema]);
}
catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}