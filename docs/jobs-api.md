# Company jobs API

Open **Settings → Jobs API** (`/admin/settings/jobs-api`) as a user with
`manage_settings`. Choose an active company and create its key. Copy the key
immediately; only its SHA-256 hash and last eight characters are persisted.
Regenerate replaces the old key, Disable pauses it, and Revoke deletes it.
Inactive or deleted companies cannot use their keys.

## Client website integration

The client's backend requests `GET /api/jobs` over HTTPS with:

```http
Accept: application/json
Authorization: Bearer jobs_YOUR_KEY
```

Keys are read-only and scoped to their company. Caller-supplied `company_id`
parameters cannot select another company. Keep keys in the client's server
environment, not public HTML or browser JavaScript. No browser CORS access is
enabled; use your website backend as a proxy or render jobs on the server.

```bash
curl --get 'https://assistmyhr.com/api/jobs' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer YOUR_API_KEY' \
  --data-urlencode 'per_page=20' \
  --data-urlencode 'page=1'
```

Example PHP website integration (server-side only):

```php
<?php
$key = getenv('ASSISTMYHR_JOBS_API_KEY');
if (!$key) { throw new RuntimeException('Jobs API key is not configured.'); }
$curl = curl_init('https://assistmyhr.com/api/jobs?per_page=20&page=1');
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Accept: application/json', 'Authorization: Bearer '.$key],
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 15,
]);
$body = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
curl_close($curl);
if ($body === false || $status !== 200) {
    throw new RuntimeException('Jobs are temporarily unavailable.');
}
$feed = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
foreach ($feed['jobs'] as $job) {
    echo '<article><h2>'.htmlspecialchars($job['job_title'], ENT_QUOTES, 'UTF-8').'</h2>';
    echo '<p>'.htmlspecialchars($job['location'], ENT_QUOTES, 'UTF-8').'</p>';
    echo '<a href="'.htmlspecialchars($job['apply_url'], ENT_QUOTES, 'UTF-8').'">View and apply</a></article>';
}
```

Request the next page while `pagination.has_more` is true. The default page size
is 20; maximum is 100. Requests are limited by the existing API middleware to 60
per minute per IP. Handle 401 (invalid, revoked, disabled, or inactive company),
422 (invalid pagination), and 429 (rate limit). A successful empty feed is 200.

The response contains `status`, `total_jobs`, `jobs`, and `pagination` with
`current_page`, `per_page`, `last_page`, `has_more`. Each job includes its ID,
code, title, slug, plain-text description and requirements, location, category,
public company name, job type, public salary, experience, start and closing
dates, and `apply_url`. Hidden salaries are null and hidden company names are
empty. Treat all text as untrusted and escape it when rendering. Candidate data,
resumes, recruiter notes, and internal job settings are excluded.

Active jobs appear once their start date arrives, through their closing date
(inclusive); no closing date means indefinitely open. This uses the ATS timezone.
The Consortium/AssistMyDay flags no longer affect publication.

## Deployment and scope

Back up the production database, then deploy:

```bash
cd /home/assistmyhr.com/public_html/ats
sudo -u assis2045 -H git pull --ff-only origin main
sudo -u assis2045 -H composer install --no-dev --prefer-dist --optimize-autoloader
sudo -u assis2045 php artisan migrate --force
sudo -u assis2045 php artisan config:clear
sudo -u assis2045 php artisan route:clear
sudo -u assis2045 php artisan view:clear
```

No frontend build is needed for these Blade/PHP changes. Existing registration
records and the legacy flag columns are preserved. The Consortium Registrations
navigation tab and public registration intake routes are removed. Historical
registration profile routes remain for Temp Staffing and Trash. The old
`/api/assistmyday/jobs`, public `/assistmyday` routes, and unkeyed global jobs feed
are retired; update existing consumers to the new API.

This is company-level publication for the current single ATS. Its settings
permissions still use the existing global `manage_settings` permission. It does
not implement SaaS signup or isolated client accounts. Before opening client
accounts, tenant ownership must also be enforced on companies, jobs, settings
actions, public job boards, applications, files, and background tasks.

Integration checks:

```bash
php tests/jobs-api-integration.php /path/to/vendor/autoload.php
```

Tests use an isolated in-memory SQLite database and do not contact a live ATS.
