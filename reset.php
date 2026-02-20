<?php
require_once 'includes/config.php';
require_once 'includes/auth_helper.php';
requireLogin();

$botUrl = 'http://localhost:3002'; // Ou var de BD
if (function_exists('getDynamicConfig')) {
    $botUrl = getDynamicConfig('WHATSAPP_API_URL', 'http://localhost:3002');
}
$botUrl = rtrim($botUrl, '/');

$ch = curl_init("$botUrl/instance/reset/admin_session");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_exec($ch);
curl_close($ch);

header("Location: admin_dashboard.php?msg=" . urlencode("Conexão resetada com sucesso!"));
exit;
?>
