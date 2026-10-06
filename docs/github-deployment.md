# VPS deployment

The public homepage is served at `/`, ATS login at `/login`, and the authenticated
dashboard at `/admin`. The web document root must be the repository's `public`
directory.

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
