<?php
session_start();
require_once 'includes/db_connect.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Money | Lucro Passivo com seu WhatsApp</title>

    <!-- SEO -->
    <meta name="description"
        content="Alugue seu WhatsApp para nossa rede de marketing e ganhe dinheiro todos os dias. 100% seguro, automático e sem complicações.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Plus+Jakarta+Sans:wght@400;500;700&display=swap"
        rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Styles -->
    <style>
        :root {
            --primary: #10b981;
            --primary-rgb: 16, 185, 129;
            --secondary: #3b82f6;
            --bg: #050505;
            --surface: #0f0f12;
            --glass: rgba(255, 255, 255, 0.03);
            --border: rgba(255, 255, 255, 0.08);
            --text: #ffffff;
            --text-dim: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            user-select: none;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: 'Plus Jakarta Sans', sans-serif;
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* --- Background Mesh --- */
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        /* --- Navbar --- */
        nav {
            padding: 1.5rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        .logo {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo span {
            color: var(--primary);
        }

        .nav-actions {
            display: flex;
            gap: 1.5rem;
        }

        .btn {
            padding: 0.8rem 1.8rem;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            cursor: pointer;
            border: none;
        }

        .btn-nav {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            border: 1px solid var(--border);
        }

        .btn-nav:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .btn-primary {
            background: var(--primary);
            color: #000;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(16, 185, 129, 0.4);
        }

        /* --- Hero --- */
        .hero {
            padding: 6rem 0;
            text-align: center;
            position: relative;
        }

        .hero-badge {
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary);
            padding: 0.5rem 1rem;
            border-radius: 100px;
            font-size: 0.85rem;
            font-weight: 700;
            border: 1px solid rgba(16, 185, 129, 0.2);
            margin-bottom: 2.5rem;
            display: inline-block;
        }

        h1 {
            font-family: 'Outfit', sans-serif;
            font-size: clamp(2.5rem, 8vw, 4.5rem);
            line-height: 1.1;
            margin-bottom: 2rem;
            letter-spacing: -2px;
            font-weight: 800;
        }

        h1 span {
            background: linear-gradient(to right, #10b981, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-text {
            color: var(--text-dim);
            font-size: 1.25rem;
            max-width: 700px;
            margin: 0 auto 3.5rem;
        }

        /* --- Features --- */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin: 4rem 0;
        }

        .feature-card {
            background: var(--surface);
            padding: 3rem 2rem;
            border-radius: 24px;
            border: 1px solid var(--border);
            text-align: left;
            transition: 0.4s;
            position: relative;
            overflow: hidden;
        }

        .feature-card:hover {
            border-color: rgba(16, 185, 129, 0.3);
            background: rgba(255, 255, 255, 0.02);
            transform: translateY(-5px);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: rgba(16, 185, 129, 0.1);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 2rem;
        }

        /* --- Privacy Section --- */
        .privacy-banner {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(59, 130, 246, 0.05) 100%);
            border: 1px solid var(--border);
            border-radius: 32px;
            padding: 4rem;
            margin: 6rem 0;
            display: flex;
            align-items: center;
            gap: 4rem;
            position: relative;
        }

        .privacy-content h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 2.2rem;
            margin-bottom: 1.5rem;
        }

        .privacy-list {
            list-style: none;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .privacy-item {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            color: var(--text-dim);
            font-size: 1rem;
        }

        .privacy-item i {
            color: var(--primary);
            margin-top: 5px;
        }

        /* --- Feedbacks --- */
        .feedbacks {
            padding: 4rem 0;
            text-align: center;
        }

        .feedbacks h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 2.8rem;
            margin-bottom: 3.5rem;
            letter-spacing: -1.5px;
        }

        .feedback-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feedback-card {
            background: var(--surface);
            padding: 2.5rem;
            border-radius: 24px;
            border: 1px solid var(--border);
            text-align: left;
            transition: 0.3s;
        }

        .feedback-card:hover {
            border-color: var(--primary);
            transform: scale(1.02);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 2rem;
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: #000;
            font-family: 'Outfit';
        }

        .user-meta h4 {
            font-size: 1rem;
            margin: 0;
            color: #fff;
        }

        .user-meta span {
            font-size: 0.8rem;
            color: var(--text-dim);
        }

        .feedback-text {
            color: var(--text-dim);
            font-size: 1rem;
            line-height: 1.6;
            font-style: italic;
        }

        .stars {
            color: #fbbf24;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            gap: 4px;
        }

        /* --- FAQ --- */
        .faq {
            padding: 6rem 0;
            text-align: center;
        }

        .faq h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 2.5rem;
            margin-bottom: 4rem;
        }

        /* --- CTA --- */
        .cta-box {
            background: linear-gradient(to right, #10b981, #059669);
            padding: 5rem 3rem;
            border-radius: 32px;
            text-align: center;
            color: #000;
            margin: 6rem 0;
        }

        .cta-box h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            letter-spacing: -1.5px;
        }

        .cta-box p {
            font-size: 1.2rem;
            font-weight: 500;
            margin-bottom: 3rem;
            opacity: 0.9;
        }

        /* --- Mobile Responsiveness --- */
        @media (max-width: 768px) {
            h1 {
                font-size: 3rem;
            }

            .privacy-banner {
                flex-direction: column;
                padding: 2.5rem;
                text-align: center;
            }

            .privacy-list {
                grid-template-columns: 1fr;
            }

            .nav-actions {
                display: none;
            }

            .hero {
                padding: 4rem 0;
            }

            .mesh-bg {
                opacity: 0.5;
            }
        }

        .animate-in {
            animation: slideUp 1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div class="mesh-bg"></div>

    <div class="container text-center">
        <nav>
            <div class="logo">
                <i class="fab fa-whatsapp"></i>
                WA <span>MONEY</span>
            </div>
            <div class="nav-actions">
                <a href="entrar.php" class="btn btn-nav">Acessar Conta</a>
                <a href="registrar.php" class="btn btn-primary">Começar Agora</a>
            </div>
        </nav>

        <section class="hero">
            <div class="hero-badge animate-in">A MAIOR REDE DE MONITORAMENTO DO BRASIL</div>
            <h1 class="animate-in" style="animation-delay: 0.1s;">Seu WhatsApp agora gera <span>Lucro Real</span>.</h1>
            <p class="hero-text animate-in" style="animation-delay: 0.2s;">
                Nós usamos sua conexão ociosa para validar campanhas de marketing globais.
                Em troca, você recebe R$ 20,00 por dia diretamente no seu PIX.
            </p>
            <div class="animate-in" style="animation-delay: 0.3s;">
                <a href="registrar.php" class="btn btn-primary" style="padding: 1.2rem 3rem; font-size: 1.1rem;">
                    <i class="fas fa-play"></i> ATIVAR MINHA CONTA GRÁTIS
                </a>
                <div style="margin-top: 1.5rem; color: var(--text-dim); font-size: 0.85rem;">
                    <i class="fas fa-check-circle"></i> Sem mensalidades &nbsp;&nbsp;
                    <i class="fas fa-check-circle"></i> Saque diário &nbsp;&nbsp;
                    <i class="fas fa-check-circle"></i> 100% Seguro
                </div>
            </div>
        </section>

        <div class="features-grid">
            <div class="feature-card animate-in" style="animation-delay: 0.4s;">
                <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                <h3>Ativação em 1 Minuto</h3>
                <p>Basta vincular seu número no painel via código e o sistema começa a trabalhar imediatamente.</p>
            </div>
            <div class="feature-card animate-in" style="animation-delay: 0.5s;">
                <div class="feature-icon"><i class="fas fa-piggy-bank"></i></div>
                <h3>Renda Passiva Real</h3>
                <p>Não precisa vender nada, nem convidar ninguém. O lucro é gerado apenas por estar online.</p>
            </div>
            <div class="feature-card animate-in" style="animation-delay: 0.6s;">
                <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                <h3>Tecnologia Anti-Ban</h3>
                <p>Nossos algoritmos simulam o comportamento humano para garantir que seu chip fique 100% protegido.</p>
            </div>
        </div>

        <section class="privacy-banner animate-in">
            <div class="privacy-content">
                <h2>O que fazemos (e o que NÃO fazemos)</h2>
                <div class="privacy-list">
                    <div class="privacy-item">
                        <i class="fas fa-check"></i>
                        <div><b>Validamos Links</b><br>Usamos sua JID para verificar se links de marketing estão ativos.
                        </div>
                    </div>
                    <div class="privacy-item">
                        <i class="fas fa-times" style="color: #ef4444;"></i>
                        <div><b>Zero Acesso a Chat</b><br>Nunca lemos suas mensagens privadas ou fotos.</div>
                    </div>
                    <div class="privacy-item">
                        <i class="fas fa-times" style="color: #ef4444;"></i>
                        <div><b>Contatos Intocáveis</b><br>Jamais enviaremos mensagens para sua família ou amigos.</div>
                    </div>
                    <div class="privacy-item">
                        <i class="fas fa-check"></i>
                        <div><b>Uso Silencioso</b><br>O bot trabalha em segundo plano sem atrapalhar seu uso do app.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="feedbacks">
            <h2 class="animate-in">O que dizem nossos <span>Parceiros</span></h2>
            <div class="feedback-grid">
                <div class="feedback-card animate-in" style="animation-delay: 0.1s;">
                    <div class="stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                            class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p class="feedback-text">"No começo achei que era golpe, mas recebi meus primeiros R$ 20,00 no PIX
                        hoje cedo. O sistema é muito simples de usar e o suporte é nota 10!"</p>
                    <div class="user-info">
                        <div class="user-avatar">RS</div>
                        <div class="user-meta">
                            <h4>Ricardo Santos</h4>
                            <span>há 2 horas</span>
                        </div>
                    </div>
                </div>

                <div class="feedback-card animate-in" style="animation-delay: 0.2s;">
                    <div class="stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                            class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p class="feedback-text">"O melhor de tudo é que não atrapalha em nada o uso do WhatsApp. Fica lá
                        quietinho em segundo plano e o saldo vai subindo todo dia."</p>
                    <div class="user-info">
                        <div class="user-avatar">AM</div>
                        <div class="user-meta">
                            <h4>Ana Maria</h4>
                            <span>há 5 horas</span>
                        </div>
                    </div>
                </div>

                <div class="feedback-card animate-in" style="animation-delay: 0.3s;">
                    <div class="stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                            class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p class="feedback-text">"Já testei vários apps de ganhar dinheiro assistindo vídeo, mas esse é o
                        único que realmente paga automático sem precisar fazer nada."</p>
                    <div class="user-info">
                        <div class="user-avatar">LT</div>
                        <div class="user-meta">
                            <h4>Lucas Teixeira</h4>
                            <span>há 8 horas</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="cta-box animate-in">

            <h2>Pronto para começar?</h2>
            <p>Junte-se a mais de 12.000 usuários que já estão lucrando com suas conexões.</p>
            <a href="registrar.php" class="btn"
                style="background: #000; color: #fff; padding: 1.5rem 4rem; font-size: 1.2rem;">
                CRIAR MINHA CONTA AGORA
            </a>
            </footer>
    </div>

    <footer style="padding: 4rem 0; border-top: 1px solid var(--border); text-align: center; color: var(--text-dim);">
        <p>&copy; 2026 WhatsApp Money Technology. Todos os direitos reservados.</p>
    </footer>

    <script>
        // Smooth reveal on scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.animate-in').forEach(el => observer.observe(el));
    </script>
</body>

</html>