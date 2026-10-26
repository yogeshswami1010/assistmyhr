# VPS deployment

The public homepage is served at `/`, ATS login at `/login`, and the authenticated
dashboard at `/admin`. The web document root must be the repository's `public`
directory.

## Public homepage

The approved blue marketing design replaces `resources/frontend/index.html`.
Its versioned CSS, JavaScript, logo and screenshots are committed under
`public/marketing/`. The original editable preview remains in
`html-designs/marketing-preview/`. After editing that preview, regenerate the
hosted files with `node scripts/build-marketing-site.mjs` before committing.
The server does not need Node.js to serve the committed output.
Run `node tests/marketing-site-check.mjs` in the development checkout to check
the generated assets, navigation references and contact submission handling.
Its enquiry transport is mocked and does not send email.

The homepage links to the existing `/login` and `/register` routes. Registration
continues to respect the platform's existing signup settings. Enquiries use the
existing `/contact.php` service and its hosting mail configuration.
The dedicated `/#request-demo` view submits a Live demo enquiry through the same
service. General contact enquiries remain on `/#contact`.

The customer login uses `layouts.marketing-auth`, with generated header, footer
and asset includes under `resources/views/marketing/`. Run the marketing build
after editing shared navigation so the homepage and login stay in sync. Only the
login view's layout selection changes; its POST forms, CSRF, workspace context,
CAPTCHA, password recovery and remember-device controls are preserved.
The SaaS registration view uses `layouts.marketing-register` and the same shared
header/footer. Its existing signup fields, password controls, CSRF, errors and
platform availability rules are preserved. The shared account navigation marks
Log in or Start a free trial as current on the corresponding page.

The redesigned Contact us view includes direct email/phone links, a demo link
and a responsive enquiry form. It retains the existing contact service field
limits and supported topics; no mail configuration changes are needed.

`/pricing` now displays the single-plan marketing page instead of redirecting
to registration. Its generated Blade content comes from `pricing.html` in the
preview folder and uses `layouts.marketing-page` with the shared header/footer.
The approved rates are $75 per user/month or $600 per user/year (equivalent to
$50/month, saving $300/year versus monthly billing). The selector compares these
rates; trial signup and existing manual subscription management are unchanged.
No payment processor, stored plans, tenant limits or active subscriptions are
modified by this page. The public route retains the SaaS-enabled check.
Below the plan, Pricing includes the supplied Product Features overview, ten
categories, all feature checklist items and their key benefits. Category links
scroll within the page; the layout uses two columns on desktop and one on mobile.
This is a marketing content update, rather than implementation of the listed tools.
The pricing page advertises a 30-day free trial with no credit card required.
Before offering this publicly, set **Trial length in days** to **30** under
**Super admin → Settings**. The live database setting controls new trial expiry;
the page copy does not override it (the code fallback is 14 days). Signup does
not collect credit card details.

The public `/privacy-policy` route serves `resources/frontend/privacy-policy.html`.
The marketing build formats `html-designs/marketing-preview/privacy-policy.txt`
into that page and the standalone local preview, and adds the policy link to the
shared footer. All 21 sections, body placeholders and implementation notes are
preserved as document content. The effective-date, last-updated and version block
is omitted from the displayed page as requested; its original metadata remains
in the text source for reference. The Contact us section includes clickable
support@assistmyhr.com and +1 (905) 374-8878 links supplied by the user.
The footer's Privacy policy link appears under **Let’s get started**. Public email
links, enquiry fallback messages and the enquiry recipient in `public/contact-config.php`
use **support@assistmyhr.com**.

The four hiring steps and AI screening panel are now the final section of Home.
The separate How it works page, hero and menu entries are removed. Old
`/#how-it-works` and `/#how` links still open Home and scroll to `#hiring-steps`;
`/#ai` opens the AI panel on Home.

For this frontend-only replacement, update the VPS with:

```bash
cd /home/assistmyhr.com/public_html/ats
sudo -u assis2045 -H git pull --ff-only origin main
sudo -u assis2045 php artisan optimize:clear
```

No database migration or Composer dependency change is required for the homepage.
Check `/`, its final hiring/AI section, the Why choose us view, the three feature tabs,
`/login`, `/register`, and the contact service after deployment. Only submit a
real enquiry when you intend to send it to the business mailbox.

Purchase validation and the vendor's HTTP updater are not registered. The old
`/verify-purchase` bookmark redirects to `/login`; no purchase code is required
for login. The vendor package remains installed for its migration-check command
and existing view helpers, but Composer discovery is disabled for that package.
Do not enable its service provider manually.

After pulling this change on the VPS, rebuild Composer discovery and clear old
cached routes and views:

```bash
cd /home/assistmyhr.com/public_html/ats
sudo -u assis2045 -H git pull --ff-only origin main
sudo -u assis2045 -H composer install --no-dev --prefer-dist --optimize-autoloader
sudo -u assis2045 php artisan config:clear
sudo -u assis2045 php artisan route:clear
sudo -u assis2045 php artisan view:clear
sudo -u assis2045 php artisan queue:restart
```

Verify `/login` displays the login page, `/verify-purchase` redirects to login,
and unauthenticated `/admin` access requires login. Confirm authenticated dashboard
access and the Update Application settings page. No email, purchase code, or
license timestamp is submitted or fabricated by these application routes.

The former vendor updater and module update scripts are retired; deploy application
and module code through GitHub. This change does not alter any third-party license
terms or grant access to vendor downloads or paid modules.
