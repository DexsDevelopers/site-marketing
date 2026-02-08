<?php
/**
 * Trigger de Marketing - Força a execução imediata de tarefas
 */
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/whatsapp_helper.php';
require_once 'includes/auth_helper.php';

// Segurança
requireLogin();

header('Content-Type: application/json');

try {
    // 1. Verificar se há campanha ativa
    $campanha = fetchOne($pdo, "SELECT * FROM marketing_campanhas WHERE id = 1 AND ativo = 1");
    if (!$campanha) {
        throw new Exception("Nenhuma campanha ativa encontrada.");
    }

    // 2. Colocar leads em 'em_progresso' que estavam como 'novo', respeitando o limite diário
    $limiteDiario = intval($campanha['membros_por_dia_grupo'] ?? 100);
    $hojeStats = fetchOne($pdo, "SELECT COUNT(*) as c FROM marketing_membros WHERE (status = 'em_progresso' OR status = 'concluido') AND DATE(data_entrada_fluxo) = CURDATE()");
    $hojeCount = intval($hojeStats['c']);

    $vagas = $limiteDiario - $hojeCount;
    if ($vagas > 0) {
        $novos = fetchData($pdo, "SELECT id FROM marketing_membros WHERE status = 'novo' ORDER BY id ASC LIMIT $vagas");
        foreach ($novos as $n) {
            executeQuery($pdo, "UPDATE marketing_membros SET status = 'em_progresso', data_proximo_envio = NOW(), ultimo_passo_id = 0, data_entrada_fluxo = CURDATE() WHERE id = ?", [$n['id']]);
        }
    }

    // 3. Forçar 'data_proximo_envio' para agora em quem já está em progresso
    executeQuery($pdo, "UPDATE marketing_membros SET data_proximo_envio = NOW() WHERE status = 'em_progresso' AND (data_proximo_envio > NOW() OR data_proximo_envio IS NULL)");

    // 4. Buscar e disparar as próximas 5 tarefas imediatamente
    $sqlTasks = "
        SELECT m.id, m.telefone, m.ultimo_passo_id, msg.conteudo, msg.tipo, msg.ordem
        FROM marketing_membros m
        JOIN marketing_mensagens msg ON (m.ultimo_passo_id + 1) = msg.ordem
        WHERE m.status = 'em_progresso' 
        AND m.data_proximo_envio <= NOW()
        ORDER BY m.data_proximo_envio ASC
        LIMIT 5
    ";
    $tasks = fetchData($pdo, $sqlTasks);
    $enviados = 0;

    foreach ($tasks as $t) {
        $randomId = substr(md5(uniqid()), 0, 5);
        $msg = $t['conteudo'] . "\n\n_" . $randomId . "_";

        $result = sendWhatsappMessage($t['telefone'], $msg);

        if ($result['success']) {
            $enviados++;
            // Atualizar status do lead
            $nextMsg = fetchOne($pdo, "SELECT delay_apos_anterior_minutos FROM marketing_mensagens WHERE campanha_id = 1 AND ordem > ? ORDER BY ordem ASC LIMIT 1", [$t['ordem']]);
            if ($nextMsg) {
                $delay = $nextMsg['delay_apos_anterior_minutos'];
                $nextTime = date('Y-m-d H:i:s', strtotime("+$delay minutes"));
                executeQuery($pdo, "UPDATE marketing_membros SET ultimo_passo_id = ?, data_proximo_envio = ?, status = 'em_progresso' WHERE id = ?", [$t['ordem'], $nextTime, $t['id']]);
            }
            else {
                executeQuery($pdo, "UPDATE marketing_membros SET ultimo_passo_id = ?, status = 'concluido' WHERE id = ?", [$t['ordem'], $t['id']]);
            }
        }

        // Pequeno sleep entre disparos manuais pra não sobrecarregar
        usleep(500000);
    }

    echo json_encode(['success' => true, 'message' => "Disparos concluídos. $enviados mensagens enviadas agora.", 'tasks_found' => count($tasks)]);

}
catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}