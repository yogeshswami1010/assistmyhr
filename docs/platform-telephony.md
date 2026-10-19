# Platform calling and SMS

In SaaS mode, clients cannot edit calling/SMS provider credentials or calling configuration. Super Admin > Settings stores the shared Telnyx API key (encrypted) and webhook public key. Super Admin > Clients > client details enables calling/SMS and assigns SIP connection ID and sender numbers. Features remain available in candidate profiles when configured. All clients start disabled until assigned centrally; old client configuration is ignored in SaaS mode. Standalone mode retains the original configuration.

Each client must have a dedicated SMS sender number. In that number's Telnyx
Messaging Profile, configure the inbound webhook as
`https://assistmyhr.com/telnyx-webhook` (also shown in Super Admin Settings and
each client's Calling & SMS tab). Save the account's webhook public key in Super
Admin Settings. Sending SMS alone does not verify this inbound setup.

The webhook validates the Telnyx signature and timestamp before selecting the
client by its assigned receiving number. It never uses a browser session or
falls back to the main workspace. Unknown, disabled, expired or ambiguous
recipients are rejected. Existing URLs with `?workspace=client-slug` still work
when that workspace matches the number's owner. A profile serving multiple
clients should use the shared URL without a workspace query.

Replies are matched to the latest outbound conversation for that sender and
recipient pair, or to a candidate with the matching phone number in that
workspace. Duplicate deliveries do not add duplicate messages. The candidate's
open SMS tab polls for new messages every seven seconds. Deferred recruiter
notifications restore the same client's database, SMTP and workspace links.

If replies are missing, inspect the Telnyx inbound webhook delivery result:
401 indicates signature/public-key/timestamp verification failed; 403 indicates
the receiving number or workspace assignment is unavailable; 200 acknowledges
the event. Unmatched candidate events are logged by message ID. Configure the
Messaging Profile's SMS webhook, rather than a voice application's webhook.

The API key is never sent to clients; browser calls use generated temporary
Telnyx tokens. A changed SIP connection invalidates the saved WebRTC credential
ID for that client.

No database migration is required; configuration uses the existing central platform settings table. Deploy with git pull and optimize:clear, then configure the provider and client assignments. SMS/calling requires a properly configured Telnyx account, active client subscription, voice/SMS enabled numbers and a supported US/Canada phone format. Live calls and SMS were not sent during tests.
