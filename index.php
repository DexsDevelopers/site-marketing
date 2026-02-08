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
            <header class="header animate-fade-in">
                <div>
                    <h1 style="margin: 0; font-size: 2.5rem; letter-spacing: -1.5px;">Dashboard</h1>
                    <p style="color: var(--text-dim); margin-top: 0.5rem;">Acompanhe em tempo real o desempenho da sua
                        operação.</p>
                </div>

                <div style="display: flex; gap: 1rem; align-items: center;">
                    <button class="btn-modern accent" onclick="triggerDisparos()" id="btn-trigger">
                        <i class="fas fa-paper-plane"></i> Iniciar Disparos Agora
                    </button>

                    <div class="panel" style="margin-bottom: 0; padding: 0.8rem 1.5rem; border-radius: 16px;">
                        <div id="bot-status-container" class="status-indicator">
                            <div class="dot" id="status-dot"></div>
                            <span id="status-text" style="font-weight: 600; font-size: 0.9rem;">Conectando...</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Stats Grid -->
            <div class="stats-grid animate-fade-in" style="animation-delay: 0.1s;">
                <div class="stat-card">
                    <div class="stat-label">Total de Leads</div>
                    <div class="stat-value" id="stat-leads-total">0</div>
                    <div style="color: #4facfe; font-size: 0.8rem; font-weight: 600;"><i class="fas fa-users"></i> Base
                        atual de contatos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Em Progresso</div>
                    <div class="stat-value" id="stat-leads-ativo" style="color: #f59e0b;">0</div>
                    <div style="color: #f59e0b; font-size: 0.8rem; font-weight: 600;"><i
                            class="fas fa-spinner fa-spin"></i> No fluxo de hoje</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Concluídos</div>
                    <div class="stat-value" id="stat-leads-concluido" style="color: #10b981;">0</div>
                    <div style="color: #10b981; font-size: 0.8rem; font-weight: 600;"><i
                            class="fas fa-check-circle"></i> Funil finalizado</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Disparos Hoje</div>
                    <div class="stat-value" id="stat-envios-hoje" style="color: var(--primary);">0</div>
                    <div style="color: var(--primary); font-size: 0.8rem; font-weight: 600;"><i
                            class="fas fa-whatsapp"></i> Total de mensagens</div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 380px; gap: 2.5rem;" class="animate-fade-in"
                style="animation-delay: 0.2s;">
                <!-- Main Controls -->
                <section>
                    <div class="panel">
                        <div class="panel-title">
                            <i class="fas fa-layer-group"></i>
                            Sequência Ativa do Funil
                        </div>
                        <div id="funnel-container" style="min-height: 200px;">
                            <p style="color: var(--text-dim); text-align: center; padding: 3rem;">Carregando
                                mensagens...</p>
                        </div>
                        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                            <a href="funnel.php" class="btn-modern" style="flex: 1; justify-content: center;">
                                <i class="fas fa-edit"></i> Editar Funil Completo
                            </a>
                            <a href="leads.php" class="btn-modern secondary" style="flex: 1; justify-content: center;">
                                <i class="fas fa-users"></i> Ver Todos os Leads
                            </a>
                        </div>
                    </div>
                </section>

                <!-- Sidebar Content -->
                <aside>
                    <div class="panel">
                        <div class="panel-title"><i class="fas fa-qrcode"></i> Conexão WhatsApp</div>
                        <div id="qr-container" class="qr-placeholder" style="width: 100%; height: 280px;">
                            <div style="text-align: center;">
                                <i class="fas fa-circle-notch fa-spin fa-2x"
                                    style="color: var(--primary); margin-bottom: 1rem;"></i>
                                <p style="font-size: 0.85rem; color: var(--text-dim);">Sincronizando com o robô...</p>
                            </div>
                        </div>
                        <div id="bot-info"
                            style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--glass-border);">
                            <!-- Info do Bot via JS -->
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-title" style="margin-bottom: 1.5rem;"><i class="fas fa-history"></i> Atividade
                            Recente</div>
                        <div id="recent-activity-container"
                            style="max-height: 400px; overflow-y: auto; padding-right: 0.5rem;">
                            <ul style="list-style: none; padding: 0; display: flex; flex-direction: column; gap: 1rem;"
                                id="recent-activity">
                                <li style="text-align: center; color: var(--text-dim); padding: 2rem;">Buscando Logs...
                                </li>
                            </ul>
                        </div>
                    </div>
                </aside>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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