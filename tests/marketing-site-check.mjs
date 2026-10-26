import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import vm from 'node:vm';
import http from 'node:http';
import { fileURLToPath } from 'node:url';
import postcss from 'postcss';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const html = fs.readFileSync(path.join(root, 'resources/frontend/index.html'), 'utf8');
const script = fs.readFileSync(path.join(root, 'public/marketing/site.js'), 'utf8');
new vm.Script(script, { filename: 'site.js' });
new vm.Script(fs.readFileSync(path.join(root, 'public/marketing/platform-section.js'), 'utf8'), { filename: 'platform-section.js' });
assert(html.includes('href="/login"') && html.includes('href="/register"'));
assert(!/Design preview|Preview enquiry|Preview only|id="signin"|id="signup"/.test(html));
assert(!/signin-form|Password recovery is not connected|enquiry preview is complete/.test(script));
assert(html.includes('action="/contact.php" method="post"'));
const form = html.match(/<form class="form-card"[\s\S]*?<\/form>/)[0];
assert.deepEqual([...form.matchAll(/\bname="([^"]+)"/g)].map(match => match[1]).sort(), ['company', 'email', 'message', 'name', 'phone', 'topic', 'website']);
assert.deepEqual([...form.matchAll(/<option>([^<]+)<\/option>/g)].map(match => match[1]), ['Live demo', 'Pricing', 'AI CV Search', 'Temp Staffing', 'Something else']);

const ids = [...html.matchAll(/\bid="([^"]+)"/g)].map(match => match[1]);
assert.equal(new Set(ids).size, ids.length, 'IDs must be unique');
for (const match of html.matchAll(/href="#([^"]+)"/g)) assert(ids.includes(match[1]) || match[1] === 'request-demo', `Unknown section ${match[1]}`);
for (const match of html.matchAll(/aria-(?:controls|labelledby)="([^"]+)"/g)) {
  for (const id of match[1].split(' ')) assert(ids.includes(id), `Unknown ARIA target ${id}`);
}
const elements = [];
const voidElements = new Set(['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr']);
for (const match of html.replace(/<!--[\s\S]*?-->/g, '').matchAll(/<\/?([a-z][a-z0-9-]*)\b[^>]*>/gi)) {
  const name = match[1].toLowerCase();
  if (voidElements.has(name) || /\/>$/.test(match[0])) continue;
  if (match[0].startsWith('</')) assert.equal(elements.pop(), name, 'HTML nesting');
  else elements.push(name);
}
assert.equal(elements.length, 0, 'No unclosed HTML elements');

const publishedFiles = new Set();
for (const match of html.matchAll(/(?:src|href)="(\/marketing\/[^"?#]+)(?:\?[^"#]*)?"/g)) publishedFiles.add(match[1]);
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
    document: { getElementById: () => form },
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
  for (const url of publishedFiles) {
    const response = await fetch(origin + url);
    assert.equal(response.status, 200, url);
    assert((await response.arrayBuffer()).byteLength > 0, url);
  }
} finally {
  await new Promise(resolve => server.close(resolve));
}
console.log('PASS: auth links, contact contract and success/error/offline/duplicate/timeout handling, section links, ARIA, CSS, original screenshots, homepage HTTP and all published assets. No enquiries sent.');
