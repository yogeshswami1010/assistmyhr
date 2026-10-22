# Client ATS app installation

Each client can select **Install ATS app** on the dashboard, beside **Copy link**.
Chrome shows its native installation prompt when available. Otherwise the button
explains how to install from the browser menu. The same manifest is available on
the company sign-in page. Serve the production site over HTTPS.

Each workspace has its own manifest ID and company name. Its app icon launches
`/admin/dashboard?workspace=the-company-slug`, so reopening an app selects that
client's database before authentication. The app uses the AssistMyHR logo and
opens in a standalone window. It needs an internet connection; ATS pages and
candidate records are not stored in an offline service-worker cache.

**Keep me signed in on this device** is enabled initially on client sign-in and
can be switched off. A successful sign-in with this option uses Laravel's
encrypted, HTTP-only remember cookie for 30 days. Initial company registration
also remembers its newly signed-in owner. Existing sessions continue working;
existing users sign in once with the option enabled to obtain the new cookie.

Remember cookies have a separate name for each workspace UUID. The old shared
remember cookie is discarded. A remembered client is restored only after its
workspace is selected; switching app icons cannot reuse another client's session
ID. Explicit logout expires that client's cookie and rotates its remember token.
Super-admin support access remains temporary and does not create a remember
cookie. Subscription and suspension checks still apply when the app reopens.
Clearing browser cookies or using a different browser profile requires sign-in.

No database migration or asset compilation is needed for this feature. Pull the
code, run `php artisan optimize:clear`, then `php artisan config:cache` as the site
user. Ensure `SESSION_SECURE_COOKIE=true` in the production HTTPS environment.

Verification: run `php tests/saas-integration.php [autoload-path]` for persistent
sign-in, encrypted-cookie isolation, logout, suspension, manifest and icon checks.
The installation prompt UI can be checked with `node tests/ats-app-install.cjs`.
For Chrome manifest validation, give the PHP suite a second argument containing a
temporary fixture filename, then run
`node tests/ats-app-install.cjs [playwright-path] [fixture-filename]`.

Browser references: [Chrome installation criteria](https://web.dev/articles/install-criteria),
[manifest IDs](https://developer.chrome.com/docs/capabilities/pwa-manifest-id).
