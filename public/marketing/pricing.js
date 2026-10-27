// This compares published prices; trial signup and subscription handling stay separate.
document.querySelectorAll('[data-pricing-plan]').forEach(plan => {
  const summary = plan.querySelector('.pricing-selection');
  const options = [...plan.querySelectorAll('input[name="pricing-billing"]')];
  const update = () => {
    const selected = options.find(input => input.checked);
    if (!selected) return;
    plan.dataset.billing = selected.value;
    summary.textContent = selected.value === 'annual'
      ? 'Annual billing: $720 per user, billed once a year ($60/month). Save $180 per user each year.'
      : 'Monthly billing: $75 per user, billed each month.';
  };
  options.forEach(input => {
    input.addEventListener('change', () => {
      if (!input.checked) return;
      update();
    });
  });
  update();
});

// Enhance the category links into accessible tabs; all details remain readable without JS.
document.querySelectorAll('.pricing-feature-explorer').forEach(explorer => {
  const nav = explorer.querySelector('.pricing-feature-nav');
  const tabs = [...nav.querySelectorAll('a')];
  const panels = tabs.map(tab => explorer.querySelector(tab.getAttribute('href')));
  nav.setAttribute('role', 'tablist');
  const mobileLayout = window.matchMedia('(max-width: 680px)');
  const updateOrientation = () => nav.setAttribute('aria-orientation', mobileLayout.matches ? 'horizontal' : 'vertical');
  updateOrientation();
  mobileLayout.addEventListener('change', updateOrientation);
  const select = index => {
    tabs.forEach((tab, i) => {
      tab.setAttribute('aria-selected', String(i === index));
      tab.tabIndex = i === index ? 0 : -1;
      panels[i].hidden = i !== index;
    });
  };
  tabs.forEach((tab, index) => {
    tab.id = panels[index].id + '-tab';
    tab.setAttribute('role', 'tab');
    tab.setAttribute('aria-controls', panels[index].id);
    panels[index].setAttribute('role', 'tabpanel');
    panels[index].setAttribute('aria-labelledby', tab.id);
    panels[index].tabIndex = 0;
    tab.addEventListener('click', event => {
      event.preventDefault();
      select(index);
    });
    tab.addEventListener('keydown', event => {
      const previous = mobileLayout.matches ? 'ArrowLeft' : 'ArrowUp';
      const next = mobileLayout.matches ? 'ArrowRight' : 'ArrowDown';
      let target;
      if (event.key === previous) target = (index - 1 + tabs.length) % tabs.length;
      if (event.key === next) target = (index + 1) % tabs.length;
      if (event.key === 'Home') target = 0;
      if (event.key === 'End') target = tabs.length - 1;
      if (target === undefined) return;
      event.preventDefault();
      select(target);
      tabs[target].focus();
    });
  });
  const selectHash = () => {
    const index = panels.findIndex(panel => '#' + panel.id === window.location.hash);
    if (index !== -1) select(index);
    return index;
  };
  select(0);
  selectHash();
  window.addEventListener('hashchange', selectHash);
});
