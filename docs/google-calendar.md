# Google Calendar in Interview Schedule

Each signed-in ATS user with `view_schedule` permission can connect the Google account matching their ATS email address. Existing primary-calendar events appear alongside interviews in the calendar view, visible only to that user. No Google events are copied into interview records. ATS interviews are not exported to Google.

The integration requests read-only Calendar event access and email identity. It expands recurring events within the displayed date range, preserves all-day dates (including Google's exclusive end date), and displays timed events in the browser's timezone. Private events show as Busy; declined invitations and cancelled events are omitted. Notes, attendees and meeting links are not sent to the browser. Google events cannot be edited, dragged or deleted from ATS. Disconnect removes the locally stored tokens; the user may also revoke app access in their Google Account.

## Backend setup

1. In the Google Cloud project, enable the Google Calendar API and configure the OAuth consent screen. During testing, add the administrators as test users. Request `openid`, `email` and `https://www.googleapis.com/auth/calendar.events.readonly`.
2. Create an OAuth client of type **Web application**. Add this exact authorized redirect URI, replacing the origin with the deployed `APP_URL`:
   `https://assistmyhr.com/admin/interview-schedule/google-calendar/callback`
   For local development with Artisan, register `http://127.0.0.1:8000/admin/interview-schedule/google-calendar/callback` as well and use that same host in the browser and `APP_URL`.
3. If both `GOOGLE_CALENDAR_CLIENT_ID` and `GOOGLE_CALENDAR_CLIENT_SECRET` are blank, the app uses `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` from Google signup. Add the calendar callback from step 2 to that OAuth client as well. To use a separate Calendar client, set both variables:

   ```dotenv
   GOOGLE_CALENDAR_CLIENT_ID=your-web-client-id
   GOOGLE_CALENDAR_CLIENT_SECRET=your-client-secret
   ```

4. Apply the migration to the main database and existing client databases:

   ```sh
   php artisan migrate --force
   php artisan saas:migrate --force
   php artisan optimize:clear
   ```

   Run `saas:migrate` only for SaaS installations. Newly provisioned clients inherit the schema without existing calendar tokens. Preserve `APP_KEY`, which encrypts stored tokens.
5. Open **Interview Schedule → Connect Google Calendar** and sign in with the Google account matching the ATS email. Google consent must be completed by someone authorized to access that mailbox. The app credentials remain backend configuration; users never enter API keys.

The calendar loads Google events on opening or changing the visible date range. **Refresh events** loads current Google changes without reloading the page. If Google is unavailable, ATS interviews still display and a separate error explains how to retry. There is no background polling or calendar write permission. Only the primary calendar is included in this version.

After email verification, Google registrants go to Interview Schedule when Calendar setup is ready and their account can view schedules. The app preselects the linked Google account. Users grant read-only Calendar access from Interview Schedule. Production OAuth verification and consent-screen publishing may be needed for external users. Google testing-mode authorizations can expire. Follow Google's current requirements.

## Verification

`php tests/google-calendar-smoke.php` uses in-memory SQLite databases and fake Google responses. It makes no live Google requests. It covers account matching, encrypted tokens, recurring-instance pagination, offsets/all-day dates, cancelled/private events, refresh, user/tenant isolation, disconnect, and OAuth permission/state checks. Composer dependencies must be installed.

References: [Google OAuth web server flow](https://developers.google.com/identity/protocols/oauth2/web-server), [Events list API](https://developers.google.com/workspace/calendar/api/v3/reference/events/list).
