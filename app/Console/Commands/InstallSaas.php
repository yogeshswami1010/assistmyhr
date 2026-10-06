<?php

namespace App\Console\Commands;

use App\Saas\ApiKey;
use App\Saas\Plan;
use App\Saas\PlatformAdmin;
use App\Saas\PlatformSetting;
use App\Saas\Subscription;
use App\Saas\Tenant;
use App\Saas\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InstallSaas extends Command
{
    protected $signature = 'saas:install {--email= : Super admin email} {--name= : Super admin name}';
    protected $description = 'Register the existing ATS as the main workspace and create a separate platform administrator';

    public function handle(): int
    {
        if (!DB::connection('saas_landlord')->getSchemaBuilder()->hasTable('saas_tenants')) {
            $this->error('Run php artisan migrate --force first.'); return self::FAILURE;
        }
        $email = $this->option('email') ?: $this->ask('Super admin email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $this->error('Enter a valid email.'); return self::FAILURE; }
        $admin = PlatformAdmin::where('email', $email)->first();
        if (!$admin) {
            $password = $this->secret('New super admin password (at least 12 characters)');
            if (strlen((string) $password) < 12 || $password !== $this->secret('Confirm password')) {
                $this->error('Passwords must match and contain at least 12 characters.'); return self::FAILURE;
            }
            PlatformAdmin::create(['name' => $this->option('name') ?: 'Platform administrator', 'email' => $email, 'password' => Hash::make($password)]);
        }
        $trial = Plan::firstOrCreate(['slug' => 'starter'], ['name' => 'Starter', 'price' => 0, 'currency' => 'INR', 'max_users' => 3, 'max_jobs' => 10, 'max_candidates' => 500, 'storage_mb' => 1024, 'enabled' => true, 'public' => true]);
        $legacy = Plan::firstOrCreate(['slug' => 'existing-workspace'], ['name' => 'Existing workspace', 'price' => 0, 'currency' => 'INR', 'enabled' => true, 'public' => false]);
        foreach (['signup_enabled' => '0', 'trial_days' => '14', 'trial_plan_id' => (string) $trial->id] as $key => $value) {
            PlatformSetting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
        $connection = DB::connection('saas_landlord');
        $company = $connection->table('company_settings')->first();
        $ownerEmail = $connection->table('users')->join('role_user', 'users.id', '=', 'role_user.user_id')->where('role_user.role_id', 1)->orderBy('users.id')->value('users.email');
        $tenant = Tenant::firstOrCreate(['slug' => 'main'], [
            'uuid' => (string) Str::uuid(), 'name' => $company->company_name ?: 'AssistMyHR',
            'owner_email' => $ownerEmail ?: ($company->company_email ?: $email), 'status' => 'active',
            'database_name' => $connection->getDatabaseName(), 'database_driver' => $connection->getDriverName(),
        ]);
        Subscription::firstOrCreate(['tenant_id' => $tenant->id], ['plan_id' => $legacy->id, 'status' => 'active', 'starts_at' => now(), 'expires_at' => null]);
        if ($connection->getSchemaBuilder()->hasTable('job_api_integrations')) {
            foreach ($connection->table('job_api_integrations')->cursor() as $integration) {
                ApiKey::updateOrCreate(['tenant_id' => $tenant->id, 'integration_id' => $integration->id], ['token_hash' => $integration->token_hash]);
            }
        }
        $context = app(TenantContext::class);
        $context->activate($tenant);
        try {
            $this->copyFiles(public_path('user-uploads'), $context->root().'/uploads');
            $this->copyFiles(storage_path('app/private/candidate-calls'), $context->root().'/private/candidate-calls');
        } finally { $context->reset(); }
        // Keep the original files as a backup, but prevent direct web access to private ATS uploads.
        $uploadRoot = public_path('user-uploads');
        if (is_dir($uploadRoot)) {
            $backup = $context->root($tenant).'/legacy-upload.htaccess';
            if (is_file($uploadRoot.'/.htaccess') && !is_file($backup) && !copy($uploadRoot.'/.htaccess', $backup)) { throw new \RuntimeException('Unable to back up upload access rules.'); }
            $deny = "# Private ATS uploads are served by signed SaaS routes.\nRewriteEngine On\nRewriteRule ^ - [F,L]\n";
            if (file_put_contents($uploadRoot.'/.htaccess', $deny) === false) { $this->error('Could not protect the legacy uploads directory.'); return self::FAILURE; }
        }
        $this->info('Platform installed. Existing ATS records remain in the main workspace.');
        $this->line('Set SAAS_ENABLED=true, configure provisioning credentials and SMTP, clear caches, then test signup before opening it in /superadmin/settings.');
        return self::SUCCESS;
    }

    private function copyFiles(string $source, string $destination): void
    {
        if (!is_dir($source)) { return; }
        if (is_link($source)) { throw new \RuntimeException('Migrate the upload directory symlink before installing SaaS.'); }
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $file) {
            if ($file->isLink()) { throw new \RuntimeException('Remove or migrate upload symlinks before installing SaaS.'); }
            $relative = substr($file->getPathname(), strlen($source) + 1);
            $target = $destination.'/'.$relative;
            if ($file->isDir()) { if (!is_dir($target) && !mkdir($target, 0770, true)) { throw new \RuntimeException('Unable to copy upload directory.'); } }
            elseif (!file_exists($target) && !copy($file->getPathname(), $target)) { throw new \RuntimeException('Unable to copy uploads.'); }
        }
    }
}
