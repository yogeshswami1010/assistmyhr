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
