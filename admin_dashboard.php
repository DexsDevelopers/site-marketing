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
                            <span id="chip-count-badge" style="background: rgba(16,185,129,0.2); color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: bold;">0 Conectados</span>
                        </div>
                        
                        <!-- Lista Dinâmica de instâncias -->
                        <div id="instances-list" style="display: flex; flex-direction: column; gap: 1rem; max-height: 480px; overflow-y: auto; padding-right: 5px;">
                            <div style="text-align: center; color: var(--text-dim); padding: 2rem;">
                                <i class="fas fa-circle-notch fa-spin fa-2x"></i><br><br>Carregando chips...
                            </div>
                        </div>

                        <!-- Adicionar Novo Chip Btn -->
                        <div style="margin-top: 1.5rem; border-top: 1px dashed rgba(255,255,255,0.1); padding-top: 1.5rem;">
             <button onclick="openNewNodeModal()" class="btn-modern accent" style="width: 100%; justify-content: center; background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3);">
                                <i class="fas fa-plus"></i> Conectar Novo Número
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

        async function updateBotStatus() {
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
                        return;
                    }

                    // Rebuild the list HTML if instances exist
                    let htmlString = '';
                    instances.forEach(inst => {
                        htmlString += buildInstanceCard(inst);
                    });
                    
                    // Only update innerHTML if it has meaningfully changed to avoid interrupting inputs or toggles, 
                    // but since this is just a display list usually, re-render is fine. For better UX we could update individual cards.
                    // For simplicity, we overwrite right now.
                    listContainer.innerHTML = htmlString;
                    
                    // Trigger QR loader on instances that are NOT ready
                    instances.forEach(inst => {
                        if (!inst.isReady) {
                            loadQR(inst.sessionId);
                        } else {
                           // If it became ready, clear any polling
                           if(window.qrPollIntervals[inst.sessionId]) {
                               clearInterval(window.qrPollIntervals[inst.sessionId]);
                               delete window.qrPollIntervals[inst.sessionId];
                           }
                        }
                    });

                } else {
                    listContainer.innerHTML = '<div style="text-align: center; color: #ef4444; padding: 2rem;"><i class="fas fa-exclamation-triangle fa-2x" style="margin-bottom:1rem;"></i><br>Servidor Node.js Desconectado. Verifique o PM2.</div>';
                }
            } catch (e) {
                console.error('Erro ao buscar status:', e);
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
               ? `<span style="background: rgba(16,185,129,0.1); color: #10b981; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: bold;"><i class="fas fa-circle" style="font-size:0.5rem; vertical-align:middle; margin-right:4px;"></i>Ativo</span>`
               : `<span style="background: rgba(245,158,11,0.1); color: #f59e0b; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: bold;"><i class="fas fa-circle-notch fa-spin" style="font-size:0.6rem; vertical-align:middle; margin-right:4px;"></i>Sincronizando</span>`;

            return `
                <div style="background: rgba(0,0,0,0.2); border: 1px solid var(--glass-border); border-radius: 12px; padding: 1rem; position: relative;" id="card-${sid}">
                    
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <h4 style="margin: 0 0 0.3rem 0; font-size: 0.95rem;">${dName}</h4>
                            ${statusBadge}
                        </div>
                        <button onclick="removeInstance('${sid}')" title="Desconectar / Excluir" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 5px;">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>

                    ${isReady ? `
                    <div style="margin-top: 1rem; font-size: 0.8rem; color: var(--text-dim); display: flex; justify-content: space-between;">
                         <span><i class="fas fa-clock"></i> Tempo Oline: <strong style="color:white;">${uptimeStr}</strong></span>
                    </div>
                    ` : `
                    <div id="qr-box-${sid}" style="min-height: 120px; display: flex; align-items: center; justify-content: center; margin-top: 1rem; background: rgba(0,0,0,0.3); border-radius: 8px;">
                          <i class="fas fa-circle-notch fa-spin" style="color: var(--primary);"></i>
                    </div>
                    <div style="margin-top:0.8rem;">
                         <input type="text" id="phone-${sid}" placeholder="Digite seu N° com DDD" style="width: 100%; border:1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.5); border-radius:6px; padding: 8px; color:white; font-size:0.8rem; margin-bottom:5px;">
                         <button onclick="requestPairing('${sid}')" style="width: 100%; background: var(--primary); color: #0a0a0c; border:none; border-radius:6px; padding: 8px; font-weight:600; cursor:pointer; font-size:0.8rem;">Gerar Código (Safeway)</button>
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
                if(!container) return; // card might be re-rendered

                if (result.success && result.qr) {
                    container.innerHTML = `<img src="${result.qr}" style="width: 120px; height: 120px; border-radius: 8px;">`;
                    if (!window.qrPollIntervals[sessionId]) {
                        window.qrPollIntervals[sessionId] = setInterval(() => loadQR(sessionId), 5000);
                    }
                } else if (result.success && result.ready) {
                     container.innerHTML = '<i class="fas fa-check-circle" style="color:#10b981; font-size: 2rem;"></i>';
                     if(window.qrPollIntervals[sessionId]) { clearInterval(window.qrPollIntervals[sessionId]); delete window.qrPollIntervals[sessionId]; }
                     setTimeout(updateBotStatus, 1000); // refresh list silently
                } else {
                     container.innerHTML = `<p style="font-size:0.75rem; color:var(--text-dim); text-align:center;">Preparando Código...</p>`;
                     if (!window.qrPollIntervals[sessionId]) {
                        window.qrPollIntervals[sessionId] = setInterval(() => loadQR(sessionId), 5000);
                    }
                }
            } catch(e) {}
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
            if(cleanPhone.length < 10) return alert('Insira seu número com DDD antes de gerar o código.');
            
            const formData = new URLSearchParams();
            formData.append('phone', cleanPhone);
            formData.append('session_id', sid);
            
            Swal.fire({ title: 'Aguarde', text: 'Solicitando código do WhatsApp...', allowOutsideClick: false, didOpen:()=>Swal.showLoading() });
            
            fetch('api_dashboard.php?action=generate_pairing', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData
            }).then(r => r.json()).then(result => {
                 if (result.success && result.code) {
                    Swal.fire({
                        title: 'Código Gerado!',
                        html: '<p style="margin-bottom: 20px">Insira no seu WhatsApp:</p><div style="font-size: 32px; font-weight: bold; letter-spacing:5px; color: #10b981; background: rgba(16,185,129,0.1); padding: 15px; border-radius: 10px">' + result.code + '</div>',
                        icon: 'success',
                        background: '#0a0a0c',
                        color: '#fff',
                        confirmButtonColor: '#10b981'
                    });
                } else {
                    Swal.fire('Erro', result.message || 'Erro ao gerar código.', 'error');
                }
            });
        }
        
        async function removeInstance(sid) {
            if(confirm("Tem certeza que deseja deletar esse chip do sistema?")) {
                const fd = new URLSearchParams();
                fd.append('session_id', sid);
                fetch('api_dashboard.php?action=remove_instance', { method: 'POST', body: fd }).then(()=>{
                    if(window.qrPollIntervals[sid]) { clearInterval(window.qrPollIntervals[sid]); delete window.qrPollIntervals[sid]; }
                    updateBotStatus();
                });
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
    </script>
</body>

</html>