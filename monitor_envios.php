<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/auth_helper.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">

    <!-- PWA -->
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#0a0a0c">
    <link rel="apple-touch-icon" href="https://cdn-icons-png.flaticon.com/512/124/124034.png">

    <title>Monitor de Envios | Marketing Hub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stat-value.success {
            color: #10b981;
        }

        .stat-value.warning {
            color: #f59e0b;
        }

        .stat-value.danger {
            color: #ef4444;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-novo {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
        }

        .status-em_progresso {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }

        .status-concluido {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
        }

        .status-bloqueado {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="header animate-fade-in" style="flex-wrap: wrap; gap: 1rem; align-items: center;">
                <div style="flex: 1;">
                    <h1 style="margin: 0; font-size: 2.5rem; letter-spacing: -1.5px;">Monitor VIP de Envios</h1>
                    <p style="color: var(--text-dim); margin-top: 0.5rem;">Acompanhamento global das operações automáticas.</p>
                </div>
                <div style="display: flex; align-items: center; gap: 10px; color: var(--primary); font-size: 0.95rem; font-weight: 600; background: rgba(255,59,59,0.1); padding: 8px 16px; border-radius: 20px;">
                    <i class="fas fa-sync-alt fa-spin"></i> Sincronizando Ao Vivo...
                </div>
            </header>

            <div class="stats-grid" id="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-users" style="color: #60a5fa;"></i>
                    <div class="stat-info">
                        <div class="stat-value" id="total-leads">-</div>
                        <div class="stat-label">Total de Leads</div>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-plus" style="color: #a78bfa;"></i>
                    <div class="stat-info">
                        <div class="stat-value" id="novos">-</div>
                        <div class="stat-label">Novos (Fila)</div>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-spinner" style="color: #fbbf24;"></i>
                    <div class="stat-info">
                        <div class="stat-value warning" id="em-progresso">-</div>
                        <div class="stat-label">Em Progresso</div>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-circle" style="color: #34d399;"></i>
                    <div class="stat-info">
                        <div class="stat-value success" id="concluidos">-</div>
                        <div class="stat-label">Concluídos</div>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock" style="color: var(--primary);"></i>
                    <div class="stat-info">
                        <div class="stat-value" id="proximo-envio" style="font-size: 1.2rem;">-</div>
                        <div class="stat-label">Próximo Envio</div>
                    </div>
                </div>
            </div>

            <div class="panel animate-fade-in" style="margin-top: 2rem;">
                <div class="panel-title"><i class="fas fa-list"></i> Leads Ativos (Em Progresso)</div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Telefone</th>
                                <th>Status</th>
                                <th>Passo</th>
                                <th>Próx. Envio</th>
                                <th>Origem</th>
                            </tr>
                        </thead>
                        <tbody id="leads-table">
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-dim);">
                                    Carregando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        async function loadData() {
            try {
                // Carregar estatísticas
                const statsRes = await fetch('api_marketing_ajax.php?action=get_marketing_stats');
                const stats = await statsRes.json();

                if (stats.success && stats.stats) {
                    document.getElementById('total-leads').textContent = stats.stats.total_leads || 0;
                    document.getElementById('novos').textContent = stats.stats.fila_espera || 0;
                    document.getElementById('em-progresso').textContent = stats.stats.hoje_andamento || 0;
                    document.getElementById('concluidos').textContent = stats.stats.hoje_concluidos || 0;

                    const proximoEnvio = stats.stats.proximo_envio || 'Nenhum';
                    if (proximoEnvio !== 'Nenhum' && proximoEnvio !== 'Nenhum agendado') {
                        const date = new Date(proximoEnvio);
                        const now = new Date();
                        const diff = date - now;

                        if (diff > 0) {
                            const minutes = Math.floor(diff / 60000);
                            const seconds = Math.floor((diff % 60000) / 1000);
                            document.getElementById('proximo-envio').textContent = `${minutes}m ${seconds}s`;
                        } else {
                            document.getElementById('proximo-envio').textContent = 'Agora!';
                        }
                    } else {
                        document.getElementById('proximo-envio').textContent = proximoEnvio;
                    }
                }

                // Carregar leads em progresso
                const leadsRes = await fetch('api_monitor.php?action=get_active_leads');
                const leadsData = await leadsRes.json();

                if (leadsData.success && leadsData.leads) {
                    const tbody = document.getElementById('leads-table');

                    if (leadsData.leads.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-dim);">Nenhum lead em progresso no momento</td></tr>';
                    } else {
                        tbody.innerHTML = leadsData.leads.map(lead => {
                            const statusClass = `status-${lead.status.replace(' ', '_')}`;
                            const proximoEnvio = lead.data_proximo_envio ? new Date(lead.data_proximo_envio).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }) : '-';
                            const telefone = lead.telefone.length > 15 ? lead.telefone.substring(0, 15) + '...' : lead.telefone;
                            const origem = lead.grupo_origem_jid ? lead.grupo_origem_jid.split('@')[0].substring(0, 15) + '...' : '-';

                            return `
                                <tr>
                                    <td>#${lead.id}</td>
                                    <td style="font-family:monospace; color:var(--text-main);">${telefone}</td>
                                    <td><span class="status-badge ${statusClass}">${lead.status}</span></td>
                                    <td>Passo ${lead.ultimo_passo_id + 1}</td>
                                    <td>${proximoEnvio}</td>
                                    <td style="font-size: 0.8rem; color: var(--text-dim);">${origem}</td>
                                </tr>
                            `;
                        }).join('');
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar dados:', error);
            }
        }

        loadData();
        setInterval(loadData, 5000);
    </script>
</body>

</html>