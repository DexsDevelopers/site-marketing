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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor de Envios - Marketing Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            overflow-x: hidden;
            width: 100%;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            padding-bottom: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .header h1 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 10px;
            word-wrap: break-word;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-label {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }

        .stat-value.success {
            color: #10b981;
        }

        .stat-value.warning {
            color: #f59e0b;
        }

        .stat-value.danger {
            color: #ef4444;
        }

        .table-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            min-width: 800px;
            /* Garante scroll se necessário */
            border-collapse: collapse;
        }

        th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        tr:hover {
            background: #f9fafb;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-novo {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-em_progresso {
            background: #fef3c7;
            color: #92400e;
        }

        .status-concluido {
            background: #d1fae5;
            color: #065f46;
        }

        .status-bloqueado {
            background: #fee2e2;
            color: #991b1b;
        }

        .phone {
            font-family: 'Courier New', monospace;
            color: #4b5563;
        }

        .time {
            color: #6b7280;
            font-size: 12px;
        }

        .refresh-info {
            text-align: center;
            margin-top: 15px;
            color: #6b7280;
            font-size: 13px;
        }

        .refresh-info i {
            color: #667eea;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .btn-back {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .btn-back:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .step-info {
            font-size: 12px;
            color: #6b7280;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .header {
                padding: 15px;
            }

            .header h1 {
                font-size: 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-value {
                font-size: 24px;
            }

            .table-container {
                padding: 15px;
            }

            th,
            td {
                padding: 10px;
                font-size: 12px;
                white-space: nowrap;
            }

            .status-badge {
                padding: 2px 8px;
                font-size: 10px;
            }

            .btn-back {
                width: 100%;
                text-align: center;
                margin-bottom: 15px;
                box-sizing: border-box;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Voltar ao Dashboard</a>

        <div class="header">
            <h1><i class="fas fa-chart-line"></i> Monitor de Envios em Tempo Real</h1>
            <p>Acompanhe o status de todos os leads e envios de mensagens</p>
        </div>

        <div class="stats-grid" id="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total de Leads</div>
                <div class="stat-value" id="total-leads">-</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Novos</div>
                <div class="stat-value" id="novos">-</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Em Progresso</div>
                <div class="stat-value warning" id="em-progresso">-</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Concluídos</div>
                <div class="stat-value success" id="concluidos">-</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Bloqueados</div>
                <div class="stat-value danger" id="bloqueados">-</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Próximo Envio</div>
                <div class="stat-value" id="proximo-envio" style="font-size: 18px;">-</div>
            </div>
        </div>

        <div class="table-container">
            <h2 style="margin-bottom: 20px; color: #667eea;"><i class="fas fa-list"></i> Leads Ativos (Em Progresso)
            </h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Telefone</th>
                        <th>Status</th>
                        <th>Passo Atual</th>
                        <th>Próximo Envio</th>
                        <th>Grupo Origem</th>
                    </tr>
                </thead>
                <tbody id="leads-table">
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px;">Carregando...</td>
                    </tr>
                </tbody>
            </table>

            <div class="refresh-info">
                <i class="fas fa-sync-alt"></i> Atualizando automaticamente a cada 5 segundos
            </div>
        </div>
    </div>

    <script>
        async function loadData() {
            try {
                // Carregar estatísticas
                const statsRes = await fetch('api_marketing_ajax.php?action=get_marketing_stats');
                const stats = await statsRes.json();

                console.log('Stats response:', stats);

                if (stats.success && stats.stats) {
                    document.getElementById('total-leads').textContent = stats.stats.total_leads || 0;
                    document.getElementById('novos').textContent = stats.stats.fila_espera || 0;
                    document.getElementById('em-progresso').textContent = stats.stats.hoje_andamento || 0;
                    document.getElementById('concluidos').textContent = stats.stats.hoje_concluidos || 0;
                    document.getElementById('bloqueados').textContent = 0;

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
                } else {
                    console.error('Stats API error:', stats);
                }

                // Carregar leads em progresso
                const leadsRes = await fetch('api_monitor.php?action=get_active_leads');
                const leadsData = await leadsRes.json();

                console.log('Leads response:', leadsData);

                if (leadsData.success && leadsData.leads) {
                    const tbody = document.getElementById('leads-table');

                    if (leadsData.leads.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #9ca3af;">Nenhum lead em progresso no momento</td></tr>';
                    } else {
                        tbody.innerHTML = leadsData.leads.map(lead => {
                            const statusClass = `status-${lead.status.replace(' ', '_')}`;
                            const proximoEnvio = lead.data_proximo_envio ? new Date(lead.data_proximo_envio).toLocaleString('pt-BR') : '-';
                            const telefone = lead.telefone.length > 15 ? lead.telefone.substring(0, 15) + '...' : lead.telefone;

                            return `
                                <tr>
                                    <td>${lead.id}</td>
                                    <td class="phone">${telefone}</td>
                                    <td><span class="status-badge ${statusClass}">${lead.status}</span></td>
                                    <td class="step-info">Passo ${lead.ultimo_passo_id + 1}</td>
                                    <td class="time">${proximoEnvio}</td>
                                    <td style="font-size: 11px; color: #9ca3af;">${lead.grupo_origem_jid ? lead.grupo_origem_jid.substring(0, 20) + '...' : '-'}</td>
                                </tr>
                            `;
                        }).join('');
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar dados:', error);
            }
        }

        // Carregar dados inicialmente
        loadData();

        // Atualizar a cada 5 segundos
        setInterval(loadData, 5000);
    </script>
</body>

</html>