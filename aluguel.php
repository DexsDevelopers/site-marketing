<?php
require_once 'includes/config.php';
require_once 'includes/auth_helper.php';
require_once 'includes/db_connect.php';
requireLogin();

$userId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conecte e Ganhe | Marketing Hub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .earn-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        .qr-container {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #111;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .status-ONLINE {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        .status-OFFLINE {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        .balance-card {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .progress-bar {
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
            margin: 1rem 0;
        }

        .progress-fill {
            height: 100%;
            background: #10b981;
            width: 0%;
            transition: width 0.5s;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="header animate-fade-in">
                <div>
                    <h1>Conecte seu WhatsApp e Ganhe</h1>
                    <p style="color: var(--text-dim);">Ganhe R$ 20,00 por dia mantendo seu número conectado para nossa
                        rede de marketing.</p>
                </div>
            </header>

            <div class="earn-grid animate-fade-in">
                <!-- Coluna 1: Status e QR -->
                <div class="panel">
                    <div class="panel-title">
                        <span><i class="fab fa-whatsapp"></i> Minha Conexão</span>
                        <span id="conn-status" class="status-badge status-OFFLINE">DESCONECTADO</span>
                    </div>

                    <div id="qr-wrapper" style="margin-top: 1.5rem;">
                        <p style="color: var(--text-dim); margin-bottom: 1rem; font-size: 0.9rem;">Escaneie o código
                            abaixo para começar a faturar:</p>
                        <div class="qr-container" id="qr-display">
                            <i class="fas fa-spinner fa-spin fa-2x" style="color: #666"></i>
                        </div>
                        <button class="btn-modern accent" style="width: 100%; margin-top: 1.5rem;"
                            onclick="startSession()">
                            <i class="fas fa-sync"></i> Gerar Novo QR Code
                        </button>
                    </div>

                    <div id="connected-info" style="display: none; text-align: center; padding: 2rem;">
                        <div style="font-size: 4rem; color: #10b981; margin-bottom: 1rem;"><i
                                class="fas fa-check-circle"></i></div>
                        <h3>WhatsApp Conectado!</h3>
                        <p style="color: var(--text-dim);">Seu número está gerando lucros agora.</p>
                    </div>
                </div>

                <!-- Coluna 2: Saldo e Ganhos -->
                <div>
                    <div class="stat-card balance-card">
                        <div class="stat-label" style="color: rgba(255,255,255,0.8)">Saldo Acumulado</div>
                        <div class="stat-value" id="user-balance">R$ 0,00</div>
                        <div style="margin-top: 1rem; font-size: 0.85rem; font-weight: 500;">
                            <i class="fas fa-info-circle"></i> Pagamento de R$ 20,00 a cada 24h online.
                        </div>
                    </div>

                    <div class="panel" style="margin-top: 1.5rem;">
                        <div class="panel-title"><span><i class="fas fa-wallet"></i> Sacar Ganhos</span></div>

                        <div style="margin: 1.5rem 0;">
                            <label
                                style="color: var(--text-dim); font-size: 0.85rem; display: block; margin-bottom: 0.5rem;">Chave
                                PIX para Recebimento</label>
                            <input type="text" id="pix_key" class="form-control" placeholder="CPF, E-mail ou Celular"
                                style="width: 100%;">
                        </div>

                        <button class="btn-modern" style="width: 100%; background: #3b82f6;"
                            onclick="requestWithdraw()">
                            <i class="fas fa-hand-holding-usd"></i> Solicitar Saque (Mín. R$ 20)
                        </button>
                    </div>

                    <div class="panel" style="margin-top: 1.5rem;">
                        <div class="panel-title"><span><i class="fas fa-history"></i> Progresso do Dia</span></div>
                        <div id="uptime-label" style="font-size: 0.9rem; margin-bottom: 0.5rem;">0h / 24h</div>
                        <div class="progress-bar">
                            <div id="uptime-progress" class="progress-fill"></div>
                        </div>
                        <p style="font-size: 0.75rem; color: var(--text-dim);">O progresso aumenta automaticamente
                            enquanto você estiver online.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let sessionId = null;
        const BOT_URL = window.location.origin.includes('localhost') ? 'http://localhost:3002' : 'https://cyan-spoonbill-539092.hostingersite.com';

        async function loadDashboard() {
            try {
                const res = await fetch('api_marketing_aluguel.php?action=get_user_dashboard');
                const data = await res.json();

                if (data.success) {
                    document.getElementById('user-balance').textContent = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(data.saldo);
                    document.getElementById('pix_key').value = data.pix_chave || '';

                    if (data.instancia) {
                        sessionId = data.instancia.session_id;
                        updateStatusUI(data.instancia.status);

                        const seconds = data.instancia.uptime_total_segundos % 86400;
                        const hours = (seconds / 3600).toFixed(1);
                        document.getElementById('uptime-label').textContent = `${hours}h / 24h`;
                        document.getElementById('uptime-progress').style.width = `${(seconds / 86400) * 100}%`;

                        if (data.instancia.status === 'aguardando_qr') {
                            showQR();
                        }
                    }
                }
            } catch (e) {
                console.error("Erro ao carregar dashboard", e);
            }
        }

        function updateStatusUI(status) {
            const badge = document.getElementById('conn-status');
            const qrWrapper = document.getElementById('qr-wrapper');
            const connectedInfo = document.getElementById('connected-info');

            badge.textContent = status.toUpperCase();
            badge.className = `status-badge status-${status === 'conectado' ? 'ONLINE' : 'OFFLINE'}`;

            if (status === 'conectado') {
                qrWrapper.style.display = 'none';
                connectedInfo.style.display = 'block';
            } else {
                qrWrapper.style.display = 'block';
                connectedInfo.style.display = 'none';
            }
        }

        async function startSession() {
            const res = await fetch('api_marketing_aluguel.php?action=setup_instance');
            const data = await res.json();
            if (data.success) {
                sessionId = data.session_id;
                showQR();
            }
        }

        function showQR() {
            if (!sessionId) return;
            const display = document.getElementById('qr-display');
            display.innerHTML = `<iframe src="${BOT_URL}/instance/qr/${sessionId}" style="border:none; width:300px; height:300px; overflow:hidden"></iframe>`;
        }

        async function requestWithdraw() {
            const pix = document.getElementById('pix_key').value;
            if (!pix) return Swal.fire('Erro', 'Insira sua chave PIX', 'error');

            const res = await fetch('api_marketing_aluguel.php?action=request_withdraw', {
                method: 'POST',
                body: JSON.stringify({ valor: 20, pix_key: pix })
            });
            const data = await res.json();

            if (data.success) {
                Swal.fire('Sucesso', 'Saque solicitado!', 'success').then(() => loadDashboard());
            } else {
                Swal.fire('Erro', data.message, 'error');
            }
        }

        loadDashboard();
        setInterval(loadDashboard, 10000);
    </script>
</body>

</html>