# Client SMTP and DeepSeek settings

With SaaS enabled, every ATS workspace (including main) reads its SMTP settings from its own database. Standard notifications and candidate/AI Search emails use those settings. Missing client credentials never fall back to platform SMTP. Configure and test SMTP in each client account before sending recruitment emails.

In Settings > AI Settings, add or edit a key with provider `deepseek`, save its model name, and enable it. The active DeepSeek key with the lowest sort order (then ID) is selected. API keys remain encrypted. A blank model retains the existing `deepseek-chat` default. The Test key action checks the DeepSeek models endpoint; it verifies credentials, not whether a model supports every ATS feature.

Signup verification uses the separate platform mailer, configured with MAIL_* in the server environment. Keep those platform settings. Once client settings are saved, AI_SEARCH_MAIL_* and DEEPSEEK_API_KEY / DEEPSEEK_MODEL are unnecessary for SaaS workspaces and can be removed from .env. Standalone installations retain environment compatibility.

Deploy:

```sh
cd /home/assistmyhr.com/public_html/ats
sudo -u assis2045 git pull
sudo -u assis2045 php artisan optimize:clear
sudo -u assis2045 php artisan migrate --force
sudo -u assis2045 php artisan saas:migrate --force
sudo -u assis2045 php artisan config:cache
```

Both migration commands are required: the first updates the original database and the second updates existing client databases. New workspaces inherit the updated schema. No client credentials are imported from the environment or copied into new accounts.

## SMTP provider setup

The SMTP settings page includes Gmail/Google Workspace, Zoho business (India/global), Zoho personal, Microsoft 365, and editable Custom SMTP presets. Presets only fill the host, port and encryption; credentials remain client-specific. Use the host shown by your provider for regional accounts. This is password-based SMTP; OAuth-only organizations need an authorized SMTP relay.

Port 465 always uses implicit TLS, including old records saved as None. TLS uses STARTTLS, with encryption required. Custom None disables automatic TLS explicitly. Password fields are not embedded in the page; leave blank to retain the saved password. Save the settings before sending a test email.

If authentication still fails, check the provider's app-password / SMTP AUTH policy. If the connection times out or closes, check the server's outbound SMTP access. Live credentials and VPS connectivity cannot be verified by the isolated tests.

Provider references: https://support.google.com/a/answer/176600, https://www.zoho.com/mail/help/zoho-smtp.html, https://learn.microsoft.com/en-us/Exchange/mail-flow-best-practices/how-to-set-up-a-multifunction-device-or-application-to-send-email-using-microsoft-365-or-office-365
