// Shared submit handling for the forms that post JSON to the mailer (mailer/server.mjs):
// client-side check, field errors from the server, status line and a busy button.
export const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

type Errors = Record<string, string>;

interface Options {
  validate: (data: FormData) => Errors;
  payload: (data: FormData) => Record<string, unknown>;
  success: string;
  // Shown when the server could not process the submission
  failure: string;
}

export function mailerForm(form: HTMLFormElement, { validate, payload, success, failure }: Options) {
  const statusEl = form.querySelector<HTMLElement>('.form-status');
  const submitBtn = form.querySelector<HTMLButtonElement>('.submit-btn');

  function setStatus(text: string, kind: 'success' | 'error' | '') {
    if (!statusEl) return;
    statusEl.textContent = text;
    statusEl.className = `form-status ${kind}`;
  }

  function showErrors(errors: Errors) {
    form.querySelectorAll<HTMLElement>('[data-error-for]').forEach((el) => {
      const field = el.dataset.errorFor!;
      el.textContent = errors[field] ?? '';
      const input = form.elements.namedItem(field);
      if (input instanceof HTMLElement) input.setAttribute('aria-invalid', String(Boolean(errors[field])));
    });
    const first = Object.keys(errors)[0];
    if (first) (form.elements.namedItem(first) as HTMLElement | null)?.focus();
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = new FormData(form);
    const errors = validate(data);
    showErrors(errors);
    if (Object.keys(errors).length > 0) {
      setStatus('', '');
      return;
    }

    if (submitBtn) submitBtn.disabled = true;
    setStatus('Wird gesendet …', '');
    try {
      const res = await fetch(form.action, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ ...payload(data), website: data.get('website') }),
      });
      const body = await res.json().catch(() => null);
      if (res.ok && body?.ok) {
        form.reset();
        showErrors({});
        setStatus(success, 'success');
      } else {
        if (body?.fields) showErrors(body.fields);
        setStatus(body?.error ?? failure, 'error');
      }
    } catch {
      setStatus('Keine Verbindung zum Server. Bitte prüfe deine Internetverbindung oder schreib an info@bricksnbytes.de.', 'error');
    } finally {
      if (submitBtn) submitBtn.disabled = false;
    }
  });
}
