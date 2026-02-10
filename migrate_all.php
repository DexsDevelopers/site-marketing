<?php
/**
 * Script de Migração TOTAL
 * Adiciona todas as colunas potencialmente faltantes no banco de dados
 */
require_once 'includes/config.php';
require_once 'includes/db_connect.php';

echo "<h1>Migração Completa do Banco de Dados</h1>";

function addColumnIfNotExists($pdo, $table, $column, $definition)
{
    try {
        // Verificar se coluna existe
        $stmt = $pdo->prepare("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        if ($stmt->fetch()) {
            echo "<p style='color:gray'>Coluna <b>$table.$column</b> já existe.</p>";
            return;
        }

        // Adicionar coluna
        $pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
        echo "<p style='color:green'>Coluna <b>$table.$column</b> adicionada com sucesso!</p>";
    }
    catch (PDOException $e) {
        echo "<p style='color:red'>Erro ao adicionar $table.$column: " . $e->getMessage() . "</p>";
    }
}

try {
    // 1. Tabela marketing_mensagens
    addColumnIfNotExists($pdo, 'marketing_mensagens', 'ativo', "TINYINT(1) DEFAULT 1");
    addColumnIfNotExists($pdo, 'marketing_mensagens', 'midia_url', "VARCHAR(500) DEFAULT NULL");
    addColumnIfNotExists($pdo, 'marketing_mensagens', 'tipo_midia', "VARCHAR(50) DEFAULT 'texto'");
    addColumnIfNotExists($pdo, 'marketing_mensagens', 'titulo', "VARCHAR(100) DEFAULT NULL"); // Só por garantia

    // 2. Tabela marketing_membros
    addColumnIfNotExists($pdo, 'marketing_membros', 'campanha_atual_id', "INT DEFAULT 1");
    addColumnIfNotExists($pdo, 'marketing_membros', 'data_entrada_fluxo', "DATE DEFAULT NULL");
    addColumnIfNotExists($pdo, 'marketing_membros', 'data_proximo_envio', "DATETIME DEFAULT NULL");
    addColumnIfNotExists($pdo, 'marketing_membros', 'ultimo_passo_id', "INT DEFAULT 0");

    // 3. Tabela marketing_campanhas
    addColumnIfNotExists($pdo, 'marketing_campanhas', 'ativo', "TINYINT(1) DEFAULT 1");
    addColumnIfNotExists($pdo, 'marketing_campanhas', 'membros_por_dia_grupo', "INT DEFAULT 10");
    addColumnIfNotExists($pdo, 'marketing_campanhas', 'intervalo_min_minutos', "INT DEFAULT 30");
    addColumnIfNotExists($pdo, 'marketing_campanhas', 'intervalo_max_minutos', "INT DEFAULT 120");

    // 4. Inserir Campanha Padrão se não existir
    $campanha = $pdo->query("SELECT COUNT(*) FROM marketing_campanhas")->fetchColumn();
    if ($campanha == 0) {
        $pdo->exec("INSERT INTO marketing_campanhas (nome, ativo) VALUES ('Campanha Principal', 1)");
        echo "<p style='color:blue'>Campanha Padrão criada.</p>";
    }

    echo "<h2>Migração Concluída! Tente usar o sistema agora.</h2>";

}
catch (PDOException $e) {
    echo "<h1>Erro Geral: " . $e->getMessage() . "</h1>";
}