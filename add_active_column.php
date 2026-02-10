<?php
// Script de Migração para Adicionar Colunas 'ativo' e 'titulo'
require_once 'includes/config.php';
require_once 'includes/db_connect.php';

echo "<h1>Migração de Banco de Dados</h1>";

try {
    // Adicionar coluna 'ativo' em marketing_mensagens
    $pdo->exec("ALTER TABLE marketing_mensagens ADD COLUMN IF NOT EXISTS ativo BOOLEAN DEFAULT 1");
    echo "<p>Coluna 'ativo' adicionada em marketing_mensagens.</p>";

    // Adicionar coluna 'titulo' em marketing_mensagens
    $pdo->exec("ALTER TABLE marketing_mensagens ADD COLUMN IF NOT EXISTS titulo VARCHAR(255) DEFAULT ''");
    echo "<p>Coluna 'titulo' adicionada em marketing_mensagens.</p>";

    // Adicionar coluna 'campanha_id' em marketing_membros (se não existir, para vincular lead a campanha)
    $pdo->exec("ALTER TABLE marketing_membros ADD COLUMN IF NOT EXISTS campanha_atual_id INT DEFAULT 1");
    echo "<p>Coluna 'campanha_atual_id' adicionada em marketing_membros.</p>";

    echo "<h2 style='color:green'>SUCESSO! Migração concluída.</h2>";

}
catch (PDOException $e) {
    echo "<h2 style='color:red'>ERRO: " . $e->getMessage() . "</h2>";
}