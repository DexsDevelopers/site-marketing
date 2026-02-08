<?php
require_once 'includes/config.php';
require_once 'includes/auth_helper.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketing Hub | Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main -->
        <main class="main-content">
            <header class="header">
                <div>
                    <h1 style="margin: 0; font-size: 2rem;">Overview</h1>
                    <p style="color: var(--text-dim);">Bem-vindo ao centro de comando do seu Bot.</p>
                </div>
                <!-- Bot Status Widget -->
                <div class="panel" style="margin-bottom: 0; padding: 1.2rem 2rem;">
                    <div id="bot-status-container" class="status-indicator">
                        <div class="dot" id="status-dot"></div>
                        <span id="status-text">Conectando...</span>
                    </div>
                </div>
            </header>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total de Leads</div>
                    <div class="stat-value" id="stat-leads-total">0</div>
                    <div style="color: #00ff88; font-size: 0.85rem;"><i class="fas fa-arrow-up"></i> +12% esse mês</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Em Progresso</div>
                    <div class="stat-value" id="stat-leads-ativo">0</div>
                    <div style="color: var(--text-dim); font-size: 0.85rem;">Fluxo ativo agora</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Concluídos</div>
                    <div class="stat-value" id="stat-leads-concluido">0</div>
                    <div style="color: #00ff88; font-size: 0.85rem;">Conversão direta</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Disparos Hoje</div>
                    <div class="stat-value" id="stat-envios-hoje" style="color: var(--primary);">0</div>
                    <div style="color: var(--text-dim); font-size: 0.85rem;">Mensagens enviadas</div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 350px; gap: 2rem;">
                <!-- Main Controls -->
                <section>
                    <div class="panel">
                        <div class="panel-title"><i class="fas fa-rocket"></i> Sequência do Funil</div>
                        <div id="funnel-container">
                            <p style="color: var(--text-dim);">Carregando mensagens...</p>
                        </div>
                        <button class="btn-modern" style="margin-top: 1rem;"><i class="fas fa-plus"></i> Nova
                            Etapa</button>
                    </div>
                </section>

                <!-- Sidebar Content -->
                <aside>
                    <div class="panel">
                        <div class="panel-title"><i class="fas fa-qrcode"></i> Conexão WhatsApp</div>
                        <div id="qr-container" class="qr-placeholder">
                            <span style="font-size: 0.8rem; text-align: center; color: var(--text-dim);">Aguardando
                                bot...</span>
                        </div>
                        <div id="bot-info" style="margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-dim);">
                            <!-- Info do Bot via JS -->
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-title"><i class="fas fa-history"></i> Atividade Recente</div>
                        <ul style="list-style: none; padding: 0; font-size: 0.85rem;" id="recent-activity">
                            <li style="padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.05);">Carregando
                                atividades...</li>
                        </ul>
                    </div>
                </aside>
            </div>
        </main>
    </div>

    <script>
        // Função para atualizar o status do Bot
        async function updateBotStatus() {
            try {
                const response = await fetch('api_dashboard.php?action=get_bot_status');
                const result = await response.json();

                const dot = document.getElementById('status-dot');
                const text = document.getElementById('status-text');
                const qrContainer = document.getElementById('qr-container');
                const botInfo = document.getElementById('bot-info');

                if (result.success && result.data.online) {
                    dot.classList.add('online');
                    text.innerText = result.data.ready ? 'Bot Online & Pronto' : 'Bot Conectado (Aguardando QR)';

                    botInfo.innerHTML = `
                        <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
                            <span>Uptime:</span>
                            <span style="color:white;">${result.data.uptime}</span>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span>Reconexões:</span>
                            <span style="color:white;">${result.data.reconnects}</span>
                        </div>
                    `;

                    if (!result.data.ready) {
                        loadQR();
                    } else {
                        qrContainer.innerHTML = '<div style="text-align:center;"><i class="fas fa-check-circle" style="font-size:3rem; color:#00ff88;"></i><p style="margin-top:1rem;">Autenticado</p></div>';
                    }
                } else {
                    dot.classList.remove('online');
                    text.innerText = 'Bot Desconectado';
                    qrContainer.innerHTML = '<span style="color:var(--primary);">Verifique o Painel PM2</span>';
                }
            } catch (e) {
                console.error('Erro ao buscar status:', e);
            }
        }

        async function loadQR() {
            const response = await fetch('api_dashboard.php?action=get_qr');
            const result = await response.json();
            if (result.success && result.qr) {
                document.getElementById('qr-container').innerHTML = `<img src="${result.qr}" class="qr-image">`;
            }
        }

        async function updateStats() {
            try {
                const response = await fetch('api_dashboard.php?action=get_stats');
                const result = await response.json();
                if (result.success) {
                    document.getElementById('stat-leads-total').innerText = result.data.leads_total;
                    document.getElementById('stat-leads-ativo').innerText = result.data.leads_ativo;
                    document.getElementById('stat-leads-concluido').innerText = result.data.leads_concluido;
                    document.getElementById('stat-envios-hoje').innerText = result.data.envios_hoje;
                }
            } catch (e) { }
        }

        // Funil mock (para visual)
        function loadFunnel() {
            const funnel = document.getElementById('funnel-container');
            funnel.innerHTML = `
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Conteúdo</th>
                            <th>Delay</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Olá! Tudo bem? Este é o seu primeiro passo...</td>
                            <td>Imediato</td>
                            <td><i class="fas fa-edit" style="cursor:pointer;"></i></td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Passando para lembrar que temos uma novidade...</td>
                            <td>2 horas</td>
                            <td><i class="fas fa-edit" style="cursor:pointer;"></i></td>
                        </tr>
                    </tbody>
                </table>
            `;
        }

        async function updateActivity() {
            try {
                const response = await fetch('api_dashboard.php?action=get_recent_activity');
                const result = await response.json();
                const list = document.getElementById('recent-activity');
                if (result.success && result.data.length > 0) {
                    list.innerHTML = result.data.map(log => `
                        <li style="padding: 0.8rem 0; border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <div style="color:white; font-weight:500;">${log.numero_origem}</div>
                            <div style="font-size:0.75rem; margin:0.2rem 0;">${log.criado_em}</div>
                            <div style="color:var(--text-dim); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${log.resposta_enviada}</div>
                        </li>
                    `).join('');
                } else {
                    list.innerHTML = '<li style="color:var(--text-dim);">Nenhuma atividade recente.</li>';
                }
            } catch (e) { }
        }

        // Init
        updateBotStatus();
        updateStats();
        loadFunnel();
        updateActivity();
        setInterval(updateBotStatus, 10000);
        setInterval(updateStats, 30000);
        setInterval(updateActivity, 15000);
    </script>
</body>

</html>