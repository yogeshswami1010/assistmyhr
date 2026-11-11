<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDefaultConnection() === 'saas_tenant') return;

        Schema::connection('saas_landlord')->table('saas_plans', function (Blueprint $table) {
            $table->unsignedInteger('duration_days')->default(30);
        });
    }

    public function down(): void
    {
        if (DB::getDefaultConnection() === 'saas_tenant') return;

        Schema::connection('saas_landlord')->table('saas_plans', function (Blueprint $table) {
            $table->dropColumn('duration_days');
        });
    }
};
