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
        return filled(config('zoho-sign.client_id'))
            && filled(config('zoho-sign.client_secret'))
            && filled(config('zoho-sign.refresh_token'))
            && in_array(config('zoho-sign.domain'), self::DOMAINS, true);
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

        $id = (string) data_get($this->json($response), 'requests.request_id');
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
        $stream = fopen($pdfPath, 'rb');
        if ($stream === false) {
            throw new RuntimeException('The uploaded PDF could not be read.');
        }

        try {
            $response = $this->client()
                ->attach('file', $stream, $filename, ['Content-Type' => 'application/pdf'])
                ->post('requests', [
                    'data' => json_encode(['requests' => [
                        'request_name' => $filename,
                        'expiration_days' => 30,
                        'is_sequential' => true,
                        'actions' => [[
                            'action_type' => 'SIGN',
                            'recipient_name' => $application->full_name,
                            'recipient_email' => $application->email,
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

        $id = (string) data_get($this->json($response), 'requests.request_id');
        if (! preg_match('/\A[0-9]{1,64}\z/', $id)) {
            throw new RuntimeException('Zoho Sign did not return a request ID.');
        }
        $this->registerWorkspace($id);

        return $id;
    }

    public function editorUrl(CandidateSigningDocument $document): string
    {
        $response = $this->client()->asForm()->post('requests/'.$document->zoho_sign_request_id.'/embedsendtoken', [
            'testing' => config('zoho-sign.testing') ? 'true' : 'false',
        ]);
        $url = $this->json($response)['send_url'] ?? null;
        $parts = is_string($url) ? parse_url($url) : false;
        if (($parts['scheme'] ?? null) !== 'https'
            || ($parts['host'] ?? null) !== 'sign.'.$this->regionalHost()
            || ($parts['path'] ?? null) !== '/embedsend'
            || isset($parts['user']) || isset($parts['pass'])
            || (isset($parts['port']) && $parts['port'] !== 443)) {
            throw new RuntimeException('Zoho Sign did not return a valid editor link.');
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

        $domain = config('zoho-sign.domain');
        $host = $this->regionalHost();
        $cacheKey = 'zoho-sign.token.'.hash('sha256', $domain.config('zoho-sign.client_id').config('zoho-sign.refresh_token'));
        $cache = config('saas.enabled')
            ? Cache::build(['driver' => 'file', 'path' => storage_path('framework/cache/data')])
            : Cache::store();
        $token = $cache->remember($cacheKey, now()->addMinutes(45), function () use ($host) {
            $response = Http::asForm()->connectTimeout(5)->timeout(15)->withoutRedirecting()
                ->post("https://accounts.{$host}/oauth/v2/token", [
                    'client_id' => config('zoho-sign.client_id'),
                    'client_secret' => config('zoho-sign.client_secret'),
                    'refresh_token' => config('zoho-sign.refresh_token'),
                    'grant_type' => 'refresh_token',
                ]);
            $token = $response->json('access_token');
            if (! $response->successful() || ! is_string($token) || $token === '') {
                throw new RuntimeException('Zoho Sign authentication failed.');
            }

            return $token;
        });

        return Http::baseUrl("https://sign.{$host}/api/v1")
            ->withHeaders(['Authorization' => 'Zoho-oauthtoken '.$token])
            ->connectTimeout(5)->timeout(25)->withoutRedirecting();
    }

    private function regionalHost(): string
    {
        $domain = config('zoho-sign.domain');

        return $domain === 'zohocloud.ca' ? $domain : 'zoho.'.$domain;
    }

    private function json(Response $response): array
    {
        $data = $response->json();
        if (! $response->successful() || ! is_array($data) || ($data['code'] ?? null) !== 0) {
            throw new RuntimeException('Zoho Sign rejected the request. Check the document, account permissions, and API credits.');
        }

        return $data;
    }
}
