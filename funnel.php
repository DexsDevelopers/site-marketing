<?php
require_once 'includes/config.php';
require_once 'includes/auth_helper.php';
require_once 'includes/db_connect.php';
requireLogin();

// 1. Carregar Campanhas/Grupos
$campanhas = fetchData($pdo, "SELECT * FROM marketing_campanhas ORDER BY id ASC");
if (empty($campanhas)) {
    // Se não existir nenhuma, criar a padrão
    $pdo->exec("INSERT INTO marketing_campanhas (nome, ativo, membros_por_dia_grupo) VALUES ('Campanha Principal', 1, 5)");
    $campanhas = fetchData($pdo, "SELECT * FROM marketing_campanhas ORDER BY id ASC");
}

// 2. Definir Campanha Atual
$currentCampanhaId = isset($_GET['campanha_id']) ? intval($_GET['campanha_id']) : ($campanhas[0]['id'] ?? 1);
$currentCampanha = null;
foreach ($campanhas as $c) {
    if ($c['id'] == $currentCampanhaId) {
        $currentCampanha = $c;
        break;
    }
}
// Fallback se ID inválido
if (!$currentCampanha && !empty($campanhas)) {
    $currentCampanha = $campanhas[0];
    $currentCampanhaId = $currentCampanha['id'];
}

// 3. Carregar Mensagens desta Campanha
$mensagens = fetchData($pdo, "SELECT * FROM marketing_mensagens WHERE campanha_id = ? ORDER BY ordem ASC", [$currentCampanhaId]);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketing Hub | Funil - <?= htmlspecialchars($currentCampanha['nome']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .loading-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); display: none; justify-content: center; align-items: center; z-index: 9999; }
        
        /* Estilo do Dropdown de Campanha */
        .campaign-selector {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            margin-right: 1rem;
        }
        .campaign-selector option {
            background: #1a1a1a;
            color: #fff;
        }

        /* Toggle Switch para Ativo/Inativo */
        .switch {
            position: relative;
            display: inline-block;
            width: 40px;
            height: 20px;
            margin-right: 10px;
        }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #444;
            transition: .4s;
            border-radius: 20px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(20px); }

        .step-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .inactive-step {
            opacity: 0.5;
            filter: grayscale(0.8);
        }
    </style>
</head>
<body>
    <div class="loading-overlay" id="loading"><div class="spinner"><i class="fas fa-circle-notch fa-spin fa-3x"></i></div></div>

    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="header animate-fade-in" style="flex-wrap: wrap; gap: 1rem;">
                <div style="flex: 1;">
                    <h1 style="margin: 0; font-size: 2.5rem; letter-spacing: -1.5px;">Funil de Vendas</h1>
                    <div style="display: flex; align-items: center; margin-top: 0.5rem;">
                        <span style="color: var(--text-dim); margin-right: 10px;">Campanha:</span>
                        <select class="campaign-selector" onchange="changeCampaign(this.value)">
                            <?php foreach ($campanhas as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $c['id'] == $currentCampanhaId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn-modern small" onclick="newCampaign()">
                            <i class="fas fa-plus"></i> Novo Grupo
                        </button>
                    </div>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <div style="text-align: right; margin-right: 1rem;">
                         <small style="color: #666; display: block;">Membros Diários</small>
                         <strong style="color: var(--primary);"><?= $currentCampanha['membros_por_dia_grupo'] ?? 5 ?></strong>
                    </div>
                    <button class="btn-modern accent" onclick="triggerDisparos()" id="btn-trigger">
                        <i class="fas fa-paper-plane"></i> Executar Agora
                    </button>
                    <button class="btn-modern" onclick="addNewStep()">
                        <i class="fas fa-plus"></i> Nova Mensagem
                    </button>
                </div>
            </header>

            <div class="funnel-sequence" id="funnel-container">
                <?php if (empty($mensagens)): ?>
                    <div class="panel" id="empty-state">
                        <div style="text-align: center; padding: 3rem; color: var(--text-dim);">
                            <i class="fas fa-layer-group" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                            <h3>Este grupo está vazio</h3>
                            <p>Adicione mensagens para começar seu funil nesta campanha.</p>
                            <button class="btn-modern accent" onclick="addNewStep()" style="margin-top: 1rem;">
                                <i class="fas fa-plus"></i> Adicionar Primeira Mensagem
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <?php foreach ($mensagens as $msg): ?>
                    <div class="funnel-step animate-fade-in <?= ($msg['ativo'] ?? 1) ? '' : 'inactive-step' ?>" id="step-<?= $msg['id'] ?>" data-id="<?= $msg['id'] ?>">
                        <div class="step-indicator">
                            <div class="step-number"><?= $msg['ordem'] ?></div>
                        </div>
                        <div class="step-content">
                            <div class="step-header-row">
                                <div class="delay-badge <?= $msg['delay_apos_anterior_minutos'] > 0 ? 'badge-primary' : '' ?>">
                                    <i class="fas fa-clock"></i>
                                    <span>Esperar <?= $msg['delay_apos_anterior_minutos'] ?> min após anterior</span>
                                </div>
                                <div style="display: flex; align-items: center;">
                                    <span style="font-size: 0.8rem; color: #666; margin-right: 8px;">Ativo:</span>
                                    <label class="switch">
                                        <input type="checkbox" onchange="toggleActive(<?= $msg['id'] ?>, this.checked)" <?= ($msg['ativo'] ?? 1) ? 'checked' : '' ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                            <textarea onchange="updateStep(<?= $msg['id'] ?>)" id="content-<?= $msg['id'] ?>"><?= htmlspecialchars($msg['conteudo']) ?></textarea>
                        </div>
                        <div class="step-actions">
                            <button class="btn-modern" style="background: rgba(255,255,255,0.05); color: var(--text-main);" onclick="editStep(<?= $msg['id'] ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-modern" style="background: rgba(255,59,59,0.1); color: var(--primary);" onclick="deleteStep(<?= $msg['id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const API_URL = 'api_marketing_ajax.php';
        const currentCampanhaId = <?= $currentCampanhaId ?>;

        function changeCampaign(id) {
            window.location.href = '?campanha_id=' + id;
        }

        async function newCampaign() {
            const { value: nome } = await Swal.fire({
                title: 'Nome do Novo Grupo/Campanha',
                input: 'text',
                inputLabel: 'Ex: Funil Black Friday, Aquecimento VIP...',
                inputPlaceholder: 'Digite o nome...',
                showCancelButton: true,
                background: '#151518', color: '#e0e0e0', confirmButtonColor: '#ff3b3b'
            });

            if (nome) {
                showLoading();
                const fd = new FormData();
                fd.append('action', 'save_campaign');
                fd.append('nome', nome);
                const res = await fetch(API_URL, { method: 'POST', body: fd }).then(r => r.json());
                hideLoading();
                if (res.success) {
                    window.location.href = '?campanha_id=' + res.id;
                } else {
                    Swal.fire('Erro', res.message, 'error');
                }
            }
        }

        async function toggleActive(id, isActive) {
            const fd = new FormData();
            fd.append('action', 'toggle_message_active');
            fd.append('id', id);
            fd.append('ativo', isActive ? 1 : 0);
            
            // UI feedback imediato
            const step = document.getElementById('step-' + id);
            if (isActive) step.classList.remove('inactive-step');
            else step.classList.add('inactive-step');

            await fetch(API_URL, { method: 'POST', body: fd });
        }

        async function triggerDisparos() {
            const btn = document.getElementById('btn-trigger');
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ...';
            
            try {
                // Passar campanha_id para trigger saber o que disparar (futuro)
                // Por enquanto trigger dispara a campanha padrao (id 1) ou se ajustarmos o backend.
                // Idealmente o backend deve ler 'campanha_id' no trigger.
                const res = await fetch(API_URL + '?action=trigger_disparos&campanha_id=' + currentCampanhaId).then(r => r.json());
                
                if (res.success) {
                    Swal.fire({
                        icon: 'success', title: 'Disparos Iniciados!',
                        text: res.message, toast: true, position: 'top-end',
                        showConfirmButton: false, timer: 3000, background: '#111114', color: '#fff'
                    });
                } else {
                    Swal.fire('Erro', res.message, 'error');
                }
            } catch(e) {
                Swal.fire('Erro', 'Falha na conexão', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        }

        async function addNewStep() {
            const { value: formValues } = await Swal.fire({
                title: 'Nova Mensagem',
                html:
                    '<label style="display:block; text-align:left; color:#888;">Conteúdo</label>' +
                    '<textarea id="swal-content" class="swal2-textarea" placeholder="Olá, tudo bem?"></textarea>' +
                    '<label style="display:block; text-align:left; color:#888; margin-top:10px;">Delay (minutos)</label>' +
                    '<input id="swal-delay" type="number" class="swal2-input" value="0" min="0">',
                focusConfirm: false,
                background: '#151518', color: '#e0e0e0', confirmButtonColor: '#ff3b3b',
                preConfirm: () => {
                    return {
                        conteudo: document.getElementById('swal-content').value,
                        delay: document.getElementById('swal-delay').value
                    }
                }
            });

            if (formValues && formValues.conteudo) {
                saveStep(0, formValues.conteudo, formValues.delay);
            }
        }

        async function editStep(id) {
            const content = document.getElementById('content-' + id).value;
            // Pegar delay do badge ou atributo data
            // Simplificando: vamos pegar do badge via regex
            const badge = document.querySelector(`#step-${id} .delay-badge span`).innerText;
            const delay = badge.match(/\d+/)[0];

            const { value: formValues } = await Swal.fire({
                title: 'Editar Mensagem',
                html:
                    '<textarea id="swal-content" class="swal2-textarea">' + content + '</textarea>' +
                    '<input id="swal-delay" type="number" class="swal2-input" value="' + delay + '">',
                focusConfirm: false,
                background: '#151518', color: '#e0e0e0', confirmButtonColor: '#ff3b3b',
                preConfirm: () => {
                    return {
                        conteudo: document.getElementById('swal-content').value,
                        delay: document.getElementById('swal-delay').value
                    }
                }
            });

            if (formValues) {
                saveStep(id, formValues.conteudo, formValues.delay);
            }
        }

        async function updateStep(id) {
            // Auto-save on blur do textarea
            // Implementacao simplificada: chama saveStep com delay atual
            const content = document.getElementById('content-' + id).value;
            const badge = document.querySelector(`#step-${id} .delay-badge span`).innerText;
            const delay = badge.match(/\d+/)[0];
            
            // Nao mostrar spinner no update silencioso
            const fd = new FormData();
            fd.append('action', 'save_step');
            fd.append('id', id);
            fd.append('conteudo', content);
            fd.append('delay', delay);
            fd.append('campanha_id', currentCampanhaId);
            
            fetch(API_URL, { method: 'POST', body: fd });
        }

        async function saveStep(id, conteudo, delay) {
            showLoading();
            const fd = new FormData();
            fd.append('action', 'save_step');
            fd.append('id', id);
            fd.append('conteudo', conteudo);
            fd.append('delay', delay);
            fd.append('campanha_id', currentCampanhaId); // IMPORTANTE: Enviar ID da campanha atual

            try {
                const res = await fetch(API_URL, { method: 'POST', body: fd }).then(r => r.json());
                if (res.success) location.reload();
                else Swal.fire('Erro', res.message, 'error');
            } catch(e) {
                Swal.fire('Erro', 'Falha na conexão', 'error');
            } finally {
                hideLoading();
            }
        }

        async function deleteStep(id) {
            const res = await Swal.fire({
                title: 'Tem certeza?', text: 'Remover esta mensagem?', icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#ff3b3b',  background: '#151518', color: '#e0e0e0'
            });

            if (res.isConfirmed) {
                showLoading();
                const fd = new FormData();
                fd.append('action', 'delete_step');
                fd.append('id', id);
                try {
                    const r = await fetch(API_URL, { method: 'POST', body: fd }).then(j => j.json());
                    if (r.success) {
                        document.getElementById('step-' + id).remove();
                        if (!document.querySelector('.funnel-step')) location.reload();
                    } else Swal.fire('Erro', r.message, 'error');
                } catch(e) { Swal.fire('Erro', 'Falha', 'error'); } 
                finally { hideLoading(); }
            }
        }

        function showLoading() { document.getElementById('loading').style.display = 'flex'; }
        function hideLoading() { document.getElementById('loading').style.display = 'none'; }
    </script>
</body>
</html>