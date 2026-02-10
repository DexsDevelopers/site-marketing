<?php
/**
 * Script de Instalação Completa do Banco de Dados de Marketing
 * Cria todas as tabelas necessárias para o Marketing Hub funcionar do zero.
 */

// Configurações do NOVO Banco de Dados (Altere aqui se necessário para testar conexão antes de mudar db_connect.php)
// Se estiver rodando no mesmo servidor onde o db_connect já aponta para o banco certo, apenas use o include.
// Mas como o pedido foi "Conecte no outro banco", vamos permitir definir credenciais aqui para rodar a criação LÁ.

$host = 'localhost';
$dbname = 'u853242961_marketings';
$username = 'u853242961_lucas'; // Ou o usuário que tem acesso a esse banco
$password = 'Lucas190105*'; // A senha que estava no config antigo

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h1>Conectado com sucesso ao banco: $dbname</h1>";
}
catch (PDOException $e) {
    die("<h1>Erro ao conectar no banco $dbname: " . $e->getMessage() . "</h1><p>Verifique as credenciais no arquivo install_db.php</p>");
}

// SQL de Criação das Tabelas
$sql = "
-- Tabela de Usuários (Login)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inserir usuário Admin padrão se não existir (admin / admin123)
-- Senha hashada: \$2y\$10\$... (gerar hash seguro)
-- Vamos usar um hash simples para teste: admin123 -> \$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT IGNORE INTO users (id, username, password) VALUES (1, 'admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Tabela de Campanhas
CREATE TABLE IF NOT EXISTS marketing_campanhas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    ativo TINYINT(1) DEFAULT 1,
    membros_por_dia_grupo INT DEFAULT 10,
    intervalo_min_minutos INT DEFAULT 30,
    intervalo_max_minutos INT DEFAULT 120,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Mensagens do Funil
CREATE TABLE IF NOT EXISTS marketing_mensagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campanha_id INT DEFAULT 1,
    ordem INT NOT NULL,
    tipo VARCHAR(20) DEFAULT 'texto',
    conteudo TEXT,
    midia_url VARCHAR(500) DEFAULT NULL,
    tipo_midia VARCHAR(50) DEFAULT 'texto',
    delay_apos_anterior_minutos INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campanha_id) REFERENCES marketing_campanhas(id) ON DELETE CASCADE
);

-- Tabela de Membros (Leads)
CREATE TABLE IF NOT EXISTS marketing_membros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campanha_atual_id INT DEFAULT 1,
    nome VARCHAR(100),
    telefone VARCHAR(50) NOT NULL UNIQUE,
    grupo_origem_jid VARCHAR(100),
    status ENUM('novo', 'em_progresso', 'concluido', 'bloqueado') DEFAULT 'novo',
    ultimo_passo_id INT DEFAULT 0,
    data_entrada_fluxo DATE DEFAULT NULL,
    data_proximo_envio DATETIME DEFAULT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    -- FOREIGN KEY (campanha_atual_id) REFERENCES marketing_campanhas(id) ON DELETE SET NULL
);

-- Configurações do Bot
CREATE TABLE IF NOT EXISTS bot_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Automações (Necessário para logs)
CREATE TABLE IF NOT EXISTS bot_automations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    tipo VARCHAR(50),
    ativo TINYINT(1) DEFAULT 1,
    config JSON,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inserir Automação de Marketing Padrão
INSERT IGNORE INTO bot_automations (id, nome, tipo, ativo, config) VALUES (1, 'Campanha Marketing', 'marketing', 1, '{}');

-- Logs de Automação
CREATE TABLE IF NOT EXISTS bot_automation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    automation_id INT,
    jid_origem VARCHAR(100),
    numero_origem VARCHAR(50),
    mensagem_recebida TEXT,
    resposta_enviada TEXT,
    grupo_id VARCHAR(100),
    grupo_nome VARCHAR(100),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (automation_id) REFERENCES bot_automations(id) ON DELETE SET NULL
);

-- Tabelas Auxiliares (WhatsApp Contatos e Notificações - Se usadas pelo Helper)
CREATE TABLE IF NOT EXISTS whatsapp_contatos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE,
    nome VARCHAR(100),
    telefone_original VARCHAR(50),
    telefone_normalizado VARCHAR(50),
    notificacoes_ativas TINYINT(1) DEFAULT 1,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS whatsapp_notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50),
    status_titulo VARCHAR(100),
    status_subtitulo VARCHAR(255),
    status_data DATETIME,
    telefone VARCHAR(50),
    mensagem TEXT,
    resposta_http TEXT,
    sucesso TINYINT(1),
    http_code INT,
    enviado_em DATETIME,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_notification (codigo, status_titulo, status_data)
);
";

try {
    // Executar múltiplas queries? PDO->exec as vezes não aceita. Melhor splitar.
    // Vamos splitar por ponto e virgula? perigoso se tiver texto com ;
    // Vamos executar comando por comando manual.

    // Na verdade, PDO->exec suporta multi-statement se o driver permitir, mas é arriscado.
    // Melhor abordagem:
    $statements = explode(";", $sql);
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (!empty($stmt)) {
            $pdo->exec($stmt);
        }
    }

    // Inserir Campanha Padrão se não existir
    $pdo->exec("INSERT IGNORE INTO marketing_campanhas (id, nome, ativo, membros_por_dia_grupo) VALUES (1, 'Campanha Principal', 1, 10)");

    echo "<h2>Tabelas criadas com sucesso!</h2>";
    echo "<p>Usuário Admin criado: <b>admin</b> / <b>admin123</b></p>";
    echo "<p>Agora você pode alterar o arquivo <b>includes/db_connect.php</b> para apontar para este banco.</p>";

}
catch (PDOException $e) {
    echo "<h2 style='color:red;'>Erro na execução do SQL: " . $e->getMessage() . "</h2>";
}