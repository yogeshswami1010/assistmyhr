<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('jobs', 'company_location')) {
                $table->string('company_location')->nullable();
            }
            if (! Schema::hasColumn('jobs', 'job_code')) {
                $table->string('job_code', 100)->nullable();
            }
            if (! Schema::hasColumn('jobs', 'hiring_status')) {
                $table->string('hiring_status', 50)->default('active');
            }
            if (! Schema::hasColumn('jobs', 'show_job_type')) {
                $table->boolean('show_job_type')->default(false);
            }
            if (! Schema::hasColumn('jobs', 'show_work_experience')) {
                $table->boolean('show_work_experience')->default(false);
            }
            if (! Schema::hasColumn('jobs', 'show_salary')) {
                $table->boolean('show_salary')->default(false);
            }
        });

        // Convert old status values to the new ones
        DB::table('jobs')->where('status', 'active')->update(['status' => 'open']);
        DB::table('jobs')->whereIn('status', ['inactive', 'expired'])->update(['status' => 'closed']);
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            foreach (['company_location', 'job_code'] as $col) {
                if (Schema::hasColumn('jobs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};