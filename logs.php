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

    <!-- PWA -->
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#0a0a0c">
    <link rel="apple-touch-icon" href="https://cdn-icons-png.flaticon.com/512/124/124034.png">

    <title>Marketing Hub | Logs do Bot</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Logs Specific Styles */
        .log-container {
            font-family: 'Fira Code', 'Monaco', 'Consolas', monospace;
            background: #0f0f10;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 1rem;
            max-height: 70vh;
            overflow-y: auto;
            font-size: 0.85rem;
        }

        .log-entry {
            display: flex;
            gap: 0.8rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            align-items: flex-start;
        }

        .log-time {
            color: #666;
            font-size: 0.75rem;
            white-space: nowrap;
            min-width: 60px;
        }

        .log-level {
            font-size: 0.7rem;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 4px;
            text-transform: uppercase;
            min-width: 50px;
            text-align: center;
        }

        .log-level.INFO {
            color: #60a5fa;
            background: rgba(96, 165, 250, 0.1);
        }

        .log-level.SUCCESS {
            color: #4ade80;
            background: rgba(74, 222, 128, 0.1);
        }

        .log-level.WARN {
            color: #fbbf24;
            background: rgba(251, 191, 36, 0.1);
        }

        .log-level.ERROR {
            color: #f87171;
            background: rgba(248, 113, 113, 0.1);
        }

        .log-message {
            color: #d1d5db;
            word-break: break-all;
        }

        /* Filter layout for mobile */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
            align-items: center;
        }

        .filter-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #888;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-btn.active,
        .filter-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(255, 59, 59, 0.1);
        }

        @media (max-width: 768px) {
            .filter-bar {
                flex-direction: row;
                justify-content: flex-start;
                overflow-x: auto;
                padding-bottom: 5px;
            }

            .filter-btn {
                flex: 0 0 auto;
                /* Don't shrink */
            }

            .log-entry {
                flex-direction: column;
                gap: 0.2rem;
            }

            .log-header-mobile {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                width: 100%;
            }
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ef4444;
            animation: none;
        }

        .status-indicator.active {
            background: #22c55e;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="header animate-fade-in">
                <div>
                    <h1 style="margin: 0; font-size: 2.5rem; letter-spacing: -1.5px;">Logs do Sistema</h1>
                    <p style="color: var(--text-dim); margin-top: 0.5rem;">Monitoramento de atividades e erros do robô.
                    </p>
                </div>
                <div class="auto-refresh-toggle">
                    <span class="status-indicator active" id="live-indicator"></span>
                    <span style="font-size: 0.9rem;">Ao vivo</span>
                </div>
            </header>

            <div class="panel animate-fade-in">
                <div class="filter-bar">
                    <div class="log-container" id="log-container">
                        <div class="empty-state">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p>Carregando logs...</p>
                        </div>
                    </div>
                </div>
        </main>
    </div>

    <script>
        let currentFilter = '';
        let autoRefreshInterval = null;

        async function fetchLogs() {
            const container = document.getElementById('log-container');
            const countEl = document.getElementById('log-count');

            try {
                const url = `api_marketing_ajax.php?action=get_bot_logs&level=${currentFilter}&limit=200`;
                const response = await fetch(url);
                const data = await response.json();

                if (!data.success) {
                    container.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-triangle fa-2x" style="color: #f59e0b;"></i><p>${data.message || 'Erro ao carregar logs'}</p></div>`;
                    countEl.textContent = '';
                    return;
                }

                if (!data.logs || data.logs.length === 0) {
                    container.innerHTML = `<div class="empty-state"><i class="fas fa-inbox fa-2x"></i><p>Nenhum log encontrado</p></div>`;
                    countEl.textContent = '0 logs';
                    return;
                }

                countEl.textContent = `${data.count} logs`;

                let html = '';
                for (const log of data.logs) {
                    const time = new Date(log.timestamp).toLocaleTimeString('pt-BR');
                    html += `
                        <div class="log-entry">
                            <span class="log-time">${time}</span>
                            <span class="log-level ${log.level}">${log.level}</span>
                            <span class="log-message">${escapeHtml(log.message)}</span>
                        </div>
                    `;
                }
                container.innerHTML = html;

            } catch (e) {
                container.innerHTML = `<div class="empty-state"><i class="fas fa-wifi-slash fa-2x" style="color: #ef4444;"></i><p>Erro de conexão com o servidor</p></div>`;
                countEl.textContent = '';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentFilter = btn.dataset.level;
                fetchLogs();
            });
        });

        // Auto-refresh toggle
        const autoRefreshCheckbox = document.getElementById('auto-refresh');
        const refreshIndicator = document.getElementById('refresh-indicator');

        function startAutoRefresh() {
            if (autoRefreshInterval) clearInterval(autoRefreshInterval);
            autoRefreshInterval = setInterval(fetchLogs, 5000); // Every 5 seconds
            refreshIndicator.classList.add('active');
        }

        function stopAutoRefresh() {
            if (autoRefreshInterval) clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
            refreshIndicator.classList.remove('active');
        }

        autoRefreshCheckbox.addEventListener('change', () => {
            if (autoRefreshCheckbox.checked) {
                startAutoRefresh();
            } else {
                stopAutoRefresh();
            }
        });

        // Initial load
        fetchLogs();
    