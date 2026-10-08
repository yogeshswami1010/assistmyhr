# Platform calling and SMS

In SaaS mode, clients cannot edit calling/SMS provider credentials or calling configuration. Super Admin > Settings stores the shared Telnyx API key (encrypted) and webhook public key. Super Admin > Clients > client details enables calling/SMS and assigns SIP connection ID and sender numbers. Features remain available in candidate profiles when configured. All clients start disabled until assigned centrally; old client configuration is ignored in SaaS mode. Standalone mode retains the original configuration.

Each client must have a dedicated SMS sender number. Configure that number's Telnyx messaging profile webhook with the workspace-specific URL shown on the client details page. The webhook verifies the shared signature and checks the recipient matches the selected client. The key is never sent to clients; browser calls use generated temporary Telnyx tokens. A changed SIP connection invalidates the saved WebRTC credential ID for that client.

No database migration is required; configuration uses the existing central platform settings table. Deploy with git pull and optimize:clear, then configure the provider and client assignments. SMS/calling requires a properly configured Telnyx account, active client subscription, voice/SMS enabled numbers and a supported US/Canada phone format. Live calls and SMS were not sent during tests.
