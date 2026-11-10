<?php

namespace App\Http\Controllers\Front;

use App\Job;
use App\Saas\Tenant;
use App\Saas\TenantContext;
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

    private function feed(string $selection): Response
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement($selection === 'publish_on_postjobfree' ? 'source' : 'jobs');

        if (config('saas.enabled')) {
            foreach (Tenant::where('status', 'active')->with('subscription')->lazyById() as $tenant) {
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
        $jobs = $query->publicJobPage()->where($selection, true)
            ->with(['company', 'jobLocation', 'jobType', 'currency'])->lazyById();
        $postJobFree = $selection === 'publish_on_postjobfree';

        foreach ($jobs as $job) {
            if ($job->joobleContentIssue() !== null || ($postJobFree && $job->postJobFreeCompanyIssue() !== null)) {
                continue;
            }

            $jobRouteParameters = [$job->slug, $job->jobLocation->first()?->id];
            if ($tenant) {
                $jobRouteParameters['workspace'] = $tenant->slug;
            }
            $reference = ($tenant ? $tenant->uuid.'-' : '').$job->id;
            $fields = $postJobFree ? $this->postJobFreeFields($job, $jobRouteParameters, $reference) : [
                'link' => route('jobs.jobDetail', $jobRouteParameters),
                'apply_url' => route('jobs.jobApply', $jobRouteParameters),
                'name' => $job->title,
                'region' => $job->joobleRegion(),
                'description' => $job->job_description,
                'pubdate' => $job->publicationDate()->format('d.m.Y'),
                'updated' => $job->updated_at?->format('d.m.Y'),
                'expire' => $job->publicationEndDate()?->format('d.m.Y'),
                'company' => $job->company->show_in_frontend === 'true' ? $job->company->company_name : null,
                'jobtype' => $job->jobType?->job_type,
                'salary' => $this->salary($job),
            ];

            $xml->startElement('job');
            if (!$postJobFree) {
                $xml->writeAttribute('id', $reference);
            }
            foreach ($fields as $name => $value) {
                if (filled($value)) {
                    $this->writeCdata($xml, $name, $value);
                }
            }
            $xml->endElement();
        }
    }

    private function postJobFreeFields(Job $job, array $routeParameters, string $reference): array
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
            'company' => $job->company->company_name,
            'location' => $job->joobleRegion(),
            'description' => trim(html_entity_decode(strip_tags($description), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            'salary' => $this->salary($job),
        ];
    }

    private function salary(Job $job): ?string
    {
        if (!$job->show_salary || !$job->currency?->currency_code) {
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
        $value = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $value) ?? '';
        $xml->startElement($name);
        $xml->writeRaw('<![CDATA['.str_replace(']]>', ']]]]><![CDATA[>', $value).']]>');
        $xml->endElement();
    }
}
