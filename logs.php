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
        .filter-bar {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            /* Garante que botões pulem linha */
            align-items: center;
        }

        .log-container {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            padding: 1.5rem;
            font-family: 'Fira Code', 'Monaco', 'Consolas', monospace;
            font-size: 0.85rem;
            max-height: 70vh;
            overflow-y: auto;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .log-entry {
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .log-entry:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .log-time {
            color: #6b7280;
            white-space: nowrap;
            font-size: 0.75rem;
        }

        .log-level {
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            min-width: 60px;
            text-align: center;
        }

        .log-level.INFO {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
        }

        .log-level.SUCCESS {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
        }

        .log-level.WARN {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
        }

        .log-level.ERROR {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }

        .log-level.HEARTBEAT {
            background: rgba(168, 85, 247, 0.2);
            color: #c084fc;
        }

        .log-message {
            color: #e5e7eb;
            word-break: break-word;
            flex: 1;
        }

        .filter-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: #9ca3af;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.85rem;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: rgba(59, 130, 246, 0.2);
            border-color: #3b82f6;
            color: #60a5fa;
        }

        .auto-refresh-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #9ca3af;
            font-size: 0.85rem;
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

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }

        .log-count {
            color: #6b7280;
            font-size: 0.85rem;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="header animate-fade-in">
                <div>
                    <h1 style="margin: 0; font-size: 2.5rem; letter-spacing: -1.5px;">
                        <i class="fas fa-terminal" style="color: #60a5fa;"></i> Logs do Bot
                    </h1>
                    <p style="color: var(--text-dim); margin-top: 0.5rem;">
                        Acompanhe em tempo real o que o bot está fazendo.
                    </p>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <div class="auto-refresh-toggle">
                        <span class="status-indicator" id="refresh-indicator"></span>
                        <label>
                            <input type="checkbox" id="auto-refresh" checked style="margin-right: 0.3rem;">
                            Auto-refresh
                        </label>
                    </div>
                    <button class="btn-modern" onclick="fetchLogs()" id="btn-refresh">
                        <i class="fas fa-sync-alt"></i> Atualizar
                    </button>
                </div>
            </header>

            <div class="panel animate-fade-in">
                <div class="filter-bar">
                    <button class="filter-btn active" data-level="">Todos</button>
                    <button class="filter-btn" data-level="INFO">Info</button>
                    <button class="filter-btn" data-level="SUCCESS">Success</button>
                    <button class="filter-btn" data-level="WARN">Warn</button>
                    <button class="filter-btn" data-level="ERROR">Error</button>
                    <span class="log-count" id="log-count"></span>
                </div>

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
        startAutoRefresh();
    </script>
</body>

</html>