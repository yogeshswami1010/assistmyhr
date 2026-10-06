<?php

namespace App\Console\Commands;

use App\Saas\Tenant;
use App\Saas\TenantContext;
use Illuminate\Console\Command;

class MigrateSaas extends Command
{
    protected $signature = 'saas:migrate {--force : Required in production}';
    protected $description = 'Apply ATS migrations to client databases after migrating the central database';

    public function handle(): int
    {
        if (app()->environment('production') && !$this->option('force')) { $this->error('Use --force after taking database backups.'); return self::FAILURE; }
        $context = app(TenantContext::class); $failed = false;
        foreach (Tenant::whereNotIn('status', ['failed', 'provisioning'])->cursor() as $tenant) {
            if ($tenant->slug === 'main') { continue; }
            try {
                $context->activate($tenant);
                if ($this->call('migrate', ['--database' => 'saas_tenant', '--force' => true]) !== 0) { $failed = true; }
            } catch (\Throwable $e) { report($e); $failed = true; $this->error('Migration failed for '.$tenant->slug); }
            finally { $context->reset(); }
        }
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
