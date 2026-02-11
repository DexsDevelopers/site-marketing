<?php
require_once 'includes/db_connect.php';

try {
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    echo "<h1>Colunas da Tabela 'users'</h1>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    // Verificar se a coluna role existe
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    if (!$stmt->fetch()) {
        echo "<p>Adicionando coluna 'role'...</p>";
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('admin', 'user') DEFAULT 'user'");
        echo "<p>Coluna 'role' adicionada!</p>";
    }
}
catch (Exception $e) {
    echo "<h1>Erro: " . $e->getMessage() . "</h1>";

    // Tentar criar a tabela se não existir
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
        echo "<p>Criando tabela users...</p>";
        $sql = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'user') DEFAULT 'user',
            saldo DECIMAL(10,2) DEFAULT 0.00,
            pix_chave VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_VALUE
        )";
        // Pequeno erro no SQL acima (CURRENT_VALUE -> CURRENT_TIMESTAMP)
        $sql = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'user') DEFAULT 'user',
            saldo DECIMAL(10,2) DEFAULT 0.00,
            pix_chave VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        try {
            $pdo->exec($sql);
            echo "<p>Tabela 'users' criada!</p>";
        }
        catch (Exception $e2) {
            echo "<p>Falha ao criar tabela: " . $e2->getMessage() . "</p>";
        }
    }
}
?>