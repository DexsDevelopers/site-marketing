<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth_helper.php';
session_start();

$isLoggedIn = (isset($_SESSION['user_id']) && $_SESSION['user_logged_in'] === true) ||
    (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true);
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

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: 'Outfit', sans-serif;
            overflow-x: hidden;
            line-height: 1.6;
        }

        .bg-glow {
            position: fixed;
            width: 100vw;
            height: 100vh;
            background: 
                radial-gradient(circle at 0% 0%, rgba(16, 185, 129, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 100% 100%, rgba(59, 130, 246, 0.08) 0%, transparent 50%);
            z-index: -1;
        }

        .container { max-width: 1200px; margin: 0 auto; padding: 0 2rem; }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem 0;
        }
        .logo { font-size: 1.8rem; font-weight: 800; color: var(--primary); letter-spacing: -1px; }
        .nav-links { display: flex; gap: 2rem; align-items: center; }
        .nav-links a { color: var(--text-dim); text-decoration: none; font-weight: 500; transition: 0.3s; }
        .nav-links a:hover { color: var(--primary); }

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
        .btn-primary:hover { transform: translateY(-5px); box-shadow: 0 15px 40px var(--primary-glow); }
        .btn-secondary { background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1); }

        .hero { padding: 8rem 0 4rem; text-align: center; }
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
        h1 { font-size: 4.5rem; line-height: 1.1; margin-bottom: 1.5rem; letter-spacing: -3px; }
        h1 span { color: var(--primary); }
        .hero-desc { color: var(--text-dim); font-size: 1.25rem; max-width: 700px; margin: 0 auto 3rem; }

        .features { padding: 6rem 0; display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; }
        .f-card {
            background: var(--card);
            padding: 3rem 2rem;
            border-radius: 24px;
            border: 1px solid rgba(255,255,255,0.05);
            transition: 0.3s;
        }
        .f-card:hover { border-color: var(--primary); transform: translateY(-10px); }
        .f-icon { font-size: 2.5rem; color: var(--primary); margin-bottom: 1.5rem; }

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

        .feedbacks { padding: 6rem 0; text-align: center; }
        .f-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; margin-top: 4rem; }
        .testimonial {
            background: var(--card);
            padding: 2rem;
            border-radius: 20px;
            text-align: left;
            border: 1px solid rgba(255,255,255,0.03);
        }

        .dash-container { padding: 4rem 0; }
        .dash-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 2rem; }
        .premium-panel {
            background: var(--card);
            backdrop-filter: blur(20px);
            padding: 2.5rem;
            border-radius: 30px;
            border: 1px solid rgba(255,255,255,0.05);
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
        .balance-card .val { font-size: 3.5rem; font-weight: 800; margin: 1rem 0; }

        .form-control {
            width: 100%;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            padding: 1.2rem;
            border-radius: 15px;
            color: #fff;
            margin-bottom: 1.5rem;
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate { animation: fadeIn 0.8s ease-out forwards; }
    </style>
</head>
<body>
    <div class="bg-glow"></div>

    <?php if (!$isLoggedIn): ?>
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
            <p class="hero-desc">Alugue seu WhatsApp para nossa rede de marketing e ganhe R$ 20,00 por dia sem fazer nada. Automático, seguro e instantâneo.</p>
            <div style="display: flex; gap: 1.5rem; justify-content: center;">
                <a href="registrar.php" class="btn btn-primary btn-lg"><i class="fas fa-rocket"></i> QUERO PARTICIPAR</a>
            </div>
        </section>

        <section class="features" id="how">
            <div class="f-card">
                <h3>Conecte</h3>
                <p>Escaneie o QR Code em nosso painel seguro.</p>
            </div>
            <div class="f-card">
                <h3>Aguarde</h3>
                <p>A cada 24h online, você acumula R$ 20,00.</p>
            </div>
            <div class="f-card">
                <h3>Receba</h3>
                <p>Saque via PIX instantaneamente.</p>
            </div>
        </section>

        <section class="privacy-highlight animate" id="privacy">
           <div style="position: relative;">
                <p>🛡️ <b>Sua privacidade é prioridade:</b> Não lemos conversas nem enviamos mensagens para seus contatos.</p>
            </div>
        </section>

        <footer style="padding: 4rem 0; text-align: center; color: var(--text-dim);">
            <p>&copy; 2026 WhatsApp Money Hub.</p>
        </footer>
    </div>
    <?php
else: ?>
    <div class="container dash-container">
        <nav>
            <div class="logo">WA MONEY <small>PAINEL</small></div>
            <div style="display: flex; gap: 1.5rem; align-items: center;">
                <a href="logout.php" class="btn btn-secondary" style="padding: 0.5rem 1.2rem;">Sair</a>
            </div>
        </nav>

        <div class="dash-grid">
            <div class="premium-panel animate">
                <div style="display:flex; justify-content: space-between; align-items: center;">
                    <h2>Conexão Ativa</h2>
                    <div id="status-tag" style="padding: 5px 15px; border-radius: 50px; font-size: 0.8rem; font-weight: 700; background: #333;">OFFLINE</div>
                </div>
                
                <div id="qr-display-area" style="text-align: center; padding: 2rem 0;">
                    <div class="qr-box" id="qr-img-container">
                        <i class="fas fa-spinner fa-spin fa-2x" style="color: #666"></i>
                    </div>
                    <button class="btn btn-primary" id="btn-gen" onclick="generateSession()" style="width: 100%; max-width: 400px; margin-top: 1rem;">
                        <i class="fas fa-sync"></i> GERAR NOVO QR CODE
                    </button>
                </div>

                <div id="online-display-area" style="display: none; text-align: center; padding: 4rem 0;">
                    <div style="font-size: 5rem; color: var(--primary); margin-bottom: 2rem;"><i class="fas fa-check-circle"></i></div>
                    <h2>Sua rede está ativa!</h2>
                </div>
            </div>

            <div style="display:flex; flex-direction: column; gap: 2rem;">
                <div class="balance-card">
                    <div class="val" id="user-balance">R$ 0,00</div>
                    <p>Saque mínimo: R$ 20,00</p>
                </div>

                <div class="premium-panel">
                    <h3>Sacar via PIX</h3>
                    <input type="text" id="pix_key" class="form-control" placeholder="Chave PIX">
                    <button class="btn btn-primary" style="width: 100%;" onclick="payout()">RESGATAR AGORA</button>
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
                    updateUIStatus(d.instancia.status);
                    if (d.instancia.status === 'aguardando_qr' && !qrMonitor) startQR();
                    else if (d.instancia.status === 'conectado') stopQR();
                }
            } catch (e) { }
        }

        function updateUIStatus(status) {
            const tag = document.getElementById('status-tag');
            const qrA = document.getElementById('qr-display-area');
            const onA = document.getElementById('online-display-area');
            tag.innerText = status.toUpperCase();
            if (status === 'conectado') {
                tag.style.color = 'var(--primary)';
                qrA.style.display = 'none';
                onA.style.display = 'block';
            } else {
                tag.style.color = '#ef4444';
                qrA.style.display = 'block';
                onA.style.display = 'none';
            }
        }

        async function generateSession() {
            const btn = document.getElementById('btn-gen');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> GERANDO...';
            btn.disabled = true;

            try {
                const res = await fetch(API_URL + '?action=setup_instance');
                const d = await res.json();
                if (d.success) {
                    sessId = d.session_id;
                    startQR();
                } else {
                    Swal.fire('Erro', d.message || 'Erro ao iniciar', 'error');
                }
            } catch(e) {
                Swal.fire('Erro', 'Falha na rede', 'error');
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
                    box.innerHTML = `<img src="${d.qr}" style="width: 250px; height: 250px;" />`;
                } else if (d.status === 'connected') {
                    stopQR();
                    loadDash();
                }
            } catch (e) { }
        }

        async function payout() {
            const pix = document.getElementById('pix_key').value;
            if (!pix) return Swal.fire('Atenção', 'Chave PIX obrigatória', 'warning');
            const res = await fetch(API_URL + '?action=request_withdraw', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ valor: 20, pix_key: pix })
            });
            const d = await res.json();
            if (d.success) Swal.fire('Sucesso', d.message, 'success').then(() => loadDash());
            else Swal.fire('Erro', d.message, 'error');
        }

        loadDash();
        setInterval(loadDash, 10000);
    </script>
    <?php
endif; ?>
</body>
</html>