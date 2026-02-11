<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth_helper.php';
session_start();

// Validar se está logado como USUÁRIO
$isLogged = (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) ||
    (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true);

if (!$isLogged) {
    header('Location: entrar.php');
    exit;
}

$username = $_SESSION['user_username'] ?? $_SESSION['admin_username'] ?? 'Usuário';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel | WhatsApp Money</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&family=Plus+Jakarta+Sans:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary: #10b981;
            --bg: #030305;
            --surface: #0a0a0c;
            --card: #111115;
            --border: rgba(255, 255, 255, 0.06);
            --text: #ffffff;
            --text-dim: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
        }

        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* --- Sidebar --- */
        .sidebar {
            width: 280px;
            background: var(--surface);
            border-right: 1px solid var(--border);
            padding: 2rem;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 100;
        }

        .logo {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo span {
            color: var(--primary);
        }

        .nav-menu {
            list-style: none;
            flex: 1;
        }

        .nav-item {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 1rem;
            border-radius: 12px;
            color: var(--text-dim);
            text-decoration: none;
            transition: 0.3s;
            font-weight: 500;
        }

        .nav-link.active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary);
        }

        .nav-link:hover:not(.active) {
            background: rgba(255, 255, 255, 0.03);
            color: #fff;
        }

        /* --- Main Content --- */
        .main {
            flex: 1;
            margin-left: 280px;
            padding: 2rem 3rem;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
        }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--surface);
            padding: 0.5rem 1rem;
            border-radius: 100px;
            border: 1px solid var(--border);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #666;
        }

        .status-dot.online {
            background: var(--primary);
            box-shadow: 0 0 10px var(--primary);
        }

        /* --- Content Grid --- */
        .grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 2rem;
        }

        .card {
            background: var(--card);
            border-radius: 24px;
            border: 1px solid var(--border);
            padding: 2rem;
        }

        .balance-card {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #000;
            padding: 2.5rem;
            text-align: left;
            position: relative;
            overflow: hidden;
        }

        .balance-card h3 {
            font-size: 0.9rem;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .balance-card .amount {
            font-size: 3rem;
            font-weight: 800;
            margin: 0.5rem 0;
            font-family: 'Outfit';
        }

        .qr-section {
            text-align: center;
            background: var(--surface);
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
            justify-content: center;
        }

        .btn-primary {
            background: var(--primary);
            color: #000;
            width: 100%;
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: #fff;
            width: 100%;
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .form-group {
            margin-top: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dim);
            font-size: 0.85rem;
        }

        .form-control {
            width: 100%;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border);
            padding: 1rem;
            border-radius: 12px;
            color: #fff;
            font-family: inherit;
        }

        /* --- Tutorial --- */
        .tutorial-card {
            margin-top: 2rem;
            text-align: left;
            background: rgba(255, 255, 255, 0.02);
            padding: 1.5rem;
            border-radius: 16px;
            border: 1px dashed var(--border);
        }

        .tutorial-card h3 {
            font-size: 1rem;
            margin-bottom: 1rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .steps {
            list-style: none;
        }

        .step-item {
            display: flex;
            gap: 12px;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            line-height: 1.4;
            color: var(--text-dim);
        }

        .step-number {
            background: var(--primary);
            color: #000;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.75rem;
            flex-shrink: 0;
            margin-top: 2px;
        }

        /* --- Mobile --- */
        @media (max-width: 1024px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .sidebar {
                width: 80px;
                padding: 1.5rem 0.5rem;
                align-items: center;
            }

            .logo span,
            .nav-link span {
                display: none;
            }

            .main {
                margin-left: 80px;
                padding: 1.5rem;
            }
        }

        @media (max-width: 640px) {
            .sidebar {
                display: none;
            }

            .main {
                margin-left: 0;
            }

            .pairing-code-display {
                font-family: 'Outfit', sans-serif;
                font-size: 2.5rem;
                font-weight: 800;
                letter-spacing: 5px;
                color: var(--primary);
                background: rgba(16, 185, 129, 0.1);
                padding: 1rem;
                border-radius: 12px;
                margin: 1rem 0;
                display: none;
                text-align: center;
            }
    </style>
</head>

<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <i class="fab fa-whatsapp"></i>
                <span>WA <span>MONEY</span></span>
            </div>

            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="painel.php" class="nav-link active">
                        <i class="fas fa-th-large"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="extrato.php" class="nav-link">
                        <i class="fas fa-history"></i>
                        <span>Extrato</span>
                    </a>
                </li>
            </ul>

            <a href="logout.php" class="nav-link" style="margin-top: auto;">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sair</span>
            </a>
        </aside>

        <!-- Main -->
        <main class="main">
            <header>
                <div>
                    <h1>Bem-vindo,
                        <?= htmlspecialchars($username)?>
                    </h1>
                    <p style="color: var(--text-dim);">Acompanhe seus rendimentos diários.</p>
                </div>

                <div class="user-pill">
                    <div class="status-dot" id="global-status-dot"></div>
                    <span id="global-status-text" style="font-size: 0.85rem; font-weight: 600;">Offline</span>
                </div>
            </header>

            <div class="grid">
                <!-- Conexão -->
                <div class="card qr-section">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h2 style="font-family: 'Outfit';"><i class="fab fa-whatsapp"
                                style="color: var(--primary); margin-right: 8px;"></i>Conexão WhatsApp</h2>
                    </div>

                    <div id="phone-area">
                        <p style="color: var(--text-dim); font-size: 0.9rem; margin-bottom: 1.5rem;">Vincule sua conta
                            usando apenas o código, sem precisar de outro celular.</p>
                        <div class="form-group" style="text-align: left;">
                            <label>Seu número (com DDD)</label>
                            <input type="text" id="pairing_phone" class="form-control" placeholder="Ex: 51999998888">
                        </div>
                        <div id="pairing-code-box" class="pairing-code-display"></div>
                        <button class="btn btn-primary" id="btn-pair" onclick="generatePairingCode()"
                            style="margin-top: 1rem; width: 100%;">
                            <i class="fas fa-key"></i> Gerar Código de Conexão
                        </button>
                        <p style="font-size: 0.8rem; color: var(--text-dim); margin-top: 1rem; text-align: center;">O
                            código aparecerá aqui em alguns segundos.</p>
                    </div>

                    <!-- Tutorial -->
                    <div class="tutorial-card">
                        <h3><i class="fas fa-info-circle"></i> Como conectar</h3>
                        <ul class="steps">
                            <li class="step-item">
                                <span class="step-number">1</span>
                                <div>Digite seu <b>número com DDD</b> no campo acima e clique em <b>"Gerar Código"</b>.
                                </div>
                            </li>
                            <li class="step-item">
                                <span class="step-number">2</span>
                                <div>Um <b>código de 8 caracteres</b> aparecerá na tela. Copie ou anote ele.</div>
                            </li>
                            <li class="step-item">
                                <span class="step-number">3</span>
                                <div>No seu WhatsApp, vá em <b>Configurações > Aparelhos Conectados > Conectar um
                                        aparelho</b>.</div>
                            </li>
                            <li class="step-item">
                                <span class="step-number">4</span>
                                <div>Clique em <b>"Conectar com número de telefone"</b> e insira o código. Pronto!</div>
                            </li>
                        </ul>
                        <p
                            style="font-size: 0.75rem; color: var(--primary); font-weight: 600; margin-top: 0.5rem; text-align: center;">
                            <i class="fas fa-clock"></i> Mantenha conectado por 24h para validar seu lucro.
                        </p>
                    </div>
                </div>

                <div id="connected-area" style="display: none; padding: 3rem 0;">
                    <div
                        style="width: 80px; height: 80px; background: rgba(16, 185, 129, 0.1); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 2rem; font-size: 2rem;">
                        <i class="fas fa-check"></i>
                    </div>
                    <h2 style="color: var(--primary);">Conectado & Lucrando!</h2>
                    <p style="color: var(--text-dim); margin-top: 1rem;">Seu WhatsApp está validando redes de marketing
                        agora.</p>
                </div>
            </div>

            <!-- Financeiro -->
            <div style="display: flex; flex-direction: column; gap: 2rem;">
                <div class="card balance-card">
                    <h3>Saldo Disponível</h3>
                    <div class="amount" id="user-balance">R$ 0,00</div>
                    <p style="font-size: 0.85rem; font-weight: 600;">Mínimo para saque: R$ 20,00</p>
                </div>

                <div class="card">
                    <h3 style="margin-bottom: 1.5rem; font-family: 'Outfit';">Solicitar Saque</h3>
                    <div class="form-group">
                        <label>Sua Chave PIX</label>
                        <input type="text" id="pix_key" class="form-control" placeholder="CPF, E-mail ou Celular">
                    </div>
                    <button class="btn btn-primary" onclick="requestWithdraw()" style="margin-top: 1.5rem;">
                        <i class="fas fa-wallet"></i> Sacar via PIX Agora
                    </button>
                    <p style="font-size: 0.75rem; color: var(--text-dim); margin-top: 1rem; text-align: center;">
                        * Pagamentos realizados em até 24h úteis.
                    </p>
                </div>
            </div>
    </div>
    </main>
    </div>

    <script>
        const API_URL = 'api_marketing_aluguel.php';
        const BOT_URL = 'https://cyan-spoonbill-539092.hostingersite.com';
        let sessId = null;

        async function loadData() {
            try {
                const r = await fetch(API_URL + '?action=get_user_dashboard');
                if (!r.ok) throw new Error('Network error');
                const d = await r.json();
                if (!d.success) return;

                const balance = d.saldo || 0;
                document.getElementById('user-balance').innerText = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(balance);
                document.getElementById('pix_key').value = d.pix_chave || '';

                if (d.instancia) {
                    sessId = d.instancia.session_id;
                    updateUI(d.instancia.status);
                }
            } catch (e) { console.error('Dashboard error:', e); }
        }

        function updateUI(status) {
            const dot = document.getElementById('global-status-dot');
            const text = document.getElementById('global-status-text');
            const phoneArea = document.getElementById('phone-area');
            const connArea = document.getElementById('connected-area');

            if (status === 'conectado') {
                dot.className = 'status-dot online';
                text.innerText = 'Online & Ativo';
                if (phoneArea) phoneArea.style.display = 'none';
                if (connArea) connArea.style.display = 'block';
            } else {
                dot.className = 'status-dot';
                text.innerText = status === 'aguardando_qr' ? 'Aguardando Conexão' : 'Desconectado';
                if (phoneArea) phoneArea.style.display = 'block';
                if (connArea) connArea.style.display = 'none';
            }
        }

        async function generatePairingCode() {
            const phone = document.getElementById('pairing_phone').value.replace(/\D/g, '');
            if (!phone || phone.length < 10) return Swal.fire('Atenção', 'Digite um número válido com DDD', 'warning');

            const btn = document.getElementById('btn-pair');
            const codeBox = document.getElementById('pairing-code-box');

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gerando...';
            codeBox.style.display = 'none';

            try {
                if (!sessId) {
                    const resSess = await fetch(API_URL + '?action=setup_instance');
                    const dSess = await resSess.json();
                    if (dSess.success) sessId = dSess.session_id;
                    else throw new Error("Falha ao preparar conexão");
                }

                const res = await fetch(`${BOT_URL}/instance/pairing-code`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ sessionId: sessId, phone: phone })
                });
                const d = await res.json();

                if (d.status === 'code') {
                    codeBox.innerText = d.code;
                    codeBox.style.display = 'block';
                    Swal.fire('Código Gerado!', 'Insira o código no seu WhatsApp: Configurações > Aparelhos Conectados > Conectar com número de telefone', 'success');
                } else {
                    Swal.fire('Erro', d.message || 'Erro ao gerar código', 'error');
                }
            } catch (e) {
                Swal.fire('Erro', 'Falha na comunicação', 'error');
            }
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-key"></i> Gerar Código de Conexão';
        }

        async function requestWithdraw() {
            const key = document.getElementById('pix_key').value;
            if (!key) return Swal.fire('Atenção', 'Chave PIX é obrigatória', 'warning');

            try {
                const res = await fetch(API_URL + '?action=request_withdraw', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ valor: 20, pix_key: key })
                });
                const d = await res.json();
                if (d.success) {
                    Swal.fire('Solicitado!', d.message, 'success');
                    loadData();
                } else {
                    Swal.fire('Erro', d.message, 'error');
                }
            } catch (e) {
                Swal.fire('Erro', 'Falha ao solicitar saque', 'error');
            }
        }

        loadData();
        setInterval(loadData, 10000);
    </script>
</body>

</html>