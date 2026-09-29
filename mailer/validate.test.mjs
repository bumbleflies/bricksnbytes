import { describe, it, expect } from 'vitest';
import { validateContact, LIMITS } from './validate.mjs';

const valid = { name: 'Anna', email: 'anna@example.de', message: 'Hallo!', privacy: true };

describe('validateContact', () => {
  it('accepts a complete submission and trims values', () => {
    const r = validateContact({ ...valid, name: '  Anna  ' });
    expect(r.spam).toBe(false);
    expect(r.data).toEqual({ name: 'Anna', email: 'anna@example.de', message: 'Hallo!' });
  });

  it('flags a filled honeypot as spam', () => {
    expect(validateContact({ ...valid, website: 'http://spam' })).toEqual({ spam: true });
  });

  it('requires all fields and the privacy checkbox', () => {
    const r = validateContact({});
    expect(r.data).toBeUndefined();
    expect(Object.keys(r.errors).sort()).toEqual(['email', 'message', 'name', 'privacy']);
  });

  it('rejects malformed email addresses', () => {
    for (const email of ['kaputt', 'a@b', 'a b@c.de', '@c.de']) {
      expect(validateContact({ ...valid, email }).errors.email).toBeDefined();
    }
  });

  it('accepts the checkbox value a plain form post sends', () => {
    expect(validateContact({ ...valid, privacy: 'on' }).data).toBeDefined();
  });

  it('strips newlines from the name so it cannot inject mail headers', () => {
    const r = validateContact({ ...valid, name: 'Anna\r\nBcc: x@y.de' });
    expect(r.data.name).not.toMatch(/[\r\n]/);
  });

  it('enforces length limits', () => {
    const r = validateContact({ ...valid, message: 'x'.repeat(LIMITS.message + 1) });
    expect(r.errors.message).toBeDefined();
  });

  it('ignores non-string values', () => {
    const r = validateContact({ ...valid, name: { $ne: '' } });
    expect(r.errors.name).toBeDefined();
  });
});
