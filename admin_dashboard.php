<?php
require_once 'includes/config.php';
require_once 'includes/auth_helper.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Marketing Hub | Dashboard</title>

    <!-- PWA -->
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#0a0a0c">
    <link rel="apple-touch-icon" href="https://cdn-icons-png.flaticon.com/512/124/124034.png">

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ff3b3b;
            --primary-glow: rgba(255, 59, 59, 0.3);
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
        }

        body {
            font-family: 'Outfit', sans-serif;
        }

        .instance-card {
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 1.5rem;
            transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .instance-card:hover {
            transform: translateY(-8px) scale(1.02);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        }

        .instance-card.ready:hover {
            box-shadow: 0 20px 40px rgba(16, 185, 129, 0.15);
        }

        .instance-card.syncing:hover {
            box-shadow: 0 20px 40px rgba(245, 158, 11, 0.15);
        }

        .instance-name {
            font-size: 1.1rem;
            font-weight: 800;
            color: #fff;
            margin: 0;
            letter-spacing: -0.5px;
            font-family: 'Outfit', sans-serif;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 100px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .status-badge.syncing {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .status-badge i {
            font-size: 0.6rem;
        }

        .btn-delete {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-delete:hover {
            background: var(--danger);
            color: #fff;
            transform: scale(1.1);
        }

        .qr-container {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 15px;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 140px;
            margin-top: 1rem;
            border: 1px solid var(--glass-border);
            position: relative;
        }

        .qr-placeholder-text {
            color: var(--text-dim);
            font-size: 0.8rem;
            text-align: center;
            margin-top: 0.5rem;
        }

        .pairing-form {
            margin-top: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .modern-input {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 10px 15px;
            color: #fff;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .modern-input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(0, 0, 0, 0.6);
            box-shadow: 0 0 0 3px rgba(255, 59, 59, 0.1);
        }

        .btn-pairing {
            background: linear-gradient(135deg, var(--primary) 0%, #ff6b6b 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 10px;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(255, 59, 59, 0.3);
        }

        .btn-pairing:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 59, 59, 0.4);
            filter: brightness(1.1);
        }

        /* Mascot / Loading Animation */
        .mascot-container {
            position: relative;
            width: 60px;
            height: 60px;
            margin-bottom: 0.5rem;
        }

        .mascot-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 0 10px var(--primary-glow));
        }

        .pulse-loader {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: var(--primary-glow);
            animation: pulse-ring 2s infinite;
            z-index: -1;
        }

        @keyframes pulse-ring {
            0% { transform: translate(-50%, -50%) scale(0.3); opacity: 0.8; }
            100% { transform: translate(-50%, -50%) scale(1.2); opacity: 0; }
        }

        /* Pulse for online dot */
        .dot.pulse {
            animation: dot-pulse 1.5s infinite;
        }

        @keyframes dot-pulse {
            0% { box-shadow: 0 0 0 0 rgba(0, 255, 136, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(0, 255, 136, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 255, 136, 0); }
        }
    </style>
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
                    <button class="btn-modern secondary" onclick="runMigration()" title="Atualizar Banco de Dados">
                        <i class="fas fa-database"></i> Migrar DB
                    </button>
                    <button class="btn-modern accent" onclick="triggerDisparos()" id="btn-trigger">
                        <i class="fas fa-paper-plane"></i> Iniciar Disparos Agora
                    </button>
                    <!-- Status Indicator ... -->

                    <div class="panel" style="margin-bottom: 0; padding: 0.8rem 1.5rem; border-radius: 16px;">
                        <div id="sys-status-container" class="status-indicator">
                            <div class="dot online" id="status-dot"></div>
                            <span id="status-text" style="font-weight: 600; font-size: 0.9rem;">Sistema Online</span>
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

            <div class="admin-grid animate-fade-in" style="animation-delay: 0.2s;">
                <!-- Main Controls -->
                <section>
                    <div class="panel">
                        <div class="panel-title">
                            <i class="fas fa-shield-virus"></i>
                            Segurança & Anti-Ban
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 1.2rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 16px; border: 1px solid var(--glass-border);">
                                <div>
                                    <div style="font-weight: 600; color: #fff;">Aquecimento Gradual</div>
                                    <div style="font-size: 0.75rem; color: var(--text-dim);">Aumenta os envios conforme a maturação do chip.</div>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="check-warming" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 16px; border: 1px solid var(--glass-border);">
                                <div>
                                    <div style="font-weight: 600; color: #fff;">Anti-Ban (SpinTax + Salt)</div>
                                    <div style="font-size: 0.75rem; color: var(--text-dim);">Gera mensagens únicas para cada contato.</div>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="check-antiban" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="form-group" style="margin: 0; background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 16px; border: 1px solid var(--glass-border);">
                                <label style="margin-bottom: 0.8rem; display: block; font-weight: 600; color: #fff;"><i class="fas fa-clock"></i> Janela de Horário</label>
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <input type="time" id="time-start" value="08:00" class="modern-input" style="flex: 1;">
                                    <span style="color: var(--text-dim);">até</span>
                                    <input type="time" id="time-end" value="20:00" class="modern-input" style="flex: 1;">
                                </div>
                            </div>
                            
                            <button onclick="saveSecuritySettings()" class="btn-modern secondary" style="width: 100%; justify-content: center; background: rgba(255,255,255,0.05);">
                                <i class="fas fa-save"></i> Salvar Configurações de Segurança
                            </button>
                        </div>
                    </div>

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

                <!-- Sidebar Content (Gerenciador de Chips) -->
                <aside>
                    <div class="panel" style="padding: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <div class="panel-title" style="margin: 0;"><i class="fas fa-mobile-alt"></i> Gerenciador de Chips</div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <button onclick="updateBotStatus()" title="Atualizar Status" style="background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); color: var(--text-dim); width: 32px; height: 32px; border-radius: 8px; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.color='white'; this.style.borderColor='rgba(255,255,255,0.2)'" onmouseout="this.style.color='var(--text-dim)'; this.style.borderColor='var(--glass-border)'">
                                    <i class="fas fa-sync-alt" id="refresh-icon"></i>
                                </button>
                                <span id="chip-count-badge" style="background: rgba(16,185,129,0.2); color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: bold;">0 Conectados</span>
                            </div>
                        </div>
                        
                        <!-- Lista Dinâmica de instâncias -->
                        <div id="instances-list" style="display: flex; flex-direction: column; gap: 1rem; max-height: 480px; overflow-y: auto; padding-right: 5px;">
                            <div style="text-align: center; color: var(--text-dim); padding: 2rem;">
                                <i class="fas fa-circle-notch fa-spin fa-2x"></i><br><br>Carregando chips...
                            </div>
                        </div>

                        <!-- Adicionar Novo Chip Btn -->
                        <div style="margin-top: 1.5rem; border-top: 1px dashed rgba(255,255,255,0.1); padding-top: 1.5rem;">
                             <button onclick="openNewNodeModal()" class="btn-modern accent" style="width: 100%; justify-content: center; background: linear-gradient(135deg, rgba(16,185,129,0.2) 0%, rgba(16,185,129,0.1) 100%); color: #10b981; border: 1px solid rgba(16,185,129,0.3); border-radius: 16px; font-weight: 700; height: 50px; transition: all 0.3s;">
                                 <i class="fas fa-plus-circle" style="font-size: 1.1rem;"></i> Conectar Novo Número
                             </button>
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
        // Store interval references per session to avoid infinite overlapping loops if QR code is polling
        window.qrPollIntervals = {};
        window.activeInstanceData = {}; // Cache current instances state
        window.userInputCache = {}; // Cache phone inputs to prevent loss on re-render

        async function updateBotStatus() {
            const refreshIcon = document.getElementById('refresh-icon');
            if(refreshIcon) refreshIcon.classList.add('fa-spin');
            
            try {
                const response = await fetch('api_dashboard.php?action=get_bot_status');
                const result = await response.json();
                
                const listContainer = document.getElementById('instances-list');
                const badge = document.getElementById('chip-count-badge');

                if (result.success && result.data.online) {
                    const instances = result.data.instances || [];
                    
                    let readyCount = instances.filter(i => i.isReady).length;
                    badge.innerText = `${readyCount} Conectados`;
                    
                    if (instances.length === 0) {
                        listContainer.innerHTML = '<div style="text-align: center; color: var(--text-dim); padding: 2rem;">Nenhum chip conectado ainda. Clique abaixo para começar.</div>';
                        window.activeInstanceData = {};
                        return;
                    }

                    // Save current inputs before re-render
                    instances.forEach(inst => {
                        const input = document.getElementById(`phone-${inst.sessionId}`);
                        if (input) window.userInputCache[inst.sessionId] = input.value;
                    });

                    // Build the list HTML
                    let htmlString = '';
                    instances.forEach(inst => {
                        htmlString += buildInstanceCard(inst);
                    });
                    
                    // Update DOM
                    listContainer.innerHTML = htmlString;
                    window.activeInstanceData = result.data.instances;
                    
                    // Restore inputs and trigger QR loaders
                    instances.forEach(inst => {
                        // Restore input value
                        const input = document.getElementById(`phone-${inst.sessionId}`);
                        if (input && window.userInputCache[inst.sessionId]) {
                            input.value = window.userInputCache[inst.sessionId];
                        }

                        if (!inst.isReady) {
                            // If not polling yet, start
                            if (!window.qrPollIntervals[inst.sessionId]) {
                                loadQR(inst.sessionId);
                            }
                        } else {
                           // If it became ready, clear any polling
                           if(window.qrPollIntervals[inst.sessionId]) {
                               clearInterval(window.qrPollIntervals[inst.sessionId]);
                               delete window.qrPollIntervals[inst.sessionId];
                           }
                        }
                    });

                } else {
                    listContainer.innerHTML = '<div style="text-align: center; color: #danger; padding: 2.5rem; background: rgba(239, 68, 68, 0.05); border-radius: 20px; border: 1px dashed rgba(239, 68, 68, 0.2);"><i class="fas fa-server fa-2x" style="margin-bottom:1rem; opacity: 0.5;"></i><br><span style="font-weight: 600;">Node.js Desconectado</span><p style="font-size: 0.8rem; color: var(--text-dim); margin-top: 10px;">O servidor de automação não está respondendo.</p></div>';
                }
            } catch (e) {
                console.error('Erro ao buscar status:', e);
            } finally {
                if(refreshIcon) setTimeout(() => refreshIcon.classList.remove('fa-spin'), 500);
            }
        }

        function formatUptime(seconds) {
            if(!seconds) return "0s";
             const d = Math.floor(seconds / (3600*24));
             const h = Math.floor(seconds % (3600*24) / 3600);
             const m = Math.floor(seconds % 3600 / 60);
             if(d > 0) return `${d}d ${h}h`;
             if(h > 0) return `${h}h ${m}m`;
             return `${m}m`;
        }

        function buildInstanceCard(inst) {
            const sid = inst.sessionId;
            const isReady = inst.isReady;
            const uptimeStr = isReady ? formatUptime(inst.uptime) : '-';
            
            // Clean ID for display name (if valid phone)
            let dName = sid;
            if(sid.length > 9) dName = sid.replace(/(\d{2})(\d{2})(\d{5})(\d{4})/, '+$1 ($2) $3-$4');
            
            let statusBadge = isReady 
               ? `<span class="status-badge active"><i class="fas fa-check-circle"></i> Ativo</span>`
               : `<span class="status-badge syncing"><i class="fas fa-sync-alt fa-spin"></i> Sincronizando</span>`;

            return `
                <div class="instance-card ${isReady ? 'ready' : 'syncing'}" id="card-${sid}">
                    <div class="instance-header">
                        <div>
                            <h4 class="instance-name">${dName}</h4>
                            ${statusBadge}
                        </div>
                        <button class="btn-delete" onclick="removeInstance('${sid}')" title="Desconectar / Excluir">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>

                    ${isReady ? `
                    <div style="margin-top: 1rem; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 0.8rem; font-size: 0.8rem; color: var(--text-dim); display: flex; justify-content: space-between; align-items: center;">
                         <span><i class="fas fa-clock" style="margin-right: 5px;"></i> Uptime: <strong style="color:white; font-weight: 600;">${uptimeStr}</strong></span>
                         <span style="color: var(--success);"><i class="fas fa-shield-alt"></i> Protegido</span>
                    </div>
                    ` : `
                    <div id="qr-box-${sid}" class="qr-container">
                        <div class="mascot-container">
                            <div class="pulse-loader"></div>
                            <img src="https://cdn-icons-png.flaticon.com/512/616/616412.png" alt="Mascot">
                        </div>
                        <p class="qr-placeholder-text">Preparando Código...</p>
                    </div>
                    <div class="pairing-form">
                         <input type="text" id="phone-${sid}" class="modern-input" placeholder="Digite seu N° com DDD">
                         <button onclick="requestPairing('${sid}')" class="btn-pairing">
                            <i class="fas fa-key"></i> Gerar Código (Safeway)
                         </button>
                    </div>
                    `}
                </div>
            `;
        }

        async function loadQR(sessionId) {
            try {
                const response = await fetch('api_dashboard.php?action=get_qr&session_id=' + encodeURIComponent(sessionId));
                const result = await response.json();
                const container = document.getElementById('qr-box-' + sessionId);
                if(!container) return; 

                if (result.success && result.qr) {
                    container.innerHTML = `
                        <div style="background: #fff; padding: 10px; border-radius: 12px; box-shadow: 0 0 20px rgba(255,255,255,0.1);">
                            <img src="${result.qr}" style="width: 140px; height: 140px; display: block;">
                        </div>
                        <p style="font-size: 0.7rem; color: #fff; margin-top: 10px; opacity: 0.8;"><i class="fas fa-qrcode"></i> Escaneie para conectar</p>
                    `;
                    if (!window.qrPollIntervals[sessionId]) {
                        window.qrPollIntervals[sessionId] = setInterval(() => loadQR(sessionId), 5000);
                    }
                } else if (result.success && result.ready) {
                     container.innerHTML = `
                        <div class="mascot-container">
                             <div class="pulse-loader" style="background: rgba(16, 185, 129, 0.3);"></div>
                             <i class="fas fa-check-circle" style="color: #10b981; font-size: 3rem; background: #0a0a0c; border-radius: 50%; padding: 5px;"></i>
                        </div>
                        <p style="color: #10b981; font-weight: 700; margin-top: 10px;">Conectado!</p>
                     `;
                     if(window.qrPollIntervals[sessionId]) { clearInterval(window.qrPollIntervals[sessionId]); delete window.qrPollIntervals[sessionId]; }
                     setTimeout(updateBotStatus, 1500); 
                } else {
                     // Keep polling if not ready but no error
                     if (!window.qrPollIntervals[sessionId]) {
                        window.qrPollIntervals[sessionId] = setInterval(() => loadQR(sessionId), 5000);
                    }
                }
            } catch(e) {
                console.error('Erro ao carregar QR:', e);
            }
        }
        
        async function openNewNodeModal() {
            const { value: phoneNumber } = await Swal.fire({
              title: 'Adicionar Chip',
              input: 'text',
              inputLabel: 'Qual o telefone do chip? (DDD + Número)',
              inputPlaceholder: 'Ex: 5511999998888',
              background: '#0a0a0c',
              color: '#fff',
              confirmButtonColor: '#10b981',
              showCancelButton: true,
              cancelButtonText: 'Cancelar'
            });

            if (phoneNumber) {
                const cleanPhone = phoneNumber.replace(/\D/g, '');
                if(cleanPhone.length < 10) return alert('Número inválido');
                
                // Creates a new random/phone based session ID
                const newSid = "bot_" + cleanPhone;
                
                // Call api_dashboard generation logic directly which initializes an instance
                const formData = new URLSearchParams();
                formData.append('phone', cleanPhone);
                formData.append('session_id', newSid);
                
                Swal.fire({ title: 'Preparando...', text: 'Registrando nova instância no servidor.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                
                fetch('api_dashboard.php?action=generate_pairing', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData
                }).then(r => r.json()).then(res => {
                    Swal.close();
                    if(res.success && res.code) {
                         Swal.fire({
                            title: 'Código Gerado!',
                            html: `<p>Insira no WhatsApp:</p><div style="font-size: 28px; font-weight: bold; color: #10b981; margin: 15px 0; letter-spacing: 5px;">${res.code}</div><p style="font-size:12px;">Se a tela do admin atualizar sozinha, o código sumirá, anote!</p>`,
                            icon: 'info',
                            background: '#0a0a0c', color: '#fff', confirmButtonColor: '#10b981'
                         });
                         updateBotStatus();
                    } else {
                         Swal.fire('Aviso', 'Instância iniciada. Aguarde na tela de gerenciador o código ou QR aparecer.', 'info');
                         updateBotStatus();
                    }
                });
            }
        }

        async function requestPairing(sid) {
            const phoneInput = document.getElementById('phone-' + sid);
            const cleanPhone = phoneInput.value.replace(/\D/g, '');
            
            if(cleanPhone.length < 10) {
                return Swal.fire({
                    title: 'Atenção',
                    text: 'Insira seu número com DDD (ex: 5511999998888) antes de gerar o código.',
                    icon: 'warning',
                    background: '#0a0a0c', color: '#fff'
                });
            }
            
            Swal.fire({ 
                title: 'Aguarde', 
                text: 'Solicitando código do WhatsApp...', 
                allowOutsideClick: false, 
                background: '#0a0a0c', color: '#fff',
                didOpen:()=>Swal.showLoading() 
            });
            
            try {
                const formData = new URLSearchParams();
                formData.append('phone', cleanPhone);
                formData.append('session_id', sid);

                const response = await fetch('api_dashboard.php?action=generate_pairing', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData
                });
                
                const result = await response.json();
                
                if (result.success && result.code) {
                    Swal.fire({
                        title: 'Código Gerado!',
                        html: `
                            <p style="margin-bottom: 20px">Insira este código no seu WhatsApp:</p>
                            <div style="font-size: 32px; font-weight: 800; letter-spacing:8px; color: #10b981; background: rgba(16,185,129,0.1); padding: 20px; border-radius: 16px; border: 1px solid rgba(16,185,129,0.3); margin: 15px 0;">
                                ${result.code}
                            </div>
                            <p style="font-size: 0.8rem; color: #888;">Navegue até: Aparelhos Conectados > Conectar um Aparelho > Conectar com número de telefone.</p>
                        `,
                        icon: 'success',
                        background: '#0a0a0c', color: '#fff', confirmButtonColor: '#10b981'
                    });
                } else {
                    Swal.fire({ title: 'Erro', text: result.message || 'Erro ao gerar código.', icon: 'error', background: '#0a0a0c', color: '#fff' });
                }
            } catch(e) {
                Swal.fire({ title: 'Erro de Conexão', text: 'Falha ao solicitar código.', icon: 'error', background: '#0a0a0c', color: '#fff' });
            }
        }
        
        async function removeInstance(sid) {
            const { isConfirmed } = await Swal.fire({
                title: 'Tem certeza?',
                text: "Isso irá desconectar e remover este número do sistema.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: 'rgba(255,255,255,0.1)',
                confirmButtonText: 'Sim, remover!',
                cancelButtonText: 'Cancelar',
                background: '#0a0a0c',
                color: '#fff'
            });

            if(isConfirmed) {
                Swal.fire({ title: 'Removendo...', allowOutsideClick: false, didOpen:()=>Swal.showLoading() });
                
                try {
                    const fd = new URLSearchParams();
                    fd.append('session_id', sid);
                    const response = await fetch('api_dashboard.php?action=remove_instance', { method: 'POST', body: fd });
                    const result = await response.json();
                    
                    if(result.success) {
                        if(window.qrPollIntervals[sid]) { clearInterval(window.qrPollIntervals[sid]); delete window.qrPollIntervals[sid]; }
                        Swal.fire({ title: 'Removido!', text: result.message, icon: 'success', background: '#0a0a0c', color: '#fff', timer: 2000, showConfirmButton: false });
                        updateBotStatus();
                    } else {
                        Swal.fire({ title: 'Erro', text: result.message, icon: 'error', background: '#0a0a0c', color: '#fff' });
                    }
                } catch(e) {
                    Swal.fire({ title: 'Erro de Conexão', text: 'Não foi possível falar com o servidor.', icon: 'error', background: '#0a0a0c', color: '#fff' });
                }
            }
        }

        async function updateStats() {
            try {
                const response = await fetch('api_dashboard.php?action=get_stats');
                const result = await response.json();
                if (result.success) {
                    const stats = [
                        { id: 'stat-leads-total', val: result.data.leads_total },
                        { id: 'stat-leads-ativo', val: result.data.leads_ativo },
                        { id: 'stat-leads-concluido', val: result.data.leads_concluido },
                        { id: 'stat-envios-hoje', val: result.data.envios_hoje }
                    ];
                    
                    stats.forEach(s => {
                        const el = document.getElementById(s.id);
                        if(el && el.innerText != s.val) {
                            el.innerText = s.val;
                            el.classList.add('animate-pulse');
                            setTimeout(() => el.classList.remove('animate-pulse'), 1000);
                        }
                    });
                }
            } catch (e) {
                console.warn('Erro ao atualizar estatísticas:', e);
            }
        }

        async function loadFunnel() {
            const funnel = document.getElementById('funnel-container');
            try {
                const response = await fetch('api_marketing_ajax.php?action=get_funnel_steps');
                const result = await response.json();

                if (result.success && result.data && result.data.length > 0) {
                    let rows = '';
                    result.data.forEach(step => {
                        const contentPreview = step.conteudo.length > 60
                            ? step.conteudo.substring(0, 60) + '...'
                            : step.conteudo;
                        const delay = step.delay_apos_anterior_minutos > 0
                            ? step.delay_apos_anterior_minutos + ' min'
                            : 'Imediato';
                        rows += `
                            <tr>
                                <td>${step.ordem}</td>
                                <td class="msg-content-preview">${escapeHtml(contentPreview)}</td>
                                <td>${delay}</td>
                                <td><a href="funnel.php" style="color: var(--primary);"><i class="fas fa-edit"></i></a></td>
                            </tr>
                        `;
                    });

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
                            <tbody>${rows}</tbody>
                        </table>
                    `;
                } else {
                    funnel.innerHTML = '<p style="color: var(--text-dim); text-align: center; padding: 2rem;">Nenhuma mensagem configurada. <a href="funnel.php" style="color: var(--primary);">Configurar funil</a></p>';
                }
            } catch (e) {
                funnel.innerHTML = '<p style="color: var(--text-dim); text-align: center; padding: 2rem;">Erro ao carregar funil.</p>';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
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

        async function triggerDisparos() {
            const btn = document.getElementById('btn-trigger');
            if (!btn) {
                console.error('Botão não encontrado!');
                return;
            }

            const originalContent = btn.innerHTML;

            try {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
                console.log('🚀 Iniciando disparos...');

                const response = await fetch('api_marketing_ajax.php?action=trigger_disparos');
                console.log('📡 Resposta recebida:', response.status);

                const data = await response.json();
                console.log('📦 Dados:', data);

                if (data.success) {
                    console.log('✅ Sucesso!');
                    alert(`✅ ${data.message}\n\nNovos ativados: ${data.novos_ativados}\nPendentes: ${data.pendentes}`);
                    // Atualizar stats após disparar
                    updateStats();
                } else {
                    console.log('❌ Erro:', data.message);
                    alert(`❌ Erro: ${data.message}`);
                }
            } catch (e) {
                console.error('💥 Exceção:', e);
                alert(`❌ Erro: ${e.message}`);
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalContent;
                console.log('🔄 Botão restaurado');
            }
        }

        // Init
        updateBotStatus();
        updateStats();
        loadFunnel();
        updateActivity();
        setInterval(updateBotStatus, 10000);
        setInterval(updateStats, 30000);
        setInterval(updateActivity, 15000);
        async function saveSecuritySettings() {
            const warming = document.getElementById('check-warming').checked ? 1 : 0;
            const antiban = document.getElementById('check-antiban').checked ? 1 : 0;
            const start = document.getElementById('time-start').value;
            const end = document.getElementById('time-end').value;

            const fd = new URLSearchParams();
            fd.append('aquecimento_gradual', warming);
            fd.append('usar_anti_ban', antiban);
            fd.append('horario_inicio', start);
            fd.append('horario_fim', end);
            fd.append('action', 'save_security_settings');

            Swal.fire({ title: 'Salvando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            try {
                const response = await fetch('api_marketing_ajax.php', { method: 'POST', body: fd });
                const res = await response.json();
                if (res.success) {
                    Swal.fire({ title: 'Sucesso!', text: 'Configurações de segurança atualizadas.', icon: 'success', background: '#0a0a0c', color: '#fff' });
                } else {
                    Swal.fire('Erro', res.message, 'error');
                }
            } catch (e) {
                Swal.fire('Erro', 'Falha ao salvar configurações.', 'error');
            }
        }

        async function runMigration() {
            Swal.fire({
                title: 'Atualizar Banco?',
                text: "Isso irá configurar as novas colunas de Anti-Ban e Aquecimento.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sim, atualizar!',
                background: '#0a0a0c', color: '#fff'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Executando migração...', didOpen: () => Swal.showLoading() });
                    try {
                        const response = await fetch('update_antiban_db.php');
                        const text = await response.text();
                        Swal.fire({ title: 'Resultado', html: text, icon: 'info', background: '#0a0a0c', color: '#fff' });
                    } catch (e) {
                        Swal.fire('Erro', 'Não foi possível rodar o script de migração.', 'error');
                    }
                }
            });
        }
    </script>
</body>

</html>