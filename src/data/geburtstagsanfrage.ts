// Pre-filled e-mail for a birthday request (mailto with subject + body).
// Package name and duration come from the tile YAML (src/content/tiles/geburtstage/).
import { EMAIL_HREF } from './site';

// Mail clients and phones reliably open mailto links up to roughly this length
export const MAILTO_MAX_LENGTH = 1800;

export function geburtstagsBody(paket: string, dauer: string): string {
  return [
    'Hallo liebes bricksnbytes-Team,',
    '',
    'hiermit möchte ich gerne einen Kindergeburtstag für mein Kind anfragen. Hier die wichtigsten Infos:',
    '(* Was ich noch nicht genau weiß, habe ich mit „?" oder einem ungefähren Wert ausgefüllt.)',
    '',
    `Gewähltes Paket: ${paket}`,
    `Dauer: ${dauer}`,
    'Wunschdatum: ',
    'Ausweichtermin (optional): ',
    'Name des Geburtstagskinds: ',
    'Alter (wird): ',
    'Name Elternteil: ',
    'Adresse: ',
    'E-Mail: ',
    'Telefon (optional): ',
    'Anzahl Kinder (ca.): ',
    'Geschenk: [ ] für alle Kinder  [ ] nur für das Geburtstagskind',
    'Sonstige Wünsche / Fragen: ',
    '',
    'Viele Grüße',
  ].join('\r\n'); // encodeURIComponent turns CRLF into %0D%0A
}

export function geburtstagsMailto({ subject, paket, dauer }: { subject: string; paket: string; dauer: string }): string {
  const href = `${EMAIL_HREF}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(geburtstagsBody(paket, dauer))}`;
  if (href.length > MAILTO_MAX_LENGTH) {
    throw new Error(`Birthday mailto link for "${paket}" is ${href.length} characters (max ${MAILTO_MAX_LENGTH})`);
  }
  return href;
}
