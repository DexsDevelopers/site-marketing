<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/auth_helper.php';

// Se já estiver logado, redireciona
if (isLoggedIn()) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Login hardcoded simples (RESTORED FOR STABILITY)
    $username_correto = 'admin';
    $senha_correta = 'Lucastav8012@';

    if ($username === $username_correto && $password === $senha_correta) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['login_time'] = time();

        // Remember Me
        if (isset($_POST['remember'])) {
            setRememberCookie(0, $username, 'admin');
        }

        header('Location: admin_dashboard.php');
        exit;
    }
    else {
        $error = 'Usuário ou senha incorretos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#0a0a0c">
    <link rel="apple-touch-icon" href="https://cdn-icons-png.flaticon.com/512/124/124034.png">
    <title>Marketing Hub | Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: radial-gradient(circle at top right, #1a0b0b, #000);
        }

        .login-card {
            background: rgba(20, 20, 25, 0.9);
            padding: 3rem;
            border-radius: 24px;
            border: 1px solid rgba(255, 59, 59, 0.2);
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            text-align: center;
        }

        .login-logo {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        .input-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .input-group label {
            display: block;
            color: var(--text-dim);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s;
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 15px rgba(255, 59, 59, 0.2);
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 59, 59, 0.3);
        }

        .error-msg {
            background: rgba(255, 59, 59, 0.1);
            color: #ff5c5c;
            padding: 0.8rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="login-logo">
            <i class="fab fa-whatsapp"></i>
        </div>
        <h2 style="margin-bottom: 2rem;">Acesso Restrito</h2>

        <?php if ($error): ?>
        <div class="error-msg">
            <i class="fas fa-exclamation-circle"></i>
            <?= $error?>
        </div>
        <?php
endif; ?>

        <form method="POST">
            <div class="input-group">
                <label>Usuário</label>
                <input type="text" name="username" class="form-control" placeholder="admin" required>
            </div>
            <div class="input-group">
                <label>Senha</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <div class="input-group" style="display: flex; align-items: center; gap: 10px;">
                <input type="checkbox" name="remember" id="remember"
                    style="width: 20px; height: 20px; accent-color: var(--primary);">
                <label for="remember" style="margin: 0; cursor: pointer;">Lembrar de mim</label>
            </div>
            <button type="submit" class="btn-login">Entrar no Sistema</button>
        </form>
        <div style="margin-top: 2rem; font-size: 0.8rem; color: var(--text-dim);">
            Desenvolvido por DexsDevelopers
        </div>
    </div>
</body>

</html>