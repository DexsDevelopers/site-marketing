<?php
require_once 'includes/config.php';
require_once 'includes/auth_helper.php';
require_once 'includes/db_connect.php';
requireLogin();

// Filtros básicos
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (telefone LIKE ? OR nome LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status) {
    $where .= " AND status = ?";
    $params[] = $status;
}

// Stats
$stats = [
    'total' => fetchOne($pdo, "SELECT COUNT(*) as c FROM marketing_membros")['c'] ?? 0,
    'novos' => fetchOne($pdo, "SELECT COUNT(*) as c FROM marketing_membros WHERE status = 'novo'")['c'] ?? 0,
    'progresso' => fetchOne($pdo, "SELECT COUNT(*) as c FROM marketing_membros WHERE status = 'em_progresso'")['c'] ?? 0,
    'concluido' => fetchOne($pdo, "SELECT COUNT(*) as c FROM marketing_membros WHERE status = 'concluido'")['c'] ?? 0
];

// Listagem
$leads = fetchData($pdo, "SELECT * FROM marketing_membros $where ORDER BY created_at DESC LIMIT 100", $params);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketing Hub | Leads</title>
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
                    <h1 style="margin: 0; font-size: 2.5rem; letter-spacing: -1.5px;">Gerenciamento de Leads</h1>
                    <p style="color: var(--text-dim); margin-top: 0.5rem;">Acompanhe o progresso dos seus contatos no
                        funil em tempo real.</p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button class="btn-modern accent" onclick="triggerDisparos()" id="btn-trigger">
                        <i class="fas fa-paper-plane"></i> Iniciar Agora
                    </button>
                    <button class="btn-modern secondary" style="color: #ef4444;" onclick="clearAllLeads()">
                        <i class="fas fa-trash-alt"></i> Limpar Tudo
                    </button>
                </div>
            </header>

            <div class="stats-grid animate-fade-in">
                <div class="stat-card">
                    <div class="stat-label">Total de Leads</div>
                    <div class="stat-value"><?= $stats['total'] ?></div>
                    <div style="color: #4facfe; font-size: 0.8rem; font-weight: 600;"><i class="fas fa-users"></i> Base total</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Na Fila</div>
                    <div class="stat-value" style="color: #3b82f6;"><?= $stats['novos'] ?></div>
                    <div style="color: #3b82f6; font-size: 0.8rem; font-weight: 600;"><i class="fas fa-clock"></i> Aguardando</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Em Progresso</div>
                    <div class="stat-value" style="color: #f59e0b;"><?= $stats['progresso'] ?></div>
                    <div style="color: #f59e0b; font-size: 0.8rem; font-weight: 600;"><i class="fas fa-spinner fa-spin"></i> No fluxo</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Concluídos</div>
                    <div class="stat-value" style="color: #10b981;"><?= $stats['concluido'] ?></div>
                    <div style="color: #10b981; font-size: 0.8rem; font-weight: 600;"><i class="fas fa-check-circle"></i> Finalizados</div>
                </div>
            </div>

            <div class="panel animate-fade-in" style="animation-delay: 0.1s;">
                <div class="panel-title">
                    <div style="display: flex; justify-content: space-between; width: 100%; align-items: center;">
                        <span><i class="fas fa-users"></i> Lista de Contatos</span>
                        <form method="GET" style="display: flex; gap: 0.5rem;">
                            <input type="text" name="search" placeholder="Buscar telefone..." class="form-control"
                                value="<?= htmlspecialchars($search)?>" style="width: 200px; padding: 0.5rem 1rem;">
                            <select name="status" class="form-control" style="width: 150px; padding: 0.5rem 1rem;"
                                onchange="this.form.submit()">
                                <option value="">Todos Status</option>
                                <option value="novo" <?= $status == 'novo' ? 'selected' : '' ?>>Novo</option>
                                <option value="em_progresso" <?= $status == 'em_progresso' ? 'selected' : '' ?>>Em
                                    Progresso</option>
                                <option value="concluido" <?= $status == 'concluido' ? 'selected' : '' ?>>Concluído
                                </option>
                                <option value="bloqueado" <?= $status == 'bloqueado' ? 'selected' : '' ?>>Bloqueado
                                </option>
                            </select>
                            <button type="submit" class="btn-modern" style="padding: 0.5rem 1rem;"><i
                                    class="fas fa-search"></i></button>
                        </form>
                    </div>
                </div>

                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Telefone</th>
                            <th>Status</th>
                            <th>Último Passo</th>
                            <th>Próximo Envio</th>
                            <th>Entrada</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($leads)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-dim);">Nenhum
                                lead encontrado.</td>
                        </tr>
                        <?php
endif; ?>
                        <?php foreach ($leads as $lead): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600;">
                                    <?= htmlspecialchars($lead['telefone'])?>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-dim);">JID:
                                    <?= htmlspecialchars($lead['grupo_origem_jid'])?>
                                </div>
                            </td>
                            <td><span class="status-pill status-<?= $lead['status']?>">
                                    <?= str_replace('_', ' ', $lead['status'])?>
                                </span></td>
                            <td>
                                <div class="step-number" style="width: 24px; height: 24px; font-size: 0.8rem;">
                                    <?= $lead['ultimo_passo_id']?>
                                </div>
                            </td>
                            <td style="color: var(--text-dim); font-size: 0.9rem;">
                                <?= $lead['data_proximo_envio'] ? date('d/m H:i', strtotime($lead['data_proximo_envio'])) : '-'?>
                            </td>
                            <td style="color: var(--text-dim); font-size: 0.9rem;">
                                <?= date('d/m/y', strtotime($lead['created_at']))?>
                            </td>
                            <td>
                                <button class="btn-modern"
                                    style="padding: 0.4rem 0.8rem; background: rgba(255,255,255,0.05); font-size: 0.8rem;"
                                    onclick="resetLead(<?= $lead['id']?>)">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                            </td>
                        </tr>
                        <?php
endforeach; ?>
                    </tbody>
                </table>
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
        async function clearAllLeads() {
            const res = await Swal.fire({
                title: 'Tem certeza?',
                text: "Isso removerá TODOS os leads da base!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff3b3b',
                background: '#151518',
                color: '#e0e0e0'
            });

            if (res.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'clear_all_members');
                const response = await fetch('api_marketing_ajax.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    location.reload();
                }
            }
        }

        async function resetLead(id) {
            // Pode implementar um reset individual se necessário via AJAX
            Swal.fire({
                title: 'Resetar Lead?',
                text: "O lead voltará para o primeiro passo do funil.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ff3b3b',
                background: '#151518',
                color: '#e0e0e0'
            });
        }
    </script>
</body>

</html>