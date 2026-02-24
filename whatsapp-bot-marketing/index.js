/* WhatsApp Bot Marketing - MULTI-INSTANCE (Stable)
 * - Persistência individual por sessão
 * - API para gerenciar múltiplas conexões
 * - Sistema de Aluguel e Uptime
 */
import { default as makeWASocket, useMultiFileAuthState, DisconnectReason, fetchLatestBaileysVersion, Browsers, proto } from '@whiskeysockets/baileys';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import express from 'express';
import cors from 'cors';
import pino from 'pino';
import dotenv from 'dotenv';
import axios from 'axios';
import os from 'os';
import { nlpEngine } from './nlp_ai.js';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
app.use(cors());
app.use(express.json());

const PORT = Number(process.env.API_PORT || 3002);
const API_TOKEN = process.env.WHATSAPP_API_TOKEN || 'lucastav8012';
const MARKETING_SITE_URL = 'https://khaki-gull-213146.hostingersite.com';
const AUTH_BASE_PATH = path.join(os.homedir(), '.whatsapp_marketing_sessions');

if (!fs.existsSync(AUTH_BASE_PATH)) fs.mkdirSync(AUTH_BASE_PATH, { recursive: true });

// --- GESTÃO DE INSTÂNCIAS ---
const instances = new Map(); // sessionId -> { sock, isReady, lastQR, ... }
const memoryLogs = [];
const MAX_LOGS = 200;

function addLog(sessionId, level, message) {
  const logEntry = {
    sessionId,
    level,
    message: String(message),
    timestamp: Date.now()
  };
  memoryLogs.unshift(logEntry);
  if (memoryLogs.length > MAX_LOGS) memoryLogs.pop();
  console.log(`[${sessionId || 'SYS'}] [${level}] ${logEntry.message}`);
}

// Helper JID
function formatJid(phone) {
  if (phone.includes('@')) return phone;
  let digits = phone.replace(/\D/g, '');
  if (!digits.startsWith('55')) digits = '55' + digits;
  return digits.length >= 15 ? digits + '@lid' : digits + '@s.whatsapp.net';
}

const instanceLocks = new Set(); // Trava para evitar criação dupla simultânea

async function createInstance(sessionId, phoneForPairing = null) {
  if (instanceLocks.has(sessionId)) {
    addLog(sessionId, 'WARN', 'Instância já está em processo de inicialização. Ignorando pedido duplicado.');
    return null;
  }

  if (instances.has(sessionId)) {
    const inst = instances.get(sessionId);
    if (inst.isReady) return inst;
    // Se já existe mas queremos código de pareamento e não está pronto
    if (phoneForPairing && !inst.isReady) {
      // Vamos forçar recriação para garantir o trigger do código
      addLog(sessionId, 'INFO', `Solicitando código de pareamento para ${phoneForPairing}`);
    } else {
      return inst;
    }
  }

  instanceLocks.add(sessionId);

  addLog(sessionId, 'INFO', `Iniciando instância: ${sessionId}`);

  // Buscar a versão mais recente em tempo real para evitar erro 405 (Protocol Mismatch)
  let version = [2, 3000, 1015901307]; // Fallback 2026
  try {
    const { version: latest } = await fetchLatestBaileysVersion();
    version = latest;
    addLog(sessionId, 'INFO', `Usando Baileys v${version.join('.')}`);
  } catch (e) {
    addLog(sessionId, 'WARN', 'Não foi possível buscar a versão mais recente, usando fallback estável.');
  }

  const sessionPath = path.join(AUTH_BASE_PATH, sessionId);
  if (!fs.existsSync(sessionPath)) fs.mkdirSync(sessionPath, { recursive: true });

  const { state, saveCreds } = await useMultiFileAuthState(sessionPath);

  let sock;
  try {
    sock = makeWASocket({
      auth: state,
      logger: pino({ level: 'silent' }),
      version,
      browser: Browsers.ubuntu('Chrome'),
      connectTimeoutMs: 60000,
      keepAliveIntervalMs: 30000,
      printQRInTerminal: false,
      generateHighQualityLinkPreview: false,
      markOnlineOnConnect: false,
      syncFullHistory: false,
      getMessage: async (key) => {
        return { conversation: 'Protocolo Elite 2026' }
      },
      maxMsgRetryCount: 5
    });
  } catch (err) {
    addLog(sessionId, 'ERROR', `Erro ao criar socket: ${err.message}`);
    return null;
  }

  const instanceData = {
    sessionId,
    sock,
    isReady: false,
    lastQR: null,
    pairingCode: null,
    _pendingPairing: null,
    uptimeStart: null,
    hasLoggedMaturation: false,
    maturationDate: null,
    safetyPausedUntil: null
  };

  instances.set(sessionId, instanceData);
  instanceLocks.delete(sessionId);

  // Controle de Maturação Elite (Grava a data da primeira conexão)
  const maturationPath = path.join(sessionPath, 'maturation_info.json');
  if (!fs.existsSync(maturationPath)) {
    fs.writeFileSync(maturationPath, JSON.stringify({ firstSeen: Date.now() }));
  }
  try {
    const matData = JSON.parse(fs.readFileSync(maturationPath));
    instanceData.maturationDate = new Date(matData.firstSeen);
  } catch (e) {
    instanceData.maturationDate = new Date();
  }

  sock.ev.on('creds.update', saveCreds);

  sock.ev.on('connection.update', async (update) => {
    const { connection, lastDisconnect, qr } = update;

    if (qr) {
      instanceData.lastQR = qr;
      addLog(sessionId, 'INFO', 'Novo QR Code gerado');
      updateRemoteStatus(sessionId, 'aguardando_qr');

      // Se estamos esperando pareamento por número, gerar o código com pequeno delay
      if (instanceData._pendingPairing && !instanceData.pairingCode) {
        const pairingNumber = instanceData._pendingPairing;
        instanceData._pendingPairing = null;

        // Delay de 2s após o QR para garantir estabilidade do socket
        setTimeout(async () => {
          try {
            const code = await sock.requestPairingCode(pairingNumber);
            instanceData.pairingCode = code;
            addLog(sessionId, 'SUCCESS', `Código de Pareamento gerado para ${pairingNumber}: ${code}`);
          } catch (err) {
            addLog(sessionId, 'ERROR', `Erro ao gerar código de pareamento: ${err.message}`);
          }
        }, 2000);
      }
    }

    if (connection === 'close') {
      const errorCode = lastDisconnect?.error?.output?.statusCode;
      const errorMsg = lastDisconnect?.error?.message;
      const shouldReconnect = errorCode !== DisconnectReason.loggedOut;
      instanceData.isReady = false;

      if (errorCode === 515) {
        // 515 = Stream Errored - comportamento ESPERADO após pairing code
        // Reconectar RÁPIDO preservando credenciais
        addLog(sessionId, 'INFO', `Reconectando após pareamento (Code: 515)...`);
        instances.delete(sessionId);
        setTimeout(() => createInstance(sessionId), 1500);
      } else if (errorCode === 440 || errorCode === 405) {
        // 440 = Conflict / 405 = MethodNotAllowed (Protocol Outdated/Mismatched)
        addLog(sessionId, 'WARN', `Erro Crítico de Conexão (${errorCode}). Limpando cache e tentando reconectar...`);
        try { sock.ev.removeAllListeners(); sock.ws.close(); } catch (e) { }
        if (errorCode === 405 && fs.existsSync(sessionPath)) {
          // Se for 405 persistente, limpamos a pasta para forçar novo QR
          fs.rmSync(sessionPath, { recursive: true, force: true });
        }
        instances.delete(sessionId);
        setTimeout(() => createInstance(sessionId), 10000);
      } else if (errorCode === 403 || errorMsg?.includes('403')) {
        // 403 = Forbidden (Geralmente BANIMENTO definitivo do chip)
        addLog(sessionId, 'ERROR', `CONTA BANIDA (Code 403). Parando reconexão e limpando arquivos.`);
        try { sock.ev.removeAllListeners(); sock.ws.close(); } catch (e) { }
        if (fs.existsSync(sessionPath)) fs.rmSync(sessionPath, { recursive: true, force: true });
        instances.delete(sessionId);
        instanceLocks.delete(sessionId);
        updateRemoteStatus(sessionId, 'desconectado');
      } else if (shouldReconnect) {
        addLog(sessionId, 'WARN', `Conexão fechada. Code: ${errorCode}, Msg: ${errorMsg}. Reconnect: true`);
        try { sock.ev.removeAllListeners(); sock.ws.close(); } catch (e) { }
        instances.delete(sessionId);
        setTimeout(() => createInstance(sessionId), 5000);
      } else {
        addLog(sessionId, 'ERROR', 'A conta foi desconectada (Logout/Ban). Removendo arquivos de sessão.');
        try { sock.ev.removeAllListeners(); sock.ws.close(); } catch (e) { }
        if (fs.existsSync(sessionPath)) fs.rmSync(sessionPath, { recursive: true, force: true });
        instances.delete(sessionId);
        instanceLocks.delete(sessionId);
        updateRemoteStatus(sessionId, 'desconectado');
      }
    } else if (connection === 'open') {
      addLog(sessionId, 'SUCCESS', 'Conectado com sucesso!');
      instanceData.isReady = true;
      instanceData.lastQR = null;
      instanceData.uptimeStart = Date.now();
      updateRemoteStatus(sessionId, 'conectado');
    }
  });

  // Se foi passado um número para pareamento, marcar para gerar quando o QR chegar
  if (phoneForPairing && !state.creds.registered) {
    let pairingNumber = phoneForPairing.replace(/\D/g, '');
    if (pairingNumber.length <= 11 && !pairingNumber.startsWith('55')) {
      pairingNumber = '55' + pairingNumber;
    }
    instanceData._pendingPairing = pairingNumber;
    addLog(sessionId, 'INFO', `Pareamento pendente para ${pairingNumber}. Aguardando socket ficar pronto...`);
  }

  // Lidar com recebimento de mensagens para simulação humana (Maturação do Chip/Aquecimento)
  sock.ev.on('messages.upsert', async m => {
    if (m.type === 'notify') {
      for (const msg of m.messages) {
        if (!msg.key.fromMe && msg.message) {
          const remoteJid = msg.key.remoteJid;
          // Ignorar mensagens de grupos ou status no auto-read de aquecimento
          if (remoteJid && !remoteJid.includes('@g.us') && !remoteJid.includes('status')) {
            try {
              // 1. Aguardar um delay aleatório para ler a mensagem e simular humano (3s a 8s)
              setTimeout(async () => {
                await sock.readMessages([msg.key]);
              }, Math.floor(Math.random() * (8000 - 3000 + 1)) + 3000);

              // 2. IA de Conversação (Aquecimento Ativo Safeway)
              // Verifica se a mensagem veio de outra instância interna nossa para evitar responder clientes finais acidentalmente
              let fromAnotherBot = false;
              for (const inst of instances.values()) {
                if (inst.isReady && inst.sock && inst.sock.user) {
                  const jid = inst.sock.user.id.split(':')[0] + '@s.whatsapp.net';
                  if (jid === remoteJid) fromAnotherBot = true;
                }
              }

              const textContent = msg.message?.conversation || msg.message?.extendedTextMessage?.text;

              if (fromAnotherBot && textContent) {
                // Tem 70% de chance de responder para gerar um diálogo orgânico e quebrar loops infinitos rápidos
                if (Math.random() < 0.70) {
                  setTimeout(async () => {
                    if (!instances.has(sessionId)) return; // Sessão pode ter caido

                    const aiResponse = nlpEngine.analyze(textContent, remoteJid);

                    // Simula tempo de digitação inteligente baseado no tamanho da resposta da IA
                    const typingTime = Math.min((aiResponse.length * 80) + 1500, 8000);
                    await sock.sendPresenceUpdate('composing', remoteJid);
                    await new Promise(r => setTimeout(r, typingTime));
                    await sock.sendPresenceUpdate('paused', remoteJid);

                    await sock.sendMessage(remoteJid, { text: aiResponse });
                    addLog(sessionId, 'INFO', `[IA RESP] A inteligência gerou resposta para ${remoteJid}`);

                    // 3. Reagir à mensagem recebida com um emoji (Simulação Humana Premium)
                    if (Math.random() < 0.4) {
                      setTimeout(async () => {
                        const emojis = ['👍', '❤️', '😂', '😮', '👏', '🔥', '🙌'];
                        const reactionEmoji = emojis[Math.floor(Math.random() * emojis.length)];
                        await sock.sendMessage(remoteJid, {
                          react: { text: reactionEmoji, key: msg.key }
                        });
                      }, 2000);
                    }

                  }, Math.floor(Math.random() * (12000 - 4000 + 1)) + 4000); // Demora de 4 a 12s para "pensar" em responder
                }
              }

            } catch (e) { }
          }
        }
      }
    }
  });

  return instanceData;
}

// Atualiza o status localmente (remover heartbeat e aluguel remoto pra economizar banda)
async function updateRemoteStatus(sessionId, status) {
  addLog(sessionId, 'INFO', `Instância [${sessionId}] status alterado para: ${status}`);
}


// --- MARKETING ENGINE (MULTIPLO) ---
let isProcessingMarketing = false;

async function processGlobalMarketing() {
  if (isProcessingMarketing) return;
  isProcessingMarketing = true;

  try {
    // 0. Modo Horário Humano (Simulação Biológica)
    const hour = new Date().getHours();
    if (hour < 8 || hour >= 22) {
      addLog('SYSTEM', 'INFO', `[MODO SONO] Horário atual (${hour}h) fora da janela comercial. Atividades suspensas.`);
      isProcessingMarketing = false;
      return;
    }

    const activeCount = Array.from(instances.values()).filter(i => i.isReady).length;
    if (activeCount > 0) {
      addLog('SYSTEM', 'INFO', `[MARKETING] Iniciando verificação de fila para ${activeCount} instâncias prontas.`);
    }

    // 1. Pegar instâncias prontas
    const activeInstances = Array.from(instances.values()).filter(i => i.isReady);

    // 2. Processar tarefas para cada instância
    for (const inst of activeInstances) {
      // Monitor de Saúde: Verificar se o chip está em Pausa de Segurança
      if (inst.safetyPausedUntil && inst.safetyPausedUntil > Date.now()) {
        const remainingMinutes = Math.round((inst.safetyPausedUntil - Date.now()) / 60000);
        addLog(inst.sessionId, 'WARN', `Chip em PAUSA DE SEGURANÇA. Marketing suspenso por mais ${remainingMinutes} min (Risco de Ban detectado).`);
        continue;
      }
      // Regra Anti-Ban Elite: 1 Dia de Maturação Ativa
      // Só dispara se a data atual for diferente da data de criação da sessão (Dia Seguinte 00:01)
      const now = new Date();
      const maturationDate = inst.maturationDate || now;
      const isSameDay = now.toDateString() === maturationDate.toDateString();

      if (isSameDay) {
        if (!inst.hasLoggedMaturation) {
          const tomorrow = new Date(now);
          tomorrow.setDate(tomorrow.getDate() + 1);
          tomorrow.setHours(0, 1, 0, 0);

          const diffMs = tomorrow - now;
          const hours = Math.floor(diffMs / (1000 * 60 * 60));
          const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));

          addLog(inst.sessionId, 'SUCCESS', `Chip em MATURAÇÃO ATIVA. Marketing libera em ${hours}h e ${minutes}min (às 00:01 de amanhã).`);
          inst.hasLoggedMaturation = true;
        }
        continue;
      }

      try {
        addLog(inst.sessionId, 'INFO', `[MARKETING] Consultando novas tarefas no servidor...`);
        const res = await axios.get(`${MARKETING_SITE_URL}/api_marketing.php?action=cron_process`, { timeout: 15000 });
        if (res.data?.success && res.data.tasks?.length > 0) {

          addLog(inst.sessionId, 'SUCCESS', `[MARKETING] Fila encontrada: ${res.data.tasks.length} mensagens para disparar.`);
          for (const task of res.data.tasks) {
            const result = await sendWithInstance(inst, task);
            await axios.post(`${MARKETING_SITE_URL}/api_marketing.php?action=update_task`, {
              member_id: task.member_id,
              step_order: task.step_order,
              success: result.success,
              reason: result.reason
            });
            // Delay anti-ban MUITO MAIS SEGURO (20s a 45s entre cada mensagem)
            const randomDelay = Math.floor(Math.random() * (45000 - 20000 + 1)) + 20000;
            addLog(inst.sessionId, 'INFO', `Aguardando ${Math.round(randomDelay / 1000)}s antes do próximo envio (Anti-ban)...`);
            await new Promise(r => setTimeout(r, randomDelay));
          }
        }
      } catch (err) {
        addLog(inst.sessionId, 'ERROR', `Erro no loop marketing: ${err.message}`);
      }
    }
  } catch (err) {
    addLog('SYSTEM', 'ERROR', `Erro global no marketing: ${err.message}`);
  } finally {
    isProcessingMarketing = false;
  }
}

async function sendWithInstance(inst, task) {
  try {
    const jid = formatJid(task.phone);

    // --- IA DE POLIMORFISMO (MENSAGENS ÚNICAS) ---
    let finalMessage = task.message;

    // 1. Injetar Caractere Invisível Aleatório (Zero-Width Space) para tornar o hash da msg único
    const invisibleChars = ['\u200B', '\u200C', '\u200D', '\uFEFF'];
    const randomChar = invisibleChars[Math.floor(Math.random() * invisibleChars.length)];
    finalMessage += randomChar;

    // 2. Variabilidade de Saudação Dinâmica (Se começar com Oi/Olá)
    if (finalMessage.toLowerCase().startsWith('oi') || finalMessage.toLowerCase().startsWith('ola')) {
      const saudacoes = ['Opa', 'Oi', 'Olá', 'E aí', 'Tudo bem?', 'Fala!'];
      const randomSaudacao = saudacoes[Math.floor(Math.random() * saudacoes.length)];
      finalMessage = finalMessage.replace(/^(oi|ola|olá)[^a-zA-Z]*/i, randomSaudacao + ' ');
    }

    let msgContent = { text: finalMessage };

    if (task.media_url) {
      const baseURL = task.media_url.startsWith('http') ? '' : `${MARKETING_SITE_URL}/`;
      const fullPath = baseURL + task.media_url;
      const mediaUrl = encodeURI(fullPath);
      const type = task.message_type === 'video' ? 'video' : 'image';
      msgContent = { [type]: { url: mediaUrl }, caption: finalMessage };
    }

    // --- SIMULAÇÃO HUMANA AVANÇADA ---
    // Cálculo de digitação: 50ms por caractere + delay base humano
    const baseDelay = Math.floor(Math.random() * 2000) + 1000;
    const typingDuration = Math.min((finalMessage.length * 50) + baseDelay, 10000); // Max 10s

    await inst.sock.sendPresenceUpdate(task.message_type === 'video' ? 'recording' : 'composing', jid);
    await new Promise(r => setTimeout(r, typingDuration));
    await inst.sock.sendPresenceUpdate('paused', jid);

    const sent = await inst.sock.sendMessage(jid, msgContent);

    if (sent) {
      addLog(inst.sessionId, 'SUCCESS', `Mensagem enviada para ${jid}`);
      // Logar estatística
      axios.post(`${MARKETING_SITE_URL}/api_marketing.php?action=log_send`, {
        session_id: inst.sessionId,
        phone: jid,
        content: task.message
      }).catch(e => { });
      return { success: true };
    }
  } catch (e) {
    const errorCode = e?.output?.statusCode || e?.data?.statusCode;

    // Detecção de Risco de Ban (403 = Forbidden, 401 = Unauthorized)
    if (errorCode === 403 || errorCode === 401 || e.message.includes('403') || e.message.includes('401')) {
      addLog(inst.sessionId, 'ERROR', `RISCO DE BAN DETECTADO (Erro ${errorCode}). Ativando Pausa de Segurança de 2 horas.`);
      inst.safetyPausedUntil = Date.now() + (120 * 60 * 1000); // 120 minutos de pausa
      return { success: false, reason: 'safety_pause_triggered' };
    }

    return { success: false, reason: e.message };
  }
}

// Loop de processamento de fila do admin
setInterval(async () => {
  const readyInstances = Array.from(instances.values()).filter(i => i.isReady).length;
  addLog('SYSTEM', 'INFO', `[MONITOR] Pulso de Vida: ${instances.size} sessões carregadas (${readyInstances} conectadas).`);

  // Tentar rodar marketing
  processGlobalMarketing();

  // Automação Elite: Sincronizar grupos automaticamente uma vez por dia (às 00:05)
  const agora = new Date();
  if (agora.getHours() === 0 && agora.getMinutes() === 5) {
    addLog('SYSTEM', 'INFO', '[AUTOMAÇÃO] Iniciando sincronização automática diária de grupos...');
    const activeInstances = Array.from(instances.values()).filter(i => i.isReady);

    for (const inst of activeInstances) {
      // Só sincroniza automaticamente se não estiver mais no primeiro dia (dia de maturação)
      const maturationDate = inst.maturationDate || agora;
      const isSameDay = agora.toDateString() === maturationDate.toDateString();

      if (!isSameDay) {
        addLog(inst.sessionId, 'INFO', '[AUTO-SYNC] Buscando novos leads nos grupos...');
        // Reutiliza a lógica de sincronização (Execução em silêncio)
        (async () => {
          try {
            const groups = await inst.sock.groupFetchAllParticipating();
            for (const group of Object.values(groups)) {
              const participants = group.participants.map(p => p.id.split('@')[0]);
              await axios.post(`${MARKETING_SITE_URL}/api_marketing.php?action=save_members`, {
                group_jid: group.subject,
                members: participants
              }).catch(() => { });
              await new Promise(r => setTimeout(r, 2000));
            }
            addLog(inst.sessionId, 'SUCCESS', '[AUTO-SYNC] Grupos sincronizados com sucesso.');
          } catch (e) {
            addLog(inst.sessionId, 'ERROR', `[AUTO-SYNC] Falha: ${e.message}`);
          }
        })();
      }
    }
  }
}, 60000);

// --- SISTEMA DE AQUECIMENTO DE NÚMEROS (MATURAÇÃO ELITE) ---
async function processGlobalWarming() {
  const hour = new Date().getHours();
  if (hour < 8 || hour >= 22) return; // Dormir também no aquecimento

  const activeInstances = Array.from(instances.values()).filter(i => i.isReady && i.sock && i.sock.user);
  if (activeInstances.length === 0) {
    addLog('SYSTEM', 'INFO', '[AQUECIMENTO] Ciclo ignorado: Nenhuma instância pronta para interação.');
    return;
  }

  // 1. Interação entre Chips (Se houver 2 ou mais)
  if (activeInstances.length >= 2) {
    addLog('SYSTEM', 'SUCCESS', `[AQUECIMENTO] Iniciando Ciclo de Diálogo Orgânico entre ${activeInstances.length} chips.`);
    const shuffled = [...activeInstances].sort(() => 0.5 - Math.random());

    for (let i = 0; i < Math.floor(shuffled.length / 2) * 2; i += 2) {
      const instA = shuffled[i];
      const instB = shuffled[i + 1];

      if (!instA.sock.user || !instB.sock.user) continue;

      try {
        const jidB = instB.sock.user.id.split(':')[0] + '@s.whatsapp.net';
        const randomMsg = nlpEngine.generateOpener();

        // 1. Simular Presença Humana (Online + Digitante)
        await instA.sock.sendPresenceUpdate('available');
        const typingDuration = Math.floor(Math.random() * (7000 - 3000 + 1)) + 3000;
        await instA.sock.sendPresenceUpdate('composing', jidB);
        await new Promise(r => setTimeout(r, typingDuration));

        // 2. Enviar Mensagem
        await instA.sock.sendMessage(jidB, { text: randomMsg });
        addLog(instA.sessionId, 'INFO', `[AQUECIMENTO] Mensagem enviada para ${jidB}: "${randomMsg}"`);

        // 3. Comportamento Extra: Chance de reagir a uma mensagem anterior (se existir)
        if (Math.random() > 0.5) {
          const emojis = ['👍', '❤️', '😂', '😮', '👏', '🙏', '🔥'];
          const randomEmoji = emojis[Math.floor(Math.random() * emojis.length)];
          // Nota: Precisaríamos do ID de uma mensagem real para reagir. 
          // Vamos deixar para o messages.upsert lidar com as reações de volta.
        }

        // 4. Comportamento Humano: Chance de postar um Status (Story)
        if (Math.random() > 0.8) {
          const captions = [
            "Dia produtivo! ✨", "Bora pra cima!", "Café e foco. ☕", "A persistência vence o talento.",
            "Novos projetos vindo aí... 🚀", "Gratidão por tudo.", "A paz de espírito é o maior luxo.",
            "Não pare até se orgulhar. 🔥", "Apenas vivendo... 🍃", "Trabalho em silêncio, sucesso faz o barulho.",
            "Mais um dia, mais uma meta! ✅", "Equilíbrio é tudo.", "Fé no processo."
          ];
          const statusText = captions[Math.floor(Math.random() * captions.length)];
          const bgs = ['#FF5733', '#3357FF', '#33FF57', '#F333FF', '#33FFF3', '#000000'];
          const randomBg = bgs[Math.floor(Math.random() * bgs.length)];

          await instA.sock.sendMessage('status@broadcast', {
            text: statusText
          }, {
            backgroundColor: randomBg,
            font: Math.floor(Math.random() * 5) + 1
          });
          addLog(instA.sessionId, 'INFO', `[AQUECIMENTO] Postou Status: "${statusText}"`);
        }

        // 5. Comportamento Humano: Mudar o Recado (Bio/Nota)
        if (Math.random() > 0.85) {
          const bios = [
            "Foco e Fé 🚀", "Trabalhando...", "Disponível para negócios", "No topo ou a caminho!",
            "Só respondo urgente", "A vida é curta, aproveite.", "Em constante evolução 🧬",
            "WhatsApp Only 📱", "Busy working", "Deus é fiel", "Mindset Milionário"
          ];
          const newBio = bios[Math.floor(Math.random() * bios.length)];
          await instA.sock.updateProfileStatus(newBio);
          addLog(instA.sessionId, 'INFO', `[AQUECIMENTO] Atualizou Bio: "${newBio}"`);
        }

        // 6. ELITE SHIELD: Interação com Contas Oficiais (Blindagem)
        // Se houver apenas 1 chip ou chance aleatória, mandar msg para um número de utilidade/oficial
        if (Math.random() > 0.90) {
          const officialAccounts = ['5511999999999', '5511947741441', '5511973161000']; // Números de exemplo (Grandes empresas)
          const target = officialAccounts[Math.floor(Math.random() * officialAccounts.length)] + '@s.whatsapp.net';
          await instA.sock.sendMessage(target, { text: "Olá! Gostaria de saber mais sobre os serviços." });
          addLog(instA.sessionId, 'SUCCESS', `[ELITE SHIELD] Interação de blindagem enviada para conta oficial.`);
        }

        await instA.sock.sendPresenceUpdate('unavailable');
      } catch (e) {
        addLog(instA.sessionId, 'ERROR', `[AQUECIMENTO] Erro no comportamento humano: ${e.message}`);
      }
      await new Promise(r => setTimeout(r, Math.floor(Math.random() * 8000) + 3000));
    } // Fim do For do loop de interação
  } // Fim do If de 2 ou mais chips

  // --- 2. ELITE SHIELD: A TRÍADE DE BLINDAGEM ELITE ---
  for (const inst of activeInstances) {
    try {
      addLog(inst.sessionId, 'INFO', `[ELITE SHIELD] Iniciando rotina de blindagem humana...`);

      await inst.sock.sendPresenceUpdate('available');
      await new Promise(r => setTimeout(r, 2000));

      // ESTRATÉGIA A: STATUS (STORIES) PERMANENTE - Versão Estável
      const statusTexts = [
        "Foco no progresso! 🚀", "Dia de grandes metas. ✅", "A persistência vence o talento.",
        "Gratidão por cada conquista. ✨", "Trabalhe em silêncio... 🤫", "Café e estratégia. ☕",
        "Apenas o começo. 📈", "Metas batidas! 🙏"
      ];

      const randomText = statusTexts[Math.floor(Math.random() * statusTexts.length)];

      // Para o Status aparecer, o WhatsApp exige saber quem pode ver. 
      // Usaremos uma lista vazia ou forçaremos via broadcast.
      await inst.sock.sendMessage('status@broadcast', {
        text: randomText
      }, {
        backgroundColor: '#FF3B3B',
        font: 1,
        statusJidList: [inst.sock.user.id] // Incluir a si mesmo ajuda a propagar o broadcast
      });

      addLog(inst.sessionId, 'SUCCESS', `[ELITE SHIELD] Blindagem A: Status/Stories publicado ("${randomText}").`);

      // ESTRATÉGIA B: OUVINTE ATIVO (ESCUTA DE GRUPOS)
      addLog(inst.sessionId, 'INFO', `[ELITE SHIELD] Blindagem B: Analisando atividade em grupos para gerar tráfego lícito.`);
      const groups = await inst.sock.groupFetchAllParticipating();
      const groupJids = Object.keys(groups);
      if (groupJids.length > 0) {
        const randomGroup = groupJids[Math.floor(Math.random() * groupJids.length)];
        // Simular consumo de dados: ler mensagens do grupo
        await inst.sock.readMessages([{ remoteJid: randomGroup, id: 'any', fromMe: false }]);
        addLog(inst.sessionId, 'INFO', `[ELITE SHIELD] Ouvinte Ativo: Grupo "${groups[randomGroup].subject}" lido.`);

        // ESTRATÉGIA C: MATURAÇÃO POR REAÇÃO (EMOJIS)
        if (Math.random() > 0.4) { // Alta chance de reagir
          const emojis = ['👍', '❤️', '🔥', '👏', '😮', '🙏'];
          // Tenta reagir a mensagens recentes se possível, ou apenas sinaliza atividade
          await inst.sock.sendMessage(randomGroup, {
            react: { text: emojis[Math.floor(Math.random() * emojis.length)], key: { remoteJid: randomGroup } }
          }).catch(() => { });
          addLog(inst.sessionId, 'SUCCESS', `[ELITE SHIELD] Blindagem C: Reação orgânica enviada em "${groups[randomGroup].subject}".`);
        }
      }

      // ESTRATÉGIA D: A "CHAMADA PERDIDA" (SIMULAÇÃO DE VOZ)
      // Simular abertura de chamada/contato com conta oficial para gerar log de voz
      if (Math.random() > 0.8) {
        const officials = ['5511999999999', '5511947741441'];
        const target = officials[Math.floor(Math.random() * officials.length)] + '@s.whatsapp.net';
        // No Baileys, apenas sinalizar 'recording' ou 'composing' por longo tempo simula atividade de voz/mídia
        await inst.sock.sendPresenceUpdate('recording', target);
        await new Promise(r => setTimeout(r, 5000));
        await inst.sock.sendPresenceUpdate('paused', target);
        addLog(inst.sessionId, 'SUCCESS', `[ELITE SHIELD] Blindagem D: Simulação de chamada de voz concluída.`);
      }

      // ESTRATÉGIA E: TROCA DE MÍDIA (ÁUDIO E IMAGEM) - NOVIDADE 2026
      if (Math.random() > 0.7 && groupJids.length > 0) {
        const targetGroup = groupJids[Math.floor(Math.random() * groupJids.length)];
        const chance = Math.random();

        if (chance > 0.5) {
          // Enviar "Áudio" (Sinalizar gravação e enviar nota de voz curta de 1s)
          await inst.sock.sendPresenceUpdate('recording', targetGroup);
          await new Promise(r => setTimeout(r, 3000));
          // Usamos um buffer de 1 segundo de silêncio para simular o áudio
          await inst.sock.sendMessage(targetGroup, {
            audio: { url: 'https://www.soundjay.com/buttons/button-09.mp3' },
            mimetype: 'audio/mp4',
            ptt: true
          }).catch(() => { });
          addLog(inst.sessionId, 'INFO', `[ELITE SHIELD] Mídia: Áudio orgânico enviado.`);
        } else {
          // Enviar Imagem (Meme/Foto aleatória de banco público)
          await inst.sock.sendMessage(targetGroup, {
            image: { url: 'https://picsum.photos/400/300' },
            caption: '🚀'
          }).catch(() => { });
          addLog(inst.sessionId, 'SUCCESS', `[ELITE SHIELD] Blindagem E: Troca de mídia (Imagem) realizada com sucesso.`);
        }
      }

      await inst.sock.sendPresenceUpdate('unavailable');
    } catch (e) {
      addLog(inst.sessionId, 'ERROR', `[ELITE SHIELD] Erro na blindagem: ${e.message}`);
    }
  }
}

function scheduleNextWarming(firstRun = false) {
  // Se for a primeira vez, começa em 2 a 5 minutos. Depois, segue o padrão de 10 a 25 min.
  const delayMinutes = firstRun
    ? Math.floor(Math.random() * 3) + 2
    : Math.floor(Math.random() * (25 - 10 + 1) + 10);

  setTimeout(() => {
    processGlobalWarming();
    scheduleNextWarming();
  }, delayMinutes * 60 * 1000);
}
// Iniciar rotina de aquecimento global (com delay inicial reduzido)
scheduleNextWarming(true);

// --- ROTAS API ---

app.get('/trigger', (req, res) => {
  addLog('SYSTEM', 'INFO', 'Trigger manual acionado pelo painel administrador.');
  processGlobalMarketing();
  res.json({ success: true, message: 'Processamento iniciado.' });
});

app.post('/sync-members', async (req, res) => {
  const activeInstances = Array.from(instances.values()).filter(i => i.isReady);

  if (activeInstances.length === 0) {
    return res.status(400).json({ success: false, message: 'Nenhuma instância conectada para sincronizar.' });
  }

  addLog('SYSTEM', 'INFO', `Iniciando sincronização de grupos em ${activeInstances.length} instâncias.`);

  // Processo em background
  (async () => {
    for (const inst of activeInstances) {
      try {
        addLog(inst.sessionId, 'INFO', 'Buscando grupos...');
        const groups = await inst.sock.groupFetchAllParticipating();
        const groupList = Object.values(groups);

        for (const group of groupList) {
          addLog(inst.sessionId, 'INFO', `Sincronizando grupo: ${group.subject}`);
          const participants = group.participants.map(p => p.id.split('@')[0]);

          // Enviar lote de números para o site de marketing via api_marketing.php (Sem requireLogin)
          await axios.post(`${MARKETING_SITE_URL}/api_marketing.php?action=save_members`, {
            group_jid: group.subject,
            members: participants
          }, {
            headers: { 'Content-Type': 'application/json' }
          }).catch(e => {
            addLog(inst.sessionId, 'ERROR', `Erro ao enviar leads do grupo ${group.subject}: ${e.message}`);
          });

          // Delay entre grupos para evitar overload
          await new Promise(r => setTimeout(r, 2000));
        }
      } catch (err) {
        addLog(inst.sessionId, 'ERROR', `Falha ao sincronizar grupos: ${err.message}`);
      }
    }
  })();

  res.json({ success: true, message: 'Sincronização iniciada em todas as instâncias conectadas.' });
});

app.get('/instance/qr/:sessionId', async (req, res) => {
  const { sessionId } = req.params;
  let inst = instances.get(sessionId);

  if (!inst) {
    inst = await createInstance(sessionId);
  }

  if (inst.isReady) return res.json({ status: 'connected' });
  if (!inst.lastQR) return res.json({ status: 'loading' });

  const qrImageUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(inst.lastQR)}`;
  res.json({ status: 'qr', qr: qrImageUrl });
});

app.post('/instance/pairing-code', async (req, res) => {
  const { sessionId, phone } = req.body;
  if (!sessionId || !phone) return res.status(400).json({ error: 'Missing sessionId or phone' });

  // Forçar limpeza total se já existir
  const existing = instances.get(sessionId);
  if (existing) {
    try {
      existing.sock.ev.removeAllListeners();
      existing.sock.ws.close();
    } catch (e) { }
    instances.delete(sessionId);
  }

  const sessionPath = path.join(AUTH_BASE_PATH, sessionId);
  if (fs.existsSync(sessionPath)) {
    fs.rmSync(sessionPath, { recursive: true, force: true });
  }

  // Criar nova instância focada no pareamento
  const inst = await createInstance(sessionId, phone);

  // Aguardar o código ser gerado (polling interno mais rápido)
  let attempts = 0;
  const maxAttempts = 20;
  const checkCode = setInterval(() => {
    if (inst.pairingCode) {
      clearInterval(checkCode);
      res.json({ status: 'code', code: inst.pairingCode });
    } else if (attempts >= maxAttempts) {
      clearInterval(checkCode);
      res.status(500).json({ status: 'error', message: 'O WhatsApp demorou muito para gerar o código. Tente novamente.' });
    }
    attempts++;
  }, 1000);
});

app.post('/instance/reset/:sessionId', async (req, res) => {
  const { sessionId } = req.params;

  // Fechar conexão antiga
  const existing = instances.get(sessionId);
  if (existing) {
    try {
      existing.sock.ev.removeAllListeners();
      existing.sock.ws.close();
    } catch (e) { }
    instances.delete(sessionId);
  }

  // Limpar arquivos de sessão
  const sessionPath = path.join(AUTH_BASE_PATH, sessionId);
  if (fs.existsSync(sessionPath)) {
    fs.rmSync(sessionPath, { recursive: true, force: true });
  }

  // Criar nova instância limpa
  const inst = await createInstance(sessionId);
  addLog(sessionId, 'INFO', 'Sessão resetada. Novo QR Code será gerado.');
  res.json({ success: true, message: 'Sessão resetada' });
});

app.post('/instance/create', async (req, res) => {
  const { sessionId } = req.body;
  if (!sessionId) return res.status(400).json({ error: 'Missing sessionId' });
  await createInstance(sessionId);
  res.json({ success: true });
});

app.get('/instances', (req, res) => {
  const { token } = req.query;
  if (token !== API_TOKEN) return res.status(401).json({ success: false, message: 'Token inválido' });

  const list = Array.from(instances.values()).map(i => ({
    sessionId: i.sessionId,
    phoneNumber: i.sock?.user?.id ? i.sock.user.id.split(':')[0] : null,
    isReady: i.isReady,
    uptimeStart: i.uptimeStart,
    maturationDate: i.maturationDate,
    safetyPausedUntil: i.safetyPausedUntil
  }));

  res.json({ success: true, instances: list });
});

app.get('/instance/list', (req, res) => {
  const list = Array.from(instances.values()).map(i => ({
    sessionId: i.sessionId,
    isReady: i.isReady,
    uptime: i.uptimeStart ? Math.floor((Date.now() - i.uptimeStart) / 1000) : 0
  }));
  res.json({ success: true, instances: list });
});

app.get('/logs', (req, res) => {
  if (req.query.token !== API_TOKEN) return res.status(401).send();
  res.json({ success: true, logs: memoryLogs });
});

app.post('/reset-instance/:sessionId', async (req, res) => {
  const { sessionId } = req.params;
  const { token } = req.query;

  if (token !== API_TOKEN) {
    return res.status(401).json({ success: false, message: 'Token inválido' });
  }

  addLog(sessionId, 'WARN', `Solicitado RESET INDIVIDUAL da instância.`);

  try {
    const inst = instances.get(sessionId);
    if (inst && inst.sock) {
      try { inst.sock.ev.removeAllListeners(); inst.sock.ws.close(); } catch (e) { }
    }

    const sessionPath = path.join(AUTH_BASE_PATH, sessionId);
    if (fs.existsSync(sessionPath)) {
      fs.rmSync(sessionPath, { recursive: true, force: true });
    }

    instances.delete(sessionId);

    addLog(sessionId, 'SUCCESS', `Instância removida com sucesso.`);
    res.json({ success: true, message: `Sessão ${sessionId} removida.` });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

app.listen(PORT, () => {
  addLog('SYSTEM', 'SUCCESS', `Server Multi-Bot running on port ${PORT}`);

  // Auto-resume sessions prontas
  const sessions = fs.readdirSync(AUTH_BASE_PATH);
  for (const s of sessions) {
    if (fs.lstatSync(path.join(AUTH_BASE_PATH, s)).isDirectory()) {
      createInstance(s);
    }
  }
});

// --- LIMPADOR AUTOMÁTICO DE LIXO (STORAGE CLEANER) ---
// Roda a cada 8 horas para manter o robô leve e rápido
setInterval(async () => {
  addLog('SYSTEM', 'INFO', 'Iniciando limpeza automática de cache e mensagens antigas para otimização...');
  for (const inst of instances.values()) {
    if (inst.isReady && inst.sock) {
      try {
        // Em 2026, limpar o histórico de chat é vital para a performance do Baileys
        // Isso não apaga os membros sincronizados, apenas limpa a memória do WhatsApp no servidor
        const chats = await inst.sock.groupFetchAllParticipating();
        for (const jid of Object.keys(chats)) {
          await inst.sock.chatModify({ delete: true, lastMessages: [{ key: { id: 'any', fromMe: true } }] }, jid).catch(() => { });
        }
        addLog(inst.sessionId, 'SUCCESS', 'Limpeza de cache e mensagens concluída com sucesso.');
      } catch (e) {
        addLog(inst.sessionId, 'ERROR', `Erro na limpeza: ${e.message}`);
      }
    }
  }
}, 8 * 60 * 60 * 1000);
