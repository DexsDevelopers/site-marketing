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
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 1.5rem;
            max-height: 70vh;
            min-height: 400px;
            overflow-y: auto;
            font-size: 0.85rem;
            margin-top: 1rem;
        }

        .log-entry {
            display: flex;
            gap: 1rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            align-items: flex-start;
            animation: fadeIn 0.3s ease;
        }

        .log-entry:last-child {
            border-bottom: none;
        }

        .log-time {
            color: var(--text-dim);
            font-size: 0.75rem;
            white-space: nowrap;
            min-width: 75px;
            padding-top: 2px;
        }

        .log-level {
            font-size: 0.7rem;
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 6px;
            text-transform: uppercase;
            min-width: 65px;
            text-align: center;
        }

        .log-level.INFO {
            color: #60a5fa;
            background: rgba(96, 165, 250, 0.1);
            border: 1px solid rgba(96, 165, 250, 0.2);
        }

        .log-level.SUCCESS {
            color: #4ade80;
            background: rgba(74, 222, 128, 0.1);
            border: 1px solid rgba(74, 222, 128, 0.2);
        }

        .log-level.WARN {
            color: #fbbf24;
            background: rgba(251, 191, 36, 0.1);
            border: 1px solid rgba(251, 191, 36, 0.2);
        }

        .log-level.ERROR {
            color: #f87171;
            background: rgba(248, 113, 113, 0.1);
            border: 1px solid rgba(248, 113, 113, 0.2);
        }

        .log-message {
            color: #d1d5db;
            word-break: break-word;
            line-height: 1.5;
        }

        .panel-header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .filter-bar {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding-bottom: 5px;
            -webkit-overflow-scrolling: touch;
        }

        .filter-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            color: var(--text-dim);
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .filter-btn.active {
            background: var(--primary-glow);
            color: var(--primary);
            border-color: var(--primary);
        }

        .filter-btn:hover:not(.active) {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-main);
        }

        .log-status-meta {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        #log-count {
            font-size: 0.85rem;
            color: var(--text-dim);
            font-weight: 500;
        }

        .auto-refresh-control {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-size: 0.85rem;
            color: var(--text-dim);
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #444;
            display: inline-block;
        }

        .status-indicator.active {
            background: #22c55e;
            box-shadow: 0 0 10px #22c55e;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.7; }
            100% { transform: scale(1); opacity: 1; }
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4rem 2rem;
            color: var(--text-dim);
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .panel-header-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .log-status-meta {
                justify-content: space-between;
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
                    <p style="color: var(--text-dim); margin-top: 0.5rem;">Monitoramento em tempo real do robô de marketing.</p>
                </div>
                <div class="auto-refresh-toggle">
                    <span class="status-indicator active" id="refresh-indicator"></span>
                    <span style="font-size: 0.9rem; font-weight: 600; margin-left: 0.5rem;">Ao vivo</span>
                </div>
            </header>

            <div class="panel animate-fade-in">
                <div class="panel-header-actions">
                    <div class="filter-bar">
                        <button class="filter-btn active" data-level="">TODOS</button>
                        <button class="filter-btn" data-level="INFO">INFO</button>
                        <button class="filter-btn" data-level="SUCCESS">SUCCESS</button>
                        <button class="filter-btn" data-level="WARN">WARN</button>
                        <button class="filter-btn" data-level="ERROR">ERROR</button>
                    </div>
                    
                    <div class="log-status-meta">
                        <span id="log-count">Carregando...</span>
                        <div class="auto-refresh-control">
                            <span>Auto-refresh</span>
                            <label class="switch">
                                <input type="checkbox" id="auto-refresh" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="log-container" id="log-container">
                    <div class="empty-state">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p>Iniciando monitoramento...</p>
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
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle fa-2x" style="color: #f59e0b;"></i>
                            <p>${data.message || 'Erro ao carregar logs'}</p>
                            <button class="btn-modern secondary" onclick="fetchLogs()" style="margin-top: 1rem;">
                                <i class="fas fa-sync"></i> Tentar Novamente
                            </button>
                        </div>`;
                    if (countEl) countEl.textContent = 'Indisponível';
                    return;
                }

                if (!data.logs || data.logs.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-inbox fa-2x"></i>
                            <p>Nenhum log encontrado para este filtro</p>
                        </div>`;
                    if (countEl) countEl.textContent = '0 logs';
                    return;
                }

                if (countEl) countEl.textContent = `${data.logs.length} logs`;

                let html = '';
                for (const log of data.logs) {
                    const date = new Date(log.timestamp);
                    const time = date.toLocaleTimeString('pt-BR');
                    
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
                console.error('Fetch error:', e);
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-wifi-slash fa-2x" style="color: #ef4444;"></i>
                        <p>Erro de conexão com o servidor</p>
                        <button class="btn-modern secondary" onclick="fetchLogs()" style="margin-top: 1rem;">
                                <i class="fas fa-sync"></i> Reconectar
                        </button>
                    </div>`;
                if (countEl) countEl.textContent = 'Erro';
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
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
            if (refreshIndicator) refreshIndicator.classList.add('active');
        }

        function stopAutoRefresh() {
            if (autoRefreshInterval) clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
            if (refreshIndicator) refreshIndicator.classList.remove('active');
        }

        if (autoRefreshCheckbox) {
            autoRefreshCheckbox.addEventListener('change', () => {
                if (autoRefreshCheckbox.checked) {
                    startAutoRefresh();
                } else {
                    stopAutoRefresh();
                }
            });
        }

        // Initial load
        fetchLogs();
        startAutoRefresh(); 
    </script>
</body>
</html>