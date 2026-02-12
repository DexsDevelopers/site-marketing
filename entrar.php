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

        if (isset($_POST['remember'])) {
            require_once 'includes/auth_helper.php';
            setRememberCookie($user['id'], $user['username'], $user['role']);
        }

        header('Location: painel.php');
        exit;
    }
    else {
        $error = "Usuário ou senha incorretos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar | WhatsApp Money</title>
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
                @apply bg-bg-dark text-white font-sans min-h-screen flex items-center justify-center p-6 overflow-hidden;
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
        class="w-full max-w-[450px] bg-surface p-10 lg:p-12 rounded-[40px] border border-white/5 shadow-2xl relative z-10 text-center">
        <div class="font-outfit text-3xl font-extrabold mb-8 flex justify-center items-center gap-2">
            <i class="fab fa-whatsapp text-primary"></i>
            WA <span class="text-primary">MONEY</span>
        </div>

        <h2 class="font-outfit text-2xl font-bold mb-2">Bem-vindo de volta</h2>
        <p class="text-text-dim text-sm mb-10">Acesse sua carteira e acompanhe seus rendimentos.</p>

        <?php if ($error): ?>
        <div
            class="bg-red-500/10 border border-red-500/20 text-red-400 p-4 rounded-xl text-sm font-bold mb-8 animate-shake">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?= $error?>
        </div>
        <?php
endif; ?>

        <form method="POST" class="space-y-6">
            <div class="text-left">
                <label class="block text-xs font-bold uppercase tracking-widest text-text-dim mb-2 ml-1">Usuário</label>
                <input type="text" name="username"
                    class="w-full bg-black/40 border border-white/10 rounded-2xl px-6 py-4 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none transition-all placeholder:text-white/20"
                    placeholder="Seu usuário" required>
            </div>

            <div class="text-left">
                <label class="block text-xs font-bold uppercase tracking-widest text-text-dim mb-2 ml-1">Senha</label>
                <input type="password" name="password"
                    class="w-full bg-black/40 border border-white/10 rounded-2xl px-6 py-4 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none transition-all placeholder:text-white/20"
                    placeholder="Sua senha" required>
            </div>

            <div class="flex items-center gap-3">
                <input type="checkbox" name="remember" id="remember"
                    class="w-5 h-5 accent-primary rounded cursor-pointer">
                <label for="remember" class="text-sm text-text-dim cursor-pointer select-none">Lembrar de mim</label>
            </div>

            <button type="submit"
                class="w-full bg-primary text-black font-extrabold py-5 rounded-2xl transition-all duration-300 hover:shadow-[0_0_30px_rgba(16,185,129,0.4)] hover:-translate-y-1 active:scale-95 shadow-xl">
                ENTRAR NO PAINEL
            </button>
        </form>

        <p class="mt-8 text-sm text-text-dim">
            Ainda não tem conta?
            <a href="registrar.php" class="text-primary font-bold hover:underline transition-all">Criar Agora</a>
        </p>
    </div>
</body>

</html>