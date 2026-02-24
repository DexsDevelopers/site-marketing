<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/auth_helper.php';

requireLogin();

$pageTitle = "Gerenciar Chips";
include 'includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-dark text-white border-secondary shadow">
                    <div class="card-body">
                        <h2 class="h4 mb-2"><i class="fas fa-microchip me-2 text-primary"></i>Controle Individual de Chips</h2>
                        <p class="text-secondary small">Gerencie cada conexão de forma independente. Ideal para limpar chips bloqueados sem afetar os saudáveis.</p>
                    </div>
                </div>
            </div>
        </div>

        <div id="instances-list" class="row">
            <!-- Carregado via JS -->
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-secondary">Buscando instâncias ativas...</p>
            </div>
        </div>
    </div>
</div>

<template id="instance-card-template">
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100 bg-dark text-white border-secondary card-hover shadow-sm">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="badge bg-primary session-id">Sessão</span>
                <span class="status-badge badge">Status</span>
            </div>
            <div class="card-body">
                <h5 class="card-title mb-3 phone-number text-info">Carregando...</h5>
                <div class="small text-secondary mb-3">
                    <div><i class="fas fa-clock me-1"></i> Uptime: <span class="uptime">0m</span></div>
                    <div><i class="fas fa-shield-alt me-1"></i> Maturação: <span class="maturation-status">---</span></div>
                </div>
                
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-danger btn-sm reset-instance-btn">
                        <i class="fas fa-trash-alt me-1"></i> Resetar Apenas Este Chip
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
        
        // Debug: Se não for JSON, mostrar o que é
        let data;
        try {
            data = JSON.parse(text);
        } catch(e) {
            console.error("Resposta não é JSON:", text);
            document.getElementById('instances-list').innerHTML = `
                <div class="col-12 text-center text-warning p-4">
                    <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                    <p>O Bot de WhatsApp parece estar offline ou retornou um erro inesperado.</p>
                    <small class="text-secondary">${text.substring(0, 100)}</small>
                </div>`;
            return;
        }

        const container = document.getElementById('instances-list');
        container.innerHTML = '';

        if (!data.success || data.instances.length === 0) {
            container.innerHTML = '<div class="col-12 text-center text-secondary">Nenhuma instância ativa no momento.</div>';
            return;
        }

        data.instances.forEach(inst => {
            const template = document.getElementById('instance-card-template').content.cloneNode(true);
            
            template.querySelector('.session-id').textContent = inst.sessionId;
            template.querySelector('.phone-number').textContent = inst.phoneNumber || 'Número não identificado';
            
            const statusBadge = template.querySelector('.status-badge');
            statusBadge.textContent = inst.isReady ? 'CONECTADO' : 'INICIALIZANDO/ERRO';
            statusBadge.classList.add(inst.isReady ? 'bg-success' : 'bg-warning');

            // Calcular Uptime amigável
            if (inst.uptimeStart) {
                const diffMin = Math.round((Date.now() - inst.uptimeStart) / 60000);
                template.querySelector('.uptime').textContent = diffMin + ' min';
            }

            // Status de Maturação
            const mStat = template.querySelector('.maturation-status');
            if (inst.safetyPausedUntil && inst.safetyPausedUntil > Date.now()) {
                mStat.textContent = 'PAUSA DE SEGURANÇA';
                mStat.className = 'text-danger fw-bold';
            } else {
                mStat.textContent = inst.isReady ? 'Normal / Aquecendo' : 'Aguardando';
            }

            const resetBtn = template.querySelector('.reset-instance-btn');
            resetBtn.onclick = () => confirmReset(inst.sessionId);

            container.appendChild(template);
        });
    } catch (err) {
        document.getElementById('instances-list').innerHTML = `<div class="col-12 text-center text-danger">Erro ao carregar instâncias: ${err.message}</div>`;
    }
}

function confirmReset(sessionId) {
    Swal.fire({
        title: 'Tem certeza?',
        text: `Isso apagará APENAS a sessão "${sessionId}". O outro chip continuará conectado normalmente!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, resetar este chip',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const botUrl = '<?php echo rtrim(getDynamicConfig("WHATSAPP_API_URL", "http://localhost:3002"), "/"); ?>';
                const token = '<?php echo getDynamicConfig("WHATSAPP_API_TOKEN", "lucastav8012"); ?>';

                const res = await fetch(`${botUrl}/reset-instance/${sessionId}?token=${token}`, { method: 'POST' });
                const data = await res.json();

                if (data.success) {
                    Swal.fire('Resetado!', 'A sessão foi limpa com sucesso.', 'success');
                    loadInstances();
                } else {
                    Swal.fire('Erro', data.message, 'error');
                }
            } catch (err) {
                Swal.fire('Erro', 'Não foi possível comunicar com o bot.', 'error');
            }
        }
    });
}

// Carregar ao iniciar e a cada 10 segundos
loadInstances();
setInterval(loadInstances, 10000);
</script>

<?php include 'includes/footer.php'; ?>
