<?php
/**
 * Conexão segura com o banco de dados
 * Utiliza prepared statements para prevenir SQL injection
 */

// Carregar variáveis de ambiente do arquivo .env na raiz
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Configurações do banco (dinâmicas do .env)
$host = getenv('DB_HOST') ?: 'localhost';
$db = getenv('DB_NAME') ?: 'u853242961_marketings';
$user = getenv('DB_USER') ?: 'u853242961_lucas';
$pass = getenv('DB_PASS') ?: 'Lucastav8012@';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Forçar timezone correto para alinhar PHP e MySQL
    date_default_timezone_set('America/Sao_Paulo');
    $pdo->exec("SET time_zone = '-03:00'");
}
catch (\PDOException $e) {
    error_log("Erro de conexão com o banco: " . $e->getMessage());
    // Sempre lançar exceção - deixar o código que usa tratar
    throw new \PDOException("Erro de conexão com o banco de dados: " . $e->getMessage(), (int)$e->getCode());
}

// Função para executar queries seguras
function executeQuery($pdo, $sql, $params = [])
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    catch (PDOException $e) {
        error_log("Erro na query: " . $e->getMessage());
        throw $e;
    }
}

// Função para buscar dados de forma segura
function fetchData($pdo, $sql, $params = [])
{
    $stmt = executeQuery($pdo, $sql, $params);
    return $stmt->fetchAll();
}

// Função para buscar um único registro
function fetchOne($pdo, $sql, $params = [])
{
    $stmt = executeQuery($pdo, $sql, $params);
    return $stmt->fetch();
}