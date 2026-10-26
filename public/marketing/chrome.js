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


document.querySelector('.footer-top').addEventListener('click', () => {
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  window.scrollTo({ top: 0, behavior: reducedMotion ? 'instant' : 'smooth' });
});

const currentPageLinks = { 'marketing-pricing': '.header-pricing', 'marketing-register': '.header-trial', 'marketing-login': '.header-login', 'marketing-privacy': '.footer-privacy' };
for (const [pageClass, selector] of Object.entries(currentPageLinks)) {
  if (document.body.classList.contains(pageClass)) {
    document.querySelectorAll(selector).forEach(link => {
      link.classList.add('active');
      link.setAttribute('aria-current', 'page');
    });
  }
}
