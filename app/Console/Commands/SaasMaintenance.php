<?php

namespace App\Console\Commands;

use App\Saas\Tenant;
use App\Saas\TenantContext;
use Illuminate\Console\Command;

class SaasMaintenance extends Command
{
    protected $signature = 'saas:maintenance {task : minute or daily} {--workspace= : Run only this client workspace} {--debug : Show email reply matching results}';
    protected $description = 'Run ATS maintenance separately in every active client database';

    public function handle(): int
    {
        if (!config('saas.enabled')) { return self::SUCCESS; }
        $commands = match ($this->argument('task')) {
            'minute' => ['candidate-emails:import-replies', 'client-reviews:notify'],
            'daily' => ['job-check-status', 'candidates:purge'],
            default => [],
        };
        if (!$commands) { $this->error('Task must be minute or daily.'); return self::FAILURE; }
        $context = app(TenantContext::class); $failed = false;
        $tenants = Tenant::where('status', 'active');
        if ($this->option('workspace')) {
            $tenants->where('slug', $this->option('workspace'));
            if (!(clone $tenants)->exists()) { $this->error('Active workspace not found.'); return self::FAILURE; }
        }
        foreach ($tenants->cursor() as $tenant) {
            if (!$tenant->hasAccess()) { continue; }
            try {
                $this->line('Workspace: '.$tenant->slug);
                $context->activate($tenant);
                foreach ($commands as $command) {
                    $options = $command === 'candidate-emails:import-replies' && $this->option('debug') ? ['--debug' => true] : [];
                    if ($this->call($command, $options) !== 0) { $failed = true; }
                }
            } catch (\Throwable $e) { report($e); $failed = true; $this->error('Maintenance failed for workspace '.$tenant->slug.'. See application logs.'); }
            finally { $context->reset(); }
        }
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
