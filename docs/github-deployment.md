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

For this frontend-only replacement, update the VPS with:

```bash
cd /home/assistmyhr.com/public_html/ats
sudo -u assis2045 -H git pull --ff-only origin main
sudo -u assis2045 php artisan optimize:clear
```

No database migration or Composer dependency change is required for the homepage.
Check `/`, the Why choose us and How it works views, the three feature tabs,
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
