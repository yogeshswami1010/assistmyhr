<?php

namespace App\Saas;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TenantContext
{
    private ?Tenant $tenant = null;
    private array $baseline;

    public function __construct()
    {
        $this->baseline = [
            'database.default' => config('database.default'),
            'filesystems.disks' => config('filesystems.disks'),
            'filesystems.default' => config('filesystems.default'),
            'cache.prefix' => config('cache.prefix'),
            'cache.stores.file.path' => config('cache.stores.file.path'),
            'cache.default' => config('cache.default'),
            'services.deepseek' => config('services.deepseek'),
            'services.candidate_email_imap' => config('services.candidate_email_imap'),
            'mail' => config('mail'),
            'app.name' => config('app.name'),
            'app.locale' => config('app.locale', 'en'),
        ];
    }

    public function current(): ?Tenant { return $this->tenant; }
    public function root(?Tenant $tenant = null): string
    {
        $tenant ??= $this->tenant;
        if (!$tenant || !preg_match('/\A[a-f0-9-]{36}\z/', $tenant->uuid)) {
            throw new \RuntimeException('A valid tenant is required for private storage.');
        }
        return rtrim(config('saas.storage_root'), '/\\').'/'.$tenant->uuid;
    }

    public function connectionConfig(Tenant $tenant): array
    {
        $config = config('database.connections.saas_landlord');
        if ($tenant->database_driver === 'sqlite') {
            // SQLite is only used by the isolated integration suite.
            if ($config['driver'] !== 'sqlite') { throw new \RuntimeException('Invalid tenant database driver.'); }
            $config['database'] = $tenant->database_name;
        } else {
            if (!preg_match('/\A[a-zA-Z0-9_]{1,64}\z/', $tenant->database_name)) { throw new \RuntimeException('Invalid tenant database name.'); }
            $config['database'] = $tenant->database_name;
            if ($tenant->slug !== 'main') {
                $config['username'] = config('saas.tenant_username') ?: $config['username'];
                $config['password'] = config('saas.tenant_password') ?? $config['password'];
            }
        }
        $config['url'] = null;
        return $config;
    }

    public function activate(Tenant $tenant): void
    {
        config($this->baseline);
        config(['database.connections.saas_tenant' => $this->connectionConfig($tenant)]);
        DB::purge('saas_tenant');
        DB::setDefaultConnection('saas_tenant');
        $this->tenant = $tenant;
        $root = $this->root();
        foreach (['uploads/temp', 'private/candidate-calls', 'cache', 'public'] as $directory) {
            if (!is_dir($root.'/'.$directory) && !mkdir($root.'/'.$directory, 0770, true) && !is_dir($root.'/'.$directory)) {
                throw new \RuntimeException('Tenant storage directory cannot be created.');
            }
        }
        config([
            'filesystems.default' => 'local',
            'filesystems.disks.local.root' => $root.'/uploads',
            'filesystems.disks.public.root' => $root.'/public',
            'filesystems.disks.candidate_call_audio.root' => $root.'/private/candidate-calls',
            'cache.prefix' => 'tenant_'.$tenant->uuid,
            'cache.stores.file.path' => $root.'/cache',
            'cache.default' => 'file',
        ]);
        // SMTP stays client-specific; DeepSeek uses the company default until overridden.
        config(['services.deepseek.key' => $this->baseline['services.deepseek']['key'] ?? null, 'services.deepseek.model' => $this->baseline['services.deepseek']['model'] ?? 'deepseek-chat', 'services.deepseek.source' => 'company',
            'services.candidate_email_imap.host' => null, 'mail.ai_search_smtp' => null]);
        $smtp = DB::getSchemaBuilder()->hasTable('smtp_settings') ? DB::table('smtp_settings')->first() : null;
        $transport = \App\Services\SmtpConfiguration::transport($smtp);
        config(['mail.default' => 'tenant', 'mail.driver' => 'smtp', 'mail.mailers.tenant' => $transport,
            'mail.host' => $transport['host'], 'mail.port' => $transport['port'],
            'mail.encryption' => $transport['encryption'], 'mail.username' => $transport['username'],
            'mail.password' => $transport['password'], 'mail.from' => $transport['from'],
            'mail.ai_search_smtp' => $transport]);
        if (DB::connection('saas_landlord')->getSchemaBuilder()->hasTable('ai_api_keys')) {
            $companyKey = \App\AiApiKey::on('saas_landlord')->whereRaw('LOWER(provider) = ?', ['deepseek'])->active()->orderBy('sort_order')->orderBy('id')->first();
            if ($companyKey && trim((string) $companyKey->api_key) !== '') {
                config(['services.deepseek.key' => $companyKey->api_key, 'services.deepseek.model' => $companyKey->model ?: 'deepseek-chat']);
            }
        }
        if (DB::getSchemaBuilder()->hasTable('ai_api_keys')) {
            $key = \App\AiApiKey::whereRaw('LOWER(provider) = ?', ['deepseek'])->active()->orderBy('sort_order')->orderBy('id')->first();
            if ($key && trim((string) $key->api_key) !== '') { config(['services.deepseek.key' => $key->api_key, 'services.deepseek.model' => $key->model ?: 'deepseek-chat', 'services.deepseek.source' => 'client']); }
        }
        config(['services.candidate_email_imap.host' => \App\Services\SmtpConfiguration::inboxHost($transport['host']), 'services.candidate_email_imap.port' => 993]);
        if (DB::getSchemaBuilder()->hasTable('tenant_service_settings') && ($service = DB::table('tenant_service_settings')->first()) && $service->imap_host) {
            config(['services.candidate_email_imap.host' => $service->imap_host, 'services.candidate_email_imap.port' => $service->imap_port]);
        }
        if (DB::getSchemaBuilder()->hasTable('company_settings') && ($company = DB::table('company_settings')->first())) {
            config(['app.name' => $company->company_name]);
            app()->setLocale($company->locale ?: $this->baseline['app.locale']);
        }
        $this->resetManagers();
    }

    public function reset(): void
    {
        DB::purge('saas_tenant');
        config($this->baseline);
        app()->setLocale($this->baseline['app.locale']);
        DB::setDefaultConnection($this->baseline['database.default']);
        $this->tenant = null;
        $this->resetManagers();
    }

    private function resetManagers(): void
    {
        foreach (['local', 'public', 'candidate_call_audio', 's3'] as $disk) { Storage::forgetDisk($disk); }
        if (app()->bound('cache')) { app('cache')->forgetDriver(); }
        if (app()->bound('auth')) { app('auth')->forgetGuards(); }
        if (app()->bound('mail.manager')) { app('mail.manager')->forgetMailers(); }
    }
}
