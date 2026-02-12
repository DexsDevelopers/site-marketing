<?php
/**
 * Helper de Autenticação
 * Gerencia sessões e verificação de login
 */

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tenta restaurar sessão via cookie se não estiver logado
if (!isset($_SESSION['user_logged_in']) && !isset($_SESSION['admin_logged_in'])) {
    checkRememberCookie();
}

function setRememberCookie($id, $username, $role)
{
    $data = json_encode(['id' => $id, 'username' => $username, 'role' => $role]);
    $token = base64_encode($data . '||' . md5($data . 'SECRET_SALT_marketing_hub_2024'));
    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true); // 30 dias
}

function checkRememberCookie()
{
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        $parts = explode('||', base64_decode($token));

        if (count($parts) === 2) {
            $data = $parts[0];
            $hash = $parts[1];

            if (md5($data . 'SECRET_SALT_marketing_hub_2024') === $hash) {
                $user = json_decode($data, true);
                if ($user) {
                    if ($user['role'] === 'admin') {
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_username'] = $user['username'];
                    }
                    else {
                        $_SESSION['user_logged_in'] = true;
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_username'] = $user['username'];
                        $_SESSION['user_role'] = $user['role'];
                    }
                    $_SESSION['login_time'] = time();
                    return true;
                }
            }
        }
    }
    return false;
}

function clearRememberCookie()
{
    setcookie('remember_token', '', time() - 3600, '/');
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
    clearRememberCookie();
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