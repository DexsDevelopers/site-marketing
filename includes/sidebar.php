<aside class="sidebar">
    <div class="logo">
        <i class="fab fa-whatsapp"></i>
        MARKETING HUB
    </div>
    <nav>
        <ul class="nav-links">
            <li>
                <a href="index.php"
                    class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''?>">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="funnel.php"
                    class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'funnel.php' ? 'active' : ''?>">
                    <i class="fas fa-layer-group"></i> Funil de Vendas
                </a>
            </li>
            <li>
                <a href="leads.php"
                    class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'leads.php' ? 'active' : ''?>">
                    <i class="fas fa-users"></i> Leads
                </a>
            </li>
            <li>
                <a href="settings.php"
                    class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''?>">
                    <i class="fas fa-cog"></i> Configurações
                </a>
            </li>
            <li>
                <a href="reset.php"
                    class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'reset.php' ? 'active' : ''?>"
                    style="color: #ff3b3b;">
                    <i class="fas fa-power-off"></i> Resetar Conexão
                </a>
            </li>
        </ul>
    </nav>
    <div style="margin-top: auto;">
        <a href="logout.php" class="nav-item" style="color: var(--primary);"><i class="fas fa-sign-out-alt"></i>
            Sair</a>
    </div>
</aside>