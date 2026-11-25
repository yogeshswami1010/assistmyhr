<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->boolean('publish_on_jobillico')->default(false);
            $table->unsignedBigInteger('jobillico_location_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn(['publish_on_jobillico', 'jobillico_location_id']);
        });
    }
};
