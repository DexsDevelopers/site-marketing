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

        $inst = fetchOne($pdo, "SELECT * FROM wa_instancias WHERE session_id = ?", [$sessionId]);
        if (!$inst)
            throw new Exception("Instância não encontrada");

        executeQuery($pdo, "UPDATE wa_instancias SET uptime_total_segundos = uptime_total_segundos + 60, last_heartbeat = NOW(), status = 'conectado' WHERE session_id = ?", [$sessionId]);

        $ganhoPorMinuto = 20 / (24 * 60);
        executeQuery($pdo, "UPDATE wa_instancias SET saldo_acumulado = saldo_acumulado + ? WHERE id = ?", [$ganhoPorMinuto, $inst['id']]);
        executeQuery($pdo, "UPDATE users SET saldo = saldo + ? WHERE id = ?", [$ganhoPorMinuto, $inst['user_id']]);

        echo json_encode(['success' => true]);
        exit;
    }

    // 2. UPDATE STATUS
    elseif ($action === 'update_instance_status') {
        $sessionId = $input['session_id'] ?? '';
        $status = $input['status'] ?? 'desconectado';
        executeQuery($pdo, "UPDATE wa_instancias SET status = ? WHERE session_id = ?", [$status, $sessionId]);
        echo json_encode(['success' => true]);
        exit;
    }

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
        $existente = fetchOne($pdo, "SELECT session_id FROM wa_instancias WHERE user_id = ?", [$userId]);

        if (!$existente) {
            $sessionId = "user_" . $userId . "_" . bin2hex(random_bytes(4));
            executeQuery($pdo, "INSERT INTO wa_instancias (user_id, session_id, status) VALUES (?, ?, 'desconectado')", [$userId, $sessionId]);
            $sessToCall = $sessionId;
        }
        else {
            $sessToCall = $existente['session_id'];
        }

        // Notificar o robô via URL PÚBLICA (Cyan Spoonbill)
        $botBaseUrl = "https://cyan-spoonbill-539092.hostingersite.com";
        $ch = curl_init("$botBaseUrl/instance/create");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['sessionId' => $sessToCall]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $resBot = curl_exec($ch);
        $errBot = curl_error($ch);
        curl_close($ch);

        if ($errBot) {
            error_log("Erro ao notificar bot: $errBot");
        }

        echo json_encode(['success' => true, 'session_id' => $sessToCall]);
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