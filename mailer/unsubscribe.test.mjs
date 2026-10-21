import { describe, it, expect, vi } from 'vitest';
import { recordedInBackoffice, unsubscribeInBackoffice, unsubscribeMail } from './unsubscribe.mjs';

const config = { url: 'https://backoffice.example/api/newsletter-abmelden', token: 'geheim' };
const answer = (status, body) => vi.fn(async () => ({ ok: status < 300, status, json: async () => body }));

describe('unsubscribeInBackoffice', () => {
  it('posts the address with the token header', async () => {
    const fetchImpl = answer(200, { ok: true, gefunden: 1, abgemeldet: 1 });
    const r = await unsubscribeInBackoffice('anna@example.de', { ...config, fetchImpl });
    expect(r).toEqual({ status: 'abgemeldet', gefunden: 1, abgemeldet: 1 });
    const [url, init] = fetchImpl.mock.calls[0];
    expect(url).toBe(config.url);
    expect(init.method).toBe('POST');
    expect(init.headers['X-Abmelde-Token']).toBe('geheim');
    expect(JSON.parse(init.body)).toEqual({ email: 'anna@example.de' });
  });

  it('reports an unknown address', async () => {
    const r = await unsubscribeInBackoffice('x@example.de', { ...config, fetchImpl: answer(200, { ok: true, gefunden: 0, abgemeldet: 0 }) });
    expect(r.status).toBe('nicht_gefunden');
  });

  it('does nothing without url or token', async () => {
    const fetchImpl = vi.fn();
    expect((await unsubscribeInBackoffice('a@b.de', { url: config.url, fetchImpl })).status).toBe('nicht_konfiguriert');
    expect((await unsubscribeInBackoffice('a@b.de', { token: 'x', fetchImpl })).status).toBe('nicht_konfiguriert');
    expect(fetchImpl).not.toHaveBeenCalled();
  });

  it('turns a rejected token, a server error or no connection into an error', async () => {
    const quiet = vi.spyOn(console, 'error').mockImplementation(() => {});
    expect((await unsubscribeInBackoffice('a@b.de', { ...config, fetchImpl: answer(403, { ok: false }) })).status).toBe('fehler');
    expect((await unsubscribeInBackoffice('a@b.de', { ...config, fetchImpl: answer(500, null) })).status).toBe('fehler');
    const offline = vi.fn(async () => { throw new Error('ECONNREFUSED'); });
    expect((await unsubscribeInBackoffice('a@b.de', { ...config, fetchImpl: offline })).status).toBe('fehler');
    quiet.mockRestore();
  });
});

describe('recordedInBackoffice', () => {
  it('is true only when the backoffice answered', () => {
    expect(recordedInBackoffice({ status: 'abgemeldet' })).toBe(true);
    expect(recordedInBackoffice({ status: 'nicht_gefunden' })).toBe(true);
    expect(recordedInBackoffice({ status: 'fehler' })).toBe(false);
    expect(recordedInBackoffice({ status: 'nicht_konfiguriert' })).toBe(false);
  });
});

describe('unsubscribeMail', () => {
  it('names the address, the time and the backoffice result', () => {
    const mail = unsubscribeMail('anna@example.de', { status: 'abgemeldet', gefunden: 1, abgemeldet: 1 }, '21.10.2026, 10:00 Uhr');
    expect(mail.subject).toBe('Newsletter-Abmeldung: anna@example.de');
    expect(mail.text).toContain('E-Mail: anna@example.de');
    expect(mail.text).toContain('21.10.2026, 10:00 Uhr');
    expect(mail.text).toContain('eingetragen: ja');
  });

  it('asks for a manual entry when the backoffice could not be reached', () => {
    expect(unsubscribeMail('a@b.de', { status: 'fehler' }, 'jetzt').text).toContain('Bitte von Hand austragen');
    expect(unsubscribeMail('a@b.de', { status: 'nicht_konfiguriert' }, 'jetzt').text).toContain('Bitte von Hand austragen');
  });
});
