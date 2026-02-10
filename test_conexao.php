<?php
// Arquivo de teste isolado - SEM includes do sistema
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Teste de Conexão DB</h1>";

$host = 'localhost';
$db = 'u853242961_marketings';
$user = 'u853242961_lucas';
$pass = 'Lucastav8012@'; // Senha confirmada por voce

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h2 style='color:green'>✅ CONEXÃO COM SUCESSO!</h2>";
    echo "<p>Banco de dados acessível.</p>";

    // Teste de consulta simples
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Tabelas encontradas: " . implode(", ", $tables) . "</p>";

}
catch (PDOException $e) {
    echo "<h2 style='color:red'>❌ ERRO DE CONEXÃO</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}