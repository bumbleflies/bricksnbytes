import { describe, it, expect } from 'vitest';
import { validateContact, validateNewsletter, LIMITS } from './validate.mjs';

const valid = { name: 'Anna', email: 'anna@example.de', message: 'Hallo!', privacy: true };

describe('validateContact', () => {
  it('accepts a complete submission and trims values', () => {
    const r = validateContact({ ...valid, name: '  Anna  ' });
    expect(r.spam).toBe(false);
    expect(r.data).toEqual({ name: 'Anna', email: 'anna@example.de', message: 'Hallo!', newsletter: null });
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

  it('adds a newsletter sign-up when ticked and consented, split into first and last name', () => {
    const r = validateContact({ ...valid, name: 'Anna Maria Muster', newsletter: true, newsletter_consent: true });
    expect(r.data.newsletter).toEqual({ vorname: 'Anna', nachname: 'Maria Muster', email: 'anna@example.de', telefon: '' });
  });

  it('requires the newsletter consent only when the newsletter is ticked', () => {
    expect(validateContact({ ...valid, newsletter: true }).errors.newsletter_consent).toBeDefined();
    expect(validateContact({ ...valid, newsletter_consent: true }).data.newsletter).toBeNull();
  });
});

const signup = { vorname: 'Anna', nachname: 'Muster', email: 'anna@example.de', telefon: '', consent: true };

describe('validateNewsletter', () => {
  it('accepts a sign-up without phone number and trims values', () => {
    const r = validateNewsletter({ ...signup, vorname: '  Anna ' });
    expect(r.data).toEqual({ vorname: 'Anna', nachname: 'Muster', email: 'anna@example.de', telefon: '' });
  });

  it('accepts common phone number formats', () => {
    for (const telefon of ['+49 179 1234567', '0179/1234567', '(0711) 12-34 56']) {
      expect(validateNewsletter({ ...signup, telefon }).data?.telefon).toBe(telefon);
    }
  });

  it('rejects phone numbers with letters or that are too short or long', () => {
    for (const telefon of ['abc', '123', '1'.repeat(31), '0179 123<script>']) {
      expect(validateNewsletter({ ...signup, telefon }).errors.telefon).toBeDefined();
    }
  });

  it('requires first name, e-mail and the consent checkbox; the last name is optional', () => {
    const r = validateNewsletter({});
    expect(r.data).toBeUndefined();
    expect(Object.keys(r.errors).sort()).toEqual(['consent', 'email', 'vorname']);
    expect(validateNewsletter({ ...signup, nachname: '' }).data?.nachname).toBe('');
  });

  it('flags a filled honeypot as spam', () => {
    expect(validateNewsletter({ ...signup, website: 'http://spam' })).toEqual({ spam: true });
  });

  it('strips newlines from the names so they cannot inject mail headers', () => {
    const r = validateNewsletter({ ...signup, nachname: 'Muster\r\nBcc: x@y.de' });
    expect(r.data.nachname).not.toMatch(/[\r\n]/);
  });

  it('enforces length limits on the names', () => {
    expect(validateNewsletter({ ...signup, vorname: 'x'.repeat(LIMITS.name + 1) }).errors.vorname).toBeDefined();
  });
});
