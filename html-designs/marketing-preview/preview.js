const views = [...document.querySelectorAll('.view')];
const siteHeader = document.querySelector('.site-header');
const menuToggle = document.querySelector('.header-menu-toggle');

function closeHeaderMenu() {
  siteHeader.dataset.menuOpen = 'false';
  menuToggle.setAttribute('aria-expanded', 'false');
  menuToggle.setAttribute('aria-label', 'Open navigation menu');
}

menuToggle.addEventListener('click', () => {
  const open = menuToggle.getAttribute('aria-expanded') !== 'true';
  siteHeader.dataset.menuOpen = String(open);
  menuToggle.setAttribute('aria-expanded', String(open));
  menuToggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
});
siteHeader.addEventListener('click', event => {
  if (event.target.closest('a')) closeHeaderMenu();
});
document.addEventListener('keydown', event => {
  if (event.key === 'Escape' && menuToggle.getAttribute('aria-expanded') === 'true') {
    closeHeaderMenu();
    menuToggle.focus();
  }
});
document.addEventListener('pointerdown', event => {
  if (!siteHeader.contains(event.target)) closeHeaderMenu();
});
window.matchMedia('(max-width: 1340px)').addEventListener('change', closeHeaderMenu);

function navigate() {
  closeHeaderMenu();
  const requested = location.hash.slice(1) || 'home';
  const legacyAnchors = { top: 'home', dashboard: 'home', features: 'why-features', how: 'hiring-steps', 'how-it-works': 'hiring-steps', communications: 'why-communications' };
  const target = legacyAnchors[requested] || requested;
  const scrollTarget = document.getElementById(target === 'modules' ? 'why-features' : target);
  const parentView = scrollTarget?.closest('.view');
  const page = parentView?.id || target;
  const selected = views.some(view => view.id === page) ? page : 'home';
  views.forEach(view => view.hidden = view.id !== selected);
  document.body.classList.toggle('is-home', selected === 'home');
  document.body.classList.toggle('is-light', selected !== 'home');
  document.querySelectorAll('nav a, .header-login, .header-trial').forEach(link => {
    const active = link.hash === '#' + target || (link.hash === '#home' && selected === 'home') || (link.hash === '#why-choose-us' && selected === 'why-choose-us') || (link.classList.contains('header-pricing') && selected === 'pricing');
    link.classList.toggle('active', active);
    if (active) link.setAttribute('aria-current', 'page');
    else link.removeAttribute('aria-current');
  });
  if (scrollTarget && !scrollTarget.classList.contains('view')) {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    scrollTarget.scrollIntoView({ behavior: reducedMotion ? 'instant' : 'smooth' });
  }
  else window.scrollTo(0, 0);
}
window.addEventListener('hashchange', navigate);
navigate();
document.getElementById('contact-form').addEventListener('submit', event => {
  event.preventDefault();
  event.currentTarget.querySelector('[role="status"]').textContent = 'Your enquiry preview is complete. This demo does not send or store your information.';
});
document.getElementById('demo-form').addEventListener('submit', event => {
  event.preventDefault();
  event.currentTarget.querySelector('[role="status"]').textContent = 'Your demo request preview is complete. This preview does not send your information.';
});
document.getElementById('signin-form').addEventListener('submit', event => {
  event.preventDefault();
  event.currentTarget.querySelector('[role="status"]').textContent = 'Design preview only. Sign-in is not connected to a live account.';
});
document.querySelector('.reveal').addEventListener('click', event => {
  const input = event.currentTarget.previousElementSibling;
  input.type = input.type === 'password' ? 'text' : 'password';
  event.currentTarget.setAttribute('aria-label', input.type === 'password' ? 'Show password' : 'Hide password');
});
document.getElementById('forgot').addEventListener('click', () => {
  document.querySelector('#signin-form [role="status"]').textContent = 'Password recovery is not connected in this local design preview.';
});

document.querySelector('.footer-top').addEventListener('click', () => {
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  window.scrollTo({ top: 0, behavior: reducedMotion ? 'instant' : 'smooth' });
});
