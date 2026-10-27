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
  if (requested === 'signin' || requested === 'signup') {
    location.replace(requested === 'signin' ? '/login' : '/register');
    return;
  }
  const legacyAnchors = { top: 'home', dashboard: 'home', features: 'why-features', how: 'hiring-steps', 'how-it-works': 'hiring-steps', communications: 'why-communications' };
  const target = legacyAnchors[requested] || requested;
  const scrollTarget = document.getElementById(target === 'modules' ? 'why-features' : target);
  const parentView = scrollTarget?.closest('.view');
  const page = parentView?.id || target;
  const selected = views.some(view => view.id === page) ? page : 'home';
  views.forEach(view => view.hidden = view.id !== selected);
  const pageTitles = { home: 'Applicant Tracking System', 'why-choose-us': 'Why choose us?', pricing: 'Pricing', 'request-demo': 'Request a demo', contact: 'Contact us' };
  document.title = 'AssistMyHR – ' + pageTitles[selected];
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

// Website enquiry forms submit to the app with CSRF protection.
// Keep confirmation visible until dismissed, including when the form is below the fold.
let enquiryNotification;
const notifyEnquirySuccess = message => {
  enquiryNotification?.remove();
  enquiryNotification = document.createElement('div');
  enquiryNotification.className = 'enquiry-notification';
  const confirmation = document.createElement('div');
  confirmation.setAttribute('role', 'status');
  confirmation.setAttribute('aria-live', 'polite');
  const heading = document.createElement('strong');
  heading.textContent = 'Submitted successfully';
  const detail = document.createElement('p');
  detail.textContent = message;
  confirmation.append(heading, detail);
  const dismiss = document.createElement('button');
  dismiss.type = 'button';
  dismiss.setAttribute('aria-label', 'Dismiss submission notification');
  dismiss.textContent = '×';
  dismiss.addEventListener('click', () => enquiryNotification.remove());
  enquiryNotification.append(confirmation, dismiss);
  document.body.append(enquiryNotification);
};

document.querySelectorAll('[data-enquiry-form]').forEach(contactForm => {
contactForm.addEventListener('submit', async event => {
  event.preventDefault();
  const button = contactForm.querySelector('button[type="submit"]');
  if (button.disabled || !contactForm.reportValidity()) return;

  const status = contactForm.querySelector('[role="status"]');
  const show = (message, state) => {
    status.textContent = message;
    status.dataset.state = state;
  };
  const payload = Object.fromEntries(new FormData(contactForm));
  const originalLabel = button.textContent;
  button.disabled = true;
  button.textContent = 'Sending…';
  contactForm.setAttribute('aria-busy', 'true');
  show('Sending your enquiry…', 'pending');

  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 20000);
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrfToken) throw new Error('Please reload this page before submitting. Your message is still in the form.');
    const response = await fetch(contactForm.action, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify(payload),
      credentials: 'same-origin',
      signal: controller.signal,
    });
    if (response.status === 419) throw new Error('Your session has expired. Reload this page and try again. Your message is still in the form.');
    const result = await response.json();
    if (!response.ok || result.success !== true) {
      throw new Error(result.message || 'Unable to send your enquiry. Please try again.');
    }
    const successMessage = payload.kind === 'demo'
      ? 'Thank you! Your demo request has been submitted successfully. Our team will contact you shortly.'
      : 'Thank you! Your message has been submitted successfully. Our team will get back to you shortly.';
    show(successMessage, 'success');
    notifyEnquirySuccess(successMessage);
    contactForm.reset();
  } catch (error) {
    const message = error.name === 'AbortError'
      ? 'The request timed out. Delivery could not be confirmed; please email support@assistmyhr.com before submitting again.'
      : error instanceof SyntaxError || error instanceof TypeError
        ? 'Unable to reach the contact service. Your message is still in the form. Please try again or email support@assistmyhr.com.'
        : error.message;
    show(message, 'error');
  } finally {
    clearTimeout(timeout);
    button.disabled = false;
    button.textContent = originalLabel;
    contactForm.removeAttribute('aria-busy');
  }
});
});

document.querySelector('.footer-top').addEventListener('click', () => {
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  window.scrollTo({ top: 0, behavior: reducedMotion ? 'instant' : 'smooth' });
});
