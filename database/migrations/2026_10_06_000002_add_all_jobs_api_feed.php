<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('job_api_integrations', function (Blueprint $table) {
            $table->unsignedInteger('company_id')->nullable()->change();
            $table->string('feed_scope', 100)->nullable()->unique();
        });
        DB::table('job_api_integrations')->orderBy('id')->chunkById(100, function ($integrations) {
            foreach ($integrations as $integration) {
                DB::table('job_api_integrations')->where('id', $integration->id)
                    ->update(['feed_scope' => 'company:'.$integration->company_id]);
            }
        });
    }

    public function down(): void
    {
        DB::table('job_api_integrations')->whereNull('company_id')->delete();
        Schema::table('job_api_integrations', function (Blueprint $table) {
            $table->dropUnique(['feed_scope']);
            $table->dropColumn('feed_scope');
            $table->unsignedInteger('company_id')->nullable(false)->change();
        });
    }
};
