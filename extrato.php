<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth_helper.php';
session_start();

// Validar se está logado como USUÁRIO
$isLogged = (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) ||
    (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true);

if (!$isLogged) {
    header('Location: entrar.php');
    exit;
}

$username = $_SESSION['user_username'] ?? $_SESSION['admin_username'] ?? 'Usuário';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extrato | WhatsApp Money</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&family=Plus+Jakarta+Sans:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #10b981;
            --bg: #030305;
            --surface: #0a0a0c;
            --card: #111115;
            --border: rgba(255, 255, 255, 0.06);
            --text: #ffffff;
            --text-dim: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
        }

        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* --- Sidebar --- */
        .sidebar {
            width: 280px;
            background: var(--surface);
            border-right: 1px solid var(--border);
            padding: 2rem;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 100;
        }

        .logo {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo span {
            color: var(--primary);
        }

        .nav-menu {
            list-style: none;
            flex: 1;
        }

        .nav-item {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            display: flex;
            color: var(--text-dim);
            text-decoration: none;
            font-weight: 500;
            align-items: center;
            gap: 12px;
            padding: 1rem;
            border-radius: 12px;
            transition: 0.3s;
        }

        .nav-link.active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary);
        }

        .nav-link:hover:not(.active) {
            background: rgba(255, 255, 255, 0.03);
            color: #fff;
        }

        /* --- Main --- */
        .main {
            flex: 1;
            margin-left: 280px;
            padding: 2rem 3rem;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
        }

        .card {
            background: var(--card);
            border-radius: 24px;
            border: 1px solid var(--border);
            padding: 2rem;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th {
            text-align: left;
            color: var(--text-dim);
            font-size: 0.85rem;
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            text-transform: uppercase;
        }

        td {
            padding: 1.2rem 1rem;
            border-bottom: 1px solid var(--border);
            font-size: 0.95rem;
        }

        .status-pill {
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-pendente {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .status-concluido {
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary);
        }

        .status-erro {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        /* --- Mobile --- */
        @media (max-width: 1024px) {
            .sidebar {
                width: 80px;
                padding: 1.5rem 0.5rem;
                align-items: center;
            }

            .logo span,
            .nav-link span {
                display: none;
            }

            .main {
                margin-left: 80px;
                padding: 1.5rem;
            }
        }

        @media (max-width: 640px) {
            .sidebar {
                display: none;
            }

            .main {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="logo">
                <i class="fab fa-whatsapp"></i>
                <span>WA <span>MONEY</span></span>
            </div>

            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="painel.php" class="nav-link">
                        <i class="fas fa-th-large"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="extrato.php" class="nav-link active">
                        <i class="fas fa-history"></i>
                        <span>Extrato</span>
                    </a>
                </li>
            </ul>

            <a href="logout.php" class="nav-link" style="margin-top: auto;">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sair</span>
            </a>
        </aside>

        <main class="main">
            <header>
                <div>
                    <h1>Seu Histórico</h1>
                    <p style="color: var(--text-dim);">Acompanhe seus saques e rendimentos.</p>
                </div>
            </header>

            <div class="card">
                <h2 style="margin-bottom: 2rem; font-family: 'Outfit';">Extrato de Saques</h2>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Chave PIX</th>
                                <th>Valor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="extrato-table">
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-dim); padding: 4rem;">
                                    <i class="fas fa-spinner fa-spin"></i> Carregando seu extrato...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        async function loadExtrato() {
            try {
                const r = await fetch('api_marketing_aluguel.php?action=get_extrato');
                const d = await r.json();
                const table = document.getElementById('extrato-table');

                if (d.success && d.extrato.length > 0) {
                    table.innerHTML = d.extrato.map(item => `
                        <tr>
                            <td>${new Date(item.created_at).toLocaleDateString('pt-BR')} ${new Date(item.created_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}</td>
                            <td><code style="background: rgba(255,255,255,0.05); padding: 2px 6px; border-radius: 4px;">${item.pix_chave}</code></td>
                            <td style="font-weight: 700; color: #fff;">R$ ${parseFloat(item.valor).toFixed(2).replace('.', ',')}</td>
                            <td>
                                <span class="status-pill status-${item.status}">
                                    ${item.status === 'pendente' ? 'Pendente' : (item.status === 'concluido' ? 'Concluído' : 'Erro')}
                                </span>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    table.innerHTML = `
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-dim); padding: 4rem;">
                                Nenhuma transação encontrada.
                            </td>
                        </tr>
                    `;
                }
            } catch (e) {
                document.getElementById('extrato-table').innerHTML = `
                    <tr>
                        <td colspan="4" style="text-align: center; color: #ef4444; padding: 4rem;">
                            Erro ao carregar dados.
                        </td>
                    </tr>
                `;
            }
        }

        loadExtrato();
    </script>
</body>

</html>