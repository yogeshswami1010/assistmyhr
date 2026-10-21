<?php

namespace App\Services;

use App\CandidateReplyMailbox;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MicrosoftReplyInbox
{
    public const SCOPES = 'offline_access https://graph.microsoft.com/User.Read https://graph.microsoft.com/Mail.Read';
    private const GRAPH = 'https://graph.microsoft.com/v1.0/';
    private const TOKEN = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';

    public function configured(): bool
    {
        $credentials = app(MicrosoftReplyAppSettings::class)->current();
        return $credentials['client_id'] !== '' && $credentials['client_secret'] !== '';
    }

    public function credentialFingerprint(): string
    {
        $credentials = app(MicrosoftReplyAppSettings::class)->current();
        return hash('sha256', $credentials['client_id'].'|'.$credentials['client_secret']);
    }

    // One registered callback for every workspace; the session binds OAuth to its client.
    public function redirectUri(): string
    {
        return rtrim(config('app.url'), '/').'/account/integrations/microsoft/callback';
    }

    public function authorizationUrl(string $state, string $verifier): string
    {
        return 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?'.http_build_query([
            'client_id' => app(MicrosoftReplyAppSettings::class)->current()['client_id'],
            'response_type' => 'code', 'redirect_uri' => $this->redirectUri(),
            'response_mode' => 'query', 'scope' => self::SCOPES, 'state' => $state,
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256', 'prompt' => 'select_account',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function connect(string $code, string $verifier): CandidateReplyMailbox
    {
        $mailbox = Cache::lock('candidate-outlook-inbox', 300)->get(fn () => $this->authorizeMailbox($code, $verifier));
        if (!$mailbox) throw new MicrosoftInboxException('An inbox sync is in progress. Try connecting Outlook again shortly.');
        return $mailbox;
    }

    public function disconnect(): bool
    {
        return Cache::lock('candidate-outlook-inbox', 300)->get(function () {
            CandidateReplyMailbox::whereKey(1)->delete();
            return true;
        }) === true;
    }

    private function authorizeMailbox(string $code, string $verifier): CandidateReplyMailbox
    {
        $tokens = $this->tokens(['grant_type' => 'authorization_code', 'code' => $code,
            'redirect_uri' => $this->redirectUri(), 'code_verifier' => $verifier]);
        if (empty($tokens['refresh_token'])) throw new MicrosoftInboxException('Microsoft did not grant ongoing inbox access. Reconnect Outlook and approve the requested permissions.');
        $profile = $this->get(self::GRAPH.'me?$select=mail,userPrincipalName', $tokens['access_token']);
        $email = strtolower(trim($profile['mail'] ?? $profile['userPrincipalName'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new MicrosoftInboxException('Microsoft did not return a valid mailbox address. Sign in with the company mailbox.');
        // Confirm this account has an inbox and that Mail.Read consent succeeded.
        $this->get(self::GRAPH.'me/mailFolders/inbox/messages?$top=1&$select=id', $tokens['access_token']);
        return CandidateReplyMailbox::updateOrCreate(['id' => 1], [
            'email' => $email, 'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_expires_at' => now()->addSeconds($tokens['expires_in']),
            'sync_cursor' => null, 'last_synced_at' => null, 'sync_error' => null,
        ]);
    }

    public function sync(CandidateReplyMailbox $mailbox): int
    {
        return Cache::lock('candidate-outlook-inbox', 300)->get(function () {
            // Re-read after obtaining the lock, in case the user reconnected the mailbox.
            $current = CandidateReplyMailbox::find(1);
            return $current ? $this->syncMailbox($current) : 0;
        }) ?: 0;
    }

    private function syncMailbox(CandidateReplyMailbox $mailbox): int
    {
        if (!$this->configured()) throw new MicrosoftInboxException('Outlook connection is unavailable. Ask the platform administrator to configure Microsoft inbox access.');
        $token = $this->accessToken($mailbox);
        $url = $mailbox->sync_cursor ?: $this->initialUrl();
        $count = 0;
        $store = new CandidateEmailReplyStore;
        // Continue large inboxes on the next scheduler run rather than blocking other clients.
        for ($page = 0; $page < 5; $page++) {
            $response = $this->request($url, $token);
            if ($response->status() === 401) {
                $token = $this->accessToken($mailbox, true);
                $response = $this->request($url, $token);
            }
            if ($response->status() === 410) {
                $mailbox->update(['sync_cursor' => null]);
                throw new MicrosoftInboxException('Outlook sync history expired. The next scheduled sync will check the inbox again.');
            }
            $data = $this->payload($response);
            if (!isset($data['value']) || !is_array($data['value'])) throw new MicrosoftInboxException('Microsoft returned an incomplete inbox response. Sync will retry automatically.');
            foreach ($data['value'] as $message) {
                if (isset($message['@removed']) || !empty($message['isDraft'])) continue;
                // Delta updates may contain only changed fields (for example isRead).
                if (!isset($message['body'], $message['from'], $message['receivedDateTime'])) continue;
                $from = strtolower(trim($message['from']['emailAddress']['address'] ?? ''));
                if (!filter_var($from, FILTER_VALIDATE_EMAIL) || $from === $mailbox->email) continue;
                $headers = [];
                foreach ($message['internetMessageHeaders'] ?? [] as $header) {
                    $headers[strtolower($header['name'] ?? '')] = (string) ($header['value'] ?? '');
                }
                $id = CandidateEmailThread::ids($message['internetMessageId'] ?? '')[0]
                    ?? 'microsoft-'.hash('sha256', $mailbox->email.'|'.($message['id'] ?? ''));
                $result = $store->store([
                    'from_address' => $from, 'to_address' => $mailbox->email,
                    'subject' => (string) ($message['subject'] ?? ''), 'message_id' => $id,
                    'in_reply_to' => $headers['in-reply-to'] ?? '', 'references' => $headers['references'] ?? '',
                    'received_at' => \Carbon\Carbon::parse($message['receivedDateTime'])->utc()->format('Y-m-d H:i:s'),
                ], static fn () => strtolower($message['body']['contentType'] ?? '') === 'html'
                    ? CandidateEmailContent::plain($message['body']['content'] ?? '') : (string) ($message['body']['content'] ?? ''));
                if ($result && $result['status'] === 'imported') $count++;
            }
            $next = $data['@odata.nextLink'] ?? null;
            $cursor = $next ?: ($data['@odata.deltaLink'] ?? null);
            if (!is_string($cursor) || !$this->graphUrl($cursor)) throw new MicrosoftInboxException('Microsoft returned an invalid sync cursor. Sync will retry automatically.');
            // Advance only after all messages on this page have been stored successfully.
            $mailbox->update(['sync_cursor' => $cursor, 'last_synced_at' => now(), 'sync_error' => null]);
            if (!$next) break;
            $url = $next;
        }
        return $count;
    }

    private function initialUrl(): string
    {
        return self::GRAPH.'me/mailFolders/inbox/messages/delta?'.http_build_query([
            '$select' => 'id,internetMessageId,from,subject,body,receivedDateTime,internetMessageHeaders,isDraft',
            '$orderby' => 'receivedDateTime desc',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    private function accessToken(CandidateReplyMailbox $mailbox, bool $force = false): string
    {
        if (!$force && $mailbox->token_expires_at->gt(now()->addMinute())) return $mailbox->access_token;
        $tokens = $this->tokens(['grant_type' => 'refresh_token', 'refresh_token' => $mailbox->refresh_token]);
        $mailbox->update(['access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'] ?? $mailbox->refresh_token,
            'token_expires_at' => now()->addSeconds($tokens['expires_in'])]);
        return $mailbox->access_token;
    }

    private function tokens(array $parameters): array
    {
        $credentials = app(MicrosoftReplyAppSettings::class)->current();
        try {
            $response = Http::asForm()->acceptJson()->connectTimeout(10)->timeout(25)->withoutRedirecting()->post(self::TOKEN, $parameters + [
                'client_id' => $credentials['client_id'],
                'client_secret' => $credentials['client_secret'], 'scope' => self::SCOPES,
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new MicrosoftInboxException('Could not reach Microsoft. Try connecting Outlook again later.');
        }
        $data = $response->json();
        if (!$response->successful() || empty($data['access_token']) || empty($data['expires_in'])) {
            // Never expose token responses, authorization codes or client secrets in logs/UI.
            throw new MicrosoftInboxException('Microsoft authorization failed. Reconnect Outlook; your organization may require administrator approval.');
        }
        return $data;
    }

    private function graphUrl(string $url): bool
    {
        $parts = parse_url($url);
        return $parts && ($parts['scheme'] ?? '') === 'https' && ($parts['host'] ?? '') === 'graph.microsoft.com'
            && !isset($parts['user']) && !isset($parts['pass']) && !isset($parts['port'])
            && str_starts_with($parts['path'] ?? '', '/v1.0/') && strlen($url) <= 16000;
    }

    private function request(string $url, string $token): \Illuminate\Http\Client\Response
    {
        if (!$this->graphUrl($url)) throw new MicrosoftInboxException('Invalid Microsoft inbox URL. Reconnect Outlook.');
        try {
            return Http::withToken($token)->acceptJson()->withHeaders(['Prefer' => 'odata.maxpagesize=50, outlook.body-content-type="text"'])
                ->connectTimeout(10)->timeout(25)->withoutRedirecting()->get($url);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new MicrosoftInboxException('Could not reach the Outlook inbox. Sync will retry automatically.');
        }
    }

    private function get(string $url, string $token): array { return $this->payload($this->request($url, $token)); }

    private function payload(\Illuminate\Http\Client\Response $response): array
    {
        if (!$response->successful()) {
            throw new MicrosoftInboxException(match ($response->status()) {
                401 => 'Outlook access expired or was revoked. Reconnect Outlook.',
                403 => 'Microsoft denied inbox access. Reconnect Outlook and approve Mail.Read; your organization may require administrator approval.',
                429 => 'Microsoft temporarily limited inbox requests. Sync will retry automatically.',
                default => 'Could not read the Outlook inbox. Check that the Microsoft account has a mailbox and reconnect Outlook.',
            });
        }
        $data = $response->json();
        if (!is_array($data)) throw new MicrosoftInboxException('Microsoft returned an invalid inbox response. Sync will retry automatically.');
        return $data;
    }
}
