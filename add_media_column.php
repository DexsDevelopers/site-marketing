<?php
// Script de Migração - Passo 2 - Mídia
require_once 'includes/config.php';
require_once 'includes/db_connect.php';

echo "<h1>Migração de Banco de Dados - Mídia no Funil</h1>";

try {
    // Adicionar colunas de mídia
    $pdo->exec("ALTER TABLE marketing_mensagens ADD COLUMN IF NOT EXISTS midia_url VARCHAR(500) DEFAULT NULL");
    $pdo->exec("ALTER TABLE marketing_mensagens ADD COLUMN IF NOT EXISTS tipo_midia VARCHAR(50) DEFAULT 'texto'");

    echo "<p>Colunas 'midia_url' e 'tipo_midia' adicionadas.</p>";

    // Criar pasta de uploads se não existir (no servidor)
    $uploadDir = __DIR__ . '/uploads/marketing';
    if (!file_exists($uploadDir)) {
        if (mkdir($uploadDir, 0777, true)) {
            echo "<p>Pasta 'uploads/marketing' criada.</p>";
        }
        else {
            echo "<p style='color:orange'>Atenção: Não foi possível criar a pasta 'uploads/marketing'. Crie manualmente via gerenciador de arquivos e dê permissão 777.</p>";
        }
    }
    else {
        echo "<p>Pasta 'uploads/marketing' já existe.</p>";
    }

    echo "<h2 style='color:green'>SUCESSO! Migração de Mídia concluída.</h2>";

}
catch (PDOException $e) {
    echo "<h2 style='color:red'>ERRO: " . $e->getMessage() . "</h2>";
}