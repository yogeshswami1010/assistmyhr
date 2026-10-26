import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import vm from 'node:vm';
import http from 'node:http';
import { fileURLToPath } from 'node:url';
import postcss from 'postcss';
import { renderPrivacyPolicy } from '../scripts/marketing-policy.mjs';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const html = fs.readFileSync(path.join(root, 'resources/frontend/index.html'), 'utf8');
const policyHtml = fs.readFileSync(path.join(root, 'resources/frontend/privacy-policy.html'), 'utf8');
const policyPreview = fs.readFileSync(path.join(root, 'html-designs/marketing-preview/privacy-policy.html'), 'utf8');
const policySource = fs.readFileSync(path.join(root, 'html-designs/marketing-preview/privacy-policy.txt'), 'utf8');
const decode = text => text.replaceAll('&lt;', '<').replaceAll('&gt;', '>').replaceAll('&quot;', '"').replaceAll('&#39;', "'").replaceAll('&amp;', '&');
const policyLines = policySource.replace(/^\uFEFF/, '').split(/\r?\n/).map(line => line.trim()).filter(Boolean);
for (const page of [policyHtml, policyPreview]) {
  const article = page.match(/<article class="privacy-document"[^>]*>([\s\S]*?)<\/article>/)[1];
  const visibleLines = [...article.matchAll(/<(h2|h3|p|li)\b[^>]*>([\s\S]*?)<\/\1>/g)].map(match => decode(match[2]));
  assert.deepEqual(visibleLines, policyLines.slice(5), 'Every supplied policy heading, paragraph and list item must remain intact');
  assert.equal((article.match(/<h2\b/g) || []).length, 21);
  const policyIds = [...page.matchAll(/\bid="([^"]+)"/g)].map(match => match[1]);
  assert.equal(new Set(policyIds).size, policyIds.length);
  for (const match of page.matchAll(/href="#([^"]+)"/g)) assert(policyIds.includes(match[1]), 'Unknown policy contents target: ' + match[1]);
  for (const line of policyLines.slice(2, 5)) assert(decode(page).includes(line.slice(line.indexOf(': ') + 2)), 'Keep the supplied policy metadata');
}
assert(policyHtml.includes('href="/privacy-policy"'));
assert(policyPreview.includes('href="privacy-policy.html"') && policyPreview.includes('href="index.html#home"'));
assert(!policyHtml.includes('/marketing/site.js'), 'Policy section links must use native page navigation');
const unsafePolicy = renderPrivacyPolicy(policySource.replace('Welcome to Assist My HR.', '<script>alert("example")</script>'));
assert(unsafePolicy.includes('&lt;script&gt;alert(&quot;example&quot;)&lt;/script&gt;'));
assert(!unsafePolicy.includes('<script>'), 'Treat supplied policy text as content, never executable markup');
const script = fs.readFileSync(path.join(root, 'public/marketing/site.js'), 'utf8');
new vm.Script(script, { filename: 'site.js' });
new vm.Script(fs.readFileSync(path.join(root, 'public/marketing/platform-section.js'), 'utf8'), { filename: 'platform-section.js' });
const pricingScript = fs.readFileSync(path.join(root, 'public/marketing/pricing.js'), 'utf8');
new vm.Script(pricingScript, { filename: 'pricing.js' });
const chromeScript = fs.readFileSync(path.join(root, 'public/marketing/chrome.js'), 'utf8');
new vm.Script(chromeScript, { filename: 'chrome.js' });
assert(html.includes('href="/login"') && html.includes('href="/register"'));
assert(!/Design preview|Preview enquiry|Preview only|id="signin"|id="signup"/.test(html));
assert(!/signin-form|Password recovery is not connected|enquiry preview is complete/.test(script));
assert(html.includes('action="/contact.php" method="post"'));
const form = html.match(/<form\b[^>]*id="contact-form"[^>]*>[\s\S]*?<\/form>/)[0];
assert.deepEqual([...form.matchAll(/\bname="([^"]+)"/g)].map(match => match[1]).sort(), ['company', 'email', 'message', 'name', 'phone', 'topic', 'website']);
assert.deepEqual([...form.matchAll(/<option>([^<]+)<\/option>/g)].map(match => match[1]), ['Live demo', 'Pricing', 'AI CV Search', 'Temp Staffing', 'Something else']);
const demoForm = html.match(/<form class="form-card demo-form"[\s\S]*?<\/form>/)[0];
assert(demoForm.includes('data-enquiry-form action="/contact.php" method="post"'));
assert.deepEqual([...demoForm.matchAll(/\bname="([^"]+)"/g)].map(match => match[1]).sort(), ['company', 'email', 'message', 'name', 'phone', 'topic', 'website']);
assert(demoForm.includes('type="hidden" name="topic" value="Live demo"'));
assert(html.includes('class="demo-page view" id="request-demo"'));
assert(html.includes('class="contact-page view" id="contact"'));
for (const field of ['name', 'email', 'company', 'phone', 'topic', 'message']) {
  assert(form.includes(`id="contact-${field}"`), `Contact label target missing: ${field}`);
  assert(form.includes(`for="contact-${field}"`), `Contact label missing: ${field}`);
}
assert(form.includes('aria-live="polite"'));
const pricingView = fs.readFileSync(path.join(root, 'resources/views/saas/pricing.blade.php'), 'utf8');
assert.equal((html.match(/data-pricing-plan/g) || []).length, 1, 'Show one plan');
assert(html.includes('href="/pricing"'));
assert(pricingView.includes('class="pricing-page" id="pricing"') && !pricingView.includes(' hidden>'));
assert(pricingView.includes('href="/register"') && pricingView.includes('href="/#request-demo"'));
assert(pricingView.includes('href="/#why-features"'));
assert(pricingView.includes('$75') && pricingView.includes('per user / month'));
assert(pricingView.includes('$600') && pricingView.includes('per user / year'));
const pricingLayout = fs.readFileSync(path.join(root, 'resources/views/layouts/marketing-page.blade.php'), 'utf8');
for (const contract of ["@include('marketing.header')", "@include('marketing.footer')", "@include('marketing.styles')", "@stack('styles')", "@stack('scripts')"]) assert(pricingLayout.includes(contract));

// Compare billing options without creating a subscription or collecting payment.
const billingOptions = ['monthly', 'annual'].map(value => ({ value, checked: value === 'monthly', addEventListener(_event, callback) { this.change = callback; } }));
const billingSummary = { textContent: 'Monthly billing: $75 per user, billed each month.' };
const pricingPlan = { dataset: { billing: 'monthly' }, querySelector: () => billingSummary, querySelectorAll: () => billingOptions };
vm.runInNewContext(pricingScript, { document: { querySelectorAll: () => [pricingPlan] } });
billingOptions[0].checked = false; billingOptions[1].checked = true; billingOptions[1].change();
assert.equal(pricingPlan.dataset.billing, 'annual');
assert.equal(billingSummary.textContent, 'Annual billing: $600 per user, billed once a year ($50/month). Save $300 per user each year.');
billingOptions[0].change(); // An unchecked option must not change the comparison.
assert.equal(pricingPlan.dataset.billing, 'annual');
billingOptions[0].checked = true; billingOptions[1].checked = false; billingOptions[0].change();
assert.equal(pricingPlan.dataset.billing, 'monthly');
assert.equal(billingSummary.textContent, 'Monthly billing: $75 per user, billed each month.');
billingOptions[0].checked = false; billingOptions[1].checked = true;
vm.runInNewContext(pricingScript, { document: { querySelectorAll: () => [pricingPlan] } });
assert.equal(pricingPlan.dataset.billing, 'annual', 'Reflect restored billing selection on load');
vm.runInNewContext(pricingScript, { document: { querySelectorAll: () => [] } });

const loginView = fs.readFileSync(path.join(root, 'resources/views/auth/login.blade.php'), 'utf8');
const loginLayout = fs.readFileSync(path.join(root, 'resources/views/layouts/marketing-auth.blade.php'), 'utf8');
assert(loginView.startsWith("@extends('layouts.marketing-auth')"));
for (const contract of ["route('login')", "route('password.email')", '@csrf', 'name="workspace"', 'name="remember"', 'id="captcha_container"', 'autocomplete="current-password"']) {
  assert(loginView.includes(contract), `Login contract missing: ${contract}`);
}
for (const contract of ["@include('marketing.header')", "@include('marketing.footer')", "@include('marketing.styles')", "@yield('content')", "@stack('scripts')", "@include('partials.ats-app-head')", 'to-recover', 'back-to-login']) {
  assert(loginLayout.includes(contract), `Login layout contract missing: ${contract}`);
}
const signupView = fs.readFileSync(path.join(root, 'resources/views/saas/signup.blade.php'), 'utf8');
const signupLayout = fs.readFileSync(path.join(root, 'resources/views/layouts/marketing-register.blade.php'), 'utf8');
assert(signupView.startsWith("@extends('layouts.marketing-register')"));
for (const contract of ["route('register')", '@csrf', 'name="company_name"', 'name="name"', 'name="email"', 'name="password"', 'name="password_confirmation"', 'autocomplete="new-password"', "@error('password')", "old('company_name')", 'data-password-target="password"', 'id="password-strength-bar"']) {
  assert(signupView.includes(contract), `Registration contract missing: ${contract}`);
}
for (const contract of ["@include('marketing.header')", "@include('marketing.footer')", "@include('marketing.styles')", "@yield('content')", "session('status')", '$errors->any()', 'role="alert"']) {
  assert(signupLayout.includes(contract), `Registration layout contract missing: ${contract}`);
}
for (const match of signupView.matchAll(/<script>([\s\S]*?)<\/script>/g)) new vm.Script(match[1], { filename: 'registration-inline.js' });
const authParts = ['header', 'footer', 'styles'].map(part => fs.readFileSync(path.join(root, `resources/views/marketing/${part}.blade.php`), 'utf8'));
assert(authParts[0].includes('href="/#why-choose-us"') && authParts[0].includes('href="/#request-demo"'));
assert(authParts[1].includes('href="/#contact"'));
assert(authParts[1].includes('class="footer-privacy" href="/privacy-policy"'));
assert(!authParts[2].includes('/marketing/site.js'), 'Public enquiry runtime must not intercept login forms');
assert(authParts[2].includes('/marketing/chrome.js'));

// Shared navigation marks the current page and supports the mobile menu.
function chromeHarness(page) {
  const element = () => ({
    dataset: {}, attributes: {}, listeners: {}, focused: false,
    classList: { values: new Set(), add(value) { this.values.add(value); }, contains(value) { return this.values.has(value); } },
    setAttribute(key, value) { this.attributes[key] = value; }, getAttribute(key) { return this.attributes[key]; },
    addEventListener(event, callback) { this.listeners[event] = callback; }, focus() { this.focused = true; }, contains() { return false; },
  });
  const header = element(), toggle = element(), footer = element(), login = element(), pricing = element(), privacy = element(), trial = [element(), element()], body = element();
  body.classList.add('marketing-' + page);
  const listeners = {};
  vm.runInNewContext(chromeScript, {
    document: {
      body, querySelector: selector => ({ '.site-header': header, '.header-menu-toggle': toggle, '.footer-top': footer })[selector],
      querySelectorAll: selector => selector === '.header-trial' ? trial : selector === '.header-login' ? [login] : selector === '.header-pricing' ? [pricing] : selector === '.footer-privacy' ? [privacy] : [],
      addEventListener(event, callback) { listeners[event] = callback; },
    },
    window: { matchMedia: () => ({ matches: false, addEventListener() {} }), scrollTo() {} },
  });
  return { header, toggle, login, pricing, privacy, trial, listeners };
}
for (const name of ['login', 'register', 'pricing', 'privacy']) {
  const page = chromeHarness(name);
  assert.equal(page.login.attributes['aria-current'] === 'page', name === 'login');
  assert.equal(page.pricing.attributes['aria-current'] === 'page', name === 'pricing');
  assert.equal(page.privacy.attributes['aria-current'] === 'page', name === 'privacy');
  assert(page.trial.every(link => (link.attributes['aria-current'] === 'page') === (name === 'register')));
  page.toggle.listeners.click();
  assert.equal(page.header.dataset.menuOpen, 'true');
  page.listeners.keydown({ key: 'Escape' });
  assert.equal(page.header.dataset.menuOpen, 'false');
  assert.equal(page.toggle.attributes['aria-expanded'], 'false');
  assert(page.toggle.focused);
  page.toggle.listeners.click();
  page.header.listeners.click({ target: { closest: () => ({}) } });
  assert.equal(page.header.dataset.menuOpen, 'false');
}

const ids = [...html.matchAll(/\bid="([^"]+)"/g)].map(match => match[1]);
assert.equal(new Set(ids).size, ids.length, 'IDs must be unique');
for (const match of html.matchAll(/href="#([^"]+)"/g)) assert(ids.includes(match[1]) || match[1] === 'request-demo', `Unknown section ${match[1]}`);
for (const match of html.matchAll(/aria-(?:controls|labelledby)="([^"]+)"/g)) {
  for (const id of match[1].split(' ')) assert(ids.includes(id), `Unknown ARIA target ${id}`);
}
const elements = [];
const voidElements = new Set(['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr']);
for (const documentHtml of [html, policyHtml, policyPreview]) {
  for (const match of documentHtml.replace(/<!--[\s\S]*?-->/g, '').matchAll(/<\/?([a-z][a-z0-9-]*)\b[^>]*>/gi)) {
    const name = match[1].toLowerCase();
    if (voidElements.has(name) || /\/>$/.test(match[0])) continue;
    if (match[0].startsWith('</')) assert.equal(elements.pop(), name, 'HTML nesting');
    else elements.push(name);
  }
  assert.equal(elements.length, 0, 'No unclosed HTML elements');
}

// Exercise the actual page router, including the dedicated demo view and legacy links.
function routeHarness(hash) {
  const makeElement = id => ({
    id, dataset: {}, attributes: {}, hidden: false,
    classList: { values: new Set(), contains(name) { return this.values.has(name); }, toggle(name, on) { on ? this.values.add(name) : this.values.delete(name); } },
    setAttribute(name, value) { this.attributes[name] = value; }, removeAttribute(name) { delete this.attributes[name]; },
    addEventListener() {}, scrollIntoView() {},
  });
  const views = ['home', 'why-choose-us', 'how-it-works', 'pricing', 'request-demo', 'contact'].map(id => {
    const view = makeElement(id); view.classList.values.add('view'); view.closest = () => view; return view;
  });
  const targets = new Map(views.map(view => [view.id, view]));
  for (const [id, owner] of [['why-features', 'why-choose-us'], ['why-ai', 'why-choose-us'], ['why-communications', 'why-choose-us'], ['ai', 'how-it-works'], ['hiring-steps', 'how-it-works'], ['platform-overview', 'home']]) {
    const target = makeElement(id); target.closest = () => targets.get(owner); targets.set(id, target);
  }
  const header = makeElement('header'), toggle = makeElement('menu'), body = makeElement('body');
  const document = {
    body, title: '', addEventListener() {},
    getElementById: id => targets.get(id),
    querySelectorAll: selector => selector === '.view' ? views : [],
    querySelector: selector => selector === '.site-header' ? header : toggle,
  };
  let redirect;
  vm.runInNewContext(script.slice(0, script.indexOf('// The existing contact.php service')), {
    document, location: { hash, replace: url => redirect = url },
    window: { matchMedia: () => ({ matches: false, addEventListener() {} }), scrollTo() {}, addEventListener() {} },
  });
  return { visible: views.filter(view => !view.hidden).map(view => view.id), redirect, title: document.title };
}
for (const [hash, expected] of [['#home', 'home'], ['#why-choose-us', 'why-choose-us'], ['#why-ai', 'why-choose-us'], ['#features', 'why-choose-us'], ['#ai', 'how-it-works'], ['#how', 'how-it-works'], ['#hiring-steps', 'how-it-works'], ['#pricing', 'pricing'], ['#request-demo', 'request-demo'], ['#contact', 'contact']]) {
  assert.deepEqual(routeHarness(hash).visible, [expected], `Page navigation ${hash}`);
}
assert.equal(routeHarness('#request-demo').title, 'AssistMyHR – Request a demo');
assert.equal(routeHarness('#pricing').title, 'AssistMyHR – Pricing');
assert.equal(routeHarness('#signin').redirect, '/login');
assert.equal(routeHarness('#signup').redirect, '/register');

const publishedFiles = new Set();
for (const match of html.matchAll(/(?:src|href)="(\/marketing\/[^"?#]+)(?:\?[^"#]*)?"/g)) publishedFiles.add(match[1]);
for (const partial of [...authParts, pricingView, policyHtml]) {
  for (const match of partial.matchAll(/(?:src|href)="(\/marketing\/[^"?#]+)(?:\?[^"#]*)?"/g)) publishedFiles.add(match[1]);
}
assert(publishedFiles.size >= 14, 'All stylesheets, scripts and screenshots should be published');
for (const url of publishedFiles) {
  const file = path.join(root, 'public', url);
  assert(fs.existsSync(file), `Missing public asset ${url}`);
  if (file.endsWith('.css')) postcss.parse(fs.readFileSync(file, 'utf8'), { from: file });
  if (url.includes('/assets/')) {
    const source = path.join(root, 'html-designs/marketing-preview/assets', path.basename(file));
    assert(fs.readFileSync(source).equals(fs.readFileSync(file)), `Image changed: ${url}`);
  }
}

// Exercise the actual contact handler with a mock transport: no email is sent.
const enquirySource = fs.readFileSync(path.join(root, 'resources/frontend/enquiry.js'), 'utf8');
function contactHarness(fetchResponse) {
  const button = { disabled: false, textContent: 'Send enquiry →' };
  const status = { textContent: '', dataset: {} };
  const attributes = new Map();
  const calls = [];
  let submit, timeoutCallback, cleared = false, resets = 0, valid = true;
  const form = {
    action: 'https://example.test/contact.php',
    values: { name: 'Example Tester', email: 'tester@example.test', phone: '', company: '', topic: 'Live demo', message: 'Example enquiry', website: '' },
    querySelector: selector => selector.startsWith('button') ? button : status,
    reportValidity: () => valid,
    addEventListener: (_event, callback) => submit = callback,
    setAttribute: (name, value) => attributes.set(name, value),
    removeAttribute: name => attributes.delete(name),
    reset: () => resets++,
  };
  vm.runInNewContext(enquirySource, {
    document: { querySelectorAll: () => [form] },
    FormData: class { constructor(form) { this.values = form.values; } *[Symbol.iterator]() { yield* Object.entries(this.values); } },
    fetch: (url, options) => { calls.push({ url, options }); return fetchResponse(url, options); },
    AbortController, TypeError, SyntaxError,
    setTimeout: callback => { timeoutCallback = callback; return 1; },
    clearTimeout: () => cleared = true,
  });
  return { button, status, form, calls, attributes, submit: () => submit({ preventDefault() {} }), timeout: () => timeoutCallback(), invalid: () => valid = false, get resets() { return resets; }, get cleared() { return cleared; } };
}
const successResponse = { ok: true, json: async () => ({ success: true, message: 'Accepted for delivery' }) };
const success = contactHarness(async () => successResponse);
await success.submit();
assert.equal(success.calls[0].url, 'https://example.test/contact.php');
assert.equal(success.calls[0].options.headers['Content-Type'], 'application/json');
assert.deepEqual(JSON.parse(success.calls[0].options.body), success.form.values);
assert.equal(success.resets, 1);
assert.equal(success.status.dataset.state, 'success');
assert(!success.button.disabled && success.cleared && !success.attributes.has('aria-busy'));

const rejected = contactHarness(async () => ({ ok: false, json: async () => ({ success: false, message: 'Please check the email address.' }) }));
await rejected.submit();
assert.equal(rejected.status.textContent, 'Please check the email address.');
assert.equal(rejected.resets, 0);
assert.equal(rejected.status.dataset.state, 'error');
assert(!rejected.button.disabled);

const offline = contactHarness(async () => { throw new TypeError('Network unavailable'); });
await offline.submit();
assert.equal(offline.resets, 0);
assert(offline.status.textContent.includes('Your message is still in the form'));

let release;
const duplicate = contactHarness(() => new Promise(resolve => release = resolve));
const pending = duplicate.submit();
await duplicate.submit();
assert.equal(duplicate.calls.length, 1, 'Prevent duplicate submissions');
release(successResponse);
await pending;

const invalid = contactHarness(async () => successResponse);
invalid.invalid();
await invalid.submit();
assert.equal(invalid.calls.length, 0);

const timedOut = contactHarness((_url, options) => new Promise((_resolve, reject) => {
  options.signal.addEventListener('abort', () => reject(Object.assign(new Error('Timeout'), { name: 'AbortError' })));
}));
const waiting = timedOut.submit();
timedOut.timeout();
await waiting;
assert(timedOut.status.textContent.includes('Delivery could not be confirmed'));
assert.equal(timedOut.resets, 0);
assert(!timedOut.button.disabled && timedOut.cleared);

// Verify the generated HTML and every referenced asset over a loopback HTTP server.
const server = http.createServer((request, response) => {
  const pathname = new URL(request.url, 'http://localhost').pathname;
  if (pathname === '/') { response.setHeader('Content-Type', 'text/html; charset=utf-8'); response.end(html); return; }
  if (pathname === '/privacy-policy') { response.setHeader('Content-Type', 'text/html; charset=utf-8'); response.end(policyHtml); return; }
  if (!publishedFiles.has(pathname)) { response.writeHead(404); response.end(); return; }
  const file = path.join(root, 'public', pathname);
  const types = { '.css': 'text/css', '.js': 'text/javascript', '.png': 'image/png', '.webp': 'image/webp' };
  response.setHeader('Content-Type', types[path.extname(file)] || 'application/octet-stream');
  response.end(fs.readFileSync(file));
});
await new Promise((resolve, reject) => { server.once('error', reject); server.listen(0, '127.0.0.1', resolve); });
try {
  const origin = `http://127.0.0.1:${server.address().port}`;
  const home = await fetch(origin);
  assert.equal(home.status, 200);
  assert((await home.text()).includes('AI Candidate Search'));
  const policy = await fetch(origin + '/privacy-policy');
  assert.equal(policy.status, 200);
  assert((await policy.text()).includes('21. CONTACT US'));
  for (const url of publishedFiles) {
    const response = await fetch(origin + url);
    assert.equal(response.status, 200, url);
    assert((await response.arrayBuffer()).byteLength > 0, url);
  }
} finally {
  await new Promise(resolve => server.close(resolve));
}
console.log('PASS: complete supplied privacy text, draft metadata, safe text rendering, policy contents and footer links, pricing and shared navigation, login/registration form contracts, enquiry handling, ARIA, CSS, screenshots and published assets over HTTP. No enquiries sent.');
