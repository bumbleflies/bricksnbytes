// Newsletter unsubscribe: hands the address to the backoffice (POST /api/newsletter-abmelden there,
// guarded by a shared token) and builds the info mail to our inbox. Kept free of module state so it
// can be unit-tested with a fake fetch.

const TIMEOUT_MS = 10_000;

/**
 * @param {string} email
 * @param {{ url?: string, token?: string, fetchImpl?: typeof fetch }} options
 * @returns {Promise<{ status: 'abgemeldet' | 'nicht_gefunden' | 'nicht_konfiguriert' | 'fehler', gefunden?: number, abgemeldet?: number }>}
 */
export async function unsubscribeInBackoffice(email, { url, token, fetchImpl = fetch } = {}) {
  if (!url || !token) return { status: 'nicht_konfiguriert' };
  try {
    const res = await fetchImpl(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Abmelde-Token': token },
      body: JSON.stringify({ email }),
      signal: AbortSignal.timeout(TIMEOUT_MS),
    });
    const body = await res.json().catch(() => null);
    if (!res.ok || !body?.ok) {
      console.error(`[mailer] backoffice unsubscribe failed: HTTP ${res.status}`);
      return { status: 'fehler' };
    }
    const gefunden = Number(body.gefunden) || 0;
    return { status: gefunden > 0 ? 'abgemeldet' : 'nicht_gefunden', gefunden, abgemeldet: Number(body.abgemeldet) || 0 };
  } catch (err) {
    console.error('[mailer] backoffice unsubscribe failed:', err.message);
    return { status: 'fehler' };
  }
}

/** true when the backoffice has the unsubscribe on record, so a failing info mail loses nothing */
export const recordedInBackoffice = (result) => result.status === 'abgemeldet' || result.status === 'nicht_gefunden';

const RESULT_TEXT = {
  abgemeldet: (r) => `Im Backoffice automatisch eingetragen: ja (${r.gefunden} Kunde(n) gefunden, ${r.abgemeldet} Newsletter-Einwilligung(en) jetzt widerrufen).`,
  nicht_gefunden: () => 'Im Backoffice automatisch eingetragen: nein – die Adresse ist dort keinem Kunden als Newsletter-Adresse zugeordnet. Nichts weiter zu tun.',
  nicht_konfiguriert: () => 'Im Backoffice automatisch eingetragen: nein – die Verbindung zum Backoffice ist nicht eingerichtet. Bitte von Hand austragen (Kunde suchen, Newsletter: Nein).',
  fehler: () => 'Im Backoffice automatisch eingetragen: nein – das Backoffice war nicht erreichbar. Bitte von Hand austragen (Kunde suchen, Newsletter: Nein).',
};

/** Info mail to our inbox; `when` is the formatted time of the request */
export function unsubscribeMail(email, result, when) {
  return {
    subject: `Newsletter-Abmeldung: ${email}`,
    text: [
      'Abmeldung vom Newsletter über die Website (bricksnbytes.de/newsletter-abmelden).',
      '',
      `E-Mail: ${email}`,
      `Abgemeldet am: ${when}`,
      '',
      RESULT_TEXT[result.status](result),
      'Diese Mail als Nachweis des Widerrufs aufheben.',
      '',
    ].join('\n'),
  };
}
