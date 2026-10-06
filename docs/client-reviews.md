# Candidate client reviews

Open a candidate profile and choose **Send profile to client** to open the compose popup. Team members with View Job Applications and Edit Job Applications permission can enter a client email, subject, formatted email message, and a separate optional **Client message**. The editor supports bold, italic, underline, bullet and numbered lists, fonts, and font sizes. Cancel, the close button, or Escape closes the popup and preserves the draft. After a successful send it closes and opens **Client Reviews**, which contains conversations only.

The email uses the existing AI Search SMTP account and the sending team member's email signature. Its button opens a private candidate-review page with the shared CV and a feedback form. The **Client message** appears above **Reply with your review**, preserving line breaks; the email message stays in the email rather than a separate recruitment-team introduction section on that page. Empty client messages show no card. Links expire after 30 days and can be revoked from the ATS conversation. Updating a candidate's CV later does not change the CV version already shared.

Revoked, expired, invalid, and missing review links show a friendly unavailable page asking the client to contact the recruitment team for a new link. The CV and feedback endpoints use the same page when access is unavailable, and do not expose candidate information or a debug trace.

Client feedback submitted on that page is saved immediately against the candidate and emailed only to the configured ATS SMTP sender mailbox (`AI_SEARCH_MAIL_FROM_ADDRESS`). Team members' profile email addresses do not receive these notifications. The client never receives a copy of their submitted feedback. If the ATS mailbox matches the client or is missing or invalid, the notification is skipped and the feedback remains in ATS. **Client Reviews** displays every invitation, client reply, sending team member, email status, and staff follow-up. It refreshes while open; new replies show an unread badge while another tab is active. Ordinary replies typed directly in a mail client are not imported into this review conversation; the email directs the client to the page's feedback form.

Use **Reply to client** for follow-ups and **Retry email** when outgoing delivery fails. Feedback remains saved if the staff notification cannot be delivered. The scheduled `client-reviews:notify` command retries pending staff notifications every minute through the existing Laravel scheduler.

## Deployment

```bash
git pull
php artisan migrate --force
php artisan optimize:clear
```

The usual scheduler cron entry must be present for notification retries:

```cron
* * * * * cd /home/virtualtecsolutions.com/public_html && php artisan schedule:run >> /dev/null 2>&1
```

## Checks

```bash
php tests/client-reviews-integration.php
node tests/client-reviews-ui.cjs
```

The integration test uses an isolated SQLite database and fake SMTP. If this checkout has no Composer dependencies, pass the path to an installed `vendor/autoload.php` as its argument. It verifies actual submission endpoints, rendering, signed URL checks, review scoping, permissions, expiry, revocation, and notification retries without contacting a real mailbox.
