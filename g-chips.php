<?php
require_once 'includes/config.php';
require_once 'includes/auth_helper.php';
requireLogin();

// Configurações e Helper de Conexão com o Bot
$botUrl = rtrim(getDynamicConfig("WHATSAPP_API_URL", "https://cyan-spoonbill-539092.hostingersite.com"), "/");
$token = getDynamicConfig("WHATSAPP_API_TOKEN", "lucastav8012");
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Marketing Hub | Gerenciar Chips</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary: #ff3b3b;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: #0a0a0c;
            color: #fff;
            margin: 0;
            overflow-x: hidden;
        }

        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        @media (max-width: 991px) {
            .main-content { margin-left: 0; padding: 1rem; }
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        }

        .instance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .chip-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 1.8rem;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .chip-card:hover {
            transform: translateY(-8px);
            border-color: var(--primary);
            box-shadow: 0 12px 40px rgba(255, 59, 59, 0.15);
        }

        .status-badge {
            font-size: 0.7rem;
            font-weight: 800;
            padding: 5px 12px;
            border-radius: 100px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-online { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
        .status-offline { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }

        .btn-reset {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
            width: 100%;
            padding: 12px;
            border-radius: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1.5rem;
        }

        .btn-reset:hover {
            background: #ef4444;
            color: white;
        }

        .chip-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
            font-size: 0.9rem;
            color: #94a3b8;
        }

        .chip-info-val { color: #f1f5f9; font-weight: 600; }
        
        .pulse-icon {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="mb-4">
            <h1 class="fw-800" style="font-size: 2.2rem; letter-spacing: -1px;">Gerenciar <span class="text-primary">Chips</span></h1>
            <p class="text-secondary">Controle individual de sessões e proteção anti-ban.</p>
        </header>

        <div id="instances-container" class="instance-grid">
            <!-- Shimmer / Loading -->
            <div class="col-12 text-center py-5">
                <i class="fas fa-circle-notch fa-spin fa-2x text-primary mb-3"></i>
                <p>Consultando servidor de WhatsApp...</p>
            </div>
        </div>
    </main>

    <script>
        const BOT_URL = "<?php echo $botUrl; ?>";
        const BOT_TOKEN = "<?php echo $token; ?>";

        async function fetchInstances() {
            try {
                const response = await fetch(`${BOT_URL}/instances?token=${BOT_TOKEN}`);
                const data = await response.json();

                const container = document.getElementById('instances-container');
                container.innerHTML = '';

                if (!data.success || data.instances.length === 0) {
                    container.innerHTML = `
                        <div class="glass-card col-12 text-center p-5">
                            <i class="fas fa-plug-slash fa-3x mb-3 text-secondary"></i>
                            <h4>Nenhuma sessão ativa</h4>
                            <p class="text-secondary">Parece que não há robôs conectados agora.</p>
                        </div>`;
                    return;
                }

                data.instances.forEach(inst => {
                    const card = document.createElement('div');
                    card.className = 'chip-card';
                    
                    const isReady = inst.isReady;
                    const statusClass = isReady ? 'status-online' : 'status-offline';
                    const statusLabel = isReady ? 'Conectado' : 'Offline / Erro';
                    
                    // Maturação Label
                    let matLabel = "Maturado";
                    let matColor = "text-success";
                    if (inst.safetyPausedUntil && inst.safetyPausedUntil > Date.now()) {
                        matLabel = "Pausa de Segurança";
                        matColor = "text-danger";
                    } else if (new Date(inst.maturationDate).toDateString() === new Date().toDateString()) {
                        matLabel = "Aquecendo (Dia 1)";
                        matColor = "text-warning";
                    }

                    card.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="status-badge ${statusClass}">
                                <span class="pulse-icon" style="background: currentColor"></span>
                                ${statusLabel}
                            </span>
                            <span class="small text-secondary">#${inst.sessionId}</span>
                        </div>
                        
                        <h3 class="mb-4">${inst.phoneNumber || 'Número Pendente'}</h3>
                        
                        <div class="chip-info-row">
                            <span>Status Maturação</span>
                            <span class="chip-info-val ${matColor}">${matLabel}</span>
                        </div>
                        
                        <div class="chip-info-row">
                            <span>Tempo Online</span>
                            <span class="chip-info-val">${formatUptime(inst.uptimeStart)}</span>
                        </div>

                        <button class="btn-reset" onclick="confirmReset('${inst.sessionId}')">
                            <i class="fas fa-trash-alt me-2"></i> Resetar Apenas Este Chip
                        </button>
                    `;
                    container.appendChild(card);
                });

            } catch (err) {
                document.getElementById('instances-container').innerHTML = `
                    <div class="glass-card col-12 text-center p-5 border-danger">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3 text-danger"></i>
                        <h4>Robô Offline ou Erro de Rede</h4>
                        <p class="text-secondary">Não foi possível conectar ao servidor em: <br><small>${BOT_URL}</small></p>
                        <p class="small text-danger">${err.message}</p>
                        <button class="btn btn-outline-light btn-sm mt-3" onclick="fetchInstances()">Tentar Novamente</button>
                    </div>`;
            }
        }

        function formatUptime(start) {
            if (!start) return '0m';
            const diff = Math.floor((Date.now() - start) / 60000);
            if (diff < 60) return diff + 'm';
            const h = Math.floor(diff / 60);
            const m = diff % 60;
            return h + 'h ' + m + 'm';
        }

        function confirmReset(sessionId) {
            Swal.fire({
                title: 'Tem certeza?',
                text: "Isso apagará APENAS a sessão deste chip. O outro continuará normal.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#3b82f6',
                confirmButtonText: 'Sim, resetar!',
                cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Processando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                    try {
                        const res = await fetch(`${BOT_URL}/reset-instance/${sessionId}?token=${BOT_TOKEN}`, { method: 'POST' });
                        const resData = await res.json();
                        if (resData.success) {
                            Swal.fire('Resetado!', 'Sessão limpa com sucesso.', 'success');
                            fetchInstances();
                        } else {
                            Swal.fire('Erro', resData.message, 'error');
                        }
                    } catch (e) {
                        Swal.fire('Erro', 'Falha na comunicação com o servidor.', 'error');
                    }
                }
            });
        }

        fetchInstances();
        setInterval(fetchInstances, 15000);
    </script>
</body>

</html>
