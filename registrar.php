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
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#10b981',
                        surface: '#0f0f12',
                        'bg-dark': '#050505',
                        'text-dim': '#94a3b8',
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer base {
            body {
                @apply bg-bg-dark text-white font-sans min-h-screen flex items-center justify-center p-6;
            }
        }
        .mesh-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background:
                radial-gradient(circle at 10% 20%, rgba(16, 185, 129, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(59, 130, 246, 0.05) 0%, transparent 40%);
        }
    </style>
</head>

<body>
    <div class="mesh-bg"></div>

    <div
        class="w-full max-w-[480px] bg-surface p-10 lg:p-12 rounded-[40px] border border-white/5 shadow-2xl relative z-10 text-center my-8">
        <div class="font-outfit text-3xl font-extrabold mb-8 flex justify-center items-center gap-2">
            <i class="fab fa-whatsapp text-primary"></i>
            WA <span class="text-primary">MONEY</span>
        </div>

        <h2 class="font-outfit text-2xl font-bold mb-2">Crie sua conta</h2>
        <p class="text-text-dim text-sm mb-10">Garanta sua vaga na maior rede de monitoramento do Brasil.</p>

        <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/20 text-red-400 p-4 rounded-xl text-sm font-bold mb-8">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?= $error?>
        </div>
        <?php
endif; ?>

        <?php if ($success): ?>
        <div class="bg-primary/10 border border-primary/20 text-primary p-4 rounded-xl text-sm font-bold mb-8">
            <i class="fas fa-check-circle mr-2"></i>
            <?= $success?>
        </div>
        <?php
endif; ?>

        <form method="POST" class="space-y-6">
            <div class="text-left">
                <label class="block text-xs font-bold uppercase tracking-widest text-text-dim mb-2 ml-1">Como quer ser
                    chamado?</label>
                <input type="text" name="username"
                    class="w-full bg-black/40 border border-white/10 rounded-2xl px-6 py-4 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none transition-all placeholder:text-white/20"
                    placeholder="Seu nome de usuário" required>
            </div>

            <div class="text-left">
                <label class="block text-xs font-bold uppercase tracking-widest text-text-dim mb-2 ml-1">Crie uma senha
                    segura</label>
                <input type="password" name="password"
                    class="w-full bg-black/40 border border-white/10 rounded-2xl px-6 py-4 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none transition-all placeholder:text-white/20"
                    placeholder="Sua senha" required>
            </div>

            <div class="bg-primary/5 border border-primary/10 p-5 rounded-2xl text-left space-y-3 mb-4">
                <div class="flex items-center gap-3 text-xs text-primary font-bold">
                    <i class="fas fa-shield-alt"></i> SEGURANÇA GARANTIDA
                </div>
                <p class="text-[11px] text-text-dim leading-relaxed">
                    Seus dados são criptografados e nossa tecnologia Sandbox protege 100% da sua privacidade pessoal.
                </p>
            </div>

            <button type="submit"
                class="w-full bg-primary text-black font-extrabold py-5 rounded-2xl transition-all duration-300 hover:shadow-[0_0_30px_rgba(16,185,129,0.4)] hover:-translate-y-1 active:scale-95 shadow-xl">
                ATIVAR MINHA CONTA AGORA
            </button>
        </form>

        <p class="mt-8 text-sm text-text-dim">
            Já participa?
            <a href="entrar.php" class="text-primary font-bold hover:underline transition-all">Fazer Login</a>
        </p>
    </div>
</body>

</html>