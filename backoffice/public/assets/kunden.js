// Customer list: row selection, "E-Mail an Auswahl" / "Newsletter verschicken" (mailto with
// all recipients in BCC and the own address in To) and copying the addresses.
// Addresses arrive already cleaned (valid, de-duplicated) from the server.
(() => {
  const tools = document.getElementById('mail-tools');
  if (!tools) return;

  // Mail clients cut off longer mailto links
  const MAX_MAILTO = 2000;
  // RFC 6068 separates addresses in mailto with commas; the clipboard gets semicolons for Outlook
  const MAILTO_SEPARATOR = ',';
  const CLIPBOARD_SEPARATOR = '; ';

  const ownEmail = tools.dataset.ownEmail || '';
  const filtered = JSON.parse(tools.dataset.filtered || '[]');
  const newsletter = JSON.parse(tools.dataset.newsletter || '[]');
  const total = Number(tools.dataset.total || 0);

  const rows = [...document.querySelectorAll('.row-select')];
  const selectPage = document.getElementById('select-page');
  const selectFiltered = document.getElementById('select-filtered');
  const count = document.getElementById('sel-count');
  const notice = document.getElementById('mail-notice');
  const mailSelection = document.getElementById('mail-selection');
  const copySelection = document.getElementById('copy-selection');
  const mailNewsletter = document.getElementById('mail-newsletter');
  const copyNewsletter = document.getElementById('copy-newsletter');

  // true once "Alle N gefilterten Kunden auswählen" was clicked (selection spans all pages)
  let allFiltered = false;

  const unique = (list) => {
    const seen = new Set();
    return list.filter((a) => {
      const key = a.toLowerCase();
      if (!a || seen.has(key)) return false;
      seen.add(key);
      return true;
    });
  };

  const selectedAddresses = () =>
    allFiltered ? filtered : unique(rows.filter((r) => r.checked).map((r) => r.dataset.email));

  const selectedCount = () => (allFiltered ? total : rows.filter((r) => r.checked).length);

  function say(text) {
    notice.textContent = text;
    notice.hidden = false;
  }

  function update() {
    const n = selectedCount();
    const addresses = selectedAddresses();
    const checked = rows.filter((r) => r.checked).length;
    count.textContent = n === 1 ? '1 Kunde ausgewählt' : `${n} Kunden ausgewählt`;
    if (addresses.length < n) {
      count.textContent += ` (${addresses.length} mit gültiger E-Mail)`;
    }
    if (selectPage) {
      selectPage.checked = rows.length > 0 && checked === rows.length;
      selectPage.indeterminate = checked > 0 && checked < rows.length;
    }
    // Offer the other pages once the whole page is selected
    selectFiltered.hidden = allFiltered || total <= rows.length || checked !== rows.length || rows.length === 0;
    mailSelection.disabled = addresses.length === 0 || !ownEmail;
    copySelection.disabled = addresses.length === 0;
    if (!ownEmail) mailNewsletter.disabled = true;
  }

  async function copy(addresses) {
    const text = addresses.join(CLIPBOARD_SEPARATOR);
    try {
      await navigator.clipboard.writeText(text);
      return true;
    } catch {
      // Older browsers / denied permission: classic copy via a hidden text field
      const field = document.createElement('textarea');
      field.value = text;
      field.setAttribute('readonly', '');
      field.style.position = 'fixed';
      field.style.opacity = '0';
      document.body.append(field);
      field.select();
      const ok = document.execCommand('copy');
      field.remove();
      return ok;
    }
  }

  const encode = (address) => encodeURIComponent(address).replace(/%40/g, '@');

  // Opens the mail program like a normal mailto link would
  function openMail(url) {
    const link = document.createElement('a');
    link.href = url;
    link.hidden = true;
    document.body.append(link);
    link.click();
    link.remove();
  }

  async function sendMail(addresses) {
    if (!ownEmail || addresses.length === 0) return;
    const to = `mailto:${encode(ownEmail)}`;
    const url = `${to}?bcc=${addresses.map(encode).join(MAILTO_SEPARATOR)}`;
    if (url.length <= MAX_MAILTO) {
      say(`Mailprogramm wird geöffnet – ${addresses.length} Empfänger im BCC.`);
      openMail(url);
      return;
    }
    const copied = await copy(addresses);
    say(copied
      ? `Zu viele Empfänger für einen Mail-Link: ${addresses.length} Adressen kopiert – in Outlook ins BCC-Feld einfügen.`
      : 'Die Adressen konnten nicht kopiert werden. Bitte „Adressen kopieren“ verwenden.');
    openMail(to);
  }

  async function copyButton(addresses) {
    const copied = await copy(addresses);
    say(copied
      ? `${addresses.length} Adressen kopiert – in Outlook ins BCC-Feld einfügen.`
      : 'Kopieren hat nicht geklappt – bitte erneut versuchen.');
  }

  rows.forEach((row) => row.addEventListener('change', () => {
    allFiltered = false;
    update();
  }));
  selectPage?.addEventListener('change', () => {
    allFiltered = false;
    rows.forEach((row) => { row.checked = selectPage.checked; });
    update();
  });
  selectFiltered.addEventListener('click', () => {
    allFiltered = true;
    rows.forEach((row) => { row.checked = true; });
    update();
  });
  mailSelection.addEventListener('click', () => sendMail(selectedAddresses()));
  copySelection.addEventListener('click', () => copyButton(selectedAddresses()));
  mailNewsletter.addEventListener('click', () => sendMail(newsletter));
  copyNewsletter.addEventListener('click', () => copyButton(newsletter));

  update();
})();
