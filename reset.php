<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/whatsapp_helper.php';
require_once 'includes/auth_helper.php';
requireLogin();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_bot'])) {

    // Usar configuração dinâmica (mesma fonte que o resto do sistema)
    $apiConfig = whatsappApiConfig();
    $token = $apiConfig['token'];
    $baseUrl = $apiConfig['base_url'];

    if (!$apiConfig['enabled'] || empty($baseUrl)) {
        $message = '❌ API do WhatsApp não está configurada. Verifique as configurações.';
        $messageType = 'alert-error';
    }
    else {
        // Chama o endpoint /reset do bot
        $ch = curl_init($baseUrl . '/reset');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => [
                'x-api-token: ' . $token,
                'Content-Type: application/json',
                'ngrok-skip-browser-warning: true'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $curlError = curl_error($ch);
            $message = "❌ Erro ao conectar com o bot: {$curlError} (URL: {$baseUrl}/reset)";
            $messageType = 'alert-error';
        }
        else {
            $data = json_decode($response, true);
            if ($httpCode === 200 && isset($data['status']) && $data['status'] === 'success') {
                $message = '✅ Bot resetado com sucesso! A conexão foi encerrada e um novo QR Code será gerado em instantes.';
                $messageType = 'alert-success';
            }
            else {
                $errorDetail = $data['message'] ?? "HTTP {$httpCode} - Resposta: " . substr($response, 0, 200);
                $message = "❌ Falha ao resetar: {$errorDetail}";
                $messageType = 'alert-error';
            }
        }
        curl_close($ch);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketing Hub | Resetar Conexão</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .danger-zone {
            border: 1px solid rgba(255, 59, 59, 0.3);
            background: rgba(255, 59, 59, 0.05);
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
        }

        .btn-danger {
            background: #ff3b3b;
            color: white;
            border: none;
            padding: 1rem 2rem;
            font-size: 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-danger:hover {
            background: #d32f2f;
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(255, 59, 59, 0.4);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(0, 255, 0, 0.1);
            color: #4caf50;
            border: 1px solid #4caf50;
        }

        .alert-error {
            background: rgba(255, 0, 0, 0.1);
            color: #f44336;
            border: 1px solid #f44336;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="header animate-fade-in">
                <div>
                    <h1 style="margin: 0; font-size: 2.5rem; letter-spacing: -1.5px;">Resetar Conexão</h1>
                    <p style="color: var(--text-dim); margin-top: 0.5rem;">Gerenciamento de sessão do WhatsApp.</p>
                </div>
            </header>

            <div class="panel animate-fade-in" style="max-width: 800px; margin: 0 auto;">
                <div class="panel-title"><i class="fas fa-exclamation-triangle" style="color: #ff3b3b;"></i> Zona de
                    Perigo</div>

                <?php if ($message): ?>
                <div class="alert <?= $messageType?>"
                    style="padding: 1rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 500; <?= $messageType == 'alert-success' ? 'background: rgba(34,197,94,0.1); color: #4ade80; border: 1px solid #4ade80;' : 'background: rgba(239,68,68,0.1); color: #f87171; border: 1px solid #f87171;'?>">
                    <?= $message?>
                </div>
                <?php
endif; ?>

                <div class="danger-zone"
                    style="border: 1px solid rgba(255, 59, 59, 0.3); background: rgba(255, 59, 59, 0.05); padding: 2rem; border-radius: 12px; text-align: center;">
                    <h3 style="margin-bottom: 1rem; color: #ff3b3b;">Desconectar e Resetar Bot</h3>
                    <p
                        style="color: var(--text-dim); margin-bottom: 2rem; max-width: 600px; margin-left: auto; margin-right: auto; line-height: 1.6;">
                        Esta ação irá <strong>derrubar a conexão atual</strong> com o WhatsApp, apagar os dados de
                        sessão e reiniciar o bot forçadamente.
                        <br><br>
                        Use isso apenas se precisar escanear um novo QR Code ou se o bot estiver travado.
                    </p>

                    <form method="POST"
                        onsubmit="return confirm('Tem certeza absoluta? Isso irá desconectar o bot do WhatsApp!');">
                        <button type="submit" name="reset_bot" class="btn-danger"
                            style="background: #ff3b3b; color: white; border: none; padding: 1rem 2rem; font-size: 1rem; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; max-width: 300px; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;">
                            <i class="fas fa-trash-alt"></i> APAGAR SESSÃO
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>

</html>