# AssistMyHR staging

Use `https://staging.assistmyhr.com` for development and acceptance testing. Deploy `codex/staging` to that subdomain and release approved changes through a pull request to `main`. The examples use a second OpenLiteSpeed virtual host on the existing VPS. Keep the staging checkout outside the production document tree.

## Create the staging site

1. In DNS, point `staging.assistmyhr.com` to the VPS. Create a separate virtual host and site account for staging. The commands below assume the site's home is `/home/staging.assistmyhr.com`. Set the staging document root to `/home/staging.assistmyhr.com/ats/public`, enable the PHP 8.3 handler and .htaccess rewrites, and map the subdomain on the HTTP and HTTPS listeners. Issue a certificate for the staging hostname. Restrict access to the team using the hosting panel or an access proxy before enabling signup. [OpenLiteSpeed virtual host setup](https://docs.openlitespeed.org/config/) · [OpenLiteSpeed TLS setup](https://docs.openlitespeed.org/security/ssl/)

2. As a MySQL administrator, create the empty staging database and separate accounts with unique passwords. The grants for the last two accounts match tenant databases beginning with the literal prefix `atsstage_`. They are separate from the credentials used for live clients.

   ```sql
   CREATE DATABASE `atsstage_platform` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'atsstage_platform'@'127.0.0.1' IDENTIFIED BY 'REPLACE_PLATFORM_PASSWORD';
   CREATE USER 'atsstage_provision'@'127.0.0.1' IDENTIFIED BY 'REPLACE_PROVISION_PASSWORD';
   CREATE USER 'atsstage_tenants'@'127.0.0.1' IDENTIFIED BY 'REPLACE_TENANT_PASSWORD';
   GRANT ALL PRIVILEGES ON `atsstage\_platform`.* TO 'atsstage_platform'@'127.0.0.1';
   GRANT CREATE ON `atsstage\_%`.* TO 'atsstage_provision'@'127.0.0.1';
   GRANT ALL PRIVILEGES ON `atsstage\_%`.* TO 'atsstage_tenants'@'127.0.0.1';
   ```

3. As the staging site account, clone the staging branch. Use a read-only GitHub deploy key if the repository requires authentication.

   ```bash
   cd /home/staging.assistmyhr.com
   git clone --branch codex/staging --single-branch https://github.com/yogeshswami1010/assistmyhr.git ats
   cd ats
   install -m 600 .env.staging.example .env
   ```

   Fill in the three database passwords in staging's `.env` and confirm `DB_DATABASE=atsstage_platform`. Start with an empty database and use synthetic clients and candidates. Use a dedicated mail capture inbox if testing workspace SMTP. Keep live mailboxes, webhooks, signing credentials and other integration secrets out of this environment.

   ```bash
   composer install --no-dev --prefer-dist --optimize-autoloader
   php artisan key:generate
   php artisan migrate --force
   php artisan saas:install
   ```

   Enter a staging-only super admin email and password when prompted. Set `SAAS_ENABLED=true` in staging's `.env`, then run:

   ```bash
   php artisan optimize:clear
   php artisan config:cache
   php artisan saas:migrate --force
   ```

4. Open `https://staging.assistmyhr.com/superadmin/login`. Enable signup briefly under platform settings, register two test companies with addresses you control, verify them using the verification link in the staging mail log or the super admin verification action, then close signup. Check that a job, candidate and uploaded file in one staging workspace are inaccessible from the other. Verify staging uses its own cookie, storage and database, and that the live URL still serves the current production release.

## Release approved changes

1. Develop against `codex/staging` and deploy the changes to the staging checkout. Test with your synthetic workspaces before opening a pull request from `codex/staging` into `main`.
2. After approval, merge the pull request and record the release commit. Back up the live central and tenant databases and private files before deploying. Use the existing production procedure from the production checkout to deploy the approved commit, apply any migrations, rebuild caches and smoke test the live ATS. [Production deployment procedure](saas-deployment.md)
3. Promote code through Git. Keep each environment's `.env`, app key, database, uploads, cache, sessions and provider credentials in its own environment.
