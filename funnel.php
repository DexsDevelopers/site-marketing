<?php
require_once 'includes/config.php';
require_once 'includes/auth_helper.php';
require_once 'includes/db_connect.php';
requireLogin();

// Buscar mensagens atuais do funil
$mensagens = fetchData($pdo, "SELECT * FROM marketing_mensagens WHERE campanha_id = 1 ORDER BY ordem ASC");
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketing Hub | Funil</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
    </style>
</head>

<body>
    <div class="loading-overlay" id="loading">
        <div class="spinner"><i class="fas fa-circle-notch fa-spin fa-3x"></i></div>
    </div>

    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="header animate-fade-in">
                <div>
                    <h1 style="margin: 0; font-size: 2.5rem; letter-spacing: -1.5px;">Funil de Vendas</h1>
                    <p style="color: var(--text-dim); margin-top: 0.5rem;">Configure a sequência de mensagens automática
                        para seus leads.</p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button class="btn-modern accent" onclick="triggerDisparos()" id="btn-trigger">
                        <i class="fas fa-paper-plane"></i> Iniciar Agora
                    </button>
                    <button class="btn-modern" onclick="addNewStep()">
                        <i class="fas fa-plus"></i> Novo Passo
                    </button>
                </div>
            </header>

            <div class="funnel-sequence" id="funnel-container">
                <?php if (empty($mensagens)): ?>
                <div class="panel" id="empty-state">
                    <div style="text-align: center; padding: 2rem; color: var(--text-dim);">
                        <i class="fas fa-layer-group" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <p>Nenhuma mensagem configurada. Clique em "Novo Passo" para começar.</p>
                    </div>
                </div>
                <?php
endif; ?>

                <?php foreach ($mensagens as $msg): ?>
                <div class="funnel-step animate-fade-in" id="step-<?= $msg['id']?>" data-id="<?= $msg['id']?>">
                    <div class="step-indicator">
                        <div class="step-number">
                            <?= $msg['ordem']?>
                        </div>
                    </div>
                    <div class="step-content">
                        <div class="delay-badge <?= $msg['delay_apos_anterior_minutos'] > 0 ? 'badge-primary' : ''?>">
                            <i class="fas fa-clock"></i>
                            <span>Esperar
                                <?= $msg['delay_apos_anterior_minutos']?> minutos após o passo anterior
                            </span>
                        </div>
                        <textarea onchange="updateStep(<?= $msg['id']?>)"
                            id="content-<?= $msg['id']?>"><?= htmlspecialchars($msg['conteudo'])?></textarea>
                    </div>
                    <div class="step-actions">
                        <button class="btn-modern" style="background: rgba(255,255,255,0.05); color: var(--text-main);"
                            onclick="editStep(<?= $msg['id']?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-modern" style="background: rgba(255,59,59,0.1); color: var(--primary);"
                            onclick="deleteStep(<?= $msg['id']?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <?php
endforeach; ?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const API_URL = 'api_marketing_ajax.php';

        async function triggerDisparos() {
            console.log('🚀 triggerDisparos() chamado');
            const btn = document.getElementById('btn-trigger');
            console.log('📍 Botão encontrado:', btn);
            const originalContent = btn.innerHTML;

            try {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
                console.log('⏳ Fazendo requisição para api_marketing_ajax.php?action=trigger_disparos');

                const response = await fetch('api_marketing_ajax.php?action=trigger_disparos');
                console.log('📡 Resposta recebida:', response.status, response.statusText);

                const data = await response.json();
                console.log('📦 Dados JSON:', data);

                if (data.success) {
                    console.log('✅ Sucesso! Mostrando alerta...');
                    Swal.fire({
                        icon: 'success',
                        title: 'Disparos Iniciados!',
                        text: 'O robô começou a processar a fila agora.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 4000,
                        timerProgressBar: true,
                        background: '#111114',
                        color: '#f8f9fa',
                        customClass: { popup: 'premium-swal' }
                    });
                } else {
                    console.log('❌ Erro retornado:', data.message);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: data.message,
                        customClass: { popup: 'premium-swal' }
                    });
                }
            } catch (e) {
                console.error('💥 Exceção capturada:', e);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Falha na conexão: ' + e.message,
                    customClass: { popup: 'premium-swal' }
                });
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalContent;
                console.log('🔄 Botão restaurado');
            }
        }

        async function addNewStep() {
            const { value: formValues } = await Swal.fire({
                title: 'Novo Passo do Funil',
                html:
                    '<label style="color: #94a3b8; display: block; margin-bottom: 8px; text-align: left; font-size: 0.9rem;">Mensagem do WhatsApp</label>' +
                    '<textarea id="swal-content" class="swal2-textarea" style="height: 150px;" placeholder="Digite aqui o que o Bot deve dizer..."></textarea>' +
                    '<label style="color: #94a3b8; display: block; margin-top: 15px; margin-bottom: 8px; text-align: left; font-size: 0.9rem;">Delay (minutos após anterior)</label>' +
                    '<input id="swal-delay" type="number" class="swal2-input" value="0" min="0">',
                focusConfirm: false,
                background: '#111114',
                color: '#f8f9fa',
                confirmButtonColor: '#ff3b3b',
                confirmButtonText: '<i class="fas fa-save"></i> Criar Passo',
                customClass: { popup: 'premium-swal' },
                preConfirm: () => {
                    return {
                        conteudo: document.getElementById('swal-content').value,
                        delay: document.getElementById('swal-delay').value
                    }
                }
            });

            if (formValues) {
                if (!formValues.conteudo) {
                    return Swal.fire('Erro', 'O conteúdo é obrigatório', 'error');
                }

                showLoading();
                const formData = new FormData();
                formData.append('action', 'save_step');
                formData.append('conteudo', formValues.conteudo);
                formData.append('delay', formValues.delay);

                try {
                    const res = await fetch(API_URL, { method: 'POST', body: formData });
                    const data = await res.json();
                    if (data.success) {
                        location.reload();
                    } else {
                        Swal.fire('Erro', data.message, 'error');
                    }
                } catch (e) {
                    Swal.fire('Erro', 'Falha na conexão', 'error');
                } finally {
                    hideLoading();
                }
            }
        }

        async function editStep(id) {
            const content = document.getElementById('content-' + id).value;
            const delayBadgeText = document.querySelector('#step-' + id + ' .delay-badge span').innerText;
            const currentDelay = delayBadgeText.match(/\d+/)[0];

            const { value: formValues } = await Swal.fire({
                title: 'Editar Passo',
                html:
                    '<label style="color: #888; display: block; margin-bottom: 5px; text-align: left;">Mensagem</label>' +
                    `<textarea id="swal-content" class="swal2-textarea">${content}</textarea>` +
                    '<label style="color: #888; display: block; margin-bottom: 5px; text-align: left;">Delay (minutos após anterior)</label>' +
                    `<input id="swal-delay" type="number" class="swal2-input" value="${currentDelay}" min="0">`,
                focusConfirm: false,
                background: '#151518',
                color: '#e0e0e0',
                confirmButtonColor: '#ff3b3b',
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
            const content = document.getElementById('content-' + id).value;
            const delayBadgeText = document.querySelector('#step-' + id + ' .delay-badge span').innerText;
            const currentDelay = delayBadgeText.match(/\d+/)[0];
            saveStep(id, content, currentDelay);
        }

        async function saveStep(id, conteudo, delay) {
            showLoading();
            const formData = new FormData();
            formData.append('action', 'save_step');
            formData.append('id', id);
            formData.append('conteudo', conteudo);
            formData.append('delay', delay);

            try {
                const res = await fetch(API_URL, { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    Swal.fire('Erro', data.message, 'error');
                }
            } catch (e) {
                Swal.fire('Erro', 'Falha na conexão', 'error');
            } finally {
                hideLoading();
            }
        }

        async function deleteStep(id) {
            const result = await Swal.fire({
                title: 'Tem certeza?',
                text: "Esta mensagem será removida da sequência.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff3b3b',
                cancelButtonColor: '#333',
                confirmButtonText: 'Sim, remover!',
                cancelButtonText: 'Cancelar',
                background: '#151518',
                color: '#e0e0e0'
            });

            if (result.isConfirmed) {
                showLoading();
                const formData = new FormData();
                formData.append('action', 'delete_step');
                formData.append('id', id);

                try {
                    const res = await fetch(API_URL, { method: 'POST', body: formData });
                    const data = await res.json();
                    if (data.success) {
                        document.getElementById('step-' + id).remove();
                        if (document.querySelectorAll('.funnel-step').length === 0) {
                            location.reload();
                        }
                        Swal.fire({
                            icon: 'success',
                            title: 'Removido!',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                    } else {
                        Swal.fire('Erro', data.message, 'error');
                    }
                } catch (e) {
                    Swal.fire('Erro', 'Falha na conexão', 'error');
                } finally {
                    hideLoading();
                }
            }
        }

        function showLoading() { document.getElementById('loading').style.display = 'flex'; }
        function hideLoading() { document.getElementById('loading').style.display = 'none'; }
    </script>
</body>

</html>