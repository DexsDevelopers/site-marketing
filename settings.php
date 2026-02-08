<?php
require_once 'includes/config.php';
require_once 'includes/auth_helper.php';
require_once 'includes/db_connect.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketing Hub | Configurações</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="header">
                <div>
                    <h1>Configurações</h1>
                    <p style="color: var(--text-dim);">Ajustes gerais do sistema.</p>
                </div>
            </header>
            <div class="panel">
                <div class="panel-title"><i class="fas fa-cog"></i> Parâmetros do Bot</div>
                <div style="text-align: center; padding: 2rem; color: var(--text-dim);">
                    <i class="fas fa-tools" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>Painel de configurações em desenvolvimento.</p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
