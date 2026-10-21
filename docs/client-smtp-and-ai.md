# Client SMTP and DeepSeek settings

With SaaS enabled, every ATS workspace (including main) reads its SMTP settings from its own database. Standard notifications and candidate/AI Search emails use those settings. Missing client credentials never fall back to platform SMTP. Configure and test SMTP in each client account before sending recruitment emails.

In Settings > AI Settings, add or edit a key with provider `deepseek`, save its model name, and enable it. The active DeepSeek key with the lowest sort order (then ID) is selected. API keys remain encrypted. A blank model retains the existing `deepseek-chat` default. The Test key action checks the DeepSeek models endpoint; it verifies credentials, not whether a model supports every ATS feature.

Signup verification uses the separate platform mailer, configured with MAIL_* in the server environment. Keep those platform settings. Once client settings are saved, AI_SEARCH_MAIL_* can be removed. DEEPSEEK_API_KEY / DEEPSEEK_MODEL can supply a company AI default when no company key is saved. Standalone installations retain environment compatibility.

Deploy:

```sh
cd /home/assistmyhr.com/public_html/ats
sudo -u assis2045 git pull
sudo -u assis2045 php artisan optimize:clear
sudo -u assis2045 php artisan migrate --force
sudo -u assis2045 php artisan saas:migrate --force
sudo -u assis2045 php artisan config:cache
```

Both migration commands are required: the first updates the original database and the second updates existing client databases. New workspaces inherit the updated schema. No SMTP credentials are copied into new accounts. DeepSeek defaults to the company key, without copying it into client databases.

## SMTP provider setup

The SMTP settings page includes Gmail/Google Workspace, Zoho business (India/global), Zoho personal, Microsoft 365, and editable Custom SMTP presets. Presets only fill the host, port and encryption; credentials remain client-specific. Use the host shown by your provider for regional accounts. This is password-based SMTP; OAuth-only organizations need an authorized SMTP relay.

Port 465 always uses implicit TLS, including old records saved as None. TLS uses STARTTLS, with encryption required. Custom None disables automatic TLS explicitly. Password fields are not embedded in the page; leave blank to retain the saved password. Save the settings before sending a test email.

If authentication still fails, check the provider's app-password / SMTP AUTH policy. If the connection times out or closes, check the server's outbound SMTP access. Live credentials and VPS connectivity cannot be verified by the isolated tests.

Provider references: https://support.google.com/a/answer/176600, https://www.zoho.com/mail/help/zoho-smtp.html, https://learn.microsoft.com/en-us/Exchange/mail-flow-best-practices/how-to-set-up-a-multifunction-device-or-application-to-send-email-using-microsoft-365-or-office-365

## Candidate reply synchronization

SMTP sends outbound messages. For Outlook / Microsoft 365 receiving, use **Connect Outlook** in Workspace Integrations. Microsoft no longer accepts basic username/password authentication for Exchange Online IMAP. The Microsoft connection uses delegated OAuth and the Microsoft Graph inbox API; it does not require PHP IMAP. Connecting an inbox does not change the client's SMTP settings.

For Gmail, Zoho and other password-based IMAP providers, receiving replies needs IMAP access and PHP's IMAP extension in the CLI PHP used by cron. SaaS automatically detects Zoho and Gmail inbox hosts from SMTP; Workspace Integrations can override the host and TLS port (normally 993). Custom providers need an explicit host. This connector uses the client's SMTP username/password, so those credentials must also support IMAP. Other OAuth-only or separate-credential IMAP providers are not supported by the password-based connector.

Enable IMAP in the provider account. Run `php artisan saas:maintenance minute` as the site user to diagnose/import replies for active subscribed clients, including previously received replies. Matching uses email thread headers and sender/subject fallback within each client's database; imports deduplicate existing messages. It does not mark mailbox messages read.

Install a cron entry for the site user (do not duplicate an existing scheduler):

```cron
* * * * * cd /home/assistmyhr.com/public_html/ats && /usr/bin/php artisan schedule:run >> /home/assistmyhr.com/logs/scheduler.log 2>&1
```

For password-based IMAP only, check `php --ri imap` before starting. If missing, install the extension matching the VPS CLI PHP version; the web PHP extension alone is insufficient. The open Email Conversation polls automatically and shows imported replies without a page refresh. Live mailbox access and the scheduler must be verified on the VPS.

## Outlook / Microsoft 365 inbox setup

Register a **Web** application in Microsoft Entra ID. Choose accounts in any organizational directory and personal Microsoft accounts if both Microsoft 365 and Outlook.com clients need access. This integration uses the global Microsoft cloud. Set this exact Web redirect URI (no workspace query):

```text
https://assistmyhr.com/account/integrations/microsoft/callback
```

The URI is generated from the server's `APP_URL`, which must be the canonical HTTPS ATS URL. Add Microsoft Graph **delegated** permissions `User.Read` and `Mail.Read`. The authorization request also asks for `offline_access` so the scheduler can renew access without repeated sign-in. Do not add application-wide mailbox permissions. Some organizations require administrator approval of the delegated consent.

Create a client secret and copy its **value**, not its ID. In **Super Admin → Clients → Manage → Outlook Inbox**, enter the application (client) ID and secret, then click **Save Microsoft app settings**. These settings take effect immediately for that client; no environment edit or configuration-cache refresh is needed. All existing and new clients have this tab. Microsoft app registration and mailbox consent are still required.

Secrets are encrypted in the central platform settings store. The Super Admin form shows only the saved app ID and whether a secret is present; leaving the secret blank preserves it. Validation never flashes a secret back into the session. Audit history records the application ID and whether settings changed, without storing secret values. Users in client accounts cannot edit these Super Admin credentials. Their company SMTP settings remain separate.

Changing the application ID requires a new secret and disconnects that client's existing Outlook authorization so they can reconnect to the new app. Stored email conversations remain available. Updating the secret for the same application retains the mailbox and automatically uses the new secret for token renewal. Settings saves share the workspace's inbox lock, preventing races with an active sync. A sign-in started before an app-credential change must be started again.

An optional platform default can still be set through the following server environment values. It applies only to workspaces with no saved Microsoft app override; each client's saved app always takes precedence. A broken client override fails closed instead of switching applications:

```dotenv
MICROSOFT_REPLY_CLIENT_ID=your-application-client-id
MICROSOFT_REPLY_CLIENT_SECRET="your-client-secret-value"
```

Deploy with both `php artisan migrate --force` and `php artisan saas:migrate --force` after taking the normal database backup. These create the new `candidate_reply_mailboxes` table in the original and existing client databases. No additional migration is needed for saving client app credentials in Super Admin. Run `php artisan optimize:clear` and `php artisan config:cache` after deployment or changing optional environment defaults. New workspaces inherit the schema, never another client's connection tokens or app credentials.

In each client, open **Workspace Integrations → Candidate reply inbox → Connect Outlook**. Sign in to the mailbox that actually receives replies to the client's outgoing emails and approve read access. The callback binds state, PKCE, expiry, local user and workspace to the initiating session. Access/refresh tokens and sync cursors are encrypted in that client's database and excluded from serialized settings. Only users with `manage_settings` permission can connect or disconnect.

The existing minute scheduler automatically chooses the connected Microsoft inbox instead of password-based IMAP. It renews expired tokens, follows delta pages (up to five 50-message pages per run), resumes large inboxes on later runs, and deduplicates messages using their Internet Message-ID. Thread headers match the correct application, including alternate candidate reply addresses; ambiguous sender/subject matches are skipped. Only messages in the connected account's **Inbox** are read. Outlook rules moving messages directly to other folders, junk mail and shared/delegated mailboxes are outside this connector's scope. Deleting/moving provider messages does not delete ATS conversation history. Read-only access never changes the mailbox's read status.

The connection card shows the mailbox, last successful inbox check and safe sync errors. If access is revoked, the app secret expires, or the organization changes its consent policy, correct the platform setup and use **Reconnect Outlook**. Disconnect removes this workspace's tokens and cursor while preserving stored conversations. Rotated refresh tokens are saved automatically. Per-workspace locks prevent overlapping sync/reconnect/disconnect operations.

To diagnose one workspace as the site user:

```sh
sudo -u assis2045 -H /usr/bin/php artisan saas:maintenance minute --workspace=the-client-workspace-slug
```

Source references: [Microsoft IMAP basic-auth retirement](https://learn.microsoft.com/en-us/exchange/clients-and-mobile-in-exchange-online/deprecation-of-basic-authentication-exchange-online), [authorization code flow and PKCE](https://learn.microsoft.com/en-us/entra/identity-platform/v2-oauth2-auth-code-flow), [Microsoft Graph inbox delta](https://learn.microsoft.com/en-us/graph/api/message-delta?view=graph-rest-1.0).

## Shared company DeepSeek default

Clients without an active nonempty DeepSeek key use the active DeepSeek key saved in the original main ATS account (lowest sort order, then ID). If none is saved there, the platform DEEPSEEK_API_KEY and DEEPSEEK_MODEL environment values supply the default. Clients can add their own active DeepSeek key and model in AI Settings to override it; disabling/deleting that override returns them to the company default. The shared secret is not copied into client databases, listed in client settings, or returned by the client's clipboard endpoint. Company API usage is billed to the company when this default is used. SMTP remains client-specific.
