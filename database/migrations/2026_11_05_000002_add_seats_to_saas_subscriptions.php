<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::connection('saas_landlord')->table('saas_subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('seat_count')->default(3);
        });
    }

    public function down(): void
    {
        Schema::connection('saas_landlord')->table('saas_subscriptions', function (Blueprint $table) {
            $table->dropColumn('seat_count');
        });
    }
};
