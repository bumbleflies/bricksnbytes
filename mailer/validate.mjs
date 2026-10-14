// Validation for contact form and newsletter submissions. Kept free of I/O so it can be unit-tested.

export const LIMITS = { name: 100, email: 254, message: 5000 };

// Pragmatic address check: one @, no whitespace, a dot in the domain
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const PHONE_RE = /^[0-9+()/ .-]{5,30}$/;

const str = (v) => (typeof v === 'string' ? v.trim() : '');
// Newlines in names/addresses would end up in mail headers
const oneLine = (v) => str(v).replace(/[\r\n]+/g, ' ');
const checked = (v) => v === true || v === 'on' || v === 'true';

function emailError(email) {
  if (!email) return 'Bitte gib deine E-Mail-Adresse an.';
  if (email.length > LIMITS.email || !EMAIL_RE.test(email)) return 'Bitte gib eine gültige E-Mail-Adresse an.';
  return null;
}

/**
 * @param {Record<string, unknown>} input raw request body
 * @returns {{ spam: true } | { spam: false, errors: Record<string, string>, data?: { name: string, email: string, message: string,
 *   newsletter: null | { vorname: string, nachname: string, email: string, telefon: string } } }}
 */
export function validateContact(input) {
  const body = input && typeof input === 'object' ? input : {};

  // Honeypot: real visitors never see or fill this field
  if (str(body.website) !== '') return { spam: true };

  const name = oneLine(body.name);
  const email = str(body.email);
  const message = str(body.message);
  const privacy = checked(body.privacy);

  const errors = {};
  if (!name) errors.name = 'Bitte gib deinen Namen an.';
  else if (name.length > LIMITS.name) errors.name = `Der Name darf höchstens ${LIMITS.name} Zeichen lang sein.`;

  const emailErr = emailError(email);
  if (emailErr) errors.email = emailErr;

  if (!message) errors.message = 'Bitte schreib uns eine Nachricht.';
  else if (message.length > LIMITS.message) errors.message = `Die Nachricht darf höchstens ${LIMITS.message} Zeichen lang sein.`;

  if (!privacy) errors.privacy = 'Bitte bestätige, dass du die Datenschutzerklärung gelesen hast.';

  // Optional newsletter sign-up in the same submission ("Ja, ich möchte auch den Newsletter erhalten")
  const wantsNewsletter = checked(body.newsletter);
  if (wantsNewsletter && !checked(body.newsletter_consent)) {
    errors.newsletter_consent = 'Bitte bestätige die Einwilligung für den Newsletter.';
  }

  if (Object.keys(errors).length > 0) return { spam: false, errors };
  const [vorname, ...rest] = name.split(' ');
  const newsletter = wantsNewsletter ? { vorname, nachname: rest.join(' '), email, telefon: '' } : null;
  return { spam: false, errors: {}, data: { name, email, message, newsletter } };
}

/**
 * @param {Record<string, unknown>} input raw request body
 * @returns {{ spam: true } | { spam: false, errors: Record<string, string>, data?: { vorname: string, nachname: string, email: string, telefon: string } }}
 */
export function validateNewsletter(input) {
  const body = input && typeof input === 'object' ? input : {};
  if (str(body.website) !== '') return { spam: true };

  const vorname = oneLine(body.vorname);
  const nachname = oneLine(body.nachname);
  const email = str(body.email);
  const telefon = oneLine(body.telefon);

  const errors = {};
  if (!vorname) errors.vorname = 'Bitte gib deinen Vornamen an.';
  else if (vorname.length > LIMITS.name) errors.vorname = `Der Vorname darf höchstens ${LIMITS.name} Zeichen lang sein.`;

  if (nachname.length > LIMITS.name) errors.nachname = `Der Nachname darf höchstens ${LIMITS.name} Zeichen lang sein.`;

  const emailErr = emailError(email);
  if (emailErr) errors.email = emailErr;

  if (telefon && !PHONE_RE.test(telefon)) errors.telefon = 'Bitte gib eine gültige Telefonnummer an (nur Ziffern, Leerzeichen, + - / ( )).';

  if (!checked(body.consent)) errors.consent = 'Bitte bestätige, dass du den Newsletter erhalten möchtest.';

  if (Object.keys(errors).length > 0) return { spam: false, errors };
  return { spam: false, errors: {}, data: { vorname, nachname, email, telefon } };
}
