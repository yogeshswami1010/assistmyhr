<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB};

return new class extends Migration {
    public function up(): void
    {
        Schema::connection('saas_landlord')->table('saas_subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('plan_id')->nullable()->change();
        });
        DB::connection('saas_landlord')->table('saas_subscriptions')->where('status', 'trialing')->update(['plan_id'=>null]);
    }

    public function down(): void
    {
        // Retain nullable plan IDs: a trial has no paid plan to restore on rollback.
    }
};
