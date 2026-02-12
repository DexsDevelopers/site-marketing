<aside class="sidebar">
    <div class="logo">
        <i class="fab fa-whatsapp"></i>
        MARKETING HUB
    </div>
    <nav>
        <ul class="nav-links">
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
                <a href="painel.php"
                    class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'painel.php' ? 'active' : ''?>"
                    style="color: #10b981;">
                    <i class="fas fa-hand-holding-usd"></i> <span>Conecte e Ganhe</span>
                </a>
            </li>
            <li>
                <a href="admin_bot_marketing.php"
                    class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'admin_bot_marketing.php' ? 'active' : ''?>"
                    style="color: #FF3333;">
                    <i class="fas fa-robot"></i> <span>Marketing Bot</span>
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
                <a href="reset.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'reset.php' ? 'active' : ''?>"
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