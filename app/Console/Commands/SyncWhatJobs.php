<?php

namespace App\Console\Commands;

use App\Job;
use App\Saas\TenantContext;
use App\Services\WhatJobsPublisher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SyncWhatJobs extends Command
{
    protected $signature = 'whatjobs:sync';
    protected $description = 'Reconcile WhatJobs listings with the ATS publication window';

    public function handle(TenantContext $context, WhatJobsPublisher $publisher): int
    {
        if (blank(config('services.whatjobs.token')) || !Schema::hasColumn('jobs', 'publish_on_whatjobs')) {
            return self::SUCCESS;
        }

        if (config('saas.enabled') && !$context->current()) {
            $this->error('Run this command through SaaS maintenance in workspace mode.');

            return self::FAILURE;
        }

        $enabled = !config('saas.enabled') || $context->featureEnabled('job_board_publishing');
        $jobs = Job::withTrashed()->where(function ($query) {
            $query->where('publish_on_whatjobs', true)->orWhere('whatjobs_sync->active', true);
        })->lazyById();

        foreach ($jobs as $job) {
            $publisher->sync($job, $enabled, scheduled: true);
        }

        return self::SUCCESS;
    }
}
