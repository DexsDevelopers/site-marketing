<?php
/**
 * Helper de Autenticação
 * Gerencia sessões e verificação de login
 */

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verificar se o usuário está logado
 * Redireciona para login se não estiver
 */
function requireLogin($redirectUrl = 'login.php')
{
    $isLogged = (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) ||
        (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true);

    if (!$isLogged) {
        if (strpos($_SERVER['PHP_SELF'], 'api_') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
            exit;
        }
        header('Location: ' . $redirectUrl);
        exit;
    }

    // Verificar timeout de sessão (2 horas)
    $sessionTimeout = 7200;
    $loginTime = $_SESSION['login_time'] ?? 0;

    if (time() - $loginTime > $sessionTimeout) {
        session_destroy();
        if (strpos($_SERVER['PHP_SELF'], 'api_') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
            exit;
        }
        header('Location: ' . $redirectUrl . '?timeout=1');
        exit;
    }
}

/**
 * Verificar se o usuário está logado (sem redirecionar)
 * @return bool
 */
function isLoggedIn()
{
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Fazer logout
 */
function logout($redirectUrl = 'login.php')
{
    session_destroy();
    header('Location: ' . $redirectUrl);
    exit;
}

/**
 * Obter nome do usuário logado
 * @return string
 */
function getLoggedUsername()
{
    return $_SESSION['admin_username'] ?? 'Admin';
}

/**
 * Obter tempo de login
 * @return int timestamp
 */
function getLoginTime()
{
    return $_SESSION['login_time'] ?? 0;
}
?>