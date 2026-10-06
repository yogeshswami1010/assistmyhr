# AssistMyHR SaaS deployment

This release adds company signup, separate client databases and private files, a separate super admin login, plans, trials, limits, suspension, renewal requests and audited manual subscription extensions. Existing ATS records become the `main` workspace. Your HTML website remains at `/`.

Billing is manual. Payment checkout, automatic charging, invoices and automatic paid renewals are not included. Prices start as free examples; configure your real plans in super admin.

## Pages

| URL | Purpose |
| --- | --- |
| `/` | Public frontend, including SaaS signup links when enabled |
| `/register` | Company and owner signup |
| `/pricing` | Public plans |
| `/saas/workspace/company-slug` | Shareable company login link |
| `/login?workspace=company-slug` | Company login |
| `/admin` | ATS for the workspace selected in the session |
| `/jobs?workspace=company-slug` | Company job board |
| `/account/subscription` | Plan, expiry, usage and renewal requests |
| `/account/integrations` | Company mailbox import configuration |
| `/superadmin/login` | Separate platform admin login |
| `/superadmin` | Clients and recent requests |
| `/superadmin/tenants/ID` | Access, plan, manual expiry extensions and audit history |
| `/superadmin/plans` | Create/edit plans and limits |
| `/superadmin/settings` | Signup and trial settings |

One workspace is selected per browser session. Switching companies clears the previous company login. Persistent “remember me” authentication is disabled in SaaS mode. Client accounts cannot authenticate as platform administrators.

SaaS mode sanitizes job descriptions, legal terms and custom-page HTML. Arbitrary custom theme CSS is not rendered; use the standard theme controls. This prevents client-supplied active content from sharing the platform's browser origin.

## First VPS activation

Use a staging copy first. Local tests use isolated SQLite databases; MySQL provisioning, installed dependencies, mail and the vhost must also be checked on the VPS before advertising signup.

1. Back up the existing database, `.env`, `public/user-uploads`, `storage/app/private/candidate-calls` and vhost configuration outside the public web root. Preserve the existing `APP_KEY`, because encrypted integrations depend on it.

2. The checkout remains `/home/assistmyhr.com/public_html/ats`. Set the web document root to **`/home/assistmyhr.com/public_html/ats/public`**. Neither the checkout nor `storage` may be served as the document root. Keep OpenLiteSpeed rewrite and auto-load `.htaccess` enabled. Use PHP 8.3 for both CLI and the site's handler.

3. Update as the site account. Correct checkout ownership first if necessary:

```bash
cd /home/assistmyhr.com/public_html/ats
sudo -u assis2045 php artisan down
sudo -u assis2045 git pull --ff-only origin main
sudo -u assis2045 composer install --no-dev --prefer-dist --optimize-autoloader
sudo -u assis2045 php artisan optimize:clear
sudo -u assis2045 php artisan migrate --force
```

4. Using your MySQL administrator, create two accounts. Replace the example passwords with different strong passwords. These examples assume `DB_HOST=127.0.0.1`; account hosts must match your server configuration. The escaped underscore in the database grant deliberately limits matching to the literal `assistmyhr_` prefix.

```sql
CREATE USER 'ats_provision'@'127.0.0.1' IDENTIFIED BY 'REPLACE_WITH_PROVISION_PASSWORD';
CREATE USER 'ats_tenants'@'127.0.0.1' IDENTIFIED BY 'REPLACE_WITH_RUNTIME_PASSWORD';
GRANT CREATE ON `assistmyhr\_%`.* TO 'ats_provision'@'127.0.0.1';
GRANT ALL PRIVILEGES ON `assistmyhr\_%`.* TO 'ats_tenants'@'127.0.0.1';
```

Keep the existing `DB_*` settings and original database access. That database stores the platform registry and the existing `main` ATS. New clients use the tenant account; the provisioning account connects without selecting a database and creates new client databases. If you change the prefix, adjust its grants too.

5. Edit `.env` on the VPS; never commit it or share its passwords:

```dotenv
APP_URL=https://assistmyhr.com
APP_NAME=AssistMyHR
APP_ENV=production
APP_DEBUG=false
REDIRECT_HTTPS=true
SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
CACHE_DRIVER=file
QUEUE_DRIVER=sync
SAAS_ENABLED=false
SAAS_DATABASE_PREFIX=assistmyhr
SAAS_PROVISIONING_USERNAME=ats_provision
SAAS_PROVISIONING_PASSWORD=YOUR_PROVISION_PASSWORD
SAAS_TENANT_DB_USERNAME=ats_tenants
SAAS_TENANT_DB_PASSWORD=YOUR_RUNTIME_PASSWORD
```

Configure `MAIL_DRIVER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME` for signup verification. Client recruitment emails use each company's ATS SMTP settings. If verification email delivery fails, super admin can approve the owner after independently verifying their identity, with a reason recorded.

6. Install while the website is in maintenance mode:

```bash
sudo -u assis2045 php artisan config:clear
sudo -u assis2045 php artisan saas:install --email=yogeshswami1010@gmail.com
```

Enter a new super admin password interactively (minimum 12 characters). Installation preserves existing records, gives `main` an unlimited subscription and indexes its jobs API keys. It copies existing uploads to `storage/app/saas/WORKSPACE_UUID/uploads`, keeps the originals, privately backs up their original `.htaccess`, and denies direct access to old `public/user-uploads` URLs. New file URLs are signed and expire after 60 minutes.

Re-running installation does not reset passwords/plans or overwrite copied private files. Resolve copying errors or upload symlinks before continuing.

7. Set `SAAS_ENABLED=true`, then:

```bash
sudo -u assis2045 php artisan optimize:clear
sudo -u assis2045 php artisan config:cache
sudo -u assis2045 php artisan saas:migrate --force
sudo -u assis2045 php artisan up
```

8. Open `https://assistmyhr.com/superadmin/login`. Configure plans and the trial plan. Signup starts **closed**. Temporarily open it in Settings to test two companies; close it if provisioning or email fails. A failed provisioning attempt stays marked `failed`, retains its database for investigation, and cannot be activated through the status form.

## Launch checks

- The frontend works at `/`; the existing `main` account still opens its original ATS.
- Two company signups get different databases and private directories. Owners initially need email verification. No original users, candidates, jobs, employers or integration secrets appear in new workspaces.
- Add a job/candidate to company A. Company B must not see it, including when requesting the same numeric ID.
- Company credentials cannot access super admin. Unauthenticated platform requests require platform login.
- Test a manual extension, suspension/reactivation and expiry. Expired/suspended clients can log in and view their subscription; ATS, public jobs and API access are blocked.
- Each company's jobs API key returns only that workspace's active jobs. Open an `apply_url` in a fresh browser and confirm the correct company application form.
- Old `/user-uploads/...` URLs return 403 from OpenLiteSpeed. Signed resume URLs work, and modified signatures fail. This protection depends on the vhost honoring `.htaccess`.
- Configure each company's SMTP and AI keys. For candidate replies, configure its IMAP host/port under Workspace integrations. Import uses that company's SMTP mailbox credentials and requires the PHP IMAP extension.
- Private storage remains outside the served document root; `storage` and `bootstrap/cache` are writable by `assis2045`.

## Manual extensions and limits

Super admin → Clients → select company → **Extend subscription manually**. Add days OR choose a later expiry date, optionally change the plan, and enter a reason. Days count from the current future expiry, or now if already expired. A date expires at 23:59:59 UTC. Finite expiries cannot be shortened by extension. Extending activates the subscription but does not silently reactivate a suspended workspace; update its access separately.

Changing a client's plan alone preserves expiry. Editing a plan's limits affects every client on that plan. Limits count total stored users/jobs/candidates, including retained candidate records. Local uploads and call recordings are metered; company-owned external S3 storage is excluded from the local usage meter.

## Jobs API

`/api/jobs` and `/api/jobs/ID` select the workspace through the central hashed Bearer-key index. An “all jobs” key includes all active employers **inside its own client ATS**, never other SaaS companies. Company-only keys still work. Public detail/application URLs include `workspace=company-slug`.

Keep keys on the third-party website's server. Its job detail page calls `/api/jobs/ID` with the same key, and Apply redirects to `apply_url`. Expiry/suspension blocks API access; key rotation/deletion updates the central index.

## Scheduler and future updates

Run the scheduler once per minute as the site account:

```cron
* * * * * cd /home/assistmyhr.com/public_html/ats && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

SaaS maintenance runs email/review tasks separately for accessible workspaces each minute, and job expiry/retention at 02:00. Previous single-database schedule entries are disabled. Queues are synchronous; stop existing asynchronous workers before activation. Do not use Octane or persistent application workers: this version assumes ordinary PHP request processes.

For subsequent deployments: back up the central and all tenant databases/private storage, enter maintenance, pull/install, run `php artisan migrate --force` for central/main, then `php artisan saas:migrate --force` for other clients, clear/cache configuration, and bring the site up. Stop if a migration fails. Tenant migrations use the current default connection; central migrations must not be replayed for each client.

Never run `migrate:fresh` or a destructive rollback against production. Setting SaaS disabled is not a full rollback: revert configuration, database/file backups and original upload access rules together while in maintenance mode.

## Developer tests

```bash
php tests/saas-integration.php /path/to/vendor/autoload.php
php tests/jobs-api-integration.php /path/to/vendor/autoload.php
```

Tests require Laravel 12, cviebrock/eloquent-sluggable and shanmuga/laravel-entrust. They create temporary databases and cover isolation, platform authentication, quotas, workspace switching, API routing, expiry/suspension, extensions and signed files. They do not verify VPS MySQL grants, SMTP delivery or OpenLiteSpeed; complete the launch checks.
