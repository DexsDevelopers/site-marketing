<?php
/**
 * API Marketing - Backend Brain (Robust Version)
 * Reconstruído para evitar Erro 500 em produção (Hostinger)
 */

// 1. Configurações Iniciais e Tratamento de Erro
ini_set('display_errors', 0); // Em produção, não mostrar erros no output (quebra JSON)
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

try {
    // 2. Carregar Dependências com Verificação
    if (!file_exists('includes/config.php'))
        throw new Exception("Arquivo includes/config.php não encontrado");
    require_once 'includes/config.php';

    if (!file_exists('includes/db_connect.php'))
        throw new Exception("Arquivo includes/db_connect.php não encontrado");
    require_once 'includes/db_connect.php';

    // 3. Configurar Timezone
    date_default_timezone_set('America/Sao_Paulo');

    // 4. Capturar Input
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    // Ler corpo da requisição JSON (se houver)
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true) ?? [];

    // --- ROTEAMENTO DE AÇÕES ---

    // AÇÃO 1: SALVAR MEMBROS (Prioridade Máxima - Deve funcionar sem falhas)
    if ($action === 'save_members') {
        if ($method !== 'POST')
            throw new Exception("Método inválido para save_members (Use POST)");

        $groupJid = $input['group_jid'] ?? '';
        $members = $input['members'] ?? [];

        if (empty($groupJid) || empty($members)) {
            echo json_encode(['success' => false, 'message' => 'Dados incompletos (group_jid ou members)']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT IGNORE INTO marketing_membros (telefone, grupo_origem_jid, status) VALUES (?, ?, 'novo')");
        $added = 0;

        foreach ($members as $phone) {
            // Se não for JID (contém @), limpar caracteres não numéricos
            if (strpos($phone, '@') === false) {
                $phone = preg_replace('/\D/', '', $phone);
                if (strlen($phone) < 10)
                    continue; // Ignorar números inválidos
            }

            try {
                $stmt->execute([$phone, $groupJid]);
                if ($stmt->rowCount() > 0)
                    $added++;
            }
            catch (Exception $e) {
            // Silenciar erro de duplicidade ou logar
            // error_log("Erro ao inserir membro $phone: " . $e->getMessage());
            }
        }

        echo json_encode(['success' => true, 'added' => $added, 'message' => "Processado com sucesso. $added novos membros."]);
        exit;
    }

    // AÇÃO 2: PROCESSAR TAREFAS (CRON)
    elseif ($action === 'cron_process') {
        // Versão Simplificada e Segura do CRON

        // Verificar campanha ativa
        $campanha = fetchOne($pdo, "SELECT * FROM marketing_campanhas WHERE id = 1 AND ativo = 1");
        if (!$campanha) {
            echo json_encode(['success' => true, 'tasks' => [], 'message' => 'Campanha inativa']);
            exit;
        }

        $tasks = [];
        $limiteDiario = intval($campanha['membros_por_dia_grupo'] ?? 100);

        // A. Seleção Diária (Novos -> Em Progresso)
        // Verificar quantos já estão em progresso hoje (usar data_entrada_fluxo, não data_proximo_envio)
        $hojeStats = fetchOne($pdo, "SELECT COUNT(*) as c FROM marketing_membros WHERE (status = 'em_progresso' OR status = 'concluido') AND DATE(data_entrada_fluxo) = CURDATE()");
        $hojeCount = intval($hojeStats['c']);

        if ($hojeCount < $limiteDiario) {
            $vagas = $limiteDiario - $hojeCount;
            // Buscar novos membros de grupos distintos
            $novosGrupos = fetchData($pdo, "SELECT DISTINCT grupo_origem_jid FROM marketing_membros WHERE status = 'novo' LIMIT 5");

            foreach ($novosGrupos as $g) {
                if ($vagas <= 0)
                    break;

                $gj = $g['grupo_origem_jid'];
                $candidatos = fetchData($pdo, "SELECT id FROM marketing_membros WHERE grupo_origem_jid = ? AND status = 'novo' LIMIT $vagas", [$gj]);

                foreach ($candidatos as $c) {
                    $delay = rand(1, 5); // Delay pequeno inicial
                    executeQuery($pdo, "UPDATE marketing_membros SET status = 'em_progresso', data_proximo_envio = DATE_ADD(NOW(), INTERVAL ? MINUTE), ultimo_passo_id = 0, data_entrada_fluxo = CURDATE() WHERE id = ?", [$delay, $c['id']]);
                    $vagas--;
                }
            }
        }

        // B. Buscar Tarefas Pendentes com Lock para Multi-Bot
        $pdo->beginTransaction();
        try {
            // Selecionar leads disponíveis e travar para outros bots não pegarem
            $sqlLeads = "
                SELECT m.id, m.telefone, m.ultimo_passo_id
                FROM marketing_membros m
                WHERE m.status = 'em_progresso' 
                AND m.data_proximo_envio <= NOW()
                ORDER BY m.data_proximo_envio ASC
                LIMIT 50
                FOR UPDATE SKIP LOCKED
            ";
            $leadsPendentes = fetchData($pdo, $sqlLeads);

            if (empty($leadsPendentes)) {
                $pdo->rollBack();
                echo json_encode(['success' => true, 'tasks' => []]);
                exit;
            }

            $ids = array_column($leadsPendentes, 'id');
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';

            // "Arrendar" por 5 minutos enquanto o bot tenta enviar
            executeQuery($pdo, "UPDATE marketing_membros SET data_proximo_envio = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id IN ($placeholders)", $ids);

            $pdo->commit();

            $tasks = [];
            foreach ($leadsPendentes as $lead) {
                // Buscar mensagem do próximo passo
                $msg = fetchOne($pdo, "
                    SELECT conteudo, tipo, ordem, midia_url, tipo_midia 
                    FROM marketing_mensagens 
                    WHERE campanha_id = 1 AND ordem = ?
                ", [$lead['ultimo_passo_id'] + 1]);

                if ($msg) {
                    $randomId = substr(md5(uniqid()), 0, 6);
                    $tasks[] = [
                        'member_id' => $lead['id'],
                        'phone' => $lead['telefone'],
                        'message' => $msg['conteudo'] . "\n\n_" . $randomId . "_",
                        'message_type' => $msg['tipo_midia'] ?: $msg['tipo'],
                        'media_url' => $msg['midia_url'],
                        'step_order' => (int)$msg['ordem']
                    ];
                }
            }

            echo json_encode(['success' => true, 'tasks' => $tasks]);
            exit;

        }
        catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // AÇÃO 3: ATUALIZAR TAREFA
    elseif ($action === 'update_task') {
        $memberId = $input['member_id'];
        $stepOrder = $input['step_order'];
        $success = $input['success'];
        $reason = $input['reason'] ?? '';

        if ($success) {
            // Verificar se tem próximo passo
            $nextMsg = fetchOne($pdo, "SELECT delay_apos_anterior_minutos FROM marketing_mensagens WHERE campanha_id = 1 AND ordem > ? ORDER BY ordem ASC LIMIT 1", [$stepOrder]);

            if ($nextMsg) {
                $delay = (int)$nextMsg['delay_apos_anterior_minutos'];
                executeQuery($pdo, "UPDATE marketing_membros SET ultimo_passo_id = ?, data_proximo_envio = DATE_ADD(NOW(), INTERVAL ? MINUTE), status = 'em_progresso' WHERE id = ?", [$stepOrder, $delay, $memberId]);
            }
            else {
                executeQuery($pdo, "UPDATE marketing_membros SET ultimo_passo_id = ?, status = 'concluido' WHERE id = ?", [$stepOrder, $memberId]);
            }
        }
        else {
            // Falhou
            if ($reason === 'invalid_number') {
                executeQuery($pdo, "UPDATE marketing_membros SET status = 'bloqueado' WHERE id = ?", [$memberId]);
            }
            else {
                // Tentar de novo em 1 hora
                $retryTime = date('Y-m-d H:i:s', strtotime("+1 hour"));
                executeQuery($pdo, "UPDATE marketing_membros SET data_proximo_envio = ?, status = 'em_progresso' WHERE id = ?", [$retryTime, $memberId]);
            }
        }

        echo json_encode(['success' => true]);
        exit;
    }

    // AÇÃO 4: RESETAR LIMITE (Manual)
    elseif ($action === 'reset_daily_limit') {
        $ontem = date('Y-m-d H:i:s', strtotime('-1 day'));
        executeQuery($pdo, "UPDATE marketing_membros SET data_proximo_envio = ? WHERE (status = 'em_progresso' OR status = 'concluido') AND DATE(data_proximo_envio) = CURDATE()", [$ontem]);
        echo json_encode(['success' => true, 'message' => 'Limite diário resetado.']);
        exit;
    }

    // AÇÃO 5: LOGAR ENVIO
    elseif ($action === 'log_send') {
        $memberId = $input['member_id'] ?? 0;
        $phone = $input['phone'] ?? '';
        $content = $input['content'] ?? '';

        // Buscar ID da automação de marketing
        $auto = fetchOne($pdo, "SELECT id FROM bot_automations WHERE nome = 'Campanha Marketing' LIMIT 1");
        if (!$auto) {
            executeQuery($pdo, "INSERT INTO bot_automations (nome, status) VALUES ('Campanha Marketing', 1)");
            $autoId = $pdo->lastInsertId();
        }
        else {
            $autoId = $auto['id'];
        }

        executeQuery($pdo, "INSERT INTO bot_automation_logs (automation_id, numero_origem, resposta_enviada, criado_em) VALUES (?, ?, ?, NOW())", [$autoId, $phone, $content]);

        echo json_encode(['success' => true]);
        exit;
    }

    // AÇÃO 6: LIMPAR TUDO
    elseif ($action === 'clear_all_members') {
        executeQuery($pdo, "TRUNCATE TABLE marketing_membros");
        echo json_encode(['success' => true, 'message' => 'Todos os contatos foram removidos.']);
        exit;
    }

    // Nenhuma ação reconhecida
    else {
        echo json_encode(['success' => false, 'message' => 'Ação desconhecida: ' . htmlspecialchars($action)]);
    }

}
catch (Exception $e) {
    // Capturar erro fatal e retornar JSON limpo
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro Interno: ' . $e->getMessage()]);
}
?>