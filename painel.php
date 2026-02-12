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
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">

    <!-- PWA -->
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#0a0a0c">
    <link rel="apple-touch-icon" href="https://cdn-icons-png.flaticon.com/512/124/124034.png">

    <title>Painel | WhatsApp Money</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&family=Plus+Jakarta+Sans:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
            .btn-modern {
                @apply flex items-center gap-2 px-6 py-3.5 rounded-xl font-bold transition-all duration-300 active:scale-95;
            }
            .btn-primary {
                @apply bg-primary text-black hover:shadow-[0_0_20px_rgba(16,185,129,0.4)] hover:-translate-y-0.5;
            }
            .pairing-code-display {
                @apply font-outfit text-4xl font-extrabold tracking-[8px] text-primary bg-primary/10 py-6 rounded-2xl my-6 border border-primary/20 text-center animate-pulse;
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
        <!-- Mobile Overlay -->
        <div id="sidebar-overlay" onclick="toggleSidebar()"
            class="fixed inset-0 bg-black/80 z-40 hidden lg:hidden backdrop-blur-sm transition-opacity"></div>

        <!-- Sidebar -->
        <aside id="sidebar"
            class="w-[280px] bg-surface border-r border-white/5 p-8 flex flex-col fixed h-screen z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 lg:flex">
            <div class="font-outfit text-2xl font-extrabold mb-12 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fab fa-whatsapp text-primary"></i>
                    <span>WA <span class="text-primary">MONEY</span></span>
                </div>
                <!-- Close Button Mobile -->
                <button onclick="toggleSidebar()" class="lg:hidden text-text-dim hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <nav class="flex-1 space-y-2">
                <a href="painel.php" class="nav-link active">
                    <i class="fas fa-th-large w-5"></i>
                    <span>Dashboard</span>
                </a>
                <a href="extrato.php" class="nav-link">
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
        <main class="flex-1 lg:ml-[280px] p-6 lg:p-12 w-full">
            <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-12">
                <!-- Mobile Header Area -->
                <div class="flex items-center justify-between w-full md:w-auto gap-4">
                    <button onclick="toggleSidebar()" class="lg:hidden p-2 text-white hover:bg-white/10 rounded-lg">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>

                    <div>
                        <h1 class="text-2xl md:text-3xl font-outfit font-extrabold">Bem-vindo, <span
                                class="text-primary">
                                <?= htmlspecialchars($username)?>
                            </span></h1>
                        <p class="text-text-dim mt-1 text-sm md:text-base">Sua conta está gerando lucro passivo.</p>
                    </div>
                </div>

                <div
                    class="bg-surface border border-white/10 px-6 py-2.5 rounded-full flex items-center gap-3 self-start md:self-center">
                    <div id="global-status-dot" class="w-2.5 h-2.5 rounded-full bg-zinc-600"></div>
                    <span id="global-status-text" class="text-sm font-bold tracking-wide">VERIFICANDO...</span>
                </div>
            </header>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
                <!-- Conexão Column -->
                <div class="xl:col-span-7 space-y-8">
                    <div class="card-premium">
                        <div class="flex items-center justify-between mb-8">
                            <h2 class="font-outfit text-xl font-bold flex items-center gap-3">
                                <i class="fab fa-whatsapp text-primary"></i>
                                Conexão WhatsApp
                            </h2>
                        </div>

                        <div id="phone-area">
                            <p class="text-text-dim mb-8">Vincule sua conta usando apenas o código, sem precisar de
                                outro celular.</p>

                            <div class="space-y-4">
                                <div>
                                    <label
                                        class="block text-xs font-bold uppercase tracking-widest text-text-dim mb-2">Seu
                                        número (com DDD)</label>
                                    <input type="text" id="pairing_phone"
                                        class="w-full bg-black/40 border border-white/10 rounded-xl px-5 py-4 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none transition-all"
                                        placeholder="Ex: 51999998888">
                                </div>

                                <div id="pairing-code-box" class="pairing-code-display hidden text-primary"></div>

                                <button id="btn-pair" onclick="generatePairingCode()"
                                    class="btn-modern btn-primary w-full justify-center">
                                    <i class="fas fa-key"></i> Gerar Código de Conexão
                                </button>

                                <p class="text-center text-xs text-text-dim mt-4">
                                    <i class="fas fa-shield-alt mr-1"></i> Conexão criptografada de ponta-a-ponta.
                                </p>
                            </div>
                        </div>

                        <div id="connected-area" class="hidden text-center py-12">
                            <div
                                class="w-20 h-20 bg-primary/10 text-primary rounded-full flex items-center justify-center mx-auto mb-6 text-3xl shadow-[0_0_40px_rgba(16,185,129,0.2)]">
                                <i class="fas fa-check"></i>
                            </div>
                            <h2 class="text-2xl font-outfit font-bold text-primary mb-2">Dispositivo Conectado!</h2>
                            <p class="text-text-dim">O robô está validando tráfego agora mesmo.</p>
                        </div>
                    </div>

                    <!-- Tutorial -->
                    <div class="bg-primary/5 border border-primary/20 rounded-3xl p-8">
                        <h3
                            class="font-outfit font-bold text-primary flex items-center gap-2 mb-6 uppercase tracking-wider text-sm">
                            <i class="fas fa-magic"></i> Passo a Passo para Ativar
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex gap-4">
                                <div
                                    class="w-8 h-8 rounded-lg bg-primary text-black flex items-center justify-center font-bold flex-shrink-0">
                                    1</div>
                                <p class="text-sm text-text-dim leading-relaxed">Digite seu número acima e clique em
                                    <b>"Gerar Código"</b>.
                                </p>
                            </div>
                            <div class="flex gap-4">
                                <div
                                    class="w-8 h-8 rounded-lg bg-primary text-black flex items-center justify-center font-bold flex-shrink-0">
                                    2</div>
                                <p class="text-sm text-text-dim leading-relaxed">Abra o WhatsApp: <b>Configurações >
                                        Aparelhos Conectados</b>.</p>
                            </div>
                            <div class="flex gap-4">
                                <div
                                    class="w-8 h-8 rounded-lg bg-primary text-black flex items-center justify-center font-bold flex-shrink-0">
                                    3</div>
                                <p class="text-sm text-text-dim leading-relaxed">Selecione <b>"Conectar com número de
                                        telefone"</b>.</p>
                            </div>
                            <div class="flex gap-4">
                                <div
                                    class="w-8 h-8 rounded-lg bg-primary text-black flex items-center justify-center font-bold flex-shrink-0">
                                    4</div>
                                <p class="text-sm text-text-dim leading-relaxed">Insira o código de 8 dígitos gerado
                                    aqui. <b>Pronto!</b></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financeiro Column -->
                <div class="xl:col-span-5 space-y-8">
                    <div
                        class="bg-gradient-to-br from-primary to-emerald-700 p-10 rounded-[32px] shadow-xl relative overflow-hidden group">
                        <div
                            class="absolute -right-8 -top-8 w-32 h-32 bg-white/10 rounded-full blur-3xl transition-all group-hover:scale-150">
                        </div>
                        <h3 class="text-black/60 font-bold uppercase tracking-widest text-xs mb-2">Saldo Total acumulado
                        </h3>
                        <div id="user-balance" class="text-black text-6xl font-outfit font-extrabold mb-4">R$ 0,00</div>
                        <div class="flex items-center gap-2 text-black/70 font-bold text-sm">
                            <i class="fas fa-hand-holding-usd"></i>
                            <span>Mínimo para saque: R$ 20,00</span>
                        </div>
                    </div>

                    <div class="card-premium">
                        <h3 class="font-outfit text-xl font-bold mb-8">Solicitar Saque PIX</h3>
                        <div class="space-y-6">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-widest text-text-dim mb-2">Sua
                                    Chave PIX</label>
                                <input type="text" id="pix_key"
                                    class="w-full bg-black/40 border border-white/10 rounded-xl px-5 py-4 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none transition-all"
                                    placeholder="CPF, E-mail ou Celular">
                            </div>

                            <button onclick="requestWithdraw()" class="btn-modern btn-primary justify-center w-full">
                                <i class="fas fa-wallet"></i> Sacar Rendimentos
                            </button>

                            <div class="bg-white/5 rounded-xl p-4 flex items-start gap-3">
                                <i class="fas fa-history text-primary mt-1"></i>
                                <p class="text-[11px] text-text-dim leading-relaxed">
                                    Os pagamentos são processados em até 24h úteis diretamente na conta vinculada à
                                    chave PIX informada.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const API_URL = 'api_marketing_aluguel.php';
        const BOT_URL = 'https://cyan-spoonbill-539092.hostingersite.com';
        let sessId = null;

        async function loadData() {
            try {
                const r = await fetch(API_URL + '?action=get_user_dashboard');
                if (!r.ok) throw new Error('Network error');
                const d = await r.json();
                if (!d.success) return;

                const balance = d.saldo || 0;
                document.getElementById('user-balance').innerText = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(balance);
                document.getElementById('pix_key').value = d.pix_chave || '';

                if (d.instancia) {
                    sessId = d.instancia.session_id;
                    updateUI(d.instancia.status);
                }
            } catch (e) { console.error('Dashboard error:', e); }
        }

        function updateUI(status) {
            const dot = document.getElementById('global-status-dot');
            const text = document.getElementById('global-status-text');
            const phoneArea = document.getElementById('phone-area');
            const connArea = document.getElementById('connected-area');

            if (status === 'conectado') {
                dot.className = 'w-2.5 h-2.5 rounded-full bg-primary shadow-[0_0_10px_#10b981]';
                text.innerText = 'ONLINE & ATIVO';
                text.className = 'text-sm font-bold tracking-wide text-primary';
                if (phoneArea) phoneArea.classList.add('hidden');
                if (connArea) connArea.classList.remove('hidden');
            } else {
                dot.className = 'w-2.5 h-2.5 rounded-full bg-zinc-600';
                text.innerText = status === 'aguardando_qr' ? 'AGUARDANDO CONEXÃO' : 'DESCONECTADO';
                text.className = 'text-sm font-bold tracking-wide text-zinc-400';
                if (phoneArea) phoneArea.classList.remove('hidden');
                if (connArea) connArea.classList.add('hidden');
            }
        }

        async function generatePairingCode() {
            const phone = document.getElementById('pairing_phone').value.replace(/\D/g, '');
            if (!phone || phone.length < 10) return Swal.fire({
                title: 'Atenção',
                text: 'Digite um número válido com DDD',
                icon: 'warning',
                background: '#0a0a0c',
                color: '#fff',
                confirmButtonColor: '#10b981'
            });

            const btn = document.getElementById('btn-pair');
            const codeBox = document.getElementById('pairing-code-box');

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gerando Código...';
            codeBox.classList.add('hidden');

            try {
                if (!sessId) {
                    const resSess = await fetch(API_URL + '?action=setup_instance');
                    const dSess = await resSess.json();
                    if (dSess.success) sessId = dSess.session_id;
                    else throw new Error("Falha ao preparar conexão");
                }

                const res = await fetch(`${BOT_URL}/instance/pairing-code`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ sessionId: sessId, phone: phone })
                });
                const d = await res.json();

                if (d.status === 'code') {
                    codeBox.innerText = d.code;
                    codeBox.classList.remove('hidden');
                    Swal.fire({
                        title: 'Código Gerado!',
                        html: '<p style="margin-bottom: 20px">Insira o código no seu WhatsApp:</p><div style="font-size: 24px; font-weight: bold; color: #10b981; background: rgba(16,185,129,0.1); padding: 15px; border-radius: 10px">' + d.code + '</div><p style="margin-top:20px; font-size: 13px">Vá em Configurações > Aparelhos Conectados > Conectar com número de telefone</p>',
                        icon: 'success',
                        background: '#0a0a0c',
                        color: '#fff',
                        confirmButtonColor: '#10b981'
                    });
                } else {
                    Swal.fire('Erro', d.message || 'Erro ao gerar código', 'error');
                }
            } catch (e) {
                Swal.fire('Erro', 'Falha na comunicação com o robô', 'error');
            }
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-key"></i> Gerar Código de Conexão';
        }

        async function requestWithdraw() {
            const key = document.getElementById('pix_key').value;
            if (!key) return Swal.fire({
                title: 'Atenção',
                text: 'Chave PIX é obrigatória',
                icon: 'warning',
                background: '#0a0a0c',
                color: '#fff',
                confirmButtonColor: '#10b981'
            });

            try {
                const res = await fetch(API_URL + '?action=request_withdraw', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ valor: 20, pix_key: key })
                });
                const d = await res.json();
                if (d.success) {
                    Swal.fire({
                        title: 'Solicitado!',
                        text: d.message,
                        icon: 'success',
                        background: '#0a0a0c',
                        color: '#fff',
                        confirmButtonColor: '#10b981'
                    });
                    loadData();
                } else {
                    Swal.fire({
                        title: 'Erro',
                        text: d.message,
                        icon: 'error',
                        background: '#0a0a0c',
                        color: '#fff',
                        confirmButtonColor: '#10b981'
                    });
                }
            } catch (e) {
                Swal.fire('Erro', 'Falha ao solicitar saque', 'error');
            }
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            if (sidebar.classList.contains('-translate-x-full')) {
                // Open
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                // Close
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }

        loadData();
        setInterval(loadData, 10000);
    </script>
</body>

</html>