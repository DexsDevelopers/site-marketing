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
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#10b981',
                        surface: '#0a0a0c',
                        card: '#111115',
                        'bg-dark': '#030305',
                        'text-dim': '#94a3b8',
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    <style type="text/tailwindcss">
        @layer base {
            body {
                @apply bg-bg-dark text-white font-sans min-h-screen overflow-x-hidden;
            }
        }

        @layer components {
            .nav-link {
                @apply flex items-center gap-3 px-4 py-3 rounded-xl text-text-dim transition-all duration-300 font-medium hover:bg-white/5 hover:text-white;
            }
            .nav-link.active {
                @apply bg-primary/10 text-primary;
            }
            .card-premium {
                @apply bg-card border border-white/5 rounded-[24px] p-8 shadow-2xl;
            }
            .status-pill {
                @apply px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider;
            }
        }

        .mesh-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background:
                radial-gradient(circle at 10% 20%, rgba(16, 185, 129, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(59, 130, 246, 0.05) 0%, transparent 40%);
        }
    </style>
</head>

<body>
    <div class="mesh-bg"></div>

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside
            class="w-[280px] bg-surface border-r border-white/5 p-8 flex flex-col fixed h-screen z-50 hidden lg:flex">
            <div class="font-outfit text-2xl font-extrabold mb-12 flex items-center gap-3">
                <i class="fab fa-whatsapp text-primary"></i>
                <span>WA <span class="text-primary">MONEY</span></span>
            </div>

            <nav class="flex-1 space-y-2">
                <a href="painel.php" class="nav-link">
                    <i class="fas fa-th-large w-5"></i>
                    <span>Dashboard</span>
                </a>
                <a href="extrato.php" class="nav-link active">
                    <i class="fas fa-history w-5"></i>
                    <span>Extrato</span>
                </a>
            </nav>

            <a href="logout.php" class="nav-link text-red-400 hover:bg-red-500/10 hover:text-red-400 mt-auto">
                <i class="fas fa-sign-out-alt w-5"></i>
                <span>Sair da Conta</span>
            </a>
        </aside>

        <!-- Main -->
        <main class="flex-1 lg:ml-[280px] p-6 lg:p-12">
            <header class="mb-12">
                <h1 class="text-3xl font-outfit font-extrabold">Seu <span class="text-primary">Histórico</span></h1>
                <p class="text-text-dim mt-1">Acompanhe todos os seus saques e rendimentos.</p>
            </header>

            <div class="card-premium overflow-hidden">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="font-outfit text-xl font-bold">Extrato de Pagamentos</h2>
                    <button onclick="loadExtrato()"
                        class="text-primary text-sm font-bold hover:underline transition-all">
                        <i class="fas fa-sync-alt mr-1"></i> Atualizar
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-white/5">
                                <th class="pb-4 pt-2 font-bold uppercase tracking-widest text-[10px] text-text-dim">Data
                                    & Hora</th>
                                <th class="pb-4 pt-2 font-bold uppercase tracking-widest text-[10px] text-text-dim">
                                    Chave PIX</th>
                                <th class="pb-4 pt-2 font-bold uppercase tracking-widest text-[10px] text-text-dim">
                                    Valor</th>
                                <th class="pb-4 pt-2 font-bold uppercase tracking-widest text-[10px] text-text-dim">
                                    Status</th>
                            </tr>
                        </thead>
                        <tbody id="extrato-table" class="divide-y divide-white/[0.02]">
                            <tr>
                                <td colspan="4" class="py-20 text-center text-text-dim">
                                    <i class="fas fa-circle-notch fa-spin text-2xl text-primary mb-4 block mx-auto"></i>
                                    Sincronizando transações...
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
                    table.innerHTML = d.extrato.map(item => {
                        const date = new Date(item.created_at);
                        const statusColors = {
                            pendente: 'bg-amber-500/10 text-amber-500',
                            concluido: 'bg-primary/10 text-primary',
                            erro: 'bg-red-500/10 text-red-500'
                        };
                        const statusLabels = {
                            pendente: 'Pendente',
                            concluido: 'Concluido',
                            erro: 'Falha'
                        };

                        return `
                            <tr class="hover:bg-white/[0.01] transition-all group">
                                <td class="py-5">
                                    <div class="text-sm font-medium">${date.toLocaleDateString('pt-BR')}</div>
                                    <div class="text-[10px] text-text-dim">${date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}</div>
                                </td>
                                <td class="py-5">
                                    <span class="text-xs bg-black/40 border border-white/5 px-2 py-1 rounded-md text-white/70">${item.pix_chave}</span>
                                </td>
                                <td class="py-5">
                                    <span class="text-sm font-bold text-white">R$ ${parseFloat(item.valor).toFixed(2).replace('.', ',')}</span>
                                </td>
                                <td class="py-5">
                                    <span class="status-pill ${statusColors[item.status] || 'bg-zinc-500/10 text-zinc-500'}">
                                        ${statusLabels[item.status] || item.status}
                                    </span>
                                </td>
                            </tr>
                        `;
                    }).join('');
                } else {
                    table.innerHTML = `
                        <tr>
                            <td colspan="4" class="py-20 text-center text-text-dim">
                                <div class="bg-white/5 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 text-xl">
                                    <i class="fas fa-folder-open opacity-20"></i>
                                </div>
                                Nenhuma transação encontrada na sua conta.
                            </td>
                        </tr>
                    `;
                }
            } catch (e) {
                document.getElementById('extrato-table').innerHTML = `
                    <tr>
                        <td colspan="4" class="py-20 text-center text-red-400">
                            <i class="fas fa-exclamation-triangle text-2xl mb-4 block mx-auto"></i>
                            Falha ao carregar o histórico de pagamentos.
                        </td>
                    </tr>
                `;
            }
        }

        loadExtrato();
    </script>
</body>

</html>