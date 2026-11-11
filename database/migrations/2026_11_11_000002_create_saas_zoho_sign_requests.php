<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDefaultConnection() === 'saas_tenant') {
            return;
        }

        Schema::connection('saas_landlord')->create('saas_zoho_sign_requests', function (Blueprint $table) {
            $table->string('request_id', 64)->primary();
            $table->foreignId('tenant_id')->constrained('saas_tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDefaultConnection() !== 'saas_tenant') {
            Schema::connection('saas_landlord')->dropIfExists('saas_zoho_sign_requests');
        }
    }
};
