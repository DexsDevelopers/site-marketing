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
  const { version: latestVersion, isLatest } = await fetchLatestBaileysVersion();
  addLog(sessionId, 'INFO', `Usando Baileys v${latestVersion} (Latest: ${isLatest})`);

  const sessionPath = path.join(AUTH_BASE_PATH, sessionId);
  if (!fs.existsSync(sessionPath)) fs.mkdirSync(sessionPath, { recursive: true });

  const { state, saveCreds } = await useMultiFileAuthState(sessionPath);

  let sock;
  try {
    sock = makeWASocket({
      auth: state,
      logger: pino({ level: 'silent' }),
      version: latestVersion,
      browser: Browsers.macOS('Desktop'),
      connectTimeoutMs: 60000,
      keepAliveIntervalMs: 30000,
      printQRInTerminal: false,
      generateHighQualityLinkPreview: false,
      markOnlineOnConnect: false,
      syncFullHistory: false
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
    uptimeStart: null
  };

  instances.set(sessionId, instanceData);
  instanceLocks.delete(sessionId);

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
      } else if (errorCode === 440) {
        // 440 = Conflict - Outra instância tentando conectar com a mesma credencial ao mesmo tempo (comum em setups PM2/Cluster ou resets rapidos)
        addLog(sessionId, 'WARN', `Conflito de Sessão (440). Tentando limpar socket para reconectar seguro...`);
        try { sock.ev.removeAllListeners(); sock.ws.close(); } catch (e) { }
        instances.delete(sessionId);
        setTimeout(() => createInstance(sessionId), 10000); // Demora um pouco mais pra dar tempo do WhatsApp liberar o login
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
    // 1. Pegar instâncias prontas
    const activeInstances = Array.from(instances.values()).filter(i => i.isReady);
    if (activeInstances.length === 0) {
      isProcessingMarketing = false;
      return;
    }

    // 2. Buscar tarefas para processar
    // Vamos processar em lotes para cada instância
    for (const inst of activeInstances) {
      // Proteção contra Ban de Chip Novo: Só disparar se estiver conectado há pelo menos 3 minutos
      if (Date.now() - inst.uptimeStart < 3 * 60 * 1000) {
        addLog(inst.sessionId, 'INFO', `Chip em maturação inicial (Uptime < 3 min). Aguardando estabilizar conexão antes de disparar.`);
        continue;
      }

      try {
        const res = await axios.get(`${MARKETING_SITE_URL}/api_marketing.php?action=cron_process`, { timeout: 15000 });
        if (res.data?.success && res.data.tasks?.length > 0) {

          addLog(inst.sessionId, 'INFO', `Processando ${res.data.tasks.length} tarefas nesta instância.`);
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
  } finally {
    isProcessingMarketing = false;
  }
}

async function sendWithInstance(inst, task) {
  try {
    const jid = formatJid(task.phone);
    let msgContent = { text: task.message };

    if (task.media_url) {
      const baseURL = task.media_url.startsWith('http') ? '' : `${MARKETING_SITE_URL}/`;
      const fullPath = baseURL + task.media_url;
      const mediaUrl = encodeURI(fullPath);
      const type = task.message_type === 'video' ? 'video' : 'image';
      msgContent = { [type]: { url: mediaUrl }, caption: task.message };
    }

    // Simular digitação realista (Anti-Ban)
    const typingDuration = Math.floor(Math.random() * (4000 - 1500 + 1)) + 1500;
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
    return { success: false, reason: 'error_sending' };
  } catch (e) {
    return { success: false, reason: e.message };
  }
}

// Loop de processamento de fila do admin
setInterval(async () => {
  // Tentar rodar marketing
  processGlobalMarketing();
}, 60000);

// --- SISTEMA DE AQUECIMENTO DE NÚMEROS (MATURAÇÃO) ---
async function processGlobalWarming() {
  const activeInstances = Array.from(instances.values()).filter(i => i.isReady && i.sock && i.sock.user);
  if (activeInstances.length < 2) return; // Precisa de 2 números para interagir

  addLog('SYSTEM', 'INFO', '[AQUECIMENTO] Iniciando ciclo de maturação entre chips conectados...');
  // Embaralhar as instâncias para criar pares aleatórios
  const shuffled = activeInstances.sort(() => 0.5 - Math.random());

  for (let i = 0; i < Math.floor(shuffled.length / 2) * 2; i += 2) {
    const instA = shuffled[i];
    const instB = shuffled[i + 1];

    if (!instA.sock.user || !instB.sock.user) continue;

    try {
      const jidB = instB.sock.user.id.split(':')[0] + '@s.whatsapp.net';
      const randomMsg = nlpEngine.generateOpener();

      const typingDuration = Math.floor(Math.random() * (6000 - 2000 + 1)) + 2000;
      await instA.sock.sendPresenceUpdate('composing', jidB);
      await new Promise(r => setTimeout(r, typingDuration));
      await instA.sock.sendPresenceUpdate('paused', jidB);

      await instA.sock.sendMessage(jidB, { text: randomMsg });
      addLog(instA.sessionId, 'INFO', `[AQUECIMENTO] Enviando interação para ${jidB}`);

    } catch (e) {
      addLog(instA.sessionId, 'ERROR', `[AQUECIMENTO] Erro na interação: ${e.message}`);
    }

    // Pequeno delay entre interações
    await new Promise(r => setTimeout(r, Math.floor(Math.random() * 5000) + 2000));
  }
}

function scheduleNextWarming() {
  // Executa o próximo loop de aquecimento aleatoriamente entre 10 e 25 minutos
  const delayMinutes = Math.floor(Math.random() * (25 - 10 + 1) + 10);
  setTimeout(() => {
    processGlobalWarming();
    scheduleNextWarming();
  }, delayMinutes * 60 * 1000);
}
// Iniciar rotina de aquecimento global
scheduleNextWarming();

// --- ROTAS API ---

app.get('/trigger', (req, res) => {
  addLog('SYSTEM', 'INFO', 'Trigger manual acionado pelo painel administrador.');
  processGlobalMarketing();
  res.json({ success: true, message: 'Processamento iniciado.' });
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
