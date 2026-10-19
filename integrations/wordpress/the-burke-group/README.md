# The Burke Group career page

`careers.php` is the complete WordPress page template supplied for this client,
with its manual job list replaced by the client's AssistMyHR feed. It preserves
the existing header/footer calls, introductory copy, black and charcoal job
rows, gold buttons, location icon, responsive styles and closing sections.

## Install

1. Deploy the accompanying ATS update first. The signed widget URL now returns
   public job JSON when requested with `Accept: application/json`. Normal iframe
   requests still return the existing HTML widget.
2. Back up the client's current Careers template in its active WordPress theme.
3. Replace that template's contents with `careers.php`. Keep the existing filename
   and the **Careers** page-template selection. The client feed URL is included.
4. Clear WordPress/CDN page caches and open the Careers page. Exclude Careers
   from full-page caching, or set a short page-cache lifetime, if job updates
   must appear promptly.

The file is PHP for the WordPress theme, not code for a Custom HTML block. No
iframe or private Jobs API key is required. It uses the signed public widget URL
provided for this client's workspace. Keep that URL's version, workspace and
signature together; if the ATS feed key is regenerated, replace the URL at the
top of this template with the new widget `src` (using `&` rather than `&amp;`).

Each page loads up to 100 jobs. Previous/Next navigation handles further pages.
New page requests reuse a successful feed response for up to 60 seconds; errors
are retried after 15 seconds. An unavailable feed has its own message rather
than displaying an old manual list or claiming there are no vacancies. Hidden
ATS salaries stay hidden. More Details opens the matching ATS job detail page
in a new tab, where the applicant can continue to the application form.

Verification uses `tests/jobs-api-integration.php` for signed-feed authorization
and `tests/wordpress-careers.php` for template rendering with simulated WordPress
HTTP/cache functions. Production WordPress deployment is separate from the ATS.
