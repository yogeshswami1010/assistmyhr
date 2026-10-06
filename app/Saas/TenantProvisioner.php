<?php

namespace App\Saas;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantProvisioner
{
    public function create(array $data): Tenant
    {
        $source = DB::connection('saas_landlord');
        $uuid = (string) Str::uuid();
        $driver = $source->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb', 'sqlite'], true)) { throw new \RuntimeException('SaaS provisioning supports MySQL/MariaDB.'); }
        $prefix = config('saas.database_prefix');
        if (!preg_match('/\A[a-z0-9_]{1,25}\z/', $prefix)) { throw new \RuntimeException('Invalid SaaS database prefix.'); }
        $database = $driver === 'sqlite'
            ? rtrim(config('saas.storage_root'), '/\\').'/'.$uuid.'/database.sqlite'
            : $prefix.'_'.str_replace('-', '', $uuid);
        $tenant = Tenant::create([
            'uuid' => $uuid, 'slug' => $data['slug'], 'name' => $data['company_name'],
            'owner_email' => $data['email'], 'status' => 'provisioning',
            'database_name' => $database, 'database_driver' => $driver === 'sqlite' ? 'sqlite' : 'mysql',
        ]);
        $context = app(TenantContext::class);
        $previous = $context->current();
        try {
            if ($driver === 'sqlite') {
                if (!app()->environment('testing')) { throw new \RuntimeException('SQLite provisioning is reserved for integration tests.'); }
                if (!is_dir(dirname($database))) { mkdir(dirname($database), 0770, true); }
                touch($database);
            } else {
                $config = config('database.connections.saas_landlord');
                $config['username'] = config('saas.provisioning_username') ?: $config['username'];
                $config['password'] = config('saas.provisioning_password') ?? $config['password'];
                $config['url'] = null;
                $config['database'] = null;
                config(['database.connections.saas_provisioning' => $config]);
                DB::purge('saas_provisioning');
                DB::connection('saas_provisioning')->statement('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            }
            $context->activate($tenant);
            $target = DB::connection('saas_tenant');
            $this->cloneSchema($source, $target);
            $target->transaction(function () use ($source, $target, $data) { $this->seed($source, $target, $data); });
            $plan = Plan::where('id', PlatformSetting::valueFor('trial_plan_id'))->where('enabled', true)->firstOrFail();
            Subscription::create([
                'tenant_id' => $tenant->id, 'plan_id' => $plan->id, 'status' => 'trialing',
                'starts_at' => now(), 'expires_at' => now()->addDays((int) PlatformSetting::valueFor('trial_days', 14)),
            ]);
            $tenant->status = 'active'; $tenant->save();
            AuditLog::create(['tenant_id' => $tenant->id, 'action' => 'tenant.created', 'reason' => 'Client signup completed.']);
            return $tenant->fresh();
        } catch (\Throwable $e) {
            $tenant->status = 'failed'; $tenant->save();
            AuditLog::create(['tenant_id' => $tenant->id, 'action' => 'tenant.provisioning_failed', 'reason' => 'Provisioning failed; inspect server logs.']);
            throw $e;
        } finally {
            $previous ? $context->activate($previous) : $context->reset();
            DB::purge('saas_provisioning');
        }
    }

    private function cloneSchema($source, $target): void
    {
        $sqlite = $source->getDriverName() === 'sqlite';
        $tables = $sqlite
            ? $source->select("SELECT name, sql FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'")
            : $source->select('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'');
        $target->getSchemaBuilder()->disableForeignKeyConstraints();
        try {
            foreach ($tables as $table) {
                $name = $sqlite ? $table->name : array_values((array) $table)[0];
                if (str_starts_with($name, 'saas_')) { continue; }
                if (!preg_match('/\A[a-zA-Z0-9_]+\z/', $name)) { throw new \RuntimeException('Invalid template table.'); }
                $sql = $sqlite ? $table->sql : array_values((array) $source->selectOne('SHOW CREATE TABLE `'.$name.'`'))[1];
                $sql = preg_replace('/ AUTO_INCREMENT=\d+/', '', $sql);
                if (!$sqlite) {
                    $sql = str_replace('REFERENCES `'.$source->getDatabaseName().'`.`', 'REFERENCES `', $sql);
                    if (preg_match('/REFERENCES\s+`[^`]+`\s*\.\s*`/i', $sql)) { throw new \RuntimeException('Tenant template cannot contain cross-database foreign keys.'); }
                }
                $target->unprepared($sql);
            }
            if ($sqlite) {
                foreach ($source->select("SELECT sql, tbl_name FROM sqlite_master WHERE type = 'index' AND sql IS NOT NULL") as $index) {
                    if (!str_starts_with($index->tbl_name, 'saas_')) { $target->unprepared($index->sql); }
                }
            }
        } finally { $target->getSchemaBuilder()->enableForeignKeyConstraints(); }
    }

    private function seed($source, $target, array $data): void
    {
        // Copy schema metadata and public reference data, never ATS records or credentials.
        foreach (['migrations', 'modules', 'permissions', 'currencies', 'language_settings'] as $table) {
            if (!$source->getSchemaBuilder()->hasTable($table)) { continue; }
            foreach ($source->table($table)->cursor() as $row) { $target->table($table)->insert((array) $row); }
        }
        foreach (['company_settings', 'theme_settings', 'application_settings', 'google_captcha_settings', 'sms_settings', 'smtp_settings', 'linked_in_settings', 'zoom_settings', 'message_settings'] as $table) {
            if ($target->getSchemaBuilder()->hasTable($table)) { $this->insertDefaults($target, $table, []); }
        }
        $this->updateKnown($target, 'company_settings', [
            'company_name' => $data['company_name'], 'company_email' => $data['email'],
            'company_phone' => '', 'address' => '', 'website' => '', 'timezone' => 'UTC', 'locale' => 'eng',
            'latitude' => 0, 'longitude' => 0, 'candidate_calls_enabled' => false,
        ]);
        $this->updateKnown($target, 'theme_settings', ['primary_color' => '#2563eb', 'disable_frontend' => 0]);
        $this->updateKnown($target, 'google_captcha_settings', ['status' => 'inactive', 'v2_status' => 'inactive', 'v3_status' => 'inactive']);
        $this->updateKnown($target, 'smtp_settings', ['mail_username' => '', 'mail_password' => '', 'mail_from_name' => $data['company_name'], 'mail_from_email' => $data['email'], 'verified' => 0]);
        $this->updateKnown($target, 'sms_settings', ['nexmo_status' => 'deactive', 'telnyx_status' => 'deactive']);
        $this->updateKnown($target, 'zoom_settings', ['enable_zoom' => 0]);
        if ($target->getSchemaBuilder()->hasTable('language_settings') && !$target->table('language_settings')->where('language_code', 'eng')->exists()) {
            $this->insertDefaults($target, 'language_settings', ['language_code' => 'eng', 'language_name' => 'English', 'status' => 'enabled']);
        }
        $this->insertDefaults($target, 'companies', ['company_name' => $data['company_name'], 'company_email' => $data['email'], 'status' => 'active', 'show_in_frontend' => 'true']);
        $this->insertDefaults($target, 'roles', ['id' => 1, 'name' => 'admin', 'display_name' => 'Administrator']);
        $this->insertDefaults($target, 'users', ['id' => 1, 'name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password'])]);
        $target->table('role_user')->insert(['user_id' => 1, 'role_id' => 1]);
        foreach ($target->table('permissions')->pluck('id') as $permission) {
            $target->table('permission_role')->insert(['role_id' => 1, 'permission_id' => $permission]);
        }
    }

    public function insertDefaults($connection, string $table, array $overrides): void
    {
        $row = [];
        foreach ($connection->getSchemaBuilder()->getColumns($table) as $column) {
            $name = $column['name'];
            if (array_key_exists($name, $overrides)) { $row[$name] = $overrides[$name]; continue; }
            if ($name === 'id' || $column['auto_increment'] || $column['nullable'] || $column['default'] !== null) { continue; }
            $type = strtolower($column['type']);
            $row[$name] = match (true) {
                preg_match('/\Aenum\(\x27([^\x27]+)\x27/', $type, $match) === 1 => $match[1],
                str_contains($type, 'int'), str_contains($type, 'decimal'), str_contains($type, 'float'), str_contains($type, 'double'), str_contains($type, 'bool') => 0,
                str_contains($type, 'json') => '{}',
                str_contains($type, 'date'), str_contains($type, 'time') => now()->format('Y-m-d H:i:s'),
                default => '',
            };
        }
        // Query builder treats insert([]) as a no-op; still create a defaults-only settings row.
        $connection->table($table)->insert($row ?: ['id' => null]);
    }

    private function updateKnown($connection, string $table, array $values): void
    {
        if (!$connection->getSchemaBuilder()->hasTable($table)) { return; }
        $columns = $connection->getSchemaBuilder()->getColumnListing($table);
        $values = array_intersect_key($values, array_flip($columns));
        if ($values) { $connection->table($table)->update($values); }
    }
}
