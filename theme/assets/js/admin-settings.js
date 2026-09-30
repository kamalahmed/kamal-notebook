(() => {
  const root = document.querySelector('[data-kn-settings]');
  if (!root) return;
  const tabs = [...root.querySelectorAll('[data-tab]')];
  const panels = [...root.querySelectorAll('[data-panel]')];
  const cards = [...root.querySelectorAll('[data-kn-setting]')];
  const search = root.querySelector('#kn-settings-search');
  const status = root.querySelector('[data-search-status]');
  const empty = root.querySelector('[data-no-results]');
  let active = tabs.some(t => t.dataset.tab === location.hash.slice(1)) ? location.hash.slice(1) : 'home';
  const show = () => {
    const query = search.value.trim().toLocaleLowerCase();
    let matches = 0;
    cards.forEach(card => {
      const text = `${card.textContent} ${card.dataset.knSetting || ''} ${card.dataset.keywords || ''}`.toLocaleLowerCase();
      card.hidden = Boolean(query) && !query.split(/\s+/).every(word => text.includes(word));
      if (!card.hidden) matches++;
    });
    panels.forEach(panel => { panel.hidden = query ? ![...panel.querySelectorAll('[data-kn-setting]')].some(card => !card.hidden) : panel.dataset.panel !== active; });
    tabs.forEach(tab => {
      const selected = !query && tab.dataset.tab === active;
      tab.setAttribute('aria-selected', String(selected));
      tab.tabIndex = tab.dataset.tab === active ? 0 : -1;
    });
    empty.hidden = !query || matches !== 0;
    status.textContent = query ? `${matches} matching ${matches === 1 ? 'section' : 'sections'} across all tabs.` : '';
  };
  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => { active = tab.dataset.tab; search.value = ''; history.replaceState(null, '', `#${active}`); show(); });
    tab.addEventListener('keydown', event => {
      const next = event.key === 'ArrowDown' || event.key === 'ArrowRight' ? (index + 1) % tabs.length
        : event.key === 'ArrowUp' || event.key === 'ArrowLeft' ? (index - 1 + tabs.length) % tabs.length
        : event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : -1;
      if (next < 0) return;
      event.preventDefault(); tabs[next].click(); tabs[next].focus();
    });
  });
  search.addEventListener('input', show);
  root.querySelector('[data-clear-search]').addEventListener('click', () => { search.value = ''; show(); search.focus(); });
  root.querySelector('form').addEventListener('invalid', event => {
    const panel = event.target.closest('[data-panel]');
    if (panel) { active = panel.dataset.panel; search.value = ''; show(); }
  }, true);
  root.classList.add('is-enhanced');
  show();
})();
