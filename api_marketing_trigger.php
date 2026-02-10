<?php
/**
 * Trigger de Marketing - Dinâmico
 * Processa envios de mensagens para a campanha especificada
 */
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/whatsapp_helper.php';
require_once 'includes/auth_helper.php';

// Se rodar via cron (cli), não precisa de login de sessão, mas sim verificação de IP ou token
if (php_sapi_name() !== 'cli') {
    requireLogin();
}

header('Content-Type: application/json');

try {
    $campanhaId = isset($_GET['campanha_id']) ? intval($_GET['campanha_id']) : 1;

    // 1. Verificar se a campanha existe e está ativa
    $campanha = fetchOne($pdo, "SELECT * FROM marketing_campanhas WHERE id = ? AND ativo = 1", [$campanhaId]);
    if (!$campanha) {
        throw new Exception("Campanha ID $campanhaId não encontrada ou inativa.");
    }

    // 2. Colocar leads em 'em_progresso' (apenas se estiverem vinculados a esta campanha ou sem vinculo?)
    // Por enquanto, assumimos que marketing_membros é global ou vinculado via campanha_atual_id
    // Se leads sao globais, pegamos os que estao 'novo' e botamos nessa campanha

    // ATENCAO: Se temos varias campanhas, precisamos saber qual campanha o lead deve entrar.
    // Vamos assumir que 'campanha_atual_id' define isso.
    // Se nao tiver campanha_atual_id (leads antigos), atualizamos para campanha atual
    $pdo->exec("UPDATE marketing_membros SET campanha_atual_id = $campanhaId WHERE campanha_atual_id IS NULL OR campanha_atual_id = 0");

    $limiteDiario = intval($campanha['membros_por_dia_grupo'] ?? 100);

    // Contar leads DESTA campanha processados hoje
    $hojeStats = fetchOne($pdo, "SELECT COUNT(*) as c FROM marketing_membros WHERE campanha_atual_id = ? AND (status = 'em_progresso' OR status = 'concluido') AND DATE(data_entrada_fluxo) = CURDATE()", [$campanhaId]);
    $hojeCount = intval($hojeStats['c']);

    $vagas = $limiteDiario - $hojeCount;
    if ($vagas > 0) {
        // Pegar novos leads que ainda nao tem campanha ou sao desta campanha
        $novos = fetchData($pdo, "SELECT id FROM marketing_membros WHERE status = 'novo' AND (campanha_atual_id = ? OR campanha_atual_id IS NULL) ORDER BY id ASC LIMIT $vagas", [$campanhaId]);
        foreach ($novos as $n) {
            executeQuery($pdo, "UPDATE marketing_membros SET status = 'em_progresso', data_proximo_envio = NOW(), ultimo_passo_id = 0, data_entrada_fluxo = CURDATE(), campanha_atual_id = ? WHERE id = ?", [$campanhaId, $n['id']]);
        }
    }

    // 3. Forçar 'data_proximo_envio' para agora em quem já está atrasado
    executeQuery($pdo, "UPDATE marketing_membros SET data_proximo_envio = NOW() WHERE status = 'em_progresso' AND campanha_atual_id = ? AND (data_proximo_envio > NOW() OR data_proximo_envio IS NULL)", [$campanhaId]);

    // 4. Buscar tarefas pendentes
    // JOIN com marketing_mensagens filtra apenas mensagens ATIVAS desta campanha
    $sqlTasks = "
        SELECT m.id, m.telefone, m.ultimo_passo_id, msg.conteudo, msg.tipo, msg.ordem, msg.midia_url, msg.tipo_midia
        FROM marketing_membros m
        JOIN marketing_mensagens msg ON (m.ultimo_passo_id + 1) = msg.ordem
        WHERE m.status = 'em_progresso' 
        AND m.campanha_atual_id = ?
        AND msg.campanha_id = ?
        AND msg.ativo = 1
        AND m.data_proximo_envio <= NOW()
        ORDER BY m.data_proximo_envio ASC
        LIMIT 10
    ";

    $tasks = fetchData($pdo, $sqlTasks, [$campanhaId, $campanhaId]);
    $enviados = 0;

    foreach ($tasks as $t) {
        $randomId = substr(md5(uniqid()), 0, 5);
        $msgContent = $t['conteudo'];

        if (!empty($t['midia_url'])) {
            // Se tiver media, envia media com caption
            // Garantir URL absoluta se for relativa
            $mediaUrl = $t['midia_url'];
            if (!filter_var($mediaUrl, FILTER_VALIDATE_URL)) {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $host = $_SERVER['HTTP_HOST'] ?? 'khaki-gull-213146.hostingersite.com';
                $mediaUrl = $protocol . $host . '/' . ltrim($mediaUrl, '/');
            }
            $result = sendWhatsappMedia($t['telefone'], $mediaUrl, $msgContent, $t['tipo_midia'] ?? 'image');
        }
        else {
            // Texto puro
            $result = sendWhatsappMessage($t['telefone'], $msgContent);
        }

        if ($result['success']) {
            $enviados++;

            // Calcular proximo passo (buscando proxima msg ATIVA)
            $nextMsg = fetchOne($pdo, "SELECT delay_apos_anterior_minutos, ordem FROM marketing_mensagens WHERE campanha_id = ? AND ativo = 1 AND ordem > ? ORDER BY ordem ASC LIMIT 1", [$campanhaId, $t['ordem']]);

            if ($nextMsg) {
                $delay = $nextMsg['delay_apos_anterior_minutos'];
                $nextTime = date('Y-m-d H:i:s', strtotime("+$delay minutes"));
                // Atualiza ultimo_passo_id para a ordem da mensagem que ACABOU de ser enviada ($t['ordem'])
                // O trigger vai pegar (t['ordem'] + 1) na proxima, entao precisamos que a proxima mensagem tenha ordem sequencial
                // SE tivermos buracos na ordem (ex: 1, 3, 5), a logica (ultimo+1) falha.
                // CORRECAO: A logica do JOIN eh (ultimo_passo_id + 1) = msg.ordem.
                // Se msg 2 esta inativa, (1+1)=2 inativa -> nao envia. TRAVA.
                // Se deletamos msg 2 e reordenamos, vira 1, 2. OK.
                // Se apenas desativamos, TRAVA.

                // MELHORIA: Atualizar ultimo_passo_id para o ID desta mensagem enviada.
                executeQuery($pdo, "UPDATE marketing_membros SET ultimo_passo_id = ?, data_proximo_envio = ?, status = 'em_progresso' WHERE id = ?", [$t['ordem'], $nextTime, $t['id']]);
            }
            else {
                // Fim do funil
                executeQuery($pdo, "UPDATE marketing_membros SET ultimo_passo_id = ?, status = 'concluido' WHERE id = ?", [$t['ordem'], $t['id']]);
            }
        }

        // Delay para evitar bloqueio
        usleep(500000); // 0.5s
    }

    echo json_encode([
        'success' => true,
        'message' => "Campanha '{$campanha['nome']}': $enviados envios processados.",
        'tasks_found' => count($tasks)
    ]);

}
catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}