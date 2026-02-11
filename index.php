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
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid transparent;
            transition: 0.3s;
        }

        nav.scrolled {
            background: rgba(5, 5, 5, 0.8);
            border-bottom-color: var(--border);
        }

        .logo {
            font-family: 'Outfit', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -1px;
        }

        .logo span {
            color: var(--primary);
        }

        .nav-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.8rem 1.8rem;
            border-radius: 14px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
            cursor: pointer;
            border: none;
        }

        .btn-nav {
            background: var(--glass);
            color: #fff;
            border: 1px solid var(--border);
        }

        .btn-nav:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .btn-primary {
            background: var(--primary);
            color: #000;
            box-shadow: 0 4px 25px rgba(16, 185, 129, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 35px rgba(16, 185, 129, 0.4);
        }

        /* --- Hero --- */
        .hero {
            padding: 8rem 0 6rem;
            text-align: center;
            position: relative;
        }

        .hero-badge {
            background: rgba(16, 185, 129, 0.08);
            color: var(--primary);
            padding: 0.6rem 1.2rem;
            border-radius: 100px;
            font-size: 0.8rem;
            font-weight: 800;
            border: 1px solid rgba(16, 185, 129, 0.2);
            margin-bottom: 2.5rem;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        h1 {
            font-family: 'Outfit', sans-serif;
            font-size: clamp(2.8rem, 9vw, 5rem);
            line-height: 1.05;
            margin-bottom: 2.5rem;
            letter-spacing: -3px;
            font-weight: 800;
        }

        h1 span {
            background: linear-gradient(135deg, #10b981, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-text {
            color: var(--text-dim);
            font-size: 1.35rem;
            max-width: 750px;
            margin: 0 auto 4rem;
            font-weight: 400;
        }

        /* --- Features --- */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
            margin: 4rem 0 8rem;
        }

        .feature-card {
            background: var(--surface);
            padding: 3.5rem 2.5rem;
            border-radius: 30px;
            border: 1px solid var(--border);
            text-align: left;
            transition: all 0.5s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
            overflow: hidden;
        }

        .feature-card:hover {
            border-color: rgba(16, 185, 129, 0.3);
            background: rgba(255, 255, 255, 0.02);
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .feature-icon {
            width: 64px;
            height: 64px;
            background: rgba(16, 185, 129, 0.1);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            color: var(--primary);
            margin-bottom: 2.5rem;
        }

        .feature-card h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .feature-card p {
            color: var(--text-dim);
            font-size: 1.05rem;
        }

        /* --- Privacy Section --- */
        .privacy-banner {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.03) 0%, rgba(59, 130, 246, 0.03) 100%);
            border: 1px solid var(--border);
            border-radius: 40px;
            padding: 5rem;
            margin: 8rem 0;
            position: relative;
        }

        .privacy-banner h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 2.8rem;
            margin-bottom: 3rem;
            letter-spacing: -1.5px;
            text-align: center;
        }

        .privacy-list {
            list-style: none;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 3rem;
        }

        .privacy-item {
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }

        .privacy-item i {
            font-size: 1.2rem;
            margin-top: 4px;
        }

        .privacy-item b {
            font-family: 'Outfit', sans-serif;
            font-size: 1.25rem;
            display: block;
            margin-bottom: 0.5rem;
            color: #fff;
        }

        .privacy-item p {
            color: var(--text-dim);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .privacy-item .fa-check {
            color: var(--primary);
        }

        .privacy-item .fa-times {
            color: #f87171;
        }

        /* --- Feedbacks --- */
        .feedbacks {
            padding: 6rem 0;
            text-align: center;
        }

        .feedbacks h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 3.2rem;
            margin-bottom: 4rem;
            letter-spacing: -2px;
            font-weight: 800;
        }

        .feedbacks h2 span {
            color: var(--primary);
        }

        .feedback-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 2.5rem;
        }

        .feedback-card {
            background: var(--surface);
            padding: 3rem;
            border-radius: 32px;
            border: 1px solid var(--border);
            text-align: left;
            transition: all 0.4s ease;
        }

        .feedback-card:hover {
            border-color: var(--primary);
            transform: scale(1.02) translateY(-5px);
            box-shadow: 0 15px 30px rgba(16, 185, 129, 0.1);
        }

        .stars {
            color: #fbbf24;
            margin-bottom: 2rem;
            font-size: 0.85rem;
            display: flex;
            gap: 5px;
        }

        .feedback-text {
            color: var(--text-dim);
            font-size: 1.1rem;
            line-height: 1.7;
            font-style: italic;
            margin-bottom: 2.5rem;
            min-height: 80px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: #000;
            font-family: 'Outfit';
            font-size: 1.1rem;
        }

        .user-meta h4 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.1rem;
            margin: 0;
            color: #fff;
        }

        .user-meta span {
            font-size: 0.8rem;
            color: var(--text-dim);
        }

        /* --- CTA --- */
        .cta-box {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            padding: 6rem 3rem;
            border-radius: 40px;
            text-align: center;
            color: #000;
            margin: 8rem 0;
            position: relative;
            overflow: hidden;
        }

        .cta-box h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            letter-spacing: -2px;
        }

        .cta-box p {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 4rem;
            opacity: 0.8;
        }

        .btn-cta {
            background: #000;
            color: #fff;
            padding: 1.4rem 4rem;
            font-size: 1.2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .btn-cta:hover {
            transform: translateY(-5px) scale(1.05);
            background: #111;
        }

        /* --- Animations --- */
        .animate-up {
            opacity: 0;
            transform: translateY(40px);
            transition: 1s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .animate-up.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* --- Mobile --- */
        @media (max-width: 768px) {
            h1 {
                font-size: 3.2rem;
            }

            .hero-text {
                font-size: 1.15rem;
            }

            .privacy-banner {
                padding: 2.5rem;
                border-radius: 30px;
            }

            .privacy-list {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .nav-actions {
                display: none;
            }

            .cta-box h2 {
                font-size: 2.5rem;
            }

            .feedback-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="mesh-bg"></div>

    <div class="container">
        <nav id="navbar">
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
            <div class="hero-badge animate-up">A MAIOR REDE DE MONITORAMENTO DO BRASIL</div>
            <h1 class="animate-up" style="transition-delay: 0.1s;">Seu WhatsApp agora gera <span>Lucro Real</span>.</h1>
            <p class="hero-text animate-up" style="transition-delay: 0.2s;">
                Nós usamos sua conexão ociosa para validar campanhas de marketing globais.
                Em troca, você recebe R$ 20,00 por dia diretamente no seu PIX.
            </p>
            <div class="animate-up" style="transition-delay: 0.3s;">
                <a href="registrar.php" class="btn btn-primary" style="padding: 1.4rem 3.5rem; font-size: 1.15rem;">
                    <i class="fas fa-play"></i> ATIVAR MINHA CONTA GRÁTIS
                </a>
                <div
                    style="margin-top: 2rem; color: var(--text-dim); font-size: 0.9rem; display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap;">
                    <span><i class="fas fa-check-circle" style="color: var(--primary)"></i> Sem mensalidades</span>
                    <span><i class="fas fa-check-circle" style="color: var(--primary)"></i> Saque diário</span>
                    <span><i class="fas fa-check-circle" style="color: var(--primary)"></i> 100% Seguro</span>
                </div>
            </div>
        </section>

        <div class="features-grid">
            <div class="feature-card animate-up">
                <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                <h3>Ativação em 1 Minuto</h3>
                <p>Basta vincular seu número no painel via código e o sistema começa a trabalhar imediatamente.</p>
            </div>
            <div class="feature-card animate-up" style="transition-delay: 0.1s;">
                <div class="feature-icon"><i class="fas fa-piggy-bank"></i></div>
                <h3>Renda Passiva Real</h3>
                <p>Não precisa vender nada, nem convidar ninguém. O lucro é gerado apenas por estar online.</p>
            </div>
            <div class="feature-card animate-up" style="transition-delay: 0.2s;">
                <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                <h3>Tecnologia Anti-Ban</h3>
                <p>Nossos algoritmos simulam o comportamento humano para garantir que seu chip fique 100% protegido.</p>
            </div>
        </div>

        <section class="privacy-banner animate-up">
            <h2>O que fazemos (e o que NÃO fazemos)</h2>
            <div class="privacy-list">
                <div class="privacy-item">
                    <i class="fas fa-check"></i>
                    <div>
                        <b>Validamos Links</b>
                        <p>Usamos sua conexão para verificar se links de marketing globais estão ativos e acessíveis.
                        </p>
                    </div>
                </div>
                <div class="privacy-item">
                    <i class="fas fa-times"></i>
                    <div>
                        <b>Zero Acesso a Chat</b>
                        <p>A tecnologia Sandbox garante que nunca leremos suas mensagens privadas, fotos ou vídeos.</p>
                    </div>
                </div>
                <div class="privacy-item">
                    <i class="fas fa-times"></i>
                    <div>
                        <b>Contatos Intocáveis</b>
                        <p>O sistema é isolado da sua agenda. Jamais enviaremos mensagens para seus contatos.</p>
                    </div>
                </div>
                <div class="privacy-item">
                    <i class="fas fa-check"></i>
                    <div>
                        <b>Uso Silencioso</b>
                        <p>O robô trabalha em segundo plano, consumindo o mínimo de dados e bateria possível.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="feedbacks">
            <h2 class="animate-up">O que dizem nossos <span>Parceiros</span></h2>
            <div class="feedback-grid">
                <div class="feedback-card animate-up">
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

                <div class="feedback-card animate-up" style="transition-delay: 0.1s;">
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

                <div class="feedback-card animate-up" style="transition-delay: 0.2s;">
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

        <section class="cta-box animate-up">
            <h2>Pronto para começar?</h2>
            <p>Junte-se a mais de 12.000 usuários que já estão lucrando com suas conexões.</p>
            <a href="registrar.php" class="btn btn-cta">
                <i class="fas fa-rocket"></i> CRIAR MINHA CONTA AGORA
            </a>
        </section>
    </div>

    <footer style="padding: 5rem 0; border-top: 1px solid var(--border); text-align: center; color: var(--text-dim);">
        <p>&copy; 2026 WhatsApp Money Technology. Todos os direitos reservados.</p>
    </footer>

    <script>
        // Scroll animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.animate-up').forEach(el => observer.observe(el));

        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const nav = document.getElementById('navbar');
            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });
    </script>
</body>

</html>