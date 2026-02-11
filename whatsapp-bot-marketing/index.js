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

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
app.use(cors());
app.use(express.json());

const PORT = Number(process.env.API_PORT || 3002);
const API_TOKEN = process.env.WHATSAPP_API_TOKEN || 'lucastav8012';
const MARKETING_SITE_URL = 'https://khaki-gull-213146.hostingersite.com';
const AUTH_BASE_PATH = path.resolve('./auth_sessions');

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

async function createInstance(sessionId, phoneForPairing = null) {
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
      browser: ["Ubuntu", "Chrome", "20.0.04"],
      connectTimeoutMs: 60000,
      keepAliveIntervalMs: 30000,
      printQRInTerminal: false
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
    uptimeStart: null
  };

  instances.set(sessionId, instanceData);

  sock.ev.on('creds.update', saveCreds);

  sock.ev.on('connection.update', async (update) => {
    const { connection, lastDisconnect, qr } = update;

    if (qr) {
      instanceData.lastQR = qr;
      addLog(sessionId, 'INFO', 'Novo QR Code gerado');
      updateRemoteStatus(sessionId, 'aguardando_qr');
    }

    if (connection === 'close') {
      const errorCode = lastDisconnect?.error?.output?.statusCode;
      const errorMsg = lastDisconnect?.error?.message;
      const shouldReconnect = errorCode !== DisconnectReason.loggedOut;
      instanceData.isReady = false;
      addLog(sessionId, 'WARN', `Conexão fechada. Code: ${errorCode}, Msg: ${errorMsg}. Reconnect: ${shouldReconnect}`);

      if (shouldReconnect) {
        setTimeout(() => createInstance(sessionId), 5000);
      } else {
        addLog(sessionId, 'ERROR', 'Logout detectado. Removendo sessão.');
        fs.rmSync(sessionPath, { recursive: true, force: true });
        instances.delete(sessionId);
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

  // Se foi passado um número para pareamento
  if (phoneForPairing && !state.creds.registered) {
    let pairingNumber = phoneForPairing.replace(/\D/g, '');
    if (pairingNumber.length <= 11 && !pairingNumber.startsWith('55')) {
      pairingNumber = '55' + pairingNumber;
    }

    setTimeout(async () => {
      try {
        if (sock.ws.readyState === 1) { // 1 = OPEN
          const code = await sock.requestPairingCode(pairingNumber);
          instanceData.pairingCode = code;
          addLog(sessionId, 'SUCCESS', `Código de Pareamento gerado para ${pairingNumber}: ${code}`);
        } else {
          addLog(sessionId, 'WARN', `Socket não está pronto para gerar código (State: ${sock.ws.readyState})`);
        }
      } catch (err) {
        addLog(sessionId, 'ERROR', `Erro ao gerar código de pareamento: ${err.message}`);
      }
    }, 6000);
  }

  return instanceData;
}

// API de atualização no PHP
async function updateRemoteStatus(sessionId, status) {
  try {
    await axios.post(`${MARKETING_SITE_URL}/api_marketing_aluguel.php?action=update_instance_status`, {
      session_id: sessionId,
      status: status
    });
  } catch (e) {
    console.error(`Erro ao atualizar status remoto (${sessionId}): ${e.message}`);
  }
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
      try {
        const res = await axios.get(`${MARKETING_SITE_URL}/api_marketing.php?action=cron_process`);
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
            await new Promise(r => setTimeout(r, 10000)); // Delay entre disparos
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
      const mediaUrl = task.media_url.startsWith('http') ? task.media_url : `${MARKETING_SITE_URL}/${task.media_url}`;
      const type = task.message_type === 'video' ? 'video' : 'image';
      msgContent = { [type]: { url: mediaUrl }, caption: task.message };
    }

    // Simular digitação
    await inst.sock.sendPresenceUpdate('composing', jid);
    await new Promise(r => setTimeout(r, 2000));
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

// Loop Heartbeat (Uptime e Marketing)
setInterval(async () => {
  // Update Uptime no DB
  for (const [sid, inst] of instances.entries()) {
    if (inst.isReady) {
      axios.post(`${MARKETING_SITE_URL}/api_marketing_aluguel.php?action=heartbeat`, { session_id: sid }).catch(() => { });
    }
  }
  // Tentar rodar marketing
  processGlobalMarketing();
}, 60000);

// --- ROTAS API ---

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
