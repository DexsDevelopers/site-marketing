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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketing Hub | Configurações</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="header">
                <div>
                    <h1>Configurações do Marketing</h1>
                    <p style="color: var(--text-dim);">Ajuste os parâmetros de envio e segurança do robô.</p>
                </div>
            </header>

            <div class="panel animate-fade-in" style="max-width: 800px;">
                <div class="panel-title"><i class="fas fa-robot"></i> Automação de Marketing</div>

                <form id="settingsForm" onsubmit="saveSettings(event)">
                    <div class="form-group"
                        style="display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--glass-border);">
                        <div>
                            <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 0.3rem;">Status da Automação
                            </div>
                            <div style="color: var(--text-dim); font-size: 0.85rem;">Ativar ou desativar o envio
                                automático para novos membros.</div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="ativo" id="ativo" <?=$campanha['ativo'] ? 'checked' : ''?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
                        <div class="form-group">
                            <label><i class="fas fa-users"></i> Limite de Novos Membros/Dia</label>
                            <input type="number" name="membros_por_dia" class="form-control"
                                value="<?= $campanha['membros_por_dia_grupo']?>" min="1" max="50">
                            <small style="color: var(--text-dim); display: block; margin-top: 0.5rem;">Quantidade de
                                pessoas que entrarão no funil por dia.</small>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-shield-alt"></i> Intervalo entre Mensagens (Segurança)</label>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <input type="number" name="min_interval" class="form-control"
                                    value="<?= $campanha['intervalo_min_minutos']?>" placeholder="Min" min="1">
                                <span style="color: var(--text-dim);">até</span>
                                <input type="number" name="max_interval" class="form-control"
                                    value="<?= $campanha['intervalo_max_minutos']?>" placeholder="Max" min="1">
                            </div>
                            <small style="color: var(--text-dim); display: block; margin-top: 0.5rem;">Tempo aleatório
                                em minutos entre cada disparo do robô.</small>
                        </div>
                    </div>

                    <div class="form-group"
                        style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--glass-border); display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn-modern" style="padding: 1rem 3rem;">
                            <i class="fas fa-save"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>

            <div class="panel animate-fade-in"
                style="max-width: 800px; animation-delay: 0.1s; border-color: rgba(245, 158, 11, 0.3);">
                <div class="panel-title" style="color: #f59e0b;"><i class="fas fa-exclamation-triangle"></i> Dicas de
                    Segurança</div>
                <div style="color: var(--text-dim); font-size: 0.9rem; line-height: 1.6;">
                    <p>• <strong>Evite excessos:</strong> Comece com limites baixos (5-10 membros/dia) e aumente
                        gradualmente.</p>
                    <p>• <strong>Variação:</strong> Use intervalos aleatórios amplos para simular comportamento humano.
                    </p>
                    <p>• <strong>Conteúdo:</strong> Mensagens muito curtas ou repetitivas aumentam o risco de bloqueio.
                    </p>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        async function saveSettings(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            formData.append('action', 'save_campaign_settings');

            // Fix checkbox value
            if (!formData.has('ativo')) {
                formData.append('ativo', '0');
            } else {
                formData.set('ativo', '1');
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
                        title: 'Configurações Salvas!',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        background: '#151518',
                        color: '#e0e0e0'
                    });
                } else {
                    Swal.fire('Erro', data.message, 'error');
                }
            } catch (err) {
                Swal.fire('Erro', 'Falha ao salvar configurações', 'error');
            }
        }
    </script>
</body>

</html>