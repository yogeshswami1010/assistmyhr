<?php

namespace App\Services;

use App\GoogleCalendarConnection;
use App\Saas\TenantContext;
use App\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GoogleCalendarService
{
    public const SCOPES = 'openid email https://www.googleapis.com/auth/calendar.events.readonly';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const EVENTS_URL = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';

    public function configured(): bool
    {
        $credentials = $this->credentials();

        return filled($credentials['client_id']) && filled($credentials['client_secret']);
    }

    private function credentials(): array
    {
        $calendar = config('services.google_calendar', []);
        $service = filled($calendar['client_id'] ?? null) || filled($calendar['client_secret'] ?? null)
            ? 'google_calendar' : 'google';

        return [
            'client_id' => config('services.'.$service.'.client_id'),
            'client_secret' => config('services.'.$service.'.client_secret'),
        ];
    }

    public function linkedSubject(User $user): ?string
    {
        $tenant = config('saas.enabled') ? app(TenantContext::class)->current() : null;

        return $tenant && strcasecmp($tenant->owner_email, trim($user->email)) === 0
            ? $tenant->google_subject : null;
    }

    public function redirectUri(): string
    {
        return rtrim(config('app.url'), '/').'/admin/interview-schedule/google-calendar/callback';
    }

    public function fingerprint(): string
    {
        $credentials = $this->credentials();
        return hash('sha256', $credentials['client_id'].'|'.$credentials['client_secret'].'|'.$this->redirectUri());
    }

    public function authorizationUrl(string $state, string $verifier, string $loginHint): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => $this->credentials()['client_id'], 'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code', 'scope' => self::SCOPES, 'state' => $state,
            'access_type' => 'offline', 'prompt' => 'consent', 'login_hint' => $loginHint,
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function connect(User $user, string $code, string $verifier): void
    {
        $this->locked($user->id, function () use ($user, $code, $verifier) {
            $tokens = $this->tokens(['grant_type' => 'authorization_code', 'code' => $code,
                'code_verifier' => $verifier, 'redirect_uri' => $this->redirectUri()]);
            $profile = $this->get('https://openidconnect.googleapis.com/v1/userinfo', $tokens['access_token']);
            $linkedSubject = $this->linkedSubject($user);
            if (empty($profile['email_verified']) || empty($profile['sub'])
                || strcasecmp((string) ($profile['email'] ?? ''), trim($user->email)) !== 0
                || ($linkedSubject !== null && $profile['sub'] !== $linkedSubject)) {
                throw new GoogleCalendarException('Sign in with the Google account matching your ATS email address.');
            }
            if (empty($tokens['refresh_token'])) {
                throw new GoogleCalendarException('Google did not grant ongoing calendar access. Reconnect and approve the requested permissions.');
            }
            GoogleCalendarConnection::updateOrCreate(['user_id' => $user->id], [
                'email' => strtolower($profile['email']), 'google_subject' => $profile['sub'],
                'access_token' => $tokens['access_token'], 'refresh_token' => $tokens['refresh_token'],
                'token_expires_at' => now()->addSeconds((int) $tokens['expires_in']),
            ]);
        });
    }

    public function disconnect(int $userId): void
    {
        $this->locked($userId, fn () => GoogleCalendarConnection::where('user_id', $userId)->delete());
    }

    public function events(User $user, CarbonImmutable $start, CarbonImmutable $end): array
    {
        return $this->locked($user->id, function () use ($user, $start, $end) {
            $connection = GoogleCalendarConnection::where('user_id', $user->id)->first();
            if (!$connection) return [];
            $linkedSubject = $this->linkedSubject($user);
            if (strcasecmp($connection->email, trim($user->email)) !== 0
                || ($linkedSubject !== null && $connection->google_subject !== $linkedSubject)) {
                throw new GoogleCalendarException('Reconnect Google Calendar using the Google account matching your ATS account.');
            }
            if (!$this->configured()) throw new GoogleCalendarException('Google Calendar is unavailable. Contact the platform administrator.');
            $token = $this->accessToken($connection);
            $events = [];
            $pageToken = null;
            for ($page = 0; $page < 4; $page++) {
                $parameters = ['timeMin' => $start->utc()->toRfc3339String(), 'timeMax' => $end->utc()->toRfc3339String(),
                    'singleEvents' => 'true', 'orderBy' => 'startTime', 'showDeleted' => 'false', 'maxResults' => 2500];
                if ($pageToken) $parameters['pageToken'] = $pageToken;
                $payload = $this->get(self::EVENTS_URL, $token, $parameters);
                if (!isset($payload['items']) || !is_array($payload['items'])) throw new GoogleCalendarException('Google returned an incomplete calendar response. Try refreshing.');
                foreach ($payload['items'] as $event) {
                    $mapped = $this->mapEvent($event);
                    if ($mapped !== null) $events[] = $mapped;
                }
                $pageToken = $payload['nextPageToken'] ?? null;
                if (!$pageToken) return $events;
            }
            throw new GoogleCalendarException('There are too many Google events in this view. Switch to a week or day.');
        });
    }

    private function mapEvent(array $event): ?array
    {
        if (($event['status'] ?? '') === 'cancelled' || empty($event['id'])) return null;
        $allDay = isset($event['start']['date']);
        $start = $event['start']['dateTime'] ?? $event['start']['date'] ?? null;
        $end = $event['end']['dateTime'] ?? $event['end']['date'] ?? null;
        if (!$start || !$end) return null;
        // Do not expose attendees, notes, meeting links or other calendar metadata.
        $busy = ($event['visibility'] ?? '') === 'private';
        foreach ($event['attendees'] ?? [] as $attendee) {
            if (!empty($attendee['self']) && ($attendee['responseStatus'] ?? '') === 'declined') return null;
        }
        return ['id' => 'google-'.$event['id'], 'title' => 'Google: '.($busy ? 'Busy' : ($event['summary'] ?? 'Busy')),
            'start' => $start, 'end' => $end, 'allDay' => $allDay, 'sourceType' => 'google',
            'editable' => false, 'startEditable' => false, 'durationEditable' => false,
            'className' => 'int-ev-google'];
    }

    private function accessToken(GoogleCalendarConnection $connection): string
    {
        if ($connection->token_expires_at->gt(now()->addMinute())) return $connection->access_token;
        $tokens = $this->tokens(['grant_type' => 'refresh_token', 'refresh_token' => $connection->refresh_token]);
        $connection->update(['access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'] ?? $connection->refresh_token,
            'token_expires_at' => now()->addSeconds((int) $tokens['expires_in'])]);
        return $connection->access_token;
    }

    private function tokens(array $parameters): array
    {
        if (!$this->configured()) throw new GoogleCalendarException('Google Calendar is not configured. Contact the platform administrator.');
        try {
            $response = Http::asForm()->acceptJson()->connectTimeout(10)->timeout(25)->withoutRedirecting()
                ->post(self::TOKEN_URL, $parameters + $this->credentials());
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new GoogleCalendarException('Could not reach Google. Try again shortly.');
        }
        $data = $response->json();
        if (!$response->successful() || empty($data['access_token']) || empty($data['expires_in'])) {
            throw new GoogleCalendarException('Google authorization expired or was denied. Reconnect Google Calendar.');
        }
        return $data;
    }

    private function get(string $url, string $token, array $parameters = []): array
    {
        try {
            $response = Http::withToken($token)->acceptJson()->connectTimeout(10)->timeout(25)->withoutRedirecting()->get($url, $parameters);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new GoogleCalendarException('Could not reach Google Calendar. Try refreshing shortly.');
        }
        if (!$response->successful()) {
            throw new GoogleCalendarException(match ($response->status()) {
                401, 403 => 'Google Calendar access is unavailable. Reconnect and approve calendar access; the platform administrator may need to enable the Google Calendar API.',
                429 => 'Google Calendar is temporarily busy. Try refreshing shortly.',
                default => 'Google Calendar could not be loaded. Try refreshing shortly.',
            });
        }
        $data = $response->json();
        if (!is_array($data)) throw new GoogleCalendarException('Google returned an invalid calendar response. Try refreshing.');
        return $data;
    }

    private function locked(int $userId, callable $action)
    {
        // TenantContext provides a separate cache directory for each client.
        $lock = Cache::lock('google-calendar-'.$userId, 300);
        if (!$lock->get()) throw new GoogleCalendarException('Google Calendar is updating. Try again shortly.');
        try { return $action(); } finally { $lock->release(); }
    }
}
