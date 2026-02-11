<?php
require_once 'includes/db_connect.php';

echo "<h1>Status de Usuários e Instâncias</h1>";

$users = fetchData($pdo, "SELECT id, username, saldo FROM users ORDER BY id DESC LIMIT 10");
echo "<h2>Últimos 10 Usuários</h2><table border='1'><tr><th>ID</th><th>User</th><th>Saldo</th></tr>";
foreach ($users as $u) {
    echo "<tr><td>{$u['id']}</td><td>{$u['username']}</td><td>{$u['saldo']}</td></tr>";
}
echo "</table>";

$insts = fetchData($pdo, "SELECT * FROM wa_instancias ORDER BY id DESC LIMIT 10");
echo "<h2>Últimas 10 Instâncias</h2><table border='1'><tr><th>ID</th><th>User ID</th><th>Session</th><th>Status</th><th>Last HB</th></tr>";
foreach ($insts as $i) {
    echo "<tr><td>{$i['id']}</td><td>{$i['user_id']}</td><td>{$i['session_id']}</td><td>{$i['status']}</td><td>{$i['last_heartbeat']}</td></tr>";
}
echo "</table>";
?>