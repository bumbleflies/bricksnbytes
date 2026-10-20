// Customer form: "+ Kind hinzufügen" opens an empty child form in place (without JavaScript the
// button reloads the form with one). A new child is only saved with "Speichern"; empty ones are skipped.
(() => {
  const rows = document.getElementById('child-rows');
  const add = document.getElementById('add-child');
  const template = document.getElementById('child-template');
  if (!rows || !add || !template) return;

  rows.addEventListener('click', (event) => {
    const discard = event.target.closest('[data-discard-child]');
    if (discard) discard.closest('[data-new-child]')?.remove();
  });

  add.addEventListener('click', (event) => {
    event.preventDefault();
    const index = rows.dataset.nextIndex;
    rows.dataset.nextIndex = String(Number(index) + 1);
    rows.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__i__', index));
    document.getElementById('no-children')?.remove();
    rows.lastElementChild.querySelector('input')?.focus();
  });
})();
