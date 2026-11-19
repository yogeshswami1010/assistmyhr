<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->boolean('publish_on_whatjobs')->default(false)->index();
            $table->json('whatjobs_settings')->nullable();
            $table->json('whatjobs_sync')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex(['publish_on_whatjobs']);
            $table->dropColumn(['publish_on_whatjobs', 'whatjobs_settings', 'whatjobs_sync']);
        });
    }
};
