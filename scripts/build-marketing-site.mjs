import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { fileURLToPath } from 'node:url';
import { renderPrivacyPolicy } from './marketing-policy.mjs';

// Build the hosted homepage from the approved standalone design. No dependencies needed.
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const preview = path.join(root, 'html-designs/marketing-preview');
const destination = path.join(root, 'public/marketing');
const read = file => fs.readFileSync(path.join(preview, file), 'utf8');
let html = read('index.html');
if (!html.includes(read('pricing.html').trim())) throw new Error('Pricing preview must match pricing.html before building.');
const policySource = read('privacy-policy.txt');
const policyMarkup = renderPrivacyPolicy(policySource);
const policyStyles = read('privacy-policy.css');
const styles = [...html.matchAll(/<link rel="stylesheet" href="([^"/]+\.css)"/g)].map(match => match[1]);
const assets = [...new Set([...html.matchAll(/(?:src|href)="(assets\/[^"?#]+)"/g)].map(match => match[1]))];

const previewScript = read('preview.js');
const contactStart = previewScript.indexOf("document.getElementById('contact-form').addEventListener");
const footerStart = previewScript.indexOf("document.querySelector('.footer-top').addEventListener");
if (contactStart < 0 || footerStart < contactStart) throw new Error('Preview runtime boundaries changed.');
let navigation = previewScript.slice(0, contactStart);
navigation = navigation.replace(
  "  const requested = location.hash.slice(1) || 'home';",
  `  const requested = location.hash.slice(1) || 'home';
  if (requested === 'signin' || requested === 'signup') {
    location.replace(requested === 'signin' ? '/login' : '/register');
    return;
  }`,
);
navigation = navigation.replace(
  "  views.forEach(view => view.hidden = view.id !== selected);",
  `  views.forEach(view => view.hidden = view.id !== selected);
  const pageTitles = { home: 'Applicant Tracking System', 'why-choose-us': 'Why choose us?', pricing: 'Pricing', 'request-demo': 'Request a demo', contact: 'Contact us' };
  document.title = 'AssistMyHR – ' + pageTitles[selected];`,
);
const enquiry = fs.readFileSync(path.join(root, 'resources/frontend/enquiry.js'), 'utf8');
const siteScript = navigation + '\n' + enquiry + '\n' + previewScript.slice(footerStart);
const chromeScript = previewScript.slice(0, previewScript.indexOf('function navigate()'))
  .replace("const views = [...document.querySelectorAll('.view')];\n", '')
  + '\n' + previewScript.slice(footerStart)
  + `\nconst currentPageLinks = { 'marketing-pricing': '.header-pricing', 'marketing-register': '.header-trial', 'marketing-login': '.header-login', 'marketing-privacy': '.footer-privacy' };
for (const [pageClass, selector] of Object.entries(currentPageLinks)) {
  if (document.body.classList.contains(pageClass)) {
    document.querySelectorAll(selector).forEach(link => {
      link.classList.add('active');
      link.setAttribute('aria-current', 'page');
    });
  }
}\n`;
const tabScript = read('platform-section.js');
const pricingScript = read('pricing.js');
const formStyles = `.contact-honeypot{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%)}
.form-status[data-state="error"]{color:#b2364c}.form-status[data-state="success"]{color:#21754c}
.form-card button:disabled{opacity:.65;cursor:wait;transform:none}.noscript-note{max-width:1296px;margin:20px auto;padding:20px;color:#16365e;background:#eef4ff;border-radius:8px}
`;
const hash = crypto.createHash('sha256').update(html).update(siteScript).update(chromeScript).update(tabScript).update(pricingScript).update(formStyles);
hash.update(policyMarkup).update(policyStyles);
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
html = html.replaceAll('href="privacy-policy.html"', 'href="/privacy-policy"');
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
<noscript><style>.view[hidden]{display:block!important}.platform-showcase[hidden]{display:block!important}.site-header nav{display:flex}.header-menu-toggle,.footer-top,#contact-form,#demo-form,.pricing-switch,.pricing-selection{display:none}</style><p class="noscript-note">For enquiries, email <a href="mailto:support@assistmyhr.com">support@assistmyhr.com</a> or call <a href="tel:+14372199151">+1 (437) 219-9151</a>.</p></noscript>`);
if (/Design preview|Preview enquiry|Preview only|id="signin"|id="signup"/.test(html)) throw new Error('Preview-only content remains in the hosted homepage.');

fs.mkdirSync(path.join(destination, 'assets'), { recursive: true });
styles.forEach(file => fs.writeFileSync(path.join(destination, file), read(file)));
assets.forEach(file => fs.copyFileSync(path.join(preview, file), path.join(destination, file)));
fs.writeFileSync(path.join(destination, 'site.js'), siteScript);
fs.writeFileSync(path.join(destination, 'chrome.js'), chromeScript);
fs.writeFileSync(path.join(destination, 'platform-section.js'), tabScript);
fs.writeFileSync(path.join(destination, 'pricing.js'), pricingScript);
fs.writeFileSync(path.join(destination, 'forms.css'), formStyles);
fs.writeFileSync(path.join(destination, 'privacy-policy.css'), policyStyles);
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
const pricingIds = new Set([...pricingSection.matchAll(/\bid="([^"]+)"/g)].map(match => match[1]));
const pricingView = pricingSection.replace('class="pricing-page view"', 'class="pricing-page"').replace(' hidden>', '>')
  .replace(/href="#([^"]+)"/g, (_match, id) => `href="${pricingIds.has(id) ? '#' : '/#'}${id}"`);
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
// Standalone policy pages keep native section links rather than the homepage hash router.
const policyDocument = (content, pageHeader, pageFooter, cssFiles, scriptFile, canonical = '') => `<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Privacy policy | AssistMyHR</title><meta name="description" content="Read the Assist My HR Privacy Policy covering personal information, privacy choices and data protection.">
${canonical ? `<link rel="canonical" href="${canonical}"><link rel="icon" href="/favicon/assistmyhr.svg">` : ''}
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
${cssFiles.map(file => `<link rel="stylesheet" href="${file}">`).join('\n')}
<script defer src="${scriptFile}"></script><noscript><style>.site-header nav{display:flex}.header-menu-toggle,.footer-top{display:none}</style></noscript>
</head><body class="marketing-auth marketing-privacy is-light">
${pageHeader}
<main>${content}</main>
${pageFooter}
</body></html>
`;
fs.writeFileSync(path.join(root, 'resources/frontend/privacy-policy.html'), policyDocument(policyMarkup, rootLinks(header), rootLinks(footer), [...authStyles, 'privacy-policy.css'].map(publicUrl), publicUrl('chrome.js'), 'https://assistmyhr.com/privacy-policy'));
const previewHtml = read('index.html');
const previewLinks = markup => markup.replaceAll('href="#', 'href="index.html#');
const previewHeader = previewHtml.match(/<header\b[\s\S]*?<\/header>/)[0];
const previewFooter = previewHtml.match(/<footer\b[\s\S]*?<\/footer>/)[0];
fs.writeFileSync(path.join(preview, 'privacy-policy.html'), policyDocument(renderPrivacyPolicy(policySource, 'index.html#home'), previewLinks(previewHeader), previewLinks(previewFooter), [...authStyles, 'privacy-policy.css'], 'page-chrome.js'));
fs.writeFileSync(path.join(preview, 'page-chrome.js'), chromeScript);
console.log(`Built public website ${version}: homepage, privacy policy, ${styles.length + 1} stylesheets, ${assets.length} images, real auth links and contact submission.`);
