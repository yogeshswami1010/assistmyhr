<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $schema = Schema::connection('saas_landlord');
        if (!$schema->hasColumn('users', 'email_verified_at')) {
            $schema->table('users', function (Blueprint $table) { $table->timestamp('email_verified_at')->nullable(); });
            \Illuminate\Support\Facades\DB::connection('saas_landlord')->table('users')->update(['email_verified_at' => now()]);
        }
        $schema->create('saas_platform_admins', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email')->unique();
            $table->string('password'); $table->rememberToken(); $table->timestamps();
        });
        $schema->create('saas_plans', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('slug')->unique();
            $table->decimal('price', 10, 2)->default(0); $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('max_users')->nullable(); $table->unsignedInteger('max_jobs')->nullable();
            $table->unsignedInteger('max_candidates')->nullable(); $table->unsignedInteger('storage_mb')->nullable();
            $table->boolean('enabled')->default(true); $table->boolean('public')->default(true); $table->timestamps();
        });
        $schema->create('saas_tenants', function (Blueprint $table) {
            $table->id(); $table->uuid('uuid')->unique(); $table->string('slug', 50)->unique();
            $table->string('name'); $table->string('owner_email');
            $table->string('status')->default('provisioning');
            $table->string('database_name')->unique(); $table->string('database_driver')->default('mysql');
            $table->timestamps();
        });
        $schema->create('saas_subscriptions', function (Blueprint $table) {
            $table->id(); $table->foreignId('tenant_id')->unique()->constrained('saas_tenants');
            $table->foreignId('plan_id')->constrained('saas_plans');
            $table->string('status')->default('trialing'); $table->timestamp('starts_at');
            $table->timestamp('expires_at')->nullable(); $table->timestamps();
        });
        $schema->create('saas_audit_logs', function (Blueprint $table) {
            $table->id(); $table->foreignId('tenant_id')->nullable()->constrained('saas_tenants');
            $table->foreignId('admin_id')->nullable()->constrained('saas_platform_admins');
            $table->string('action'); $table->text('reason');
            $table->json('before')->nullable(); $table->json('after')->nullable(); $table->timestamps();
        });
        $schema->create('saas_api_keys', function (Blueprint $table) {
            $table->id(); $table->foreignId('tenant_id')->constrained('saas_tenants');
            $table->unsignedInteger('integration_id'); $table->string('token_hash', 64)->unique();
            $table->unique(['tenant_id', 'integration_id']); $table->timestamps();
        });
        $schema->create('saas_platform_settings', function (Blueprint $table) {
            $table->id(); $table->string('key')->unique(); $table->text('value'); $table->timestamps();
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('saas_landlord');
        foreach (['saas_platform_settings', 'saas_api_keys', 'saas_audit_logs', 'saas_subscriptions', 'saas_tenants', 'saas_plans', 'saas_platform_admins'] as $table) {
            $schema->dropIfExists($table);
        }
    }
};
