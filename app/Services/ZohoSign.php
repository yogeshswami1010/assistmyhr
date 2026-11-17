<?php

namespace App\Services;

use App\CandidateSigningDocument;
use App\JobApplication;
use App\Notifications\JobOfferAccepted;
use App\Notifications\JobOfferRejected;
use App\Onboard;
use App\Saas\TenantContext;
use App\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ZohoSign
{
    private const DOMAINS = ['com', 'eu', 'in', 'jp', 'com.au', 'zohocloud.ca', 'sa', 'uk', 'sg', 'ae'];

    public function enabled(): bool
    {
        return in_array(config('zoho-sign.domain'), self::DOMAINS, true)
            && ($this->developmentToken() !== null || (
                filled(config('zoho-sign.client_id'))
                && filled(config('zoho-sign.client_secret'))
                && filled(config('zoho-sign.refresh_token'))
            ));
    }

    public function createDraft(Onboard $offer): string
    {
        $file = $offer->files()->find($offer->zoho_sign_file_id);
        if (! $file || strtolower($file->ext) !== 'pdf') {
            throw new RuntimeException('The selected offer PDF is missing.');
        }

        $stream = Storage::readStream('onboard-files/'.$file->hash_name);
        if (! is_resource($stream)) {
            throw new RuntimeException('The selected offer PDF could not be read.');
        }

        $days = (int) ceil(now()->diffInHours($offer->accept_last_date->copy()->endOfDay()) / 24);
        try {
            $response = $this->client()
                ->attach('file', $stream, 'offer-letter.pdf', ['Content-Type' => 'application/pdf'])
                ->post('requests', [
                    'data' => json_encode(['requests' => [
                        'request_name' => 'ATS offer '.$offer->id,
                        'expiration_days' => max(1, min(365, $days)),
                        'is_sequential' => true,
                    ]], JSON_THROW_ON_ERROR),
                    'testing' => config('zoho-sign.testing') ? 'true' : 'false',
                ]);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $id = (string) data_get($this->json($response, 'create the PDF draft'), 'requests.request_id');
        if (! preg_match('/\A[0-9]{1,64}\z/', $id)) {
            throw new RuntimeException('Zoho Sign did not return a request ID.');
        }

        return $id;
    }

    public function submit(Onboard $offer): void
    {
        $path = 'requests/'.$offer->zoho_sign_request_id;
        $status = $this->status($offer);
        if ($status === 'draft') {
            $candidate = $offer->applications;
            $response = $this->client()->asMultipart()->post($path.'/submit', [
                'data' => json_encode(['requests' => ['actions' => [[
                    'action_type' => 'SIGN',
                    'recipient_name' => $candidate->full_name,
                    'recipient_email' => $candidate->email,
                    'signing_order' => 0,
                    'verify_recipient' => true,
                    'verification_type' => 'EMAIL',
                ]]]], JSON_THROW_ON_ERROR),
                'testing' => config('zoho-sign.testing') ? 'true' : 'false',
            ]);
            $this->json($response);
            $status = 'inprogress';
        }

        $this->applyStatus($offer, $status);
    }

    public function createCandidateDraft(JobApplication $application, string $pdfPath, string $filename): string
    {
        return $this->createRecipientDraft($application->full_name, $application->email, $pdfPath, $filename);
    }

    public function createRecipientDraft(string $name, string $email, string $pdfPath, string $filename, ?string $message = null, ?string $title = null): string
    {
        $stream = fopen($pdfPath, 'rb');
        if ($stream === false) {
            throw new RuntimeException('The uploaded PDF could not be read.');
        }

        try {
            $response = $this->client()
                ->attach('file', $stream, $filename, ['Content-Type' => 'application/pdf'])
                ->post('requests', [
                    'data' => json_encode(['requests' => [
                        'request_name' => $title ?? $filename,
                        ...($message === null ? [] : ['notes' => $message]),
                        'expiration_days' => 30,
                        'is_sequential' => true,
                        'actions' => [[
                            'action_type' => 'SIGN',
                            'recipient_name' => $name,
                            'recipient_email' => $email,
                            'signing_order' => 0,
                            'verify_recipient' => true,
                            'verification_type' => 'EMAIL',
                        ]],
                    ]], JSON_THROW_ON_ERROR),
                    'testing' => config('zoho-sign.testing') ? 'true' : 'false',
                ]);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $id = (string) data_get($this->json($response, 'create the PDF draft'), 'requests.request_id');
        if (! preg_match('/\A[0-9]{1,64}\z/', $id)) {
            throw new RuntimeException('Zoho Sign did not return a request ID.');
        }
        $this->registerWorkspace($id);

        return $id;
    }

    public function editorUrl(CandidateSigningDocument $document): string
    {
        $response = $this->client()->send('POST', 'requests/'.$document->zoho_sign_request_id.'/embedsendtoken');
        $url = $this->json($response, 'open the document editor')['send_url'] ?? null;
        $parts = is_string($url) ? parse_url($url) : false;
        if (($parts['scheme'] ?? null) !== 'https'
            || ($parts['host'] ?? null) !== 'sign.'.$this->regionalHost()
            || ($parts['path'] ?? null) !== '/embedsend'
            || isset($parts['user']) || isset($parts['pass'])
            || (isset($parts['port']) && $parts['port'] !== 443)) {
            throw new ZohoSignException('Zoho Sign did not return a valid editor link.');
        }

        return $url;
    }

    public function status(Onboard|CandidateSigningDocument $offer): string
    {
        $this->registerWorkspace($offer->zoho_sign_request_id);
        $response = $this->json($this->client()->get('requests/'.$offer->zoho_sign_request_id));
        $status = data_get($response, 'requests.request_status');
        if (! is_string($status) || ! preg_match('/\A[a-z_ ]{1,32}\z/i', $status)) {
            throw new RuntimeException('Zoho Sign did not return a valid status.');
        }

        return strtolower($status);
    }

    private function registerWorkspace(string $requestId): void
    {
        if (! config('saas.enabled')) {
            return;
        }

        $tenant = app(TenantContext::class)->current();
        if (! $tenant) {
            throw new RuntimeException('The Zoho Sign request requires a workspace.');
        }

        $requests = DB::connection('saas_landlord')->table('saas_zoho_sign_requests');
        $requests->insertOrIgnore([
            'request_id' => $requestId,
            'tenant_id' => $tenant->id,
        ]);
        if ((int) $requests->where('request_id', $requestId)->value('tenant_id') !== (int) $tenant->id) {
            throw new RuntimeException('The Zoho Sign request could not be assigned to this workspace.');
        }
    }

    public function applyStatus(Onboard $offer, string $status): void
    {
        $updated = DB::transaction(function () use ($offer, $status) {
            $current = Onboard::whereKey($offer->id)->lockForUpdate()->firstOrFail();
            if ($current->hired_status !== 'offered' || $current->zoho_sign_request_id !== $offer->zoho_sign_request_id) {
                return null;
            }

            $current->zoho_sign_status = $status;
            $current->hired_status = match ($status) {
                'completed' => 'accepted',
                'declined', 'rejected' => 'rejected',
                'expired', 'recalled' => 'canceled',
                default => 'offered',
            };
            $current->save();

            return $current->hired_status === 'offered' ? null : $current;
        });

        if ($updated && in_array($updated->hired_status, ['accepted', 'rejected'], true)) {
            $notify = function () use ($updated) {
                try {
                    $notification = $updated->hired_status === 'accepted'
                        ? new JobOfferAccepted($updated->applications)
                        : new JobOfferRejected($updated->applications);
                    Notification::send(User::allAdmins(), $notification);
                } catch (\Exception $exception) {
                    report($exception);
                }
            };
            if (DB::transactionLevel() > 0) {
                DB::afterCommit($notify);
            } else {
                $notify();
            }
        }
    }

    public function signedPdf(Onboard|CandidateSigningDocument $offer): string
    {
        $response = $this->client()->get('requests/'.$offer->zoho_sign_request_id.'/pdf', [
            'with_coc' => 'true',
            'merge' => 'true',
        ]);
        if (! $response->successful() || ! str_starts_with($response->body(), '%PDF-')) {
            throw new RuntimeException('Zoho Sign could not provide the signed PDF.');
        }

        return $response->body();
    }

    private function client(): PendingRequest
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Zoho Sign is unavailable for this workspace.');
        }

        $host = $this->regionalHost();
        $token = $this->developmentToken() ?? $this->refreshAccessToken($host);

        return Http::baseUrl("https://sign.{$host}/api/v1")
            ->withHeaders(['Authorization' => 'Zoho-oauthtoken '.$token])
            ->connectTimeout(5)->timeout(25)->withoutRedirecting();
    }

    private function developmentToken(): ?string
    {
        $token = trim((string) config('zoho-sign.development_token'));

        return config('zoho-sign.testing') && $token !== '' ? $token : null;
    }

    private function refreshAccessToken(string $host): string
    {
        $cacheKey = 'zoho-sign.token.'.hash('sha256', $host.config('zoho-sign.client_id').config('zoho-sign.refresh_token'));
        $cache = config('saas.enabled')
            ? Cache::build(['driver' => 'file', 'path' => storage_path('framework/cache/data')])
            : Cache::store();
        return $cache->remember($cacheKey, now()->addMinutes(45), function () use ($host) {
            $response = Http::asForm()->connectTimeout(5)->timeout(15)->withoutRedirecting()
                ->post("https://accounts.{$host}/oauth/v2/token", [
                    'client_id' => config('zoho-sign.client_id'),
                    'client_secret' => config('zoho-sign.client_secret'),
                    'refresh_token' => config('zoho-sign.refresh_token'),
                    'grant_type' => 'refresh_token',
                ]);
            $token = $response->json('access_token');
            if (! $response->successful() || ! is_string($token) || $token === '') {
                $hint = match ($response->json('error')) {
                    'invalid_client' => 'Check ZOHO_SIGN_CLIENT_ID and the Zoho account region.',
                    'invalid_client_secret' => 'Check ZOHO_SIGN_CLIENT_SECRET for the same Zoho Self Client.',
                    'invalid_code', 'invalid_grant' => 'Check ZOHO_SIGN_REFRESH_TOKEN. It must be the refresh token from the same Zoho Self Client.',
                    'access_denied' => 'Wait a moment and retry. If it persists, check the Zoho Self Client access.',
                    default => 'Check the Zoho OAuth credentials and try again.',
                };
                throw new ZohoSignException('Zoho Accounts could not refresh the access token. '.$hint);
            }

            return $token;
        });
    }

    private function regionalHost(): string
    {
        $domain = config('zoho-sign.domain');

        return $domain === 'zohocloud.ca' ? $domain : 'zoho.'.$domain;
    }

    private function json(Response $response, string $operation = 'process the request'): array
    {
        $data = $response->json();
        if (! $response->successful() || ! is_array($data) || ! in_array($data['code'] ?? null, [0, '0'], true)) {
            $code = $data['code'] ?? null;
            $code = is_int($code) || (is_string($code) && ctype_digit($code) && strlen($code) <= 8)
                ? (int) $code
                : null;
            $status = $response->status();
            $hint = match ($code) {
                8026 => 'Check that your Zoho Sign plan supports this operation.',
                9018 => 'Try a smaller PDF.',
                9020 => 'Export the document as a new PDF and try again.',
                default => match ($status) {
                    401, 403 => $this->developmentToken() !== null
                        ? 'Check the Zoho region and generate a new API token under Developer settings → API token - development if it has expired.'
                        : 'Check the Zoho account region and document permissions on the OAuth token.',
                    429 => 'Wait a moment and try again.',
                    default => 'Check the document and Zoho Sign account settings.',
                },
            };
            $reference = $code === null ? "HTTP {$status}" : "HTTP {$status}, Zoho code {$code}";
            throw new ZohoSignException("Zoho Sign could not {$operation} ({$reference}). {$hint}");
        }

        return $data;
    }
}
