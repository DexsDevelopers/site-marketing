<?php
require_once 'includes/config.php';
require_once 'includes/auth_helper.php';
require_once 'includes/db_connect.php';
requireLogin();

// Buscar configurações atuais da campanha
$campanha = fetchOne($pdo, "SELECT * FROM marketing_campanhas WHERE id = 1");
if (!$campanha) {
    // Garantir que existe a linha 1
    $pdo->exec("INSERT IGNORE INTO marketing_campanhas (id, nome, ativo) VALUES (1, 'Campanha Padrão', 0)");
    $campanha = fetchOne($pdo, "SELECT * FROM marketing_campanhas WHERE id = 1");
}
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

    <title>Marketing Hub | Configurações</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="header animate-fade-in">
                <div>
                    <h1 style="margin: 0; font-size: 2.5rem; letter-spacing: -1.5px;">Configurações</h1>
                    <p style="color: var(--text-dim); margin-top: 0.5rem;">Ajuste os parâmetros de envio e segurança.
                    </p>
                </div>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button class="btn-modern accent" onclick="triggerDisparos()" id="btn-trigger">
                        <i class="fas fa-paper-plane"></i> Iniciar Agora
                    </button>
                    <button class="btn-modern secondary" onclick="restartBot()">
                        <i class="fas fa-sync"></i> Reiniciar Bot
                    </button>
                </div>
            </header>

            <div class="panel animate-fade-in">
                <div class="panel-title"><i class="fas fa-robot"></i> Automação de Marketing</div>

                <form id="settingsForm" onsubmit="saveSettings(event)">
                    <div class="form-group settings-header-status"
                        style="display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--glass-border); margin-bottom: 2rem;">
                        <div>
                            <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 0.3rem;">Status da Automação
                            </div>
                            <div style="color: var(--text-dim); font-size: 0.85rem;">Ativar ou desativar envios
                                automáticos.</div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="ativo" id="ativo" <?= $campanha['ativo'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="settings-grid">
                        <div class="form-group">
                            <label><i class="fas fa-users"></i> Limite de Novos Membros/Dia</label>
                            <input type="number" name="membros_por_dia" class="form-control"
                                value="<?= $campanha['membros_por_dia_grupo']?>" min="1" max="50">
                            <small style="color: var(--text-dim); display: block; margin-top: 0.5rem;">Limite diário
                                para entrada no funil.</small>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-shield-alt"></i> Intervalo entre Mensagens (Minutos)</label>
                            <div class="interval-inputs">
                                <input type="number" name="min_interval" class="form-control"
                                    value="<?= $campanha['intervalo_min_minutos']?>" placeholder="Mínimo" min="1">
                                <span style="color: var(--text-dim); padding: 0 10px;">até</span>
                                <input type="number" name="max_interval" class="form-control"
                                    value="<?= $campanha['intervalo_max_minutos']?>" placeholder="Máximo" min="1">
                            </div>
                            <small style="color: var(--text-dim); display: block; margin-top: 0.5rem;">Tempo aleatório
                                entre disparos.</small>
                        </div>
                    </div>

                    <div class="form-group"
                        style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--glass-border); display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn-modern">
                            <i class="fas fa-save"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        async function triggerDisparos() {
            const btn = document.getElementById('btn-trigger');
            const originalContent = btn.innerHTML;

            try {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>...';

                const response = await fetch('api_marketing_ajax.php?action=trigger_disparos');
                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Iniciado!',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        background: '#111114',
                        color: '#f8f9fa',
                        customClass: { popup: 'premium-swal' }
                    });
                }
            } catch (e) { }
            finally {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        }

        async function restartBot() {
            const res = await Swal.fire({
                title: 'Reiniciar Robô?',
                text: "Isso forçará a reconexão do robô com o WhatsApp.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff3b3b',
                background: '#111114',
                color: '#f8f9fa',
                customClass: { popup: 'premium-swal' }
            });

            if (res.isConfirmed) {
                // Comando de reset
                await fetch('api_dashboard.php?action=restart_bot');
                Swal.fire('Comando Enviado!', 'O robô está reiniciando...', 'success');
            }
        }

        async function saveSettings(e) {
            e.preventDefault();
            const form = e.target;
            const btn = form.querySelector('button[type="submit"]');
            const originalContent = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

            const formData = new FormData(form);
            formData.append('action', 'save_campaign_settings');
            // Checkbox handling
            if (!form.querySelector('#ativo').checked) {
                formData.set('ativo', 0);
            } else {
                formData.set('ativo', 1);
            }

            try {
                const response = await fetch('api_marketing_ajax.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Salvo!',
                        text: 'Configurações atualizadas com sucesso.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        background: '#151518',
                        color: '#fff'
                    });
                } else {
                    Swal.fire('Erro', data.message, 'error');
                }
            } catch (error) {
                Swal.fire('Erro', 'Falha ao salvar configurações', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        }
    </script>
</body>

</html>