// Customer list: eye icon opens a read-only quick view in a modal (<dialog>), loaded from
// /kunden/schnellansicht. Closes via X, Escape (native) or a click on the backdrop.
(() => {
  const dialog = document.getElementById('quick-view');
  if (!dialog || typeof dialog.showModal !== 'function') return;

  const body = document.getElementById('quick-body');
  const edit = document.getElementById('quick-edit');
  const detail = document.getElementById('quick-detail');
  const listUrl = dialog.dataset.listUrl || '/kunden';
  let opener = null;

  // Action icons never count as a click on the row or its checkbox
  document.querySelectorAll('[data-stop-row]').forEach((el) => {
    el.addEventListener('click', (event) => event.stopPropagation());
  });

  function setLinks(enabled, editHref = '#', detailHref = '#') {
    edit.href = editHref;
    detail.href = detailHref;
    [edit, detail].forEach((a) => {
      a.hidden = !enabled;
    });
  }

  async function open(id, button) {
    opener = button;
    body.innerHTML = '<p class="muted">Wird geladen …</p>';
    setLinks(false);
    dialog.showModal();
    try {
      const res = await fetch(`/kunden/schnellansicht?id=${encodeURIComponent(id)}&zurueck=${encodeURIComponent(listUrl)}`, {
        credentials: 'same-origin',
        headers: { Accept: 'text/html' },
      });
      // Session expired: the router sent us to the login page
      if (res.redirected && new URL(res.url).pathname === '/login') {
        window.location.href = '/login';
        return;
      }
      // The fragment is rendered and escaped on the server
      body.innerHTML = await res.text();
      const quick = body.querySelector('.quick');
      if (res.ok && quick) setLinks(true, quick.dataset.editHref, quick.dataset.detailHref);
    } catch {
      body.innerHTML = '<p class="muted">Die Schnellansicht konnte nicht geladen werden. Bitte die Seite neu laden.</p>';
    }
  }

  document.querySelectorAll('.quick-open').forEach((button) => {
    button.addEventListener('click', () => open(button.dataset.id, button));
  });

  // Click on the dark backdrop (outside the inner box) closes
  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) dialog.close();
  });

  // Focus back on the eye icon that opened it
  dialog.addEventListener('close', () => {
    opener?.focus();
    opener = null;
  });
})();
