<?php
session_start();
require_once 'includes/db_connect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Preencha todos os campos.";
    }
    else {
        $check = fetchOne($pdo, "SELECT id FROM users WHERE username = ?", [$username]);
        if ($check) {
            $error = "Este nome de usuário já está sendo usado.";
        }
        else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $res = executeQuery($pdo, "INSERT INTO users (username, password, role) VALUES (?, ?, 'user')", [$username, $hash]);
            if ($res) {
                $success = "Conta criada com sucesso! Redirecionando...";
                echo "<script>setTimeout(() => { window.location.href = 'entrar.php'; }, 2000);</script>";
            }
            else {
                $error = "Erro ao criar conta.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta | WhatsApp Money</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&family=Plus+Jakarta+Sans:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #10b981;
            --bg: #050505;
            --surface: #0f0f12;
            --border: rgba(255, 255, 255, 0.08);
            --text: #ffffff;
            --text-dim: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--bg);
            color: #fff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.5rem;
        }

        .card {
            background: var(--surface);
            padding: 3rem;
            border-radius: 32px;
            border: 1px solid var(--border);
            width: 100%;
            max-width: 450px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .logo {
            font-family: 'Outfit', sans-serif;
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 2rem;
        }

        .logo span {
            color: var(--primary);
        }

        .input-group {
            text-align: left;
            margin-bottom: 1.5rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dim);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            padding: 1.2rem;
            border-radius: 16px;
            color: #fff;
            transition: 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            background: rgba(255, 255, 255, 0.05);
        }

        .btn {
            width: 100%;
            padding: 1.2rem;
            background: var(--primary);
            border: none;
            border-radius: 16px;
            color: #000;
            font-weight: 800;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 1rem;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);
        }

        .msg {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .error {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .success {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="logo">WA <span>MONEY</span></div>
        <h2 style="margin-bottom: 0.5rem; font-family: 'Outfit';">Crie sua conta</h2>
        <p style="color: var(--text-dim); margin-bottom: 2.5rem;">Garanta sua vaga na rede de monitoramento.</p>

        <?php if ($error): ?>
        <div class="msg error">
            <?= $error?>
        </div>
        <?php
endif; ?>
        <?php if ($success): ?>
        <div class="msg success">
            <?= $success?>
        </div>
        <?php
endif; ?>

        <form method="POST">
            <div class="input-group">
                <label>Como quer ser chamado?</label>
                <input type="text" name="username" class="form-control" placeholder="Seu nome de usuário" required>
            </div>
            <div class="input-group">
                <label>Crie uma senha segura</label>
                <input type="password" name="password" class="form-control" placeholder="Sua senha" required>
            </div>
            <button type="submit" class="btn">ATIVAR MINHA CONTA</button>
        </form>

        <p style="margin-top: 2rem; color: var(--text-dim); font-size: 0.9rem;">
            Já participa? <a href="entrar.php"
                style="color: var(--primary); text-decoration: none; font-weight: 700;">Fazer Login</a>
        </p>
    </div>
</body>

</html>