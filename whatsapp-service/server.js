import express from 'express';
import makeWASocket, {
    DisconnectReason,
    useMultiFileAuthState,
    fetchLatestBaileysVersion
} from '@whiskeysockets/baileys';
import { Boom } from '@hapi/boom';
import qrcodeTerminal from 'qrcode-terminal';
import QRCode from 'qrcode';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

const PORT = process.env.PORT || 3001;
const AUTH_DIR = path.join(__dirname, 'auth_info_baileys');

let sock = null;
let currentQR = null;
let isConnected = false;
let userJid = null;

async function startWhatsAppSocket() {
    try {
        const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR);
        const { version } = await fetchLatestBaileysVersion();

        console.log(`[Baileys] Starting WhatsApp connection using Baileys v${version.join('.')}...`);

        sock = makeWASocket({
            version,
            auth: state,
            printQRInTerminal: false,
            browser: ['Centralized IT Helpdesk', 'Chrome', '1.0.0'],
            syncFullHistory: false,
        });

        sock.ev.on('creds.update', saveCreds);

        sock.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;

            if (qr) {
                currentQR = qr;
                isConnected = false;
                console.log('\n======================================================');
                console.log('SCAN THIS QR CODE WITH YOUR WHATSAPP TO LINK BOT:');
                console.log('Or visit: http://127.0.0.1:' + PORT + '/qr in your browser');
                console.log('======================================================\n');
                qrcodeTerminal.generate(qr, { small: true });
            }

            if (connection === 'close') {
                isConnected = false;
                userJid = null;
                const statusCode = (lastDisconnect?.error instanceof Boom)
                    ? lastDisconnect.error.output?.statusCode
                    : null;

                const shouldReconnect = statusCode !== DisconnectReason.loggedOut;
                console.log(`[Baileys] Connection closed (code: ${statusCode}). Reconnecting: ${shouldReconnect}`);

                if (shouldReconnect) {
                    setTimeout(() => startWhatsAppSocket(), 3000);
                } else {
                    console.log('[Baileys] Device was logged out. Please restart and scan a new QR code.');
                }
            } else if (connection === 'open') {
                isConnected = true;
                currentQR = null;
                userJid = sock.user?.id || 'Connected';
                console.log(`\n✅ [Baileys] WhatsApp connected successfully as: ${userJid}`);
                console.log(`[Baileys] Helpdesk notification gateway ready on port ${PORT}\n`);
            }
        });
    } catch (err) {
        console.error('[Baileys] Failed to start socket:', err);
        setTimeout(() => startWhatsAppSocket(), 5000);
    }
}

// Endpoint 1: Health / Status
app.get('/status', (req, res) => {
    return res.json({
        success: true,
        service: 'Centralized IT Helpdesk WhatsApp Gateway',
        connected: isConnected,
        user: userJid,
        has_qr_pending: !!currentQR,
    });
});

// Endpoint 2: Web QR View
app.get('/qr', async (req, res) => {
    if (isConnected) {
        return res.send(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>WhatsApp Gateway - Connected</title>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
                <style>body { background: #f4f6f9; display: flex; align-items: center; justify-content: center; height: 100vh; font-family: sans-serif; }</style>
            </head>
            <body>
                <div class="card shadow-sm p-4 text-center" style="max-width: 450px;">
                    <div class="mb-3 text-success">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-check-circle-fill" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                        </svg>
                    </div>
                    <h4 class="fw-bold text-success mb-2">WhatsApp Connected!</h4>
                    <p class="text-secondary small mb-3">Gateway is active and linked as: <strong>${userJid}</strong></p>
                    <div class="p-2 bg-light rounded text-muted small">Notifications from IT Helpdesk will be automatically dispatched.</div>
                </div>
            </body>
            </html>
        `);
    }

    if (!currentQR) {
        return res.send(`
            <!DOCTYPE html>
            <html>
            <head>
                <meta http-equiv="refresh" content="3">
                <title>WhatsApp Gateway - Initializing</title>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
                <style>body { background: #f4f6f9; display: flex; align-items: center; justify-content: center; height: 100vh; font-family: sans-serif; }</style>
            </head>
            <body>
                <div class="card shadow-sm p-4 text-center" style="max-width: 450px;">
                    <div class="spinner-border text-primary mx-auto mb-3" role="status"></div>
                    <h5 class="fw-bold mb-1">Generating QR Code...</h5>
                    <p class="text-muted small mb-0">Connecting to WhatsApp Web servers. Page refreshes automatically.</p>
                </div>
            </body>
            </html>
        `);
    }

    const qrDataUrl = await QRCode.toDataURL(currentQR, { width: 320, margin: 2 });

    return res.send(`
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="refresh" content="15">
            <title>Scan WhatsApp QR Code - IT Helpdesk</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
            <style>
                body { background: #f4f6f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: sans-serif; padding: 20px; }
                .qr-card { max-width: 460px; border-radius: 12px; }
            </style>
        </head>
        <body>
            <div class="card shadow-sm qr-card p-4 text-center">
                <h4 class="fw-bold text-dark mb-1">Scan WhatsApp QR Code</h4>
                <div class="text-muted small mb-3">Tautkan akun WhatsApp resmi Helpdesk / EDP</div>
                
                <div class="p-3 bg-white border rounded d-inline-block mx-auto mb-3">
                    <img src="${qrDataUrl}" alt="WhatsApp QR Code" class="img-fluid" style="width: 280px; height: 280px;">
                </div>

                <ol class="text-start small text-secondary ps-3 mb-3">
                    <li>Buka aplikasi <strong>WhatsApp</strong> di HP Anda</li>
                    <li>Ketuk <strong>Menu (titik tiga)</strong> atau <strong>Pengaturan (Settings)</strong></li>
                    <li>Pilih <strong>Perangkat Tertaut (Linked Devices)</strong> &rarr; <strong>Tautkan Perangkat</strong></li>
                    <li>Arahkan kamera HP ke QR Code di atas</li>
                </ol>

                <div class="text-muted" style="font-size: 0.75rem;">
                    Halaman ini akan auto-refresh setiap 15 detik.
                </div>
            </div>
        </body>
        </html>
    `);
});

// Endpoint 3: Send Message API
app.post('/send-message', async (req, res) => {
    try {
        const { phone, message } = req.body;

        if (!phone || !message) {
            return res.status(400).json({
                success: false,
                error: 'Missing required parameters: "phone" and "message".',
            });
        }

        if (!isConnected || !sock) {
            console.warn(`[Baileys] Cannot send message to ${phone}: WhatsApp socket is not connected.`);
            return res.status(503).json({
                success: false,
                error: 'WhatsApp socket is not connected yet. Please scan the QR code first.',
            });
        }

        // Clean digits and build JID: e.g. 628123456789@s.whatsapp.net
        let cleanDigits = phone.toString().replace(/\D/g, '');
        if (cleanDigits.startsWith('0')) {
            cleanDigits = '62' + cleanDigits.substring(1);
        }

        const jid = `${cleanDigits}@s.whatsapp.net`;

        console.log(`[Baileys] Sending WhatsApp notification to: ${jid}`);
        const result = await sock.sendMessage(jid, { text: message });

        return res.json({
            success: true,
            message: 'WhatsApp notification sent successfully.',
            recipient: jid,
            messageId: result?.key?.id,
        });
    } catch (err) {
        console.error('[Baileys] Error sending WhatsApp message:', err);
        return res.status(500).json({
            success: false,
            error: err.message || 'Failed to send WhatsApp message via Baileys socket.',
        });
    }
});

app.listen(PORT, async () => {
    console.log(`======================================================`);
    console.log(` Centralized IT Helpdesk WhatsApp Gateway`);
    console.log(` Listening on: http://127.0.0.1:${PORT}`);
    console.log(` Web QR View:  http://127.0.0.1:${PORT}/qr`);
    console.log(` Status:       http://127.0.0.1:${PORT}/status`);
    console.log(`======================================================`);
    await startWhatsAppSocket();
});
