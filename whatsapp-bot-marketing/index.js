/* WhatsApp Bot Marketing - Baseado no Rastreamento (Stable)
 * - Persistência via Arquivos (Fora do diretório de deploy)
 * - API para disparos de marketing
 * - Sistema Anti-Ban e Reconexão Robusta
 */
import { default as makeWASocket, useMultiFileAuthState, DisconnectReason, fetchLatestBaileysVersion, Browsers, proto } from '@whiskeysockets/baileys';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import express from 'express';
import cors from 'cors';
import pino from 'pino';
import dotenv from 'dotenv';
import qrcode from 'qrcode-terminal';
import axios from 'axios';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Configuração do Express
const app = express();
app.use(cors());
app.use(express.json());

// Porta da API
const PORT = Number(process.env.API_PORT || 3002);
const API_TOKEN = process.env.WHATSAPP_API_TOKEN || 'lucastav8012';
const MARKETING_SITE_URL = 'https://khaki-gull-213146.hostingersite.com';

// CONFIGURAÇÃO DE PERSISTÊNCIA
const args = process.argv.slice(2);
const isProduction = process.env.NODE_ENV === 'production' || args.includes('--prod');
let authPath;

if (isProduction) {
  authPath = path.join(__dirname, '..', '..', '.whatsapp-auth-marketing');
} else {
  authPath = path.resolve('./auth_info_baileys');
}

if (!fs.existsSync(authPath)) {
  fs.mkdirSync(authPath, { recursive: true });
}

// Variáveis Globais
let sock;
let isReady = false;
let lastQR = null;
const memoryLogs = [];
const MAX_LOGS = 200;

// Configuração de Segurança (Anti-Ban)
const SAFETY_ENABLED = true;
const MIN_DELAY_BETWEEN_MESSAGES = 2000; // 2 segundos
const SIMULATE_TYPING = true;

// Funções de Log
const originalConsoleLog = console.log;
const originalConsoleError = console.error;

function addLog(level, message) {
  const logEntry = {
    level,
    message: typeof message === 'object' ? JSON.stringify(message) : String(message),
    timestamp: Date.now()
  };
  memoryLogs.unshift(logEntry);
  if (memoryLogs.length > MAX_LOGS) memoryLogs.pop();
  originalConsoleLog(`[${level}] ${logEntry.message}`);
}

console.log = function (...args) {
  const msg = args.map(a => typeof a === 'object' ? JSON.stringify(a) : String(a)).join(' ');
  if (!msg.includes('rate-limit') && !msg.startsWith('[')) {
    addLog('INFO', msg);
  } else {
    originalConsoleLog.apply(console, args);
  }
};

console.error = function (...args) {
  const msg = args.map(a => typeof a === 'object' ? JSON.stringify(a) : String(a)).join(' ');
  addLog('ERROR', msg);
  originalConsoleError.apply(console, args);
};

// Helpers de JID
function formatBrazilNumber(raw) {
  let digits = String(raw).replace(/\D+/g, '');
  if (digits.startsWith('0')) digits = digits.slice(1);
  if (digits.length > 13) return digits; // LID ou JID
  if (!digits.startsWith('55')) digits = '55' + digits;
  return digits;
}

function isGroupJid(jid) {
  return jid.includes('@g.us') || jid.includes('@newsletter');
}

// Função de Envio Seguro
async function safeSendMessage(sock, jid, message, options = {}) {
  try {
    if (!sock || !isReady) return null;

    if (SIMULATE_TYPING) {
      await sock.sendPresenceUpdate('composing', jid);
      const text = message.text || message.caption || '';
      const typingTime = Math.min(Math.max(text.length * 50, 2000), 5000);
      await new Promise(r => setTimeout(r, typingTime));
      await sock.sendPresenceUpdate('paused', jid);
    }

    await new Promise(r => setTimeout(r, 1000));
    return await sock.sendMessage(jid, message, options);
  } catch (e) {
    addLog('ERROR', `Falha no envio seguro: ${e.message}`);
    return null;
  }
}

// Função Principal de Conexão
async function connectToWhatsApp() {
  try {
    addLog('INFO', `Iniciando conexão Baileys... (Auth: ${authPath})`);

    addLog('INFO', 'Carregando estado de autenticação...');
    const { state, saveCreds } = await useMultiFileAuthState(authPath);

    addLog('INFO', 'Buscando versão do Baileys...');
    const { version } = await fetchLatestBaileysVersion().catch(e => ({ version: [0, 4, 0] }));
    addLog('INFO', `Versão Baileys: ${version}`);

    addLog('INFO', 'Criando socket...');
    sock = makeWASocket({
      auth: state,
      logger: pino({ level: 'silent' }),
      version,
      browser: Browsers.macOS('Desktop'), // Mais estável
      printQRInTerminal: false,
      markOnlineOnConnect: true,
      syncFullHistory: false,
      connectTimeoutMs: 60000,
      defaultQueryTimeoutMs: 0,
      keepAliveIntervalMs: 10000
    });

    sock.ev.on('creds.update', async () => {
      addLog('INFO', 'Credenciais atualizadas');
      await saveCreds();
    });

    sock.ev.on('connection.update', (update) => {
      const { connection, lastDisconnect, qr } = update;

      if (qr) {
        lastQR = qr;
        addLog('INFO', 'Novo QR Code gerado');
      }

      if (connection === 'close') {
        const errorCode = (lastDisconnect?.error)?.output?.statusCode;
        const shouldReconnect = errorCode !== DisconnectReason.loggedOut;
        addLog('WARN', `Conexão fechada (${errorCode}). Reconectando: ${shouldReconnect}`);
        isReady = false;
        if (shouldReconnect) {
          setTimeout(connectToWhatsApp, 5000);
        } else {
          addLog('ERROR', 'Logged out. Resetando sessão...');
          if (fs.existsSync(authPath)) {
            fs.rmSync(authPath, { recursive: true, force: true });
          }
          connectToWhatsApp();
        }
      } else if (connection === 'open') {
        addLog('SUCCESS', 'WhatsApp Conectado!');
        isReady = true;
        lastQR = null;
        startMarketingLoop();
      }
    });

  } catch (e) {
    addLog('ERROR', `Erro fatal na conexão: ${e.message}`);
    setTimeout(connectToWhatsApp, 10000);
  }
}

// ===== MARKETING LOOP =====
let marketingTimer = null;
let isProcessingMarketing = false;

async function processMarketingTasks() {
  if (isProcessingMarketing || !isReady || !sock) return;
  isProcessingMarketing = true;

  try {
    addLog('INFO', 'Consultando tarefas pendentes no funil...');
    const response = await axios.get(`${MARKETING_SITE_URL}/api_marketing.php?action=cron_process`);
    const data = response.data;

    if (data && data.success) {
      if (data.tasks && data.tasks.length > 0) {
        addLog('INFO', `Encontradas ${data.tasks.length} tarefas. Iniciando disparos...`);
        for (const task of data.tasks) {
          const result = await sendMarketingMessage(task);
          await axios.post(`${MARKETING_SITE_URL}/api_marketing.php?action=update_task`, {
            member_id: task.member_id,
            step_order: task.step_order,
            success: result.success,
            reason: result.reason
          });
          // Delay entre envios (10-30s)
          const delay = Math.floor(Math.random() * 20000) + 10000;
          await new Promise(r => setTimeout(r, delay));
        }
      } else {
        addLog('INFO', 'Nenhuma mensagem pendente no momento.');
      }
    } else {
      addLog('WARN', `Erro na resposta da API: ${data?.message || 'Erro desconhecido'}`);
    }
  } catch (e) {
    addLog('ERROR', `Falha ao conectar com o site para buscar tarefas: ${e.message}`);
  } finally {
    isProcessingMarketing = false;
  }
}

function startMarketingLoop() {
  if (marketingTimer) clearInterval(marketingTimer);
  addLog('INFO', 'Iniciando Loop de Marketing (60s)');
  marketingTimer = setInterval(processMarketingTasks, 60000);
}

async function sendMarketingMessage(task) {
  try {
    const rawPhone = task.phone;
    let jid = rawPhone;

    if (!jid.includes('@')) {
      const clean = formatBrazilNumber(rawPhone);
      jid = clean.length >= 15 ? clean + '@lid' : clean + '@s.whatsapp.net';
    }

    let msgContent = { text: task.message };

    if (task.media_url) {
      const mediaUrl = task.media_url.startsWith('http') ? task.media_url : `${MARKETING_SITE_URL}/${task.media_url}`;
      if (task.message_type === 'image') {
        msgContent = { image: { url: mediaUrl }, caption: task.message };
      } else if (task.message_type === 'video') {
        msgContent = { video: { url: mediaUrl }, caption: task.message };
      }
    }

    const sent = await safeSendMessage(sock, jid, msgContent);

    if (sent) {
      addLog('SUCCESS', `Mensagem enviada para ${jid}`);
      return { success: true };
    } else {
      return { success: false, reason: 'fail_send' };
    }
  } catch (e) {
    addLog('ERROR', `Erro task marketing: ${e.message}`);
    return { success: false, reason: e.message };
  }
}

// --- ROTAS DA API ---

app.get('/', (req, res) => {
  res.send(`<html><body style="background:#111;color:#eee;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh">
    <div style="text-align:center">
      <h3>🤖 Bot Marketing Hub</h3>
      <p>Status: <b>${isReady ? 'CONECTADO' : 'AGUARDANDO'}</b></p>
      <a href="/qr" style="color:#4fc3f7">Ver QR Code</a>
    </div>
  </body></html>`);
});

app.get('/status', (req, res) => {
  res.json({ status: isReady ? 'CONNECTED' : (lastQR ? 'QR_CODE' : 'CONNECTING'), timestamp: Date.now() });
});

app.get('/trigger', async (req, res) => {
  res.json({ success: true, message: 'Disparo manual iniciado' });
  // Executa o processamento imediatamente sem esperar o timer do setInterval
  processMarketingTasks();
});

app.get('/ping', async (req, res) => {
  try {
    const start = Date.now();
    const response = await axios.get(`${MARKETING_SITE_URL}/api_marketing.php?action=cron_process`);
    const duration = Date.now() - start;
    res.json({ success: true, duration, data: response.data });
  } catch (e) {
    res.status(500).json({ success: false, error: e.message });
  }
});

app.get('/logs', (req, res) => {
  const token = req.query.token || req.headers['x-api-token'];
  if (token !== API_TOKEN) return res.status(401).json({ success: false });
  res.json({ success: true, logs: memoryLogs.slice(0, 100), count: memoryLogs.length });
});

app.get('/qr', (req, res) => {
  if (isReady) return res.send('<h1>✅ Já conectado!</h1>');
  if (!lastQR) return res.send('<h1>🌀 Carregando QR...</h1><script>setTimeout(()=>location.reload(), 2000)</script>');
  const qrImage = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(lastQR)}`;
  res.send(`<body style="text-align:center;font-family:sans-serif;padding:50px;background:#f0f2f5;">
    <div style="background:white;padding:20px;border-radius:10px;display:inline-block;box-shadow:0 2px 10px rgba(0,0,0,0.1);">
      <h2>Conectar Bot Marketing</h2>
      <img src="${qrImage}" /><br>
      <script>setTimeout(()=>location.reload(), 5000)</script>
    </div>
  </body>`);
});

// Sincronizar membros de grupos
app.post('/sync-members', async (req, res) => {
  const token = req.headers['x-api-token'];
  if (token !== API_TOKEN) return res.status(401).json({ success: false });

  res.json({ success: true, message: 'Sincronização iniciada' });

  (async () => {
    try {
      if (!isReady || !sock) return;
      addLog('INFO', 'Buscando grupos para sincronização...');
      const groups = await sock.groupFetchAllParticipating();
      const groupJids = Object.keys(groups);

      for (const jid of groupJids) {
        const metadata = await sock.groupMetadata(jid);
        const participants = metadata.participants.map(p => p.id);

        await axios.post(`${MARKETING_SITE_URL}/api_marketing.php?action=save_members`, {
          group_jid: jid,
          members: participants
        });
        addLog('INFO', `Sincronizados ${participants.length} membros de: ${metadata.subject}`);
        await new Promise(r => setTimeout(r, 2000));
      }
      addLog('SUCCESS', 'Sincronização de grupos concluída!');
    } catch (e) {
      addLog('ERROR', `Falha sync membros: ${e.message}`);
    }
  })();
});

app.post('/send', async (req, res) => {
  if (req.headers['x-api-token'] !== API_TOKEN) return res.status(401).send();
  const { to, text } = req.body;
  let jid = to;
  if (!jid.includes('@')) jid = formatBrazilNumber(jid) + '@s.whatsapp.net';
  const sent = await safeSendMessage(sock, jid, { text });
  res.json({ success: !!sent });
});

// Iniciar Servidor
app.listen(PORT, () => {
  addLog('SUCCESS', `Bot Marketing rodando na porta ${PORT}`);
  connectToWhatsApp();
});
