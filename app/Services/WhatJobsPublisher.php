<?php

namespace App\Services;

use App\Job;
use App\Saas\TenantContext;
use DomainException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WhatJobsPublisher
{
    public static function wordCount(string $html): int
    {
        $text = preg_replace('/<[^>]+>/u', ' ', $html);
        $text = html_entity_decode($text ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_match_all('/[\p{L}\p{N}]+(?:[’\x27-][\p{L}\p{N}]+)*/u', $text) ?: 0;
    }

    public function sync(Job $job, bool $publishingEnabled, bool $scheduled = false): ?string
    {
        if (!$job->publish_on_whatjobs && !$job->whatjobs_sync) {
            return null;
        }

        return $job->getConnection()->transaction(function () use ($job, $publishingEnabled, $scheduled) {
            $job = $job->newQuery()->withTrashed()->lockForUpdate()->find($job->id);
            if (!$job) {
                return null;
            }

            $state = $job->whatjobs_sync ?? [];
            $remoteId = $state['id'] ?? null;
            $active = $state['active'] ?? false;
            $shouldPublish = $publishingEnabled && $job->publish_on_whatjobs && !$job->trashed()
                && $job->newQuery()->publicJobPage()->whereKey($job->id)->exists();

            if (!$shouldPublish && !$active) {
                return $job->publish_on_whatjobs && !$remoteId
                    ? 'WhatJobs will publish when this job is publicly available.'
                    : null;
            }

            // Leave rejected submissions for the recruiter to correct and save again.
            if ($scheduled && $shouldPublish && !empty($state['error'])) {
                return null;
            }

            if (!$shouldPublish) {
                return $this->send($job, $state, 'pause', [
                    'countryCode' => $state['country'],
                    'jobIdent' => $remoteId,
                ], ['active' => false]);
            }

            $location = $job->jobLocation()->with('country')->orderBy('job_locations.id')->first()
                ?? $job->location()->with('country')->first();
            try {
                $settings = app(WhatJobsJobDetails::class)->forJob($job, $location, $state['country'] ?? null);
            } catch (DomainException $exception) {
                return $this->recordResult($job, $state, $remoteId ? 'update' : 'add', $exception->getMessage());
            }

            $tenant = app(TenantContext::class)->current();
            $identity = [
                'countryCode' => $settings['countryCode'],
                'jobIdent' => $remoteId ?? ($tenant ? $tenant->id.str_pad((string) $job->id, 10, '0', STR_PAD_LEFT) : (string) $job->id),
            ];

            $applicationField = config('services.whatjobs.application_type_field') === 'jobCommunicationType'
                ? 'jobCommunicationType' : 'jobApplicationType';
            $description = $job->job_description;
            $experience = $job->work_experience_label;
            if (filled($experience) && !str_contains($description, $experience)) {
                $withExperience = $description.'<p>Work experience: '.e($experience).'</p>';
                if (self::wordCount($withExperience) <= 1000) {
                    $description = $withExperience;
                }
            }
            $content = [
                'jobEmploymentType' => (int) $settings['jobEmploymentType'],
                'jobEmploymentSubType' => (int) $settings['jobEmploymentSubType'],
                'jobScheduleType' => (int) $settings['jobScheduleType'],
                'jobStartType' => 1,
                'jobSector' => $settings['jobSector'],
                'jobDescription' => $description,
                'jobOpeningCount' => (string) (int) $job->total_positions,
                'jobReferenceId' => $job->job_code ?: (string) $job->id,
            ];
            if ($content['jobEmploymentSubType'] === 1) {
                $content['jobPeriod'] = (int) $settings['jobPeriod'];
                $content['jobLength'] = (int) $settings['jobLength'];
            }
            if ($content['jobScheduleType'] === 12) {
                $content['jobScheduleTypeOther'] = $settings['jobScheduleTypeOther'];
            }

            $contentHash = hash('sha256', json_encode($content, JSON_THROW_ON_ERROR));
            if (!$remoteId) {
                $add = [
                    'jobTitle' => $job->title,
                    'jobWorkFrom' => (int) $settings['jobWorkFrom'],
                    $applicationField => 2,
                    'jobCommunicationURL' => tenant_route('jobs.jobApply', ['slug' => $job->slug, 'location' => $location?->id]),
                ];
                if ($add['jobWorkFrom'] !== 4) {
                    $add += array_intersect_key($settings, array_flip(['city', 'state', 'zip', 'jobLocationAddress']));
                }
                if ($job->company?->show_in_frontend === 'true') {
                    $add['companyName'] = $job->company->company_name;
                }

                return $this->send($job, $state, 'add', $identity + $content + $add, [
                    'country' => $identity['countryCode'],
                    'active' => true,
                    'content_hash' => $contentHash,
                ]);
            }

            $message = null;
            if (($state['content_hash'] ?? null) !== $contentHash) {
                $message = $this->send($job, $state, 'update', $identity + $content, ['content_hash' => $contentHash]);
                $state = $job->whatjobs_sync;
                if (isset($state['error'])) {
                    return $message;
                }
            }
            if (!$active) {
                return $this->send($job, $state, 'activate', $identity, ['active' => true]);
            }
            if (($state['error_command'] ?? null) === 'pause') {
                $state['error'] = null;
                $state['error_command'] = null;
                $job->whatjobs_sync = $state;
                Job::withoutTimestamps(fn () => $job->saveQuietly());
            }

            return $message;
        });
    }

    private function send(Job $job, array $state, string $command, array $fields, array $changes): string
    {
        $token = trim((string) config('services.whatjobs.token'));
        $error = null;
        if ($token === '') {
            $error = 'The WhatJobs API token has not been configured.';
        } else {
            try {
                $response = Http::withHeaders(['x-feed-token' => $token])->acceptJson()
                    ->connectTimeout(3)->timeout(8)->withoutRedirecting()->asMultipart()
                    ->post('https://api.whatjobs.com/api/v1/jobs/submit/json', [
                        'data' => json_encode([['command' => $command] + $fields], JSON_THROW_ON_ERROR),
                    ]);
                $result = self::responseDetails($response->json());
                if ($response->status() === 200 && ($result['success'] ?? null) === true
                    && ($command !== 'add' || ctype_digit((string) ($result['jobID'] ?? '')))) {
                    if ($command === 'add') {
                        $changes['id'] = (string) $result['jobID'];
                    }
                    $url = self::listingUrl($result['jobUrl'] ?? null);
                    if ($url !== null) {
                        $changes['url'] = $url;
                    }
                    $state = array_replace($state, $changes);
                } else {
                    $error = match ($response->status()) {
                        203, 400 => is_string($result['message'] ?? null)
                            ? Str::limit(strip_tags(str_replace($token, '[redacted]', $result['message'])), 300)
                            : 'WhatJobs rejected the job details.',
                        401 => 'WhatJobs rejected the API token.',
                        default => 'WhatJobs did not confirm the request (HTTP '.$response->status().').',
                    };
                }
            } catch (ConnectionException) {
                $error = 'Could not confirm the request with WhatJobs. Check the posting before retrying.';
            }
        }

        return $this->recordResult($job, $state, $command, $error);
    }

    public static function listingUrl(mixed $url): ?string
    {
        if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');

        return ($parts['scheme'] ?? '') === 'https' && !isset($parts['user'])
            && ($host === 'whatjobs.com' || str_ends_with($host, '.whatjobs.com')) ? $url : null;
    }

    public static function responseDetails(mixed $body): array
    {
        $result = $body['response'] ?? $body[0]['response'] ?? [];

        return is_array($result) ? $result : [];
    }

    private function recordResult(Job $job, array $state, string $command, ?string $error): string
    {
        $state['error'] = $error;
        $state['error_command'] = $error !== null ? $command : null;
        $job->whatjobs_sync = $state;
        Job::withoutTimestamps(fn () => $job->saveQuietly());

        if ($error !== null) {
            return 'WhatJobs: '.$error;
        }

        return match ($command) {
            'add' => 'WhatJobs accepted the job posting.',
            'pause' => 'WhatJobs paused the posting.',
            'activate' => 'WhatJobs reactivated the posting.',
            default => 'WhatJobs updated the posting.',
        };
    }
}
