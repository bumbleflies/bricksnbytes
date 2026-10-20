// Dashboard: "Woche | Monat" switch of the birthday tile. Both lists come from the server; the
// choice is remembered in this browser. Without JavaScript the default period is shown.
(() => {
  const tile = document.getElementById('birthdays');
  if (!tile) return;
  const KEY = 'dashboard-geburtstage-zeitraum';
  const buttons = [...tile.querySelectorAll('[data-period]')];
  const lists = [...tile.querySelectorAll('[data-period-list]')];
  const valid = (p) => buttons.some((b) => b.dataset.period === p);

  const show = (period) => {
    buttons.forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.period === period)));
    lists.forEach((l) => { l.hidden = l.dataset.periodList !== period; });
  };

  let saved = null;
  try { saved = localStorage.getItem(KEY); } catch { /* storage blocked: default */ }
  show(valid(saved) ? saved : tile.dataset.default);
  tile.querySelector('.period-switch').hidden = false;

  buttons.forEach((b) => b.addEventListener('click', () => {
    show(b.dataset.period);
    try { localStorage.setItem(KEY, b.dataset.period); } catch { /* ignore */ }
  }));
})();
