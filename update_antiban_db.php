<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';

try {
    echo "Iniciando atualização do banco para Anti-Ban e Aquecimento...<br>";

    // 1. Atualizar marketing_campanhas
    $cols = $pdo->query("SHOW COLUMNS FROM marketing_campanhas")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('horario_inicio', $cols)) {
        $pdo->exec("ALTER TABLE marketing_campanhas ADD COLUMN horario_inicio TIME DEFAULT '08:00:00'");
        $pdo->exec("ALTER TABLE marketing_campanhas ADD COLUMN horario_fim TIME DEFAULT '20:00:00'");
    }
    
    if (!in_array('usar_anti_ban', $cols)) {
        $pdo->exec("ALTER TABLE marketing_campanhas ADD COLUMN usar_anti_ban BOOLEAN DEFAULT 1");
        $pdo->exec("ALTER TABLE marketing_campanhas ADD COLUMN aquecimento_gradual BOOLEAN DEFAULT 1");
    }

    // 2. Criar tabela de estatísticas de sessão para controle de aquecimento
    $pdo->exec("CREATE TABLE IF NOT EXISTS marketing_session_history (
        id INT PRIMARY KEY AUTO_INCREMENT,
        session_id VARCHAR(100) NOT NULL,
        data DATE NOT NULL,
        envios_sucesso INT DEFAULT 0,
        envios_falha INT DEFAULT 0,
        UNIQUE KEY (session_id, data)
    )");

    // 3. Adicionar coluna de data_entrada_fluxo se não existir (necessária para limites diários)
    $colsMembros = $pdo->query("SHOW COLUMNS FROM marketing_membros")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('data_entrada_fluxo', $colsMembros)) {
        $pdo->exec("ALTER TABLE marketing_membros ADD COLUMN data_entrada_fluxo DATE DEFAULT NULL");
    }

    echo "✅ Banco de dados atualizado com padrões Anti-Ban e Aquecimento!<br>";
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}
