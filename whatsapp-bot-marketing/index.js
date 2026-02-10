/* WhatsApp Bot Marketing - Baseado no Rastreamento (Stable)
 * - Persistência via Arquivos (Fora do diretório de deploy)
 * - API para disparos de marketing
 */
import { default as makeWASocket, useMultiFileAuthState, DisconnectReason, fetchLatestBaileysVersion, Browsers } from '@whiskeysockets/baileys';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import express from 'express';
import cors from 'cors';
import pino from 'pino';
import dotenv from 'dotenv';
import qrcode from 'qrcode-terminal';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Configuração do Express
const app = express();
app.use(cors());
app.use(express.json());

// Porta da API
const PORT = Number(process.env.API_PORT || 3002);

// CONFIGURAÇÃO DE PERSISTÊNCIA (SEGREDO DO SUCESSO)
// Salvar fora da pasta do projeto para sobreviver a deploys na Hostinger
const args = process.argv.slice(2);
const isProduction = process.env.NODE_ENV === 'production' || args.includes('--prod');
let authPath;

if (isProduction) {
  // 2 níveis acima (fora do repo/deploy)
  authPath = path.join(__dirname, '..', '..', '.whatsapp-auth-marketing');
  console.log(`[INIT] Modo PRODUÇÃO. Usando pasta persistente externa: ${authPath}`);
} else {
  // Localmente usa pasta interna
  authPath = path.resolve('./auth_info_baileys');
  console.log(`[INIT] Modo LOCAL. Usando pasta local: ${authPath}`);
}

// Cria diretório se não existir
if (!fs.existsSync(authPath)) {
  fs.mkdirSync(authPath, { recursive: true });
}

// Variáveis Globais
let sock;
let isReady = false;
let lastQR = null;

// Logger silencioso
const logger = pino({ level: 'silent' });

// Função Principal de Conexão
async function connectToWhatsApp() {
  const { state, saveCreds } = await useMultiFileAuthState(authPath);
  const { version } = await fetchLatestBaileysVersion();

  sock = makeWASocket({
    auth: state,
    logger,
    version,
    browser: ["Marketing Bot", "Chrome", "10.0"], // Assinatura fixa
    connectTimeoutMs: 60000,
    keepAliveIntervalMs: 20000,
    printQRInTerminal: true,
    defaultQueryTimeoutMs: 60000,
    emitOwnEvents: false,
    markOnlineOnConnect: true,
    syncFullHistory: false
  });

  sock.ev.on('creds.update', saveCreds);

  sock.ev.on('connection.update', (update) => {
    const { connection, lastDisconnect, qr } = update;

    if (qr) {
      lastQR = qr;
      qrcode.generate(qr, { small: true });
      const publicUrl = process.env.WHATSAPP_API_URL || `http://localhost:${PORT}`;
      console.log(`[MARKETING] QR Code gerado - Acesse ${publicUrl}/qr`);
    }

    if (connection === 'close') {
      const shouldReconnect = (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut;
      console.log(`[MARKETING] Conexão fechada. Reconectando: ${shouldReconnect}`, lastDisconnect?.error?.message);
      isReady = false;

      if (shouldReconnect) {
        setTimeout(connectToWhatsApp, 5000); // Tentar em 5s
      } else {
        console.log('[MARKETING] Desconectado permanentemente (Logout). Apagando credenciais antigas...');
        fs.rmSync(authPath, { recursive: true, force: true });
        connectToWhatsApp();
      }
    } else if (connection === 'open') {
      console.log('[MARKETING] ✅ Conexão estabelecida com sucesso!');
      isReady = true;
      lastQR = null;
    }
  });

  // Lidar com mensagens (Simples Log)
  sock.ev.on('messages.upsert', async ({ messages }) => {
    // Lógica de recebimento aqui se necessário
  });
}

// --- ROTAS DA API ---

app.get('/', (req, res) => {
  res.send(`
      <html><body style="background:#111;color:#eee;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh">
        <div style="text-align:center">
          <h3>🤖 Bot Marketing Iniciado</h3>
          <p>Acesse <a href="/qr" style="color:#4fc3f7">/qr</a> para conectar</p>
          <p>Status: <span id="status">Aguardando...</span></p>
          <script>
            fetch('/status').then(r => r.json()).then(d => {
              document.getElementById('status').innerText = d.status || 'Desconhecido';
            }).catch(() => document.getElementById('status').innerText = 'Erro ao buscar status');
          </script>
        </div>
      </body></html>
    `);
});

app.get('/health', (req, res) => res.status(200).send('OK'));

app.get('/status', (req, res) => {
  res.json({
    status: isReady ? 'CONNECTED' : (lastQR ? 'QR_CODE' : 'CONNECTING'),
    timestamp: Date.now()
  });
});

app.post('/check', async (req, res) => {
  const token = req.headers['x-api-token'];
  if (token !== process.env.API_TOKEN && token !== 'lucastav8012') {
    return res.status(401).json({ error: 'Unauthorized' });
  }

  const { to } = req.body;
  if (!sock || !isReady) return res.status(503).json({ error: 'Bot not connected' });

  try {
    const jid = to.includes('@') ? to : `${to}@s.whatsapp.net`;
    const [result] = await sock.onWhatsApp(jid);
    res.json({ exists: result?.exists || false, jid: result?.jid });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

app.get('/qr', (req, res) => {
  if (isReady) return res.send('<html><body><h1>✅ Já conectado!</h1></body></html>');
  if (!lastQR) return res.send('<html><body><h1>🌀 Carregando QR... (veja logs)</h1><script>setTimeout(()=>location.reload(), 2000)</script></body></html>');

  // Gerar QR code na tela
  const qrImage = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(lastQR)}`;

  res.send(`
        <html>
            <body style="font-family:sans-serif;text-align:center;background:#f0f2f5;padding:50px;">
                <div style="background:white;padding:20px;border-radius:10px;display:inline-block;box-shadow:0 2px 10px rgba(0,0,0,0.1);">
                    <h2>WhatsApp Marketing</h2>
                    <p>Escaneie para conectar</p>
                    <img src="${qrImage}" />
                    <p style="color:#666;font-size:12px">Atualiza automaticamente</p>
                    <script>setTimeout(()=>location.reload(), 5000)</script>
                </div>
            </body>
        </html>
    `);
});

// Formata número brasileiro para WhatsApp
function formatBrazilNumber(raw) {
  let digits = String(raw).replace(/\D+/g, '');
  if (digits.startsWith('0')) digits = digits.slice(1);

  // Se for muito longo (provável LID ou JID), não adicionar 55
  if (digits.length > 13) return digits;

  if (!digits.startsWith('55')) digits = '55' + digits;
  return digits;
}

app.post('/send', async (req, res) => {
  // Validar token
  const token = req.headers['x-api-token'];
  if (token !== process.env.API_TOKEN && token !== 'lucastav8012') {
    return res.status(401).json({ error: 'Unauthorized' });
  }

  const { to, text } = req.body;
  if (!sock || !isReady) return res.status(503).json({ error: 'Bot not connected' });

  try {
    let jid = to;
    if (!jid.includes('@')) {
      jid = formatBrazilNumber(jid) + '@s.whatsapp.net';
    }

    await sock.sendMessage(jid, { text });
    console.log(`[SEND] Mensagem enviada para ${jid}`);
    res.json({ success: true });
  } catch (error) {
    console.error('Erro envio:', error);
    res.status(500).json({ error: error.message });
  }
});

app.post('/send-media', async (req, res) => {
  const token = req.headers['x-api-token'];
  if (token !== process.env.API_TOKEN && token !== 'lucastav8012') {
    return res.status(401).json({ error: 'Unauthorized' });
  }

  const { to, mediaUrl, caption, type } = req.body; // type: 'image' | 'video' | 'audio'
  if (!sock || !isReady) return res.status(503).json({ error: 'Bot not connected' });

  try {
    let jid = to;
    if (!jid.includes('@')) {
      jid = formatBrazilNumber(jid) + '@s.whatsapp.net';
    }

    const message = {};

    if (type === 'image') message.image = { url: mediaUrl };
    else if (type === 'video') message.video = { url: mediaUrl };
    else if (type === 'audio') message.audio = { url: mediaUrl, mimetype: 'audio/mp4', ptt: true };
    else message.document = { url: mediaUrl };

    if (caption) message.caption = caption;

    await sock.sendMessage(jid, message);
    console.log(`[SEND-MEDIA] Mídia (${type}) enviada para ${jid}`);
    res.json({ success: true });
  } catch (error) {
    console.error('Erro envio mídia:', error);
    res.status(500).json({ error: error.message });
  }
});

// Array circular de logs para exibir na interface
const memoryLogs = [];
const MAX_LOGS = 200;

function addLog(level, message) {
  const logEntry = {
    level,
    message,
    timestamp: Date.now()
  };
  memoryLogs.unshift(logEntry);
  if (memoryLogs.length > MAX_LOGS) memoryLogs.pop();

  // Também imprimir no console para debug da Hostinger
  console.log(`[${level}] ${message}`);
}

// Sobrescrever console.log para capturar logs importantes
const originalConsoleLog = console.log;
console.log = function (...args) {
  const msg = args.map(a => typeof a === 'object' ? JSON.stringify(a) : String(a)).join(' ');
  // Filtrar logs irrelevantes de debug
  if (!msg.includes('rate-limit') && !msg.includes('Closing session')) {
    addLog('INFO', msg);
  }
  originalConsoleLog.apply(console, args);
};

const originalConsoleError = console.error;
console.error = function (...args) {
  const msg = args.map(a => typeof a === 'object' ? JSON.stringify(a) : String(a)).join(' ');
  addLog('ERROR', msg);
  originalConsoleError.apply(console, args);
};

app.get('/logs', (req, res) => {
  const level = req.query.level;
  const limit = Number(req.query.limit) || 100;

  let filtered = memoryLogs;
  if (level) {
    filtered = filtered.filter(l => l.level === level);
  }

  res.json({
    success: true,
    logs: filtered.slice(0, limit),
    count: filtered.length
  });
});

// Iniciar
app.listen(PORT, () => {
  addLog('SUCCESS', `Servidor API rodando na porta ${PORT}`);
  connectToWhatsApp();
});
