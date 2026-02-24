<!-- Mobile Menu Button -->
<button class="mobile-menu-btn" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="admin-sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fab fa-whatsapp"></i>
            MARKETING HUB
        </div>
        <button class="close-btn" onclick="toggleSidebar()">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <nav>
        <ul class="nav-links">
            <li>
                <a href="admin_dashboard.php"
                    class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''?>">
                    <i class="fas fa-chart-line"></i> <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="funnel.php"
                    class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'funnel.php' ? 'active' : ''?>">
                    <i class="fas fa-layer-group"></i> <span>Funil de Vendas</span>
                </a>
            </li>
            <li>
                <a href="leads.php"
                    class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'leads.php' ? 'active' : ''?>">
                    <i class="fas fa-users"></i> <span>Leads</span>
                </a>
            </li>

            <li>
                <a href="settings.php"
                    class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''?>">
                    <i class="fas fa-cog"></i> <span>Configurações</span>
                </a>
            </li>
            <li>
                <a href="logs.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active' : ''?>"
                    style="color: #60a5fa;">
                    <i class="fas fa-terminal"></i> <span>Logs do Bot</span>
                </a>
            </li>
            <li>
                <a href="javascript:void(0)" onclick="confirmResetConnection()" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'reset.php' ? 'active' : ''?>"
                    style="color: #ff3b3b;">
                    <i class="fas fa-power-off"></i> <span>Resetar Conexão</span>
                </a>
            </li>
        </ul>
    </nav>
    <div style="margin-top: auto;">
        <a href="logout.php" class="nav-item" style="color: var(--primary);"><i class="fas fa-sign-out-alt"></i>
            <span>Sair</span></a>
    </div>
</aside>

<script>
    async function confirmResetConnection() {
        const { isConfirmed } = await Swal.fire({
            title: 'Tem certeza?',
            text: "Isso irá desconectar o chip principal (admin_session) e resetar a conexão. Você precisará escanear o QR Code novamente.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff3b3b',
            cancelButtonColor: 'rgba(255,255,255,0.1)',
            confirmButtonText: 'Sim, resetar agora!',
            cancelButtonText: 'Cancelar',
            background: '#0a0a0c',
            color: '#fff'
        });

        if (isConfirmed) {
            window.location.href = 'reset.php';
        }
    }

    function toggleSidebar() {
        document.getElementById('admin-sidebar').classList.toggle('active');
        document.getElementById('sidebar-overlay').classList.toggle('active');
    }
</script>