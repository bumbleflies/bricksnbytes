import { describe, it, expect } from 'vitest';
import { readdirSync, readFileSync } from 'node:fs';
import { join } from 'node:path';
import { parse } from 'yaml';
import { geburtstagsMailto, MAILTO_MAX_LENGTH } from './geburtstagsanfrage';

const dir = join(process.cwd(), 'src/content/tiles/geburtstage');
const pakete = readdirSync(dir)
  .filter((file) => file.endsWith('.yaml'))
  .map((file) => parse(readFileSync(join(dir, file), 'utf8')));

describe('geburtstagsMailto', () => {
  it.each(pakete.map((p) => [p.name, p]))('%s: subject, package and duration from the YAML', (_name, p) => {
    const dauer = p.bullets.find((b: { icon: string }) => b.icon === 'clock').text;
    const href = geburtstagsMailto({ subject: p.requestSubject, paket: p.name, dauer });
    const url = new URL(href);

    expect(url.protocol).toBe('mailto:');
    expect(url.searchParams.get('subject')).toBe(p.requestSubject);
    const body = decodeURIComponent(href.split('&body=')[1]);
    expect(body).toContain(`Gewähltes Paket: ${p.name}\r\n`);
    expect(body).toContain(`Dauer: ${dauer}\r\n`);
    expect(href.length).toBeLessThanOrEqual(MAILTO_MAX_LENGTH);
  });

  it('uses CRLF line breaks and no bare LF', () => {
    const href = geburtstagsMailto({ subject: 'Anfrage', paket: 'Test', dauer: '2 Std.' });
    expect(href).toContain('%0D%0A');
    expect(href.replaceAll('%0D%0A', '')).not.toContain('%0A');
  });

  it('refuses links that would get too long', () => {
    expect(() => geburtstagsMailto({ subject: 'Anfrage', paket: 'x'.repeat(2000), dauer: '2 Std.' })).toThrow();
  });
});
