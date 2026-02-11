<?php
require_once 'includes/db_connect.php';
session_start();

$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Money | Ganhe Dinheiro Conectado</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #10b981;
            --primary-glow: rgba(16, 185, 129, 0.4);
            --bg: #050505;
            --card: rgba(15, 15, 20, 0.8);
            --text: #ffffff;
            --text-dim: #a1a1aa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: 'Outfit', sans-serif;
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* Background Effects */
        .bg-glow {
            position: fixed;
            width: 100vw;
            height: 100vh;
            background:
                radial-gradient(circle at 0% 0%, rgba(16, 185, 129, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 100% 100%, rgba(59, 130, 246, 0.08) 0%, transparent 50%);
            z-index: -1;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Navigation */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem 0;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -1px;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: var(--text-dim);
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        /* Hero Section */
        .hero {
            padding: 8rem 0 4rem;
            text-align: center;
        }

        .badge {
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary);
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 2rem;
            display: inline-block;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        h1 {
            font-size: 4.5rem;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            letter-spacing: -3px;
        }

        h1 span {
            color: var(--primary);
        }

        .hero-desc {
            color: var(--text-dim);
            font-size: 1.25rem;
            max-width: 700px;
            margin: 0 auto 3rem;
        }

        /* Buttons */
        .btn {
            padding: 1rem 2.5rem;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: var(--primary);
            color: #000;
            box-shadow: 0 10px 30px var(--primary-glow);
        }

        .btn-primary:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px var(--primary-glow);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Features Section */
        .features {
            padding: 6rem 0;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }

        .f-card {
            background: var(--card);
            padding: 3rem 2rem;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: 0.3s;
        }

        .f-card:hover {
            border-color: var(--primary);
            transform: translateY(-10px);
        }

        .f-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        .f-card h3 {
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .f-card p {
            color: var(--text-dim);
        }

        /* Privacy Section - Highlighted */
        .privacy-highlight {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(59, 130, 246, 0.1) 100%);
            padding: 4rem;
            border-radius: 30px;
            display: flex;
            align-items: center;
            gap: 4rem;
            margin: 4rem 0;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .privacy-content h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        /* Feedbacks */
        .feedbacks {
            padding: 6rem 0;
            text-align: center;
        }

        .f-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-top: 4rem;
        }

        .testimonial {
            background: var(--card);
            padding: 2rem;
            border-radius: 20px;
            text-align: left;
            border: 1px solid rgba(255, 255, 255, 0.03);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .user-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #333;
        }

        /* Dashboard Styles (Visible only when logged in) */
        .dash-container {
            display: none;
            padding: 4rem 0;
        }

        <?php if ($isLoggedIn): ?>.dash-container {
            display: block;
        }

        .landing-container {
            display: none;
        }

        <?php
endif;

        ?>.dash-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 2rem;
        }

        .premium-panel {
            background: var(--card);
            backdrop-filter: blur(20px);
            padding: 2.5rem;
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .qr-box {
            background: #fff;
            padding: 1.5rem;
            border-radius: 24px;
            width: fit-content;
            margin: 2rem auto;
            position: relative;
            min-height: 250px;
            min-width: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .balance-card {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            padding: 2.5rem;
            border-radius: 30px;
            color: #000;
            text-align: center;
        }

        .balance-card .val {
            font-size: 3.5rem;
            font-weight: 800;
            margin: 1rem 0;
        }

        .form-control {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1.2rem;
            border-radius: 15px;
            color: #fff;
            margin-bottom: 1.5rem;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate {
            animation: fadeIn 0.8s ease-out forwards;
        }
    </style>
</head>

<body>
    <div class="bg-glow"></div>

    <div class="container landing-container">
        <nav>
            <div class="logo">WA MONEY</div>
            <div class="nav-links">
                <a href="#how">Como funciona</a>
                <a href="#privacy">Segurança</a>
                <a href="entrar.php" class="btn btn-secondary">Entrar</a>
                <a href="registrar.php" class="btn btn-primary">Começar Agora</a>
            </div>
        </nav>

        <section class="hero animate">
            <span class="badge">🔥 NOVO MÉTODO DE RENDA PASSIVA</span>
            <h1>Sua Conexão Vale <span>Ouro</span>.</h1>
            <p class="hero-desc">Alugue seu WhatsApp para nossa rede de marketing e ganhe R$ 20,00 por dia sem fazer
                nada. Automático, seguro e instantâneo.</p>
            <div style="display: flex; gap: 1.5rem; justify-content: center;">
                <a href="registrar.php" class="btn btn-primary btn-lg"><i class="fas fa-rocket"></i> QUERO
                    PARTICIPAR</a>
                <a href="#how" class="btn btn-secondary"><i class="fas fa-play"></i> VER COMO FUNCIONA</a>
            </div>
        </section>

        <section class="features" id="how">
            <div class="f-card animate" style="animation-delay: 0.1s;">
                <div class="f-icon"><i class="fas fa-qrcode"></i></div>
                <h3>Conecte</h3>
                <p>Escaneie o QR Code em nosso painel seguro. É simples como conectar o WhatsApp Web.</p>
            </div>
            <div class="f-card animate" style="animation-delay: 0.2s;">
                <div class="f-icon"><i class="fas fa-clock"></i></div>
                <h3>Aguarde</h3>
                <p>Mantenha sua conexão ativa. A cada 24h online, você acumula R$ 20,00 em saldo.</p>
            </div>
            <div class="f-card animate" style="animation-delay: 0.3s;">
                <div class="f-icon"><i class="fas fa-wallet"></i></div>
                <h3>Receba</h3>
                <p>Solicite seu saque via PIX a qualquer momento. O dinheiro cai na sua conta em minutos.</p>
            </div>
        </section>

        <section class="privacy-highlight animate" id="privacy">
            <div style="position: relative;">
                <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Privacy" width="200"
                    style="filter: hue-rotate(90deg) brightness(1.5);">
                <div
                    style="position: absolute; bottom: -10px; right: -10px; background: #fff; color: #000; padding: 5px 10px; border-radius: 8px; font-weight: 800; font-size: 0.7rem; border: 2px solid var(--primary);">
                    NÚMERO 100% SEGURO</div>
            </div>
            <div class="privacy-content">
                <h2 style="letter-spacing:-1px">Segurança <span style="color:var(--primary)">Nível Bancário</span></h2>
                <p style="font-size: 1.1rem; color: var(--text-dim); margin-bottom: 1.5rem;">
                    Nossa tecnologia foi desenvolvida para isolar completamente o motor de envio das suas informações
                    pessoais. Garantimos que nada acontece com seu WhatsApp pessoal.
                </p>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div style="display: flex; gap: 10px; align-items: start;">
                        <i class="fas fa-lock" style="color: var(--primary); margin-top: 5px;"></i>
                        <span><b>Privacidade Total:</b> Não acessamos, não lemos e não salvamos nenhuma de suas
                            conversas.</span>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: start;">
                        <i class="fas fa-user-shield" style="color: var(--primary); margin-top: 5px;"></i>
                        <span><b>Seus Contatos a Salvo:</b> Jamais enviaremos mensagens para seus amigos, família ou
                            grupos pessoais.</span>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: start;">
                        <i class="fas fa-check-double" style="color: var(--primary); margin-top: 5px;"></i>
                        <span><b>Uso Invisível:</b> O sistema trabalha em "segundo plano" sem interferir no seu uso
                            diário do app.</span>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: start;">
                        <i class="fas fa-shield-virus" style="color: var(--primary); margin-top: 5px;"></i>
                        <span><b>Zero Risco:</b> Protocolo anti-banimento avançado mantém seu número protegido e
                            saudável.</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="feedbacks">
            <h2>O que dizem nossos <span>parceiros</span></h2>
            <div class="f-grid">
                <div class="testimonial">
                    <p>"No começo achei que era golpe, mas recebi meu primeiro PIX hoje. O sistema é muito limpo!"</p>
                    <div class="user-info">
                        <div class="user-img" style="background: url('https://i.pravatar.cc/150?u=1') center/cover;">
                        </div>
                        <div><b>Ricardo M.</b><br><small style="color:var(--primary)">Há 5 dias conectado</small></div>
                    </div>
                </div>
                <div class="testimonial">
                    <p>"Deixo um celular antigo conectado o dia todo. São R$ 600 extras por mês garantidos."</p>
                    <div class="user-info">
                        <div class="user-img" style="background: url('https://i.pravatar.cc/150?u=2') center/cover;">
                        </div>
                        <div><b>Ana Silva</b><br><small style="color:var(--primary)">Há 12 dias conectado</small></div>
                    </div>
                </div>
                <div class="testimonial">
                    <p>"O suporte é excelente e a interface muito bonita. Recomendo para todos!"</p>
                    <div class="user-info">
                        <div class="user-img" style="background: url('https://i.pravatar.cc/150?u=3') center/cover;">
                        </div>
                        <div><b>Lucas T.</b><br><small style="color:var(--primary)">Há 3 dias conectado</small></div>
                    </div>
                </div>
            </div>
        </section>

        <footer
            style="padding: 4rem 0; border-top: 1px solid rgba(255,255,255,0.05); text-align: center; color: var(--text-dim);">
            <p>&copy; 2026 WhatsApp Money Hub. Todos os direitos reservados por DexsDevelopers.</p>
        </footer>
    </div>

    <!-- Dashboard Section -->
    <?php if ($isLoggedIn): ?>
    <div class="container dash-container">
        <nav>
            <div class="logo">WA MONEY <small style="font-size: 0.8rem; opacity: 0.5;">PAINEL</small></div>
            <div style="display: flex; gap: 1.5rem; align-items: center;">
                <span>Olá, <b>
                        <?= htmlspecialchars($_SESSION['user_username'])?>
                    </b></span>
                <a href="logout.php" class="btn btn-secondary" style="padding: 0.5rem 1.2rem;">Sair</a>
            </div>
        </nav>

        <div class="dash-grid">
            <div class="premium-panel animate">
                <div style="display:flex; justify-content: space-between; align-items: center;">
                    <h2>Conexão Ativa</h2>
                    <div id="status-tag"
                        style="padding: 5px 15px; border-radius: 50px; font-size: 0.8rem; font-weight: 700;">OFFLINE
                    </div>
                </div>

                <div id="qr-display-area" style="text-align: center; padding: 2rem 0;">
                    <p style="color: var(--text-dim); margin-bottom: 2rem;">Aponte seu WhatsApp para o código abaixo
                        para começar a faturar.</p>
                    <div class="qr-box" id="qr-img-container">
                        <i class="fas fa-spinner fa-spin fa-2x" style="color: #666"></i>
                    </div>
                    <button class="btn btn-primary" id="btn-gen" onclick="generateSession()"
                        style="width: 100%; max-width: 400px; margin-top: 1rem;">
                        <i class="fas fa-sync"></i> GERAR NOVO QR CODE
                    </button>
                    <div
                        style="margin-top:2rem; font-size:0.8rem; color:var(--text-dim); background:rgba(255,255,255,0.02); padding:1rem; border-radius:12px;">
                        <i class="fas fa-shield-alt" style="color:var(--primary)"></i> <b>Segurança Ativa:</b> Suas
                        conversas e contatos pessoais estão protegidos e não serão acessados.
                    </div>
                </div>

                <div id="online-display-area" style="display: none; text-align: center; padding: 4rem 0;">
                    <div style="font-size: 5rem; color: var(--primary); margin-bottom: 2rem;"><i
                            class="fas fa-check-circle shadow-glow"></i></div>
                    <h2 style="font-size: 2.5rem;">Sua rede está ativa!</h2>
                    <p style="color: var(--text-dim); font-size: 1.1rem; max-width: 400px; margin: 0 auto;">Estamos
                        processando campanhas através da sua conexão. Não desconecte para continuar ganhando.</p>
                </div>
            </div>

            <div style="display:flex; flex-direction: column; gap: 2rem;">
                <div class="balance-card animate" style="animation-delay: 0.1s;">
                    <div style="font-size: 0.9rem; font-weight: 700; opacity: 0.7;">SALDO ACUMULADO</div>
                    <div class="val" id="user-balance">R$ 0,00</div>
                    <p style="font-size: 0.85rem; font-weight: 600;">Saque mínimo: R$ 20,00</p>
                </div>

                <div class="premium-panel animate" style="animation-delay: 0.2s;">
                    <h3>Sacar via PIX</h3>
                    <div style="margin-top: 1.5rem;">
                        <label style="font-size: 0.8rem; color: var(--text-dim);">Chave PIX (CPF, E-mail ou
                            Celular)</label>
                        <input type="text" id="pix_key" class="form-control" placeholder="Sua chave aqui...">
                        <button class="btn btn-primary" style="width: 100%;" onclick="payout()">RESGATAR AGORA</button>
                    </div>
                </div>

                <div class="premium-panel animate" style="animation-delay: 0.3s;">
                    <h3>Tempo Online (Ciclo 24h)</h3>
                    <div style="margin-top: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span id="uptime-h">0h / 24h</span>
                            <span id="uptime-p">0%</span>
                        </div>
                        <div
                            style="height: 10px; background: rgba(255,255,255,0.05); border-radius: 10px; overflow: hidden;">
                            <div id="uptime-bar"
                                style="height: 100%; background: var(--primary); width: 0%; transition: 1s;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const API_URL = 'api_marketing_aluguel.php';
        const BOT_URL = 'https://cyan-spoonbill-539092.hostingersite.com';
        let sessId = null;
        let qrMonitor = null;

        async function loadDash() {
            try {
                const r = await fetch(API_URL + '?action=get_user_dashboard');
                const d = await r.json();
                if (!d.success) return;

                document.getElementById('user-balance').innerText = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(d.saldo);
                document.getElementById('pix_key').value = d.pix_chave || '';

                if (d.instancia) {
                    sessId = d.instancia.session_id;
                    updateUIStatus(d.insta              
                    const s = d.instancia.uptime_total_segundos % 86400;
                    const h = (s / 3600).toFixed(1);
                    const p = Math.floor((s / 8              
                    document.getElementById('uptime-h').innerText = `${h}h / 24h`;
                    document.getElementById('uptime-p').innerText = `${p}%`;
                    document.getElementById('uptime-bar').style.width = `${p}%`;

                    if (d.instancia.status === 'aguardando_qr' && !qrMonitor) startQR();
                    else if (d.instancia.status === 'conectado') stopQR();
                }
            } catch (e) { }
        }

        function updateUIStatus(status) {
            const tag = document.getElementById('status-tag');
            const qrA = document.getElementById('qr-display-area');
            const onA = document.getElementById('online-di;
            
            tag.innerText = status.toUpperCase();
            if (status === 'conectado') {
                tag.style.background = 'rgba(16, 185, 129, 0.2)';
                tag.style.color = 'var(--primary)';
                qrA.style.display = 'none';
                onA.style.display = 'block';
            } else {
                tag.style.background = 'rgba(239, 68, 68, 0.2)';
                tag.style.color = '#ef4444';
                qrA.style.display = 'block';
                onA.style.display = 'none';
            }
        }

        async function generateSession() {
            const btn = document.getElementById('btn-gen');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> GERANDO...';
            btn.disabled = true;

            const res = await fetch(API_URL + '?action=setup_instance');
            const d = await res.json();
            if (d.success) {
                sessId = d.session_id;
                startQR();
            } else {
                Swal.fire('Erro', d.message || 'Erro ao iniciar conexão', 'error');
            }
            btn.innerHTML = '<i class="fas fa-sync"></i> GERAR NOVO QR CODE';
            btn.disabled = false;
        }

        function startQR() {
            if (qrMonitor) return;
            fetchRealQR();
            qrMonitor = setInterval(fetchRealQR, 5000);
        }
        function stopQR() { if (qrMonitor) { clearInterval(qrMonitor); qrMonitor = null; } }

        async function fetchRealQR() {
            if (!sessId) return;
            try {
                const r = await fetch(`${BOT_URL}/instance/qr/${sessId}`);
                const d = await r.json();
                const box = document.getElementById('qr-img-container');

                if (d.status === 'qr') {
                    box.innerHTML = `<img src="${d.qr}" style="width: 250px; height: 250px; image-rendering: pixelated;" />`;
                } else if (d.status === 'loading') {
                    box.innerHTML = '<div style="color:#000"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Gerando...</p></div>';
                } else if (d.status === 'connected') {
                    stopQR();
                    loadDash();
                }
            } catch (e) { }
        }

        async function payout() {
            const pix = document.getElementById('pix_key').value;
            if (!pix) return Swal.fire('Atenção', 'Insira sua chave PIX', 'warning');
            con st res = await fetch(API_URL + '?a ction=request_withdraw', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ valor: 20, pix_key: pix })
            });
            const d = await res.json();
            if (d.success) Swal.fire('Sucesso', d.me ssage, 'success').then(()            Dash());
            else Swal.fire('Erro', d.message, 'erro r');
        }     if ($isLoggedIn): ?>
        loadDash();
        setInterval(loadDash, 10000);
        <?php
    endif; ?>
    </script>
    <?php
endif; ?>

</body>

</html>