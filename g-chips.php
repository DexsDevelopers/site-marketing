<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/auth_helper.php';

requireLogin();

$pageTitle = "Gerenciar Chips VIP";
include 'includes/header.php';
?>

<style>
    .glass-card {
        background: rgba(20, 20, 20, 0.6);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
        border-radius: 15px;
        overflow: hidden;
    }

    .glass-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        border-color: var(--primary-color);
    }

    .status-pulse {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
    }

    .status-online {
        background: #22c55e;
        box-shadow: 0 0 10px #22c55e;
        animation: pulse-green 2s infinite;
    }

    .status-offline {
        background: #ef4444;
        box-shadow: 0 0 10px #ef4444;
    }

    @keyframes pulse-green {
        0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
        100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }

    .progress-maturation {
        height: 6px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
    }

    .btn-action {
        border-radius: 10px;
        padding: 8px 15px;
        font-weight: 600;
        transition: all 0.2s;
    }

    .btn-action:hover {
        filter: brightness(1.2);
    }

    .chip-icon {
        font-size: 2.5rem;
        background: linear-gradient(45deg, var(--primary-color), #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
</style>

<div class="main-content">
    <div class="container-fluid py-4">
        <!-- Cabeçalho de Controle -->
        <div class="row mb-5 mt-3">
            <div class="col-12">
                <div class="glass-card p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 fw-bold mb-1"><i class="fas fa-microchip me-2"></i> Central de Chips Elite</h1>
                        <p class="text-secondary mb-0">Monitoramento em tempo real de conexão, saúde e maturação dos seus robôs.</p>
                    </div>
                    <div class="text-end">
                        <button onclick="loadInstances()" class="btn btn-outline-primary btn-action">
                            <i class="fas fa-sync-alt me-2"></i> Atualizar Agora
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Chips -->
        <div id="instances-list" class="row">
            <!-- Loading State -->
            <div class="col-12 text-center py-5">
                <div class="spinner-grow text-primary" role="status"></div>
                <p class="mt-3 text-secondary fw-bold">Comunicando com o servidor de instâncias...</p>
            </div>
        </div>
    </div>
</div>

<!-- Template Premium para cada Chip -->
<template id="instance-card-template">
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="glass-card h-100 d-flex flex-column">
            <div class="card-body p-4 flex-grow-1">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div class="chip-icon"><i class="fab fa-whatsapp"></i></div>
                    <div class="text-end">
                        <span class="badge bg-dark border border-secondary session-id-text fw-normal">Sessão: ---</span>
                        <div class="mt-2 d-flex align-items-center justify-content-end">
                            <span class="status-pulse"></span>
                            <span class="status-text small fw-bold">Carregando...</span>
                        </div>
                    </div>
                </div>

                <h4 class="phone-number fw-bold mb-1">...</h4>
                <p class="text-secondary small mb-4 maturation-label">Aguardando dados de maturação...</p>

                <div class="mb-4">
                    <div class="d-flex justify-content-between small text-secondary mb-1">
                        <span>Progresso de Confiabilidade</span>
                        <span class="percent-text">0%</span>
                    </div>
                    <div class="progress progress-maturation">
                        <div class="progress-bar bg-primary" id="progress-bar-el" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>

                <div class="row g-2 mb-4">
                    <div class="col-6">
                        <div class="bg-dark p-2 rounded text-center border border-secondary">
                            <div class="small text-secondary">Tempo Online</div>
                            <div class="uptime-text fw-bold">0m</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-dark p-2 rounded text-center border border-secondary">
                            <div class="small text-secondary">Saúde</div>
                            <div class="health-text fw-bold text-success">Excelente</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-transparent border-secondary p-4 pt-0">
                <div class="d-grid">
                    <button class="btn btn-outline-danger btn-action reset-instance-btn">
                        <i class="fas fa-trash-alt me-2"></i> Resetar Apenas Este Chip
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
async function loadInstances() {
    try {
        const botUrl = '<?php echo rtrim(getDynamicConfig("WHATSAPP_API_URL", "http://localhost:3002"), "/"); ?>';
        const token = '<?php echo getDynamicConfig("WHATSAPP_API_TOKEN", "lucastav8012"); ?>';

        const res = await fetch(`${botUrl}/instances?token=${token}`);
        const text = await res.text();
        
        let data;
        try {
            data = JSON.parse(text);
        } catch(e) {
            document.getElementById('instances-list').innerHTML = `
                <div class="col-12 py-5 text-center">
                    <div class="glass-card p-5 border-warning mx-auto" style="max-width: 500px">
                        <i class="fas fa-server fa-3x text-warning mb-3"></i>
                        <h5 class="text-white">Bot Offline no Servidor</h5>
                        <p class="text-secondary">O serviço central do robô não está respondendo em ${botUrl}. Reinicie-o via PM2.</p>
                        <button onclick="location.reload()" class="btn btn-primary btn-sm">Tentar Novamente</button>
                    </div>
                </div>`;
            return;
        }

        const container = document.getElementById('instances-list');
        container.innerHTML = '';

        if (!data.success || data.instances.length === 0) {
            container.innerHTML = `
                <div class="col-12 py-5 text-center">
                    <div class="glass-card p-5 mx-auto" style="max-width: 500px">
                        <i class="fas fa-plug text-primary fa-3x mb-3"></i>
                        <h5 class="text-white">Nenhum Chip Conectado</h5>
                        <p class="text-secondary">Parece que não há sessões ativas no motor do bot neste momento.</p>
                        <a href="index.php" class="btn btn-primary btn-sm">Novo Pareamento</a>
                    </div>
                </div>`;
            return;
        }

        data.instances.forEach(inst => {
            const template = document.getElementById('instance-card-template').content.cloneNode(true);
            
            template.querySelector('.session-id-text').textContent = "Sessão: " + inst.sessionId;
            template.querySelector('.phone-number').textContent = inst.phoneNumber || 'Aguardando Número...';
            
            const pulse = template.querySelector('.status-pulse');
            const statusText = template.querySelector('.status-text');
            
            if (inst.isReady) {
                pulse.classList.add('status-online');
                statusText.textContent = 'ATIVO E OPERANTE';
                statusText.className = 'status-text small fw-bold text-success';
            } else {
                pulse.classList.add('status-offline');
                statusText.textContent = 'ERRO / DESCONECTADO';
                statusText.className = 'status-text small fw-bold text-danger';
            }

            // Uptime
            if (inst.uptimeStart) {
                const diffMin = Math.round((Date.now() - inst.uptimeStart) / 60000);
                template.querySelector('.uptime-text').textContent = diffMin >= 60 ? Math.floor(diffMin/60)+'h '+(diffMin%60)+'m' : diffMin + 'm';
            }

            // Maturação & Saúde
            const mLabel = template.querySelector('.maturation-label');
            const pBar = template.querySelector('#progress-bar-el');
            const pText = template.querySelector('.percent-text');
            const healthText = template.querySelector('.health-text');

            if (inst.safetyPausedUntil && inst.safetyPausedUntil > Date.now()) {
                mLabel.textContent = '⚠️ EM PAUSA DE SEGURANÇA (Risco Detectado)';
                mLabel.className = 'maturation-label text-danger fw-bold small mb-4';
                healthText.textContent = 'Instável';
                healthText.className = 'health-text fw-bold text-warning';
                pBar.style.width = '100%';
                pBar.className = 'progress-bar bg-danger';
                pText.textContent = 'BLOQUEIO';
            } else {
                // Cálculo de maturação visual (Simulação do dia seguinte)
                const now = new Date();
                const maturationDate = new Date(inst.maturationDate || now);
                const isSameDay = now.toDateString() === maturationDate.toDateString();

                if (isSameDay) {
                    mLabel.textContent = '🛡️ Modulo Aquecimento Ativo (Libera 00:01)';
                    pBar.style.width = '45%';
                    pText.textContent = '45%';
                } else {
                    mLabel.textContent = '💎 Chip VIP Maturado e Pronto';
                    pBar.style.width = '100%';
                    pBar.className = 'progress-bar bg-success';
                    pText.textContent = '100%';
                }
            }

            const resetBtn = template.querySelector('.reset-instance-btn');
            resetBtn.onclick = () => confirmReset(inst.sessionId);

            container.appendChild(template);
        });
    } catch (err) {
        console.error(err);
    }
}

function confirmReset(sessionId) {
    Swal.fire({
        title: 'Resetar este Chip?',
        text: `Isso apagará APENAS a sessão "${sessionId}". Use isso se o chip tomou ban. O outro chip ficará intacto!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ff4444',
        cancelButtonColor: '#22c55e',
        confirmButtonText: 'Sim, deletar agora',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Limpando sessão...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            
            try {
                const botUrl = '<?php echo rtrim(getDynamicConfig("WHATSAPP_API_URL", "http://localhost:3002"), "/"); ?>';
                const token = '<?php echo getDynamicConfig("WHATSAPP_API_TOKEN", "lucastav8012"); ?>';

                const res = await fetch(`${botUrl}/reset-instance/${sessionId}?token=${token}`, { method: 'POST' });
                const data = await res.json();

                if (data.success) {
                    Swal.fire('Chip Removido!', 'A memória desse chip foi limpa do servidor.', 'success');
                    loadInstances();
                } else {
                    Swal.fire('Erro', data.message, 'error');
                }
            } catch (err) {
                Swal.fire('Erro de Conexão', 'O bot não respondeu ao pedido de reset.', 'error');
            }
        }
    });
}

loadInstances();
setInterval(loadInstances, 10000); // Atualiza a cada 10s para ver se voltaram
</script>

<?php include 'includes/footer.php'; ?>
