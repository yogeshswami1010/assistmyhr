<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::connection('saas_landlord')->table('saas_plans', function (Blueprint $table) {
            $table->unsignedInteger('duration_days')->default(30);
        });
    }

    public function down(): void
    {
        Schema::connection('saas_landlord')->table('saas_plans', function (Blueprint $table) {
            $table->dropColumn('duration_days');
        });
    }
};
