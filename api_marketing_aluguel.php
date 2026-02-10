<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth_helper.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

try {
    // 1. HEARTBEAT (Chamado pelo Bot a cada 60s)
    if ($action === 'heartbeat') {
        $sessionId = $input['session_id'] ?? '';
        if (!$sessionId)
            throw new Exception("Session ID missing");

        // Buscar a instância
        $inst = fetchOne($pdo, "SELECT * FROM wa_instancias WHERE session_id = ?", [$sessionId]);
        if (!$inst)
            throw new Exception("Instância não encontrada");

        // Incrementar uptime (60 segundos)
        executeQuery($pdo, "UPDATE wa_instancias SET uptime_total_segundos = uptime_total_segundos + 60, last_heartbeat = NOW(), status = 'conectado' WHERE session_id = ?", [$sessionId]);

        // Cálculo de Ganho: 24h (86400s) = R$ 20.00
        // Por minuto (60s): (20 / 1440) = 0.01388
        $ganhoPorMinuto = 20 / (24 * 60);

        // Atualizar saldo do usuário e saldo acumulado da instância
        executeQuery($pdo, "UPDATE wa_instancias SET saldo_acumulado = saldo_acumulado + ? WHERE id = ?", [$ganhoPorMinuto, $inst['id']]);
        executeQuery($pdo, "UPDATE users SET saldo = saldo + ? WHERE id = ?", [$ganhoPorMinuto, $inst['user_id']]);

        echo json_encode(['success' => true]);
        exit;
    }

    // 2. UPDATE STATUS (Chamado pelo Bot quando muda estado)
    elseif ($action === 'update_instance_status') {
        $sessionId = $input['session_id'] ?? '';
        $status = $input['status'] ?? 'desconectado';

        executeQuery($pdo, "UPDATE wa_instancias SET status = ? WHERE session_id = ?", [$status, $sessionId]);
        echo json_encode(['success' => true]);
        exit;
    }

    // --- AÇÕES DO PAINEL DO USUÁRIO ---

    // 3. GET DASHBOARD DATA
    elseif ($action === 'get_user_dashboard') {
        requireLogin();
        $userId = $_SESSION['user_id'];

        $user = fetchOne($pdo, "SELECT saldo, pix_chave FROM users WHERE id = ?", [$userId]);
        $instancia = fetchOne($pdo, "SELECT * FROM wa_instancias WHERE user_id = ?", [$userId]);

        echo json_encode([
            'success' => true,
            'saldo' => (float)($user['saldo'] ?? 0),
            'pix_chave' => $user['pix_chave'],
            'instancia' => $instancia
        ]);
        exit;
    }

    // 4. CRIAR/VINCULAR INSTÂNCIA
    elseif ($action === 'setup_instance') {
        requireLogin();
        $userId = $_SESSION['user_id'];

        // Verificar se já tem
        $existente = fetchOne($pdo, "SELECT session_id FROM wa_instancias WHERE user_id = ?", [$userId]);

        if (!$existente) {
            $sessionId = "user_" . $userId . "_" . bin2hex(random_bytes(4));
            executeQuery($pdo, "INSERT INTO wa_instancias (user_id, session_id, status) VALUES (?, ?, 'desconectado')", [$userId, $sessionId]);
            $existente = ['session_id' => $sessionId];
        }

        // Notificar o robô para iniciar essa sessão
        $botUrl = "http://localhost:3002/instance/create"; // Ajustar se necessário
        // Como o bot está no mesmo servidor mas acessível externamente:
        $ch = curl_init("http://127.0.0.1:3002/instance/create");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['sessionId' => $existente['session_id']]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_exec($ch);
        curl_close($ch);

        echo json_encode(['success' => true, 'session_id' => $existente['session_id']]);
        exit;
    }

    // 5. SOLICITAR SAQUE
    elseif ($action === 'request_withdraw') {
        requireLogin();
        $userId = $_SESSION['user_id'];
        $valor = (float)($input['valor'] ?? 0);

        $user = fetchOne($pdo, "SELECT saldo, pix_chave FROM users WHERE id = ?", [$userId]);

        if ($valor < 20)
            throw new Exception("Valor mínimo para saque é R$ 20,00");
        if ($valor > $user['saldo'])
            throw new Exception("Saldo insuficiente");
        if (!$user['pix_chave'])
            throw new Exception("Cadastre sua chave PIX primeiro");

        $pdo->beginTransaction();
        executeQuery($pdo, "UPDATE users SET saldo = saldo - ? WHERE id = ?", [$valor, $userId]);
        executeQuery($pdo, "INSERT INTO wa_pagamentos (user_id, valor, pix_chave, status) VALUES (?, ?, ?, 'pendente')", [$userId, $valor, $user['pix_chave']]);
        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Saque solicitado com sucesso!']);
        exit;
    }

}
catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}