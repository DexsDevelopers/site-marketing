<?php
// Teste de conexão com o banco ANTIGO (Rastreio)
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Teste Conexão Banco ANTIGO (Rastreio)</h1>";

$host = 'localhost';
$db = 'u853242961_rastreio'; // Banco ORIGINAL
$user = 'u853242961_johan71'; // Usuário ORIGINAL
$pass = 'Lucastav8012@'; // Senha (igual)

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    echo "<h2 style='color:green'>✅ CONEXÃO COM SUCESSO NO ANTIGO!</h2>";

    // Verificar se tem leads
    $stmt = $pdo->query("SELECT count(*) FROM marketing_membros");
    $leads = $stmt->fetchColumn();
    echo "<p>Leads encontrados: <strong>$leads</strong></p>";

    // Verificar funil
    $stmt = $pdo->query("SELECT count(*) FROM marketing_mensagens");
    $msgs = $stmt->fetchColumn();
    echo "<p>Mensagens no funil: <strong>$msgs</strong></p>";

}
catch (PDOException $e) {
    echo "<h2 style='color:red'>❌ ERRO NO ANTIGO</h2>";
    echo $e->getMessage();
}