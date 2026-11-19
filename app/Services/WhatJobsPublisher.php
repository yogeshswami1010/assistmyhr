<?php

namespace App\Services;

use App\Job;
use App\Saas\TenantContext;
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
            if ($scheduled && $shouldPublish && self::visibleError($state) !== null) {
                return null;
            }

            if (!$shouldPublish) {
                return $this->send($job, $state, 'pause', [
                    'countryCode' => $state['country'] ?? null,
                    'jobIdent' => $remoteId,
                ], ['active' => false]);
            }

            $location = $job->jobLocation()->with('country')->orderBy('job_locations.id')->first()
                ?? $job->location()->with('country')->first();
            $settings = app(WhatJobsJobDetails::class)->forJob($job, $location, $state['country'] ?? null);

            $tenant = app(TenantContext::class)->current();
            $identity = [
                'countryCode' => $settings['countryCode'] ?? null,
                'jobIdent' => $remoteId ?? ($tenant ? $tenant->id.str_pad((string) $job->id, 10, '0', STR_PAD_LEFT) : (string) $job->id),
            ];

            $applicationField = config('services.whatjobs.application_type_field') === 'jobCommunicationType'
                ? 'jobCommunicationType' : 'jobApplicationType';
            $employmentType = $settings['jobEmploymentType'] ?? null;
            $employmentSubtype = $settings['jobEmploymentSubType'] ?? null;
            $scheduleType = $settings['jobScheduleType'] ?? null;
            $workFrom = $settings['jobWorkFrom'] ?? null;
            $content = array_filter([
                'jobEmploymentType' => $employmentType === null ? null : (int) $employmentType,
                'jobEmploymentSubType' => $employmentSubtype === null ? null : (int) $employmentSubtype,
                'jobScheduleType' => $scheduleType === null ? null : (int) $scheduleType,
                'jobSector' => $settings['jobSector'] ?? null,
                'jobDescription' => $this->description($job),
                'jobOpeningCount' => filled($job->total_positions) ? (string) $job->total_positions : null,
                'jobReferenceId' => $job->job_code ?: (string) $job->id,
                'jobSkills' => $job->skills()->with('skill')->get()->pluck('skill.name')->filter()->unique()->values()->all(),
            ], fn ($value) => filled($value));
            if ((int) $employmentSubtype === 1) {
                foreach (['jobPeriod', 'jobLength'] as $field) {
                    if (isset($settings[$field])) {
                        $content[$field] = (int) $settings[$field];
                    }
                }
            }
            if ((int) $scheduleType === 12 && filled($settings['jobScheduleTypeOther'] ?? null)) {
                $content['jobScheduleTypeOther'] = $settings['jobScheduleTypeOther'];
            }

            $contentHash = hash('sha256', json_encode($content, JSON_THROW_ON_ERROR));
            if (!$remoteId) {
                $add = [
                    'jobTitle' => $job->title,
                    'jobWorkFrom' => $workFrom === null ? null : (int) $workFrom,
                    $applicationField => 2,
                    'jobCommunicationURL' => tenant_route('jobs.jobApply', ['slug' => $job->slug, 'location' => $location?->id]),
                ];
                if ((int) $workFrom !== 4) {
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

    private function description(Job $job): string
    {
        $description = $job->job_description;
        $details = [
            'Work experience' => $job->work_experience_label,
            'Salary' => $job->show_salary ? $this->salary($job) : null,
        ];
        foreach ($details as $label => $value) {
            if (filled($value) && !str_contains($description, $value)) {
                $extended = $description.'<p>'.$label.': '.e($value).'</p>';
                if (self::wordCount($extended) <= 1000) {
                    $description = $extended;
                }
            }
        }

        return $description;
    }

    private function salary(Job $job): ?string
    {
        $amount = match ($job->pay_type) {
            'Range' => implode('–', array_filter([$job->starting_salary, $job->maximum_salary], fn ($value) => filled($value))),
            'Maximum' => filled($job->maximum_salary ?: $job->starting_salary) ? 'Up to '.($job->maximum_salary ?: $job->starting_salary) : null,
            'Starting' => filled($job->starting_salary) ? 'From '.$job->starting_salary : null,
            default => $job->starting_salary,
        };

        return filled($amount)
            ? trim($amount.' '.($job->currency?->currency_code ?? '')).($job->pay_according ? ' / '.$job->pay_according : '')
            : null;
    }

    public static function visibleError(array $state): ?string
    {
        $error = $state['error'] ?? null;

        return $error && !str_starts_with($error, 'WhatJobs needs these details in the saved job:') ? $error : null;
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
                        'data' => json_encode([['command' => $command] + array_filter($fields, fn ($value) => filled($value))], JSON_THROW_ON_ERROR),
                    ]);
                $body = $response->json();
                $result = self::responseDetails($body);
                $accepted = self::responseAccepted($result);
                $remoteId = $result['jobID'] ?? null;
                $hasId = (is_string($remoteId) || is_int($remoteId))
                    && ctype_digit((string) $remoteId) && ltrim((string) $remoteId, '0') !== '';
                if ($response->status() === 200 && $accepted && ($command !== 'add' || $hasId)) {
                    if ($command === 'add') {
                        $changes['id'] = (string) $remoteId;
                    }
                    $url = self::listingUrl($result['jobUrl'] ?? null);
                    if ($url !== null) {
                        $changes['url'] = $url;
                    }
                    $state = array_replace($state, $changes);
                } else {
                    $error = self::responseError($response->status(), $result, $token, $accepted);
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
        if (!is_array($body)) {
            return [];
        }
        $item = is_array($body[0] ?? null) ? $body[0] : $body;
        $result = $item['response'] ?? $item;
        if (is_array($result[0] ?? null)) {
            $result = $result[0];
        }

        return is_array($result) ? $result : [];
    }

    private static function responseAccepted(array $result): bool
    {
        $message = $result['message'] ?? null;

        return in_array($result['success'] ?? null, [true, 1, '1', 'true'], true)
            && !(is_array($message) && $message !== [])
            && !(is_string($message) && preg_match('/^\s*invalid command\b/i', $message));
    }

    private static function responseError(int $status, array $result, string $token, bool $accepted): string
    {
        if ($status === 401) {
            return 'WhatJobs rejected the API token.';
        }
        $message = $result['message'] ?? null;
        if (in_array($status, [200, 203, 400], true) && is_string($message)
            && preg_match('/\bcountrycode\b.*\bmissing\b/i', $message)) {
            return 'WhatJobs requires a country code. Add the country to the job’s Locations field or configure WHATJOBS_DEFAULT_COUNTRY_CODE.';
        }
        if ($status === 200 && $accepted) {
            $details = is_string($message) && trim($message) !== '' ? ' '.self::cleanResponseMessage($message, $token) : '';

            return 'WhatJobs returned no valid posting ID.'.$details.' Check the WhatJobs dashboard before retrying.';
        }

        if (in_array($status, [200, 203, 400], true) && is_string($message) && trim($message) !== '') {
            return self::cleanResponseMessage($message, $token);
        }
        if (in_array($status, [200, 203, 400], true) && is_array($message)) {
            $messages = self::validationMessages($message);
            if ($messages !== []) {
                return self::cleanResponseMessage(implode(' ', array_unique($messages)), $token);
            }
        }

        return match ($status) {
            200 => 'WhatJobs returned HTTP 200 without a recognizable posting confirmation. Check the WhatJobs dashboard before retrying.',
            203, 400 => 'WhatJobs rejected the job details.',
            default => 'WhatJobs did not confirm the request (HTTP '.$status.').',
        };
    }

    private static function validationMessages(array $errors, string $field = ''): array
    {
        $messages = [];
        foreach ($errors as $key => $error) {
            $label = is_string($key) ? $key : $field;
            if (is_array($error)) {
                array_push($messages, ...self::validationMessages($error, $label));
            } elseif (is_string($error) && trim($error) !== '') {
                $messages[] = ($label === '' ? '' : $label.': ').trim($error);
            }
        }

        return $messages;
    }

    private static function cleanResponseMessage(string $message, string $token): string
    {
        return Str::limit(strip_tags(str_replace($token, '[redacted]', $message)), 300);
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
