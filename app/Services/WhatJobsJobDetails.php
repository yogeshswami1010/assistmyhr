<?php

namespace App\Services;

use App\Country;
use App\Job;
use App\JobLocation;

class WhatJobsJobDetails
{
    public function forJob(Job $job, ?JobLocation $location, ?string $postedCountry): array
    {
        $saved = $job->whatjobs_settings ?? [];
        $description = html_entity_decode(strip_tags(preg_replace(
            '~<br\b[^>]*>|</(?:p|div|li|h[1-6])>~i',
            "\n",
            $job->job_description
        )), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $jobType = $job->jobType?->job_type ?? '';
        $employment = $jobType."\n".$description;
        $locationText = $location?->location ?? '';
        $locationParts = array_values(array_filter(array_map('trim', explode(',', $locationText))));
        $locationCountry = $this->countryCode($location?->country?->country_code);
        $countrySuffix = $this->countrySuffix($locationParts, $locationCountry);
        $work = $jobType."\n".$job->title."\n".$location?->location."\n"
            .$this->descriptionField($description, 'work arrangement|work location|work mode|work from|work setting');
        if (preg_match('/\b(?:fully remote|this is (?:a |an )?(?:remote|hybrid|on[ -]?site) (?:job|role|position))\b/i', $description, $workPhrase)) {
            $work .= "\n".$workPhrase[0];
        }
        $scheduleDetails = $this->descriptionField($description, 'job schedule|work schedule|schedule|shift');
        $schedule = $scheduleDetails ?? $description;
        $customScheduleWords = WhatJobsPublisher::wordCount($scheduleDetails ?? '');
        $customSchedule = $customScheduleWords >= 30 && $customScheduleWords <= 300 ? $scheduleDetails : null;
        $workFrom = $this->matchCode($work, [
            3 => 'hybrid', 4 => 'remote', 1 => 'work[ -]from[ -]home',
            2 => 'office[ -]based|office', 5 => 'on[ -]?site|in[ -]person',
        ]) ?? $saved['jobWorkFrom'] ?? config('services.whatjobs.default_work_from');
        $scheduleType = $this->matchCode($schedule, [
            1 => '8[ -]hour shifts?', 2 => '10[ -]hour shifts?', 3 => '12[ -]hour shifts?',
            4 => 'day shifts?', 5 => 'night shifts?', 6 => 'mon(?:day)?\s*(?:to|through|[-–])\s*fri(?:day)?',
            7 => 'holidays? required', 8 => 'weekend availability', 9 => 'weekends only',
            10 => 'no weekends', 11 => 'overtime required',
        ]) ?? ($customSchedule ? 12 : ($saved['jobScheduleType'] ?? null));

        $employmentType = $this->matchCode($jobType, [1 => 'full[ -]time', 2 => 'part[ -]time'])
            ?? $this->matchCode($description, [1 => 'full[ -]time', 2 => 'part[ -]time'])
            ?? $saved['jobEmploymentType'] ?? null;
        $subtypes = [
            1 => '(?:on )?contract', 2 => 'seasonal', 3 => 'temporary', 4 => 'permanent',
            5 => 'freelance', 6 => 'apprenticeship', 7 => 'graduate', 8 => 'maternity cover',
        ];
        $employmentSubtype = $this->matchCode($jobType, $subtypes)
            ?? $this->matchCode($this->descriptionField($description, 'employment subtype|employment type|job type') ?? '', $subtypes)
            ?? $this->matchCode($description, [
                1 => '(?:days?|months?|years?)\s+(?:fixed-term\s+)?contract|fixed[ -]term contract',
                4 => 'permanent (?:full[ -]time |part[ -]time )?(?:job|role|position|employment)',
            ])
            ?? $saved['jobEmploymentSubType'] ?? null;
        preg_match('/\b(\d+)\s*[- ]?\s*(days?|months?|years?)\s+(?:fixed-term\s+)?contract\b/i', $employment, $duration);
        $category = trim((string) $job->category?->name);
        $sector = match (strtolower($category)) {
            'hr' => 'Human Resources',
            'software', 'information technology', 'it' => 'IT & Software',
            'customer service', 'helpdesk' => 'Customer Service & Helpdesk',
            default => $saved['jobSector'] ?? $category,
        };

        if ($countrySuffix && count($locationParts) > 1) {
            array_pop($locationParts);
        }
        $addressFromDescription = array_filter([
            'jobLocationAddress' => $this->descriptionField($description, 'street address|worksite address'),
            'city' => $this->descriptionField($description, 'city'),
            'state' => $this->descriptionField($description, 'state|province|region'),
            'zip' => $this->descriptionField($description, 'postal code|zip code|postcode'),
        ]);
        return array_replace($saved, $addressFromDescription, $this->locationFields($locationParts), array_filter([
            'countryCode' => $postedCountry ?? $locationCountry
                ?? $countrySuffix
                ?? $this->countryCode($this->descriptionField($description, 'country')),
            'jobWorkFrom' => $workFrom,
            'jobEmploymentType' => $employmentType,
            'jobEmploymentSubType' => $employmentSubtype,
            'jobScheduleType' => $scheduleType,
            'jobScheduleTypeOther' => $customSchedule,
            'jobSector' => $sector,
            'jobLength' => isset($duration[1]) ? (int) $duration[1] : null,
            'jobPeriod' => isset($duration[2]) ? ['day' => 1, 'month' => 2, 'year' => 3][rtrim(strtolower($duration[2]), 's')] : null,
        ], fn ($value) => $value !== null && $value !== ''));
    }

    private function descriptionField(string $description, string $labels): ?string
    {
        return preg_match('/^\h*(?:'.$labels.')\h*:\h*(.+)$/im', $description, $matches) ? trim($matches[1]) : null;
    }

    private function matchCode(string $text, array $patterns): ?int
    {
        foreach ($patterns as $code => $pattern) {
            if (preg_match('/\b(?:'.$pattern.')\b/iu', $text)) {
                return $code;
            }
        }

        return null;
    }

    private function countryCode(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));

        return match ($value) {
            'usa', 'united states of america' => 'US',
            'uk', 'great britain' => 'GB',
            '' => null,
            default => Country::query()->whereRaw('LOWER(country_name) = ?', [$value])
                ->orWhere('country_code', strtoupper($value))->value('country_code'),
        };
    }

    private function countrySuffix(array $parts, ?string $locationCountry): ?string
    {
        $suffix = (string) end($parts);
        // A two-letter suffix on a city can also be a state or province.
        if (strlen($suffix) === 2 && count($parts) === 2 && strcasecmp($parts[0], 'Remote') !== 0) {
            return strcasecmp($suffix, $locationCountry ?? '') === 0 ? $locationCountry : null;
        }

        return $this->countryCode($suffix);
    }

    private function locationFields(array $parts): array
    {
        $postalCode = '\d[\d-]{2,8}|[A-Z]\d[A-Z] \d[A-Z]\d|[A-Z]{1,2}\d[A-Z\d]? \d[A-Z]{2}';
        $regionIndex = count($parts) - 1;
        if (in_array(count($parts), [2, 3], true)
            && preg_match('/^(.+?)\s+('.$postalCode.')$/i', $parts[$regionIndex], $matches)) {
            $parts[$regionIndex] = $matches[1];
            $parts[] = $matches[2];
        }

        return match (count($parts)) {
            4 => array_combine(['jobLocationAddress', 'city', 'state', 'zip'], $parts),
            3 => preg_match('/^(?:'.$postalCode.')$/i', $parts[2])
                ? array_combine(['city', 'state', 'zip'], $parts)
                : array_combine(['jobLocationAddress', 'city', 'state'], $parts),
            2 => array_combine(['city', 'state'], $parts),
            default => [],
        };
    }
}
