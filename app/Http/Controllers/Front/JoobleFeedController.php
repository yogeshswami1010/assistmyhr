<?php

namespace App\Http\Controllers\Front;

use App\Job;
use App\Saas\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use XMLWriter;

class JoobleFeedController
{
    public function __invoke(Request $request): Response
    {
        $tenant = config('saas.enabled') ? app(TenantContext::class)->current() : null;
        if (config('saas.enabled')) {
            abort_unless($tenant && $request->query('workspace') === $tenant->slug && $tenant->hasAccess(), 404);
        }

        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('jobs');

        foreach (Job::jooblePublished()->with(['company', 'jobLocation', 'jobType', 'currency'])->lazyById() as $job) {
            if ($job->joobleContentIssue() !== null) {
                continue;
            }

            $fields = [
                'link' => tenant_route('jobs.jobDetail', [$job->slug, $job->jobLocation->first()?->id]),
                'name' => $job->title,
                'region' => $job->joobleRegion(),
                'description' => $job->job_description,
                'pubdate' => $job->publicationDate()->format('d.m.Y'),
                'updated' => $job->updated_at?->format('d.m.Y'),
                'expire' => $job->publicationEndDate()?->format('d.m.Y'),
                'company' => $job->company->show_in_frontend === 'true' ? $job->company->company_name : null,
                'jobtype' => $job->show_job_type ? $job->jobType?->job_type : null,
                'salary' => $this->salary($job),
            ];

            $xml->startElement('job');
            $xml->writeAttribute('id', ($tenant ? $tenant->uuid.'-' : '').$job->id);
            foreach ($fields as $name => $value) {
                if (filled($value)) {
                    $this->writeCdata($xml, $name, $value);
                }
            }
            $xml->endElement();
        }

        $xml->endElement();
        $xml->endDocument();

        return new Response($xml->outputMemory(), 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'no-store']);
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
