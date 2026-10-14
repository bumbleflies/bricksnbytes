// Minimal mailer for the website forms (JSON) -> SMTP mail to MAIL_TO:
//   POST /api/contact     contact form (optionally with newsletter sign-up)
//   POST /api/newsletter  newsletter sign-up (entered in the backoffice by hand)
// Runs next to nginx in the same container; nginx proxies /api/contact and /api/newsletter here.
import http from 'node:http';
import nodemailer from 'nodemailer';
import { validateContact, validateNewsletter } from './validate.mjs';

const PORT = Number(process.env.MAILER_PORT || 3001);
const HOST = process.env.MAILER_HOST || '127.0.0.1';
const MAIL_TO = process.env.MAIL_TO || 'info@bricksnbytes.de';
const MAIL_FROM = process.env.MAIL_FROM || process.env.SMTP_USER;
const DRY_RUN = process.env.MAILER_DRY_RUN === '1';
const MAX_BODY = 32 * 1024;

// Rate limit: per client and in total, to keep a bot from flooding the inbox
const PER_IP_LIMIT = 5;
const GLOBAL_LIMIT = 100;
const WINDOW_MS = 60 * 60 * 1000;
const hits = new Map();
let globalHits = [];

function rateLimited(ip) {
  const now = Date.now();
  globalHits = globalHits.filter((t) => now - t < WINDOW_MS);
  const list = (hits.get(ip) || []).filter((t) => now - t < WINDOW_MS);
  if (list.length >= PER_IP_LIMIT || globalHits.length >= GLOBAL_LIMIT) {
    hits.set(ip, list);
    return true;
  }
  list.push(now);
  hits.set(ip, list);
  globalHits.push(now);
  return false;
}

function createTransport() {
  if (DRY_RUN) return nodemailer.createTransport({ jsonTransport: true });
  if (!process.env.SMTP_HOST || !MAIL_FROM) return null;
  const port = Number(process.env.SMTP_PORT || 465);
  return nodemailer.createTransport({
    host: process.env.SMTP_HOST,
    port,
    secure: process.env.SMTP_SECURE ? process.env.SMTP_SECURE === 'true' : port === 465,
    auth: process.env.SMTP_USER ? { user: process.env.SMTP_USER, pass: process.env.SMTP_PASS } : undefined,
  });
}

const transport = createTransport();
if (!transport) console.warn('[mailer] SMTP_HOST/MAIL_FROM not set – the forms will answer 503');

function send(res, status, payload) {
  res.writeHead(status, { 'Content-Type': 'application/json; charset=utf-8', 'Cache-Control': 'no-store' });
  res.end(JSON.stringify(payload));
}

function clientIp(req) {
  const fwd = req.headers['x-forwarded-for'];
  if (typeof fwd === 'string' && fwd) return fwd.split(',')[0].trim();
  return req.socket.remoteAddress || 'unknown';
}

function readJson(req) {
  return new Promise((resolve, reject) => {
    let size = 0;
    const chunks = [];
    req.on('data', (c) => {
      size += c.length;
      if (size > MAX_BODY) {
        reject(Object.assign(new Error('too large'), { status: 413 }));
        req.destroy();
        return;
      }
      chunks.push(c);
    });
    req.on('end', () => {
      try {
        resolve(JSON.parse(Buffer.concat(chunks).toString('utf8') || '{}'));
      } catch {
        reject(Object.assign(new Error('bad json'), { status: 400 }));
      }
    });
    req.on('error', reject);
  });
}

// "14.10.2026, 17:49 Uhr" – built from parts because the Alpine Node has no German locale data
function berlinTime(date) {
  const parts = Object.fromEntries(
    new Intl.DateTimeFormat('en-US', {
      timeZone: 'Europe/Berlin', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
    }).formatToParts(date).map((p) => [p.type, p.value]),
  );
  return `${parts.day}.${parts.month}.${parts.year}, ${parts.hour}:${parts.minute} Uhr`;
}

// The newsletter sign-up mail, from the newsletter form or ticked in the contact form
function newsletterMail({ vorname, nachname, email, telefon }, via) {
  const name = [vorname, nachname].filter(Boolean).join(' ');
  return {
    replyTo: { name, address: email },
    subject: `Newsletter-Anmeldung: ${name}`,
    text: [
      `Neue Anmeldung zum Newsletter über die Website (${via}).`,
      '',
      `Vorname: ${vorname}`,
      `Nachname: ${nachname || '–'}`,
      `E-Mail: ${email}`,
      `Telefon: ${telefon || '–'}`,
      `Angemeldet am: ${berlinTime(new Date())}`,
      '',
      `Einwilligung: Checkbox zur Newsletter-Einwilligung im ${via} angehakt.`,
      'Bitte im Backoffice eintragen (Kunde anlegen bzw. suchen, Newsletter: Ja) und diese Mail als Nachweis aufheben.',
      '',
    ].join('\n'),
  };
}

// Per form: validation, the mail(s) to us and the wording of the error messages
const FORMS = {
  '/api/contact': {
    validate: validateContact,
    // Ticked "auch den Newsletter erhalten": the same sign-up mail as the newsletter form
    extra: (data) => (data.newsletter ? newsletterMail(data.newsletter, 'Kontaktformular') : null),
    mail: ({ name, email, message }) => ({
      replyTo: { name, address: email },
      subject: `Kontaktanfrage von ${name}`,
      text: `Name: ${name}\nE-Mail: ${email}\n\n${message}\n`,
    }),
    what: 'Deine Nachricht',
    tooMany: 'Zu viele Nachrichten in kurzer Zeit.',
    unavailable: 'Das Kontaktformular ist gerade nicht erreichbar.',
  },
  '/api/newsletter': {
    validate: validateNewsletter,
    mail: (data) => newsletterMail(data, 'Newsletter-Formular'),
    what: 'Deine Anmeldung',
    tooMany: 'Zu viele Anmeldungen in kurzer Zeit.',
    unavailable: 'Die Newsletter-Anmeldung ist gerade nicht erreichbar.',
  },
};

const server = http.createServer(async (req, res) => {
  const form = Object.hasOwn(FORMS, req.url) ? FORMS[req.url] : null;
  if (!form) return send(res, 404, { ok: false, error: 'Nicht gefunden.' });
  if (req.method !== 'POST') return send(res, 405, { ok: false, error: 'Methode nicht erlaubt.' });

  let body;
  try {
    body = await readJson(req);
  } catch (err) {
    const status = err.status || 400;
    return send(res, status, {
      ok: false,
      error: status === 413 ? 'Die Nachricht ist zu lang.' : 'Die Anfrage konnte nicht gelesen werden.',
    });
  }

  const result = form.validate(body);
  // Pretend success so bots get no signal that the honeypot caught them
  if (result.spam) return send(res, 200, { ok: true });
  if (!result.data) {
    return send(res, 422, { ok: false, error: 'Bitte prüfe deine Eingaben.', fields: result.errors });
  }

  if (rateLimited(clientIp(req))) {
    return send(res, 429, { ok: false, error: `${form.tooMany} Bitte versuch es später noch einmal oder schreib an info@bricksnbytes.de.` });
  }

  if (!transport) {
    return send(res, 503, { ok: false, error: `${form.unavailable} Bitte schreib direkt an info@bricksnbytes.de.` });
  }

  const deliver = async (mail) => {
    const info = await transport.sendMail({
      from: { name: 'BricksnBytes Website', address: MAIL_FROM || 'dry-run@localhost' },
      to: MAIL_TO,
      ...mail,
    });
    if (DRY_RUN) console.log('[mailer] dry run:', info.message.toString());
  };

  try {
    await deliver(form.mail(result.data));
  } catch (err) {
    console.error('[mailer] send failed:', err.message);
    return send(res, 502, { ok: false, error: `${form.what} konnte nicht gesendet werden. Bitte versuch es später noch einmal oder schreib an info@bricksnbytes.de.` });
  }

  // The message is out; a failing second mail is reported separately so the visitor can retry only that
  const extra = form.extra?.(result.data);
  if (!extra) return send(res, 200, { ok: true });
  try {
    await deliver(extra);
    return send(res, 200, { ok: true, newsletter: true });
  } catch (err) {
    console.error('[mailer] newsletter mail failed:', err.message);
    return send(res, 200, { ok: true, newsletter: false });
  }
});

server.listen(PORT, HOST, () => console.log(`[mailer] listening on ${HOST}:${PORT}`));
