<?php
session_start();
require_once 'includes/db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $user = fetchOne($pdo, "SELECT * FROM users WHERE username = ?", [$username]);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();

        header('Location: aluguel.php');
        exit;
    }
    else {
        $error = "Usuário ou senha inválidos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar | Ganhe Conectando</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #10b981;
            --bg: #09090b;
        }

        body {
            background: var(--bg);
            color: white;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background-image: radial-gradient(circle at bottom left, rgba(59, 130, 246, 0.1), transparent 50%);
        }

        .card {
            background: rgba(24, 24, 27, 0.8);
            backdrop-filter: blur(10px);
            padding: 2.5rem;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .input-group {
            text-align: left;
            margin-bottom: 1.5rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #a1a1aa;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 12px;
            color: white;
            box-sizing: border-box;
        }

        .btn {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            border: none;
            border-radius: 12px;
            color: black;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);
        }

        .error {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <div class="card">
        <div style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"><i class="fab fa-whatsapp"></i></div>
        <h2 style="font-size: 2rem; margin-bottom: 0.5rem;">Bem-vindo de volta</h2>
        <p style="color: #a1a1aa; margin-bottom: 2rem;">Acesse seu painel para ver seus ganhos.</p>

        <?php if ($error): ?>
        <div class="error">
            <?= $error?>
        </div>
        <?php
endif; ?>

        <form method="POST">
            <div class="input-group">
                <label>Usuário</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="input-group">
                <label>Senha</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn">ENTRAR NO PAINEL</button>
        </form>

        <p style="margin-top: 2rem; font-size: 0.9rem;">Ainda não tem conta? <a href="registrar.php"
                style="color: var(--primary); text-decoration: none;">Cadastrar-se</a></p>
    </div>
</body>

</html>