<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDefaultConnection() === 'saas_tenant') return;

        Schema::connection('saas_landlord')->table('saas_tenants', function (Blueprint $table) {
            $table->string('google_subject')->nullable()->unique();
        });
    }

    public function down(): void
    {
        if (DB::getDefaultConnection() === 'saas_tenant') return;

        Schema::connection('saas_landlord')->table('saas_tenants', function (Blueprint $table) {
            $table->dropUnique(['google_subject']);
            $table->dropColumn('google_subject');
        });
    }
};
