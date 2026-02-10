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
  addLog('INFO', 'Iniciando conexão Baileys...');
  const { state, saveCreds } = await useMultiFileAuthState(authPath);
  const { version } = await fetchLatestBaileysVersion();

  sock = makeWASocket({
    auth: state,
    logger: pino({ level: 'silent' }),
    version,
    browser: ["Marketing Bot", "Chrome", "10.0"],
    printQRInTerminal: true,
    markOnlineOnConnect: true,
    syncFullHistory: false
  });

  sock.ev.on('creds.update', saveCreds);

  sock.ev.on('connection.update', (update) => {
    const { connection, lastDisconnect, qr } = update;

    if (qr) {
      lastQR = qr;
      addLog('INFO', 'Novo QR Code gerado');
    }

    if (connection === 'close') {
      const shouldReconnect = (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut;
      addLog('WARN', `Conexão fechada. Reconectando: ${shouldReconnect}`);
      isReady = false;
      if (shouldReconnect) {
        setTimeout(connectToWhatsApp, 5000);
      } else {
        addLog('ERROR', 'Logged out. Resetando sessão...');
        fs.rmSync(authPath, { recursive: true, force: true });
        connectToWhatsApp();
      }
    } else if (connection === 'open') {
      addLog('SUCCESS', 'WhatsApp Conectado!');
      isReady = true;
      lastQR = null;
      startMarketingLoop();
    }
  });
}

// ===== MARKETING LOOP =====
let marketingTimer = null;
let isProcessingMarketing = false;

function startMarketingLoop() {
  if (marketingTimer) clearInterval(marketingTimer);
  addLog('INFO', 'Iniciando Loop de Marketing (60s)');

  marketingTimer = setInterval(async () => {
    if (isProcessingMarketing || !isReady || !sock) return;
    isProcessingMarketing = true;

    try {
      const response = await axios.get(`${MARKETING_SITE_URL}/api_marketing.php?action=cron_process`);
      const data = response.data;

      if (data && data.success && data.tasks && data.tasks.length > 0) {
        addLog('INFO', `Processando ${data.tasks.length} envios...`);

        for (const task of data.tasks) {
          const result = await sendMarketingMessage(task);
          await axios.post(`${MARKETING_SITE_URL}/api_marketing.php?action=update_task`, {
            member_id: task.member_id,
            step_order: task.step_order,
            success: result.success,
            reason: result.reason
          });
          // Delay entre envios do loop
          await new Promise(r => setTimeout(r, Math.floor(Math.random() * 30000) + 15000));
        }
      }
    } catch (e) {
      // Ignorar erros silenciosos de rede
    } finally {
      isProcessingMarketing = false;
    }
  }, 60000);
}

async function sendMarketingMessage(task) {
  try {
    const rawPhone = task.phone;
    let jid = rawPhone;

    if (!jid.includes('@')) {
      const clean = formatBrazilNumber(rawPhone);
      jid = clean.length >= 15 ? clean + '@lid' : clean + '@s.whatsapp.net';
    }

    const msgContent = { text: task.message };
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
