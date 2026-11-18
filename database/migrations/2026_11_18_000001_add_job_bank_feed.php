<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->boolean('publish_on_jobbank')->default(false);
            $table->string('jobbank_noc_code', 5)->nullable();
            $table->string('jobbank_noc_title', 191)->nullable();
            $table->string('jobbank_city', 191)->nullable();
            $table->string('jobbank_province', 2)->nullable();
        });

        Schema::create('job_bank_feed_state', function (Blueprint $table) {
            $table->string('reference', 64)->primary();
            $table->longText('payload')->nullable();
        });
        DB::table('job_bank_feed_state')->insert(['reference' => '', 'payload' => null]);

        Schema::create('job_bank_feed_events', function (Blueprint $table) {
            $table->id();
            $table->string('recorded_at', 14)->index();
            $table->longText('payload');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_bank_feed_events');
        Schema::dropIfExists('job_bank_feed_state');
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn(['publish_on_jobbank', 'jobbank_noc_code', 'jobbank_noc_title', 'jobbank_city', 'jobbank_province']);
        });
    }
};
