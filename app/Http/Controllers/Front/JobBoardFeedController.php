<?php

namespace App\Http\Controllers\Front;

use App\CompanySetting;
use App\Job;
use App\Saas\Tenant;
use App\Saas\TenantContext;
use App\Services\JobillicoLocations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use XMLWriter;

class JobBoardFeedController
{
    private const CONNECTION = 'saas_job_board_feed';

    public function jooble(): Response
    {
        return $this->feed('publish_on_jooble');
    }

    public function assistMyHr(): Response
    {
        return $this->feed('publish_on_assistmyhr');
    }

    public function postJobFree(): Response
    {
        return $this->feed('publish_on_postjobfree');
    }

    public function jobillico(): Response
    {
        return $this->feed('publish_on_jobillico');
    }

    public function talent(): Response
    {
        return $this->feed('publish_on_talent');
    }

    private function feed(string $selection): Response
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement(match ($selection) {
            'publish_on_jooble' => 'jobs',
            'publish_on_jobillico' => 'request',
            default => 'source',
        });

        if (config('saas.enabled')) {
            $tenants = Tenant::where('status', 'active')->withEnabledFeature('job_board_publishing')->with('subscription');
            foreach ($tenants->lazyById() as $tenant) {
                if ($tenant->hasAccess()) {
                    $this->writeWorkspaceJobs($xml, $tenant, $selection);
                }
            }
        } else {
            $this->writeJobs($xml, Job::query(), $selection);
        }

        $xml->endElement();
        $xml->endDocument();

        return new Response($xml->outputMemory(), 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }

    private function writeWorkspaceJobs(XMLWriter $xml, Tenant $tenant, string $selection): void
    {
        $connectionKey = 'database.connections.'.self::CONNECTION;
        $previous = config($connectionKey);
        config([$connectionKey => app(TenantContext::class)->connectionConfig($tenant)]);

        try {
            $this->writeJobs($xml, Job::on(self::CONNECTION), $selection, $tenant);
        } finally {
            DB::purge(self::CONNECTION);
            config([$connectionKey => $previous]);
        }
    }

    private function writeJobs(XMLWriter $xml, Builder $query, string $selection, ?Tenant $tenant = null): void
    {
        $assistMyHr = $selection === 'publish_on_assistmyhr';
        $postJobFree = $selection === 'publish_on_postjobfree';
        $jobillico = $selection === 'publish_on_jobillico';
        $talent = $selection === 'publish_on_talent';
        if (($jobillico && !$query->getModel()->getConnection()->getSchemaBuilder()->hasColumn('jobs', $selection))
            || ($talent && !$query->getModel()->supportsTalentPublishing())) {
            return;
        }
        $companyName = $jobillico ? '' : trim((string) CompanySetting::on($query->getModel()->getConnectionName())->value('company_name'));
        $locations = $jobillico ? app(JobillicoLocations::class) : null;
        $jobs = $query->publicJobPage()->where($selection, true)
            ->with([$assistMyHr || $talent ? 'jobLocation.country' : 'jobLocation', 'jobType', 'currency'])
            ->when($talent, fn (Builder $jobs) => $jobs->with('location.country'))
            ->when($selection === 'publish_on_jooble', fn (Builder $jobs) => $jobs->with('skills.skill'))
            ->when($jobillico, fn (Builder $jobs) => $jobs->with(['location', 'category']))->lazyById();

        foreach ($jobs as $job) {
            $talentLocation = $talent ? $job->talentLocationFields() : [];
            $contentIssue = $talent ? $job->talentFeedIssue($companyName, $talentLocation) : $job->joobleContentIssue();
            if ($contentIssue !== null || ($postJobFree && $job->postJobFreeCompanyIssue($companyName) !== null)) {
                continue;
            }

            $locationId = $job->jobLocation->first()?->id ?? ($talent ? $job->location?->id : null);
            $jobRouteParameters = [$job->slug, $locationId];
            $jobRouteParameters['job_board'] = match ($selection) {
                'publish_on_assistmyhr' => 'jobxoom',
                'publish_on_postjobfree' => 'postjobfree',
                'publish_on_jobillico' => 'jobillico',
                'publish_on_talent' => 'talent',
                default => 'jooble',
            };
            if ($tenant) {
                $jobRouteParameters['workspace'] = $tenant->slug;
            }
            $reference = ($tenant ? $tenant->uuid.'-' : '').$job->id;
            if ($jobillico) {
                $locationId = $locations->forJob($job);
                $this->writeJobillicoJob($xml, $job, $jobRouteParameters, $reference, $locationId);
                continue;
            }
            $fields = match ($selection) {
                'publish_on_assistmyhr' => $this->assistMyHrFields($job, $jobRouteParameters, $reference, $companyName),
                'publish_on_postjobfree' => $this->postJobFreeFields($job, $jobRouteParameters, $reference, $companyName),
                'publish_on_talent' => $this->talentFields($job, $jobRouteParameters, $reference, $companyName, $talentLocation),
                default => $this->joobleFields($job, $jobRouteParameters, $companyName),
            };

            $xml->startElement('job');
            if ($selection === 'publish_on_jooble') {
                $xml->writeAttribute('id', $reference);
            }
            foreach ($fields as $name => $value) {
                if ($assistMyHr && $name !== 'description') {
                    $xml->writeElement($name, $this->xmlText((string) $value));
                } elseif (filled($value)) {
                    $this->writeCdata($xml, $name, $value);
                }
            }
            if ($talent && $job->show_salary) {
                $this->writeTalentSalary($xml, $job);
            }
            $xml->endElement();
        }
    }

    private function writeJobillicoJob(XMLWriter $xml, Job $job, array $routeParameters, string $reference, ?int $locationId): void
    {
        $xml->startElement('job');
        $xml->writeElement('elang', 'en');
        $this->writeCdata($xml, 'stitle_en', $job->title);
        $this->writeCdata($xml, 'srefnumber', 'ATS-'.$reference);
        if ($locationId !== null) {
            $xml->startElement('olocations');
            $xml->writeElement('ilocation', (string) $locationId);
            $xml->endElement();
        }
        $xml->writeElement('iavailability', (string) $job->total_positions);
        $xml->writeElement('dbegin', $job->publicationDate()->format('Y-m-d'));
        $experience = $job->show_work_experience ? $job->work_experience_label : null;
        $salary = $job->show_salary ? $this->salary($job) : null;
        $description = '';
        foreach ([
            'Job location' => $job->joobleRegion(),
            'Job category' => $job->category?->name,
            'Salary' => $salary,
            'Work experience' => $experience,
        ] as $label => $value) {
            if (filled($value)) {
                $description .= '<p>'.$label.': '.e($value).'</p>';
            }
        }
        $this->writeCdata($xml, 'sdescription_en', $description.$job->job_description);

        $status = match ((int) $job->employment_subtype) {
            1 => 'bcontract',
            2 => 'bseasonal',
            4 => 'bpermanent',
            default => null,
        };
        if ($status) {
            $xml->startElement('ostatus');
            $xml->writeElement($status, '1');
            $xml->endElement();
        }
        $schedule = match (strtolower(trim((string) $job->jobType?->job_type))) {
            'full time', 'full-time' => 'bfulltime',
            'part time', 'part-time' => 'bparttime',
            default => null,
        };
        if ($schedule) {
            $xml->startElement('oschedule');
            $xml->writeElement($schedule, '1');
            $xml->endElement();
        }

        if ($salary !== null) {
            $this->writeJobillicoSalary($xml, $job);
        }
        $experienceCode = match (strtolower(trim((string) $experience))) {
            '0-2 years', '0–2 years' => 'EXP2',
            '3-5 years', '3–5 years' => 'EXP5',
            '6-9 years', '6–9 years' => 'EXP9',
            '10+ years', '10 + years' => 'EXP10',
            default => null,
        };
        if ($experienceCode !== null) {
            $xml->startElement('oexperiencecriteria');
            $xml->writeElement('evalue', $experienceCode);
            $xml->endElement();
        }

        $this->writeCdata($xml, 'surl_en', route('jobs.jobApply', $routeParameters));
        $xml->endElement();
    }

    private function writeJobillicoSalary(XMLWriter $xml, Job $job): void
    {
        $unit = strtoupper(trim((string) $job->pay_according));
        if (!in_array($unit, ['HOUR', 'YEAR'], true) || strtoupper($job->currency->currency_code) !== 'CAD') {
            return;
        }

        $minimum = $job->pay_type === 'Maximum' ? null : $job->starting_salary;
        $maximum = match ($job->pay_type) {
            'Range', 'Maximum' => $job->maximum_salary,
            'Exact Amount' => $minimum,
            default => null,
        };
        if (!is_numeric($minimum) && !is_numeric($maximum)) {
            return;
        }

        $xml->startElement('osalary');
        if (is_numeric($minimum)) {
            $xml->writeElement('fmin', (string) $minimum);
        }
        if (is_numeric($maximum)) {
            $xml->writeElement('fmax', (string) $maximum);
        }
        $xml->writeElement('eunit', $unit);
        $xml->endElement();
    }

    private function talentFields(Job $job, array $routeParameters, string $reference, string $companyName, array $location): array
    {
        return [
            'referencenumber' => $reference,
            'title' => $job->title,
            'company' => $companyName,
            ...$location,
            'dateposted' => $job->publicationDate()->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
            'url' => route('jobs.jobDetail', $routeParameters),
            'description' => $this->descriptionWithoutCss($job->job_description),
        ];
    }

    private function writeTalentSalary(XMLWriter $xml, Job $job): void
    {
        $currency = strtoupper(trim((string) $job->currency?->currency_code));
        $period = strtolower(trim((string) $job->pay_according));
        if ($currency === '' || !in_array($period, ['year', 'month', 'week', 'day', 'hour'], true)) {
            return;
        }

        $minimum = $job->pay_type === 'Maximum' ? null : $job->starting_salary;
        $maximum = match ($job->pay_type) {
            'Range', 'Maximum' => $job->maximum_salary,
            'Exact Amount' => $minimum,
            default => null,
        };
        if (!is_numeric($minimum) && !is_numeric($maximum)) {
            return;
        }

        $xml->startElement('salary');
        if (is_numeric($maximum)) {
            $this->writeCdata($xml, 'salary_max', (string) $maximum);
        }
        if (is_numeric($minimum)) {
            $this->writeCdata($xml, 'salary_min', (string) $minimum);
        }
        $this->writeCdata($xml, 'salary_currency', $currency);
        $this->writeCdata($xml, 'period', $period);
        $this->writeCdata($xml, 'type', 'BASE_SALARY');
        $xml->endElement();
    }

    private function assistMyHrFields(Job $job, array $routeParameters, string $reference, string $companyName): array
    {
        return [
            'title' => $job->title,
            'url' => route('jobs.jobDetail', $routeParameters),
            'company' => $companyName,
            'location' => $job->joobleRegion(),
            'country' => $job->jobLocation->first()?->country?->country_code,
            'referencenumber' => $reference,
            'salary' => $this->salary($job),
            'jobtype' => $job->jobType?->job_type,
            'description' => $job->job_description,
        ];
    }

    private function joobleFields(Job $job, array $routeParameters, string $companyName): array
    {
        $skills = $job->skills->pluck('skill.name')->filter();
        $description = $this->descriptionWithoutCss($job->job_description);
        if ($skills->isNotEmpty()) {
            $description = '<p>Skills</p><ul>'.$skills->map(fn ($skill) => '<li>'.e($skill).'</li>')->implode('').'</ul>'.$description;
        }

        return [
            'link' => route('jobs.jobDetail', $routeParameters),
            'apply_url' => route('jobs.jobApply', $routeParameters),
            'name' => $job->title,
            'region' => $job->joobleRegion(),
            'description' => $description,
            'pubdate' => $job->publicationDate()->format('d.m.Y'),
            'updated' => $job->updated_at?->format('d.m.Y'),
            'expire' => $job->publicationEndDate()?->format('d.m.Y'),
            'company' => $companyName,
            'jobtype' => $job->show_job_type ? $job->jobType?->job_type : null,
            'salary' => $job->show_salary ? $this->salary($job) : null,
        ];
    }

    private function descriptionWithoutCss(string $description): string
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->loadHTML('<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>'
            .$description.'</body></html>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        foreach ($xpath->query('//style') as $style) {
            $style->parentNode->removeChild($style);
        }
        foreach ($xpath->query('//*[@style]') as $element) {
            $element->removeAttribute('style');
        }

        $html = '';
        foreach ($document->getElementsByTagName('body')->item(0)->childNodes as $node) {
            $html .= $document->saveHTML($node);
        }

        return $html;
    }

    private function postJobFreeFields(Job $job, array $routeParameters, string $reference, string $companyName): array
    {
        $date = $job->publicationDate();
        if ($job->created_at?->isSameDay($date)) {
            $date = $job->created_at;
        }
        $description = preg_replace('~<br\b[^>]*>|</(?:p|div|li|h[1-6]|tr)>~i', "\n", $job->job_description);

        return [
            'title' => $job->title,
            'date' => $date->format('Y-m-d H:i:s'),
            'referencenumber' => $reference,
            'url' => route('jobs.jobDetail', $routeParameters),
            'company' => $companyName,
            'location' => $job->joobleRegion(),
            'description' => trim(html_entity_decode(strip_tags($description), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            'salary' => $this->salary($job),
        ];
    }

    private function salary(Job $job): ?string
    {
        if (!$job->currency?->currency_code) {
            return null;
        }

        $amount = match ($job->pay_type) {
            'Range' => $job->starting_salary.'–'.$job->maximum_salary,
            'Maximum' => $job->maximum_salary,
            default => $job->starting_salary,
        };

        return filled($amount) ? $amount.' '.$job->currency->currency_code.($job->pay_according ? ' / '.$job->pay_according : '') : null;
    }

    private function writeCdata(XMLWriter $xml, string $name, string $value): void
    {
        // Strip characters disallowed by XML 1.0 and split embedded CDATA terminators.
        $value = $this->xmlText($value);
        $xml->startElement($name);
        $xml->writeRaw('<![CDATA['.str_replace(']]>', ']]]]><![CDATA[>', $value).']]>');
        $xml->endElement();
    }

    private function xmlText(string $value): string
    {
        return preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $value) ?? '';
    }
}
