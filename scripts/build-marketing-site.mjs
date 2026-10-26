import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { fileURLToPath } from 'node:url';

// Build the hosted homepage from the approved standalone design. No dependencies needed.
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const preview = path.join(root, 'html-designs/marketing-preview');
const destination = path.join(root, 'public/marketing');
const read = file => fs.readFileSync(path.join(preview, file), 'utf8');
let html = read('index.html');
if (!html.includes(read('pricing.html').trim())) throw new Error('Pricing preview must match pricing.html before building.');
const styles = [...html.matchAll(/<link rel="stylesheet" href="([^"/]+\.css)"/g)].map(match => match[1]);
const assets = [...new Set([...html.matchAll(/(?:src|href)="(assets\/[^"?#]+)"/g)].map(match => match[1]))];

const previewScript = read('preview.js');
const contactStart = previewScript.indexOf("document.getElementById('contact-form').addEventListener");
const footerStart = previewScript.indexOf("document.querySelector('.footer-top').addEventListener");
if (contactStart < 0 || footerStart < contactStart) throw new Error('Preview runtime boundaries changed.');
let navigation = previewScript.slice(0, contactStart);
navigation = navigation.replace(
  "  const target = location.hash.slice(1) || 'home';",
  `  const requested = location.hash.slice(1) || 'home';
  if (requested === 'signin' || requested === 'signup') {
    location.replace(requested === 'signin' ? '/login' : '/register');
    return;
  }
  const legacyAnchors = { top: 'home', dashboard: 'home', features: 'why-features', how: 'how-it-works', communications: 'why-communications' };
  const target = legacyAnchors[requested] || requested;`,
);
navigation = navigation.replace(
  "  views.forEach(view => view.hidden = view.id !== selected);",
  `  views.forEach(view => view.hidden = view.id !== selected);
  const pageTitles = { home: 'Applicant Tracking System', 'why-choose-us': 'Why choose us?', 'how-it-works': 'How it works', pricing: 'Pricing', 'request-demo': 'Request a demo', contact: 'Contact us' };
  document.title = 'AssistMyHR – ' + pageTitles[selected];`,
);
const enquiry = fs.readFileSync(path.join(root, 'resources/frontend/enquiry.js'), 'utf8');
const siteScript = navigation + '\n' + enquiry + '\n' + previewScript.slice(footerStart);
const chromeScript = previewScript.slice(0, previewScript.indexOf('function navigate()'))
  .replace("const views = [...document.querySelectorAll('.view')];\n", '')
  + '\n' + previewScript.slice(footerStart)
  + `\nconst accountLink = document.body.classList.contains('marketing-pricing') ? '.header-pricing' : document.body.classList.contains('marketing-register') ? '.header-trial' : '.header-login';
document.querySelectorAll(accountLink).forEach(link => {
  link.classList.add('active');
  link.setAttribute('aria-current', 'page');
});\n`;
const tabScript = read('platform-section.js');
const pricingScript = read('pricing.js');
const formStyles = `.contact-honeypot{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%)}
.form-status[data-state="error"]{color:#b2364c}.form-status[data-state="success"]{color:#21754c}
.form-card button:disabled{opacity:.65;cursor:wait;transform:none}.noscript-note{max-width:1296px;margin:20px auto;padding:20px;color:#16365e;background:#eef4ff;border-radius:8px}
`;
const hash = crypto.createHash('sha256').update(html).update(siteScript).update(chromeScript).update(tabScript).update(pricingScript).update(formStyles);
styles.forEach(file => hash.update(read(file)));
assets.forEach(file => hash.update(fs.readFileSync(path.join(preview, file))));
const version = hash.digest('hex').slice(0, 12);
const publicUrl = file => `/marketing/${file}?v=${version}`;

html = html.replace('<title>AssistMyHR · Design preview</title>', '<title>AssistMyHR – Applicant Tracking System</title>');
html = html.replace('</head>', '<meta name="description" content="Connect your jobs, candidates and hiring team with AssistMyHR. Explore AI candidate search, recruitment reporting and applicant tracking in one workspace."><link rel="canonical" href="https://assistmyhr.com/"><link rel="icon" href="/favicon/assistmyhr.svg"><link rel="stylesheet" href="' + publicUrl('forms.css') + '"></head>');
html = html.replace(/<section class="auth view" id="signin">[\s\S]*?<\/section>\s*/, '');
html = html.replace(/<section class="signup light view" id="signup">[\s\S]*?<\/section>\s*/, '');
html = html.replaceAll('href="#signin"', 'href="/login"').replaceAll('href="#signup"', 'href="/register"');
html = html.replaceAll('href="#pricing"', 'href="/pricing"');
html = html.replace(/<!-- Local preview section\.[\s\S]*?-->/g, '<!-- Hiring platform -->');
html = html.replace(/<!-- Dedicated local page\.[\s\S]*?-->/g, '<!-- Why choose AssistMyHR -->');
html = html.replaceAll('Product screenshots supplied for this preview. Other product examples are illustrative.', 'Screenshots show an example workspace.');
html = html.replaceAll('Your supplied dashboard screenshot · Click to view full size', 'Dashboard overview · Click to view full size');
html = html.replaceAll('View the supplied AssistMyHR dashboard screenshot', 'View the AssistMyHR dashboard screenshot');

let formCount = 0;
html = html.replace(/<form\b[^>]*id="(?:contact|demo)-form"[^>]*>[\s\S]*?<\/form>/g, originalForm => {
let form = originalForm;
formCount++;
form = form.replace(/id="((?:contact|demo)-form)"/, 'id="$1" data-enquiry-form action="/contact.php" method="post"');
form = form.replace('<input required autocomplete="name"', '<input name="name" maxlength="150" required autocomplete="name"');
form = form.replace('<input required type="email"', '<input name="email" maxlength="254" required type="email"');
form = form.replace('<input type="tel"', '<input name="phone" maxlength="60" type="tel"');
form = form.replace('<input autocomplete="organization"', '<input name="company" maxlength="200" autocomplete="organization"');
form = form.replace(/<select>[\s\S]*?<\/select>/, '<select name="topic"><option>Live demo</option><option>Pricing</option><option>AI CV Search</option><option>Temp Staffing</option><option>Something else</option></select>');
form = form.replace('<textarea required', '<textarea name="message" maxlength="10000" required');
form = form.replace(/(<button\b[^>]*type="submit"[^>]*>)/, '<div class="contact-honeypot" aria-hidden="true"><label>Leave this field empty<input name="website" tabindex="-1" autocomplete="off"></label></div>$1');
form = form.replace('>Preview enquiry', '>Send enquiry');
form = form.replace('<p class="form-status" role="status">Preview only. Your enquiry will not be sent.</p>', '<p class="form-status" role="status" aria-live="polite"></p>');
return form;
});
if (formCount !== 2) throw new Error('Expected contact and demo enquiry forms.');

styles.forEach(file => html = html.replaceAll(`href="${file}"`, `href="${publicUrl(file)}"`));
assets.forEach(file => html = html.replaceAll(`"${file}"`, `"${publicUrl(file)}"`));
html = html.replace('src="preview.js"', `src="${publicUrl('site.js')}"`);
html = html.replace('src="platform-section.js"', `src="${publicUrl('platform-section.js')}"`);
html = html.replace('src="pricing.js"', `src="${publicUrl('pricing.js')}"`);
html = html.replace('<body class="is-home">', `<body class="is-home">
<noscript><style>.view[hidden]{display:block!important}.platform-showcase[hidden]{display:block!important}.site-header nav{display:flex}.header-menu-toggle,.footer-top,#contact-form,#demo-form,.pricing-switch,.pricing-selection{display:none}</style><p class="noscript-note">For enquiries, email <a href="mailto:assistmyday@gmail.com">assistmyday@gmail.com</a> or call <a href="tel:+14372199151">+1 (437) 219-9151</a>.</p></noscript>`);
if (/Design preview|Preview enquiry|Preview only|id="signin"|id="signup"/.test(html)) throw new Error('Preview-only content remains in the hosted homepage.');

fs.mkdirSync(path.join(destination, 'assets'), { recursive: true });
styles.forEach(file => fs.writeFileSync(path.join(destination, file), read(file)));
assets.forEach(file => fs.copyFileSync(path.join(preview, file), path.join(destination, file)));
fs.writeFileSync(path.join(destination, 'site.js'), siteScript);
fs.writeFileSync(path.join(destination, 'chrome.js'), chromeScript);
fs.writeFileSync(path.join(destination, 'platform-section.js'), tabScript);
fs.writeFileSync(path.join(destination, 'pricing.js'), pricingScript);
fs.writeFileSync(path.join(destination, 'forms.css'), formStyles);
fs.writeFileSync(path.join(root, 'resources/frontend/index.html'), html);
// Reuse the same navigation and footer on Laravel's real login and registration pages.
const partials = path.join(root, 'resources/views/marketing');
fs.mkdirSync(partials, { recursive: true });
const rootLinks = markup => markup.replaceAll('href="#', 'href="/#');
const header = html.match(/<header\b[\s\S]*?<\/header>/)?.[0];
const footer = html.match(/<footer\b[\s\S]*?<\/footer>/)?.[0];
if (!header || !footer) throw new Error('Shared marketing navigation not found.');
fs.writeFileSync(path.join(partials, 'header.blade.php'), rootLinks(header) + '\n');
fs.writeFileSync(path.join(partials, 'footer.blade.php'), rootLinks(footer) + '\n');
const authStyles = ['header.css', 'footer.css', 'typography.css', 'auth-shell.css'];
fs.writeFileSync(path.join(partials, 'styles.blade.php'), authStyles.map(file => `<link rel="stylesheet" href="${publicUrl(file)}">`).join('\n') + `\n<script defer src="${publicUrl('chrome.js')}"></script>\n`);
// The public /pricing route uses the same approved content, with real auth and root links.
const pricingSection = html.match(/<section class="pricing-page view" id="pricing"[\s\S]*?<\/section>/)?.[0];
if (!pricingSection) throw new Error('Pricing content not found.');
const pricingView = rootLinks(pricingSection.replace('class="pricing-page view"', 'class="pricing-page"').replace(' hidden>', '>'));
fs.writeFileSync(path.join(root, 'resources/views/saas/pricing.blade.php'), `{{-- Generated by scripts/build-marketing-site.mjs. Edit the standalone pricing preview. --}}
@extends('layouts.marketing-page')
@section('title', 'Pricing | AssistMyHR')
@section('description', 'Try AssistMyHR free for 30 days. No credit card required. One plan at $75 per user/month or $600 per user/year.')
@section('body-class', 'marketing-pricing')
@push('styles')
<link rel="canonical" href="https://assistmyhr.com/pricing">
<link rel="stylesheet" href="${publicUrl('pricing.css')}">
@endpush
@section('content')
${pricingView}
@endsection
@push('scripts')
<script defer src="${publicUrl('pricing.js')}"></script>
@endpush
`);
console.log(`Built public homepage ${version}: ${styles.length} stylesheets, ${assets.length} images, real auth links and contact submission.`);
