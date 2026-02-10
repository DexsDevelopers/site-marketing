<?php
require_once 'includes/db_connect.php';

echo "<h2>Iniciando Migração para Sistema Multi-Instância e Aluguel</h2>";

try {
    // 1. Criar tabela de Instâncias de WhatsApp
    $pdo->exec("CREATE TABLE IF NOT EXISTS wa_instancias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        nome VARCHAR(100) DEFAULT 'Minha Conexão',
        status ENUM('desconectado', 'aguardando_qr', 'conectado') DEFAULT 'desconectado',
        session_id VARCHAR(50) UNIQUE NOT NULL,
        uptime_total_segundos INT DEFAULT 0,
        last_heartbeat DATETIME DEFAULT NULL,
        saldo_acumulado DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");
    echo "✅ Tabela wa_instancias criada ou já existe.<br>";

    // 2. Atualizar tabela de Usuários para saldo e pix
    $pdo->exec("ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS saldo DECIMAL(10,2) DEFAULT 0.00,
        ADD COLUMN IF NOT EXISTS pix_chave VARCHAR(255) DEFAULT NULL;");
    echo "✅ Tabela users atualizada com saldo e pix.<br>";

    // 3. Criar tabela de Pagamentos/Saques
    $pdo->exec("CREATE TABLE IF NOT EXISTS wa_pagamentos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        valor DECIMAL(10,2) NOT NULL,
        status ENUM('pendente', 'pago', 'recusado') DEFAULT 'pendente',
        pix_chave VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        pago_em DATETIME DEFAULT NULL
    ) ENGINE=InnoDB;");
    echo "✅ Tabela wa_pagamentos criada.<br>";

    // 4. Inserir a instância principal (o bot administrador já existente)
    // Vamos usar session_id 'admin_bot'
    $pdo->exec("INSERT IGNORE INTO wa_instancias (user_id, nome, session_id, status) VALUES (1, 'Admin Bot', 'admin_bot', 'desconectado')");

    echo "<h3>Migração concluída com sucesso!</h3>";

}
catch (Exception $e) {
    echo "❌ Erro na migração: " . $e->getMessage();
}