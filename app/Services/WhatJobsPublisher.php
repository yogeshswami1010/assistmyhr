<?php

namespace App\Services;

use App\Job;
use App\Saas\TenantContext;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WhatJobsPublisher
{
    public const WORK_FROM = [1 => 'Home', 2 => 'Office', 3 => 'Hybrid', 4 => 'Remote', 5 => 'Onsite'];
    public const EMPLOYMENT_TYPE = [1 => 'Full-time', 2 => 'Part-time'];
    public const EMPLOYMENT_SUBTYPE = [
        1 => 'Contract', 2 => 'Seasonal', 3 => 'Temporary', 4 => 'Permanent', 5 => 'Freelance',
        6 => 'Apprenticeship', 7 => 'Graduate', 8 => 'Maternity cover',
    ];
    public const SCHEDULE = [
        1 => '8-hour shift', 2 => '10-hour shift', 3 => '12-hour shift', 4 => 'Day shift',
        5 => 'Night shift', 6 => 'Monday to Friday', 7 => 'Holidays', 8 => 'Weekend availability',
        9 => 'Weekends only', 10 => 'No weekends', 11 => 'Overtime', 12 => 'Other',
    ];
    public const CONTRACT_PERIOD = [1 => 'Days', 2 => 'Months', 3 => 'Years'];

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

            $tenant = app(TenantContext::class)->current();
            $settings = $job->whatjobs_settings ?? [];
            $identity = [
                'countryCode' => $state['country'] ?? $settings['countryCode'],
                'jobIdent' => $remoteId ?? ($tenant ? $tenant->id.str_pad((string) $job->id, 10, '0', STR_PAD_LEFT) : (string) $job->id),
            ];

            if (!$shouldPublish) {
                return $this->send($job, $state, 'pause', $identity, ['active' => false]);
            }

            $applicationField = config('services.whatjobs.application_type_field') === 'jobCommunicationType'
                ? 'jobCommunicationType' : 'jobApplicationType';
            $content = [
                'jobEmploymentType' => (int) $settings['jobEmploymentType'],
                'jobEmploymentSubType' => (int) $settings['jobEmploymentSubType'],
                'jobScheduleType' => (int) $settings['jobScheduleType'],
                'jobStartType' => 1,
                'jobSector' => $settings['jobSector'],
                'jobDescription' => $job->job_description,
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
                $location = $job->jobLocation()->orderBy('job_locations.id')->first();
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
                $result = $response->json('response');
                if ($response->status() === 200 && ($result['success'] ?? null) === true
                    && ($command !== 'add' || ctype_digit((string) ($result['jobID'] ?? '')))) {
                    if ($command === 'add') {
                        $changes['id'] = (string) $result['jobID'];
                    }
                    $state = array_replace($state, $changes);
                } else {
                    $error = match ($response->status()) {
                        203 => is_string($result['message'] ?? null)
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
