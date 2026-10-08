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

SMTP sends outbound messages. Receiving replies needs IMAP access and PHP's IMAP extension in the CLI PHP used by cron. SaaS automatically detects Zoho and Gmail inbox hosts from SMTP; Workspace Integrations can override the host and TLS port (normally 993). Custom providers need an explicit host. The importer uses the client's SMTP username/password for the inbox, so those credentials must support IMAP. OAuth-only or separate-credential mailboxes are not supported by this password-based connector.

Enable IMAP in the provider account. Run `php artisan saas:maintenance minute` as the site user to diagnose/import replies for active subscribed clients, including previously received replies. Matching uses email thread headers and sender/subject fallback within each client's database; imports deduplicate existing messages. It does not mark mailbox messages read.

Install a cron entry for the site user (do not duplicate an existing scheduler):

```cron
* * * * * cd /home/assistmyhr.com/public_html/ats && /usr/bin/php artisan schedule:run >> /home/assistmyhr.com/logs/scheduler.log 2>&1
```

Check `php --ri imap` before starting. If missing, install the IMAP extension matching the VPS CLI PHP version; the web PHP extension alone is insufficient. In the ATS, refresh Email Conversation after the scheduled import. The server mailbox connection and scheduler must be verified on the VPS.

## Shared company DeepSeek default

Clients without an active nonempty DeepSeek key use the active DeepSeek key saved in the original main ATS account (lowest sort order, then ID). If none is saved there, the platform DEEPSEEK_API_KEY and DEEPSEEK_MODEL environment values supply the default. Clients can add their own active DeepSeek key and model in AI Settings to override it; disabling/deleting that override returns them to the company default. The shared secret is not copied into client databases, listed in client settings, or returned by the client's clipboard endpoint. Company API usage is billed to the company when this default is used. SMTP remains client-specific.
