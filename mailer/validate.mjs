// Validation for contact form submissions. Kept free of I/O so it can be unit-tested.

export const LIMITS = { name: 100, email: 254, message: 5000 };

// Pragmatic address check: one @, no whitespace, a dot in the domain
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/**
 * @param {Record<string, unknown>} input raw request body
 * @returns {{ spam: true } | { spam: false, errors: Record<string, string>, data?: { name: string, email: string, message: string } }}
 */
export function validateContact(input) {
  const body = input && typeof input === 'object' ? input : {};
  const str = (v) => (typeof v === 'string' ? v.trim() : '');

  // Honeypot: real visitors never see or fill this field
  if (str(body.website) !== '') return { spam: true };

  // Newlines in name/email would end up in mail headers
  const name = str(body.name).replace(/[\r\n]+/g, ' ');
  const email = str(body.email);
  const message = str(body.message);
  const privacy = body.privacy === true || body.privacy === 'on' || body.privacy === 'true';

  const errors = {};
  if (!name) errors.name = 'Bitte gib deinen Namen an.';
  else if (name.length > LIMITS.name) errors.name = `Der Name darf höchstens ${LIMITS.name} Zeichen lang sein.`;

  if (!email) errors.email = 'Bitte gib deine E-Mail-Adresse an.';
  else if (email.length > LIMITS.email || !EMAIL_RE.test(email)) errors.email = 'Bitte gib eine gültige E-Mail-Adresse an.';

  if (!message) errors.message = 'Bitte schreib uns eine Nachricht.';
  else if (message.length > LIMITS.message) errors.message = `Die Nachricht darf höchstens ${LIMITS.message} Zeichen lang sein.`;

  if (!privacy) errors.privacy = 'Bitte bestätige, dass du die Datenschutzerklärung gelesen hast.';

  if (Object.keys(errors).length > 0) return { spam: false, errors };
  return { spam: false, errors: {}, data: { name, email, message } };
}
