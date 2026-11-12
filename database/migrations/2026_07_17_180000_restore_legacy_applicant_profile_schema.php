<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('job_applications', 'is_candidate')) {
            Schema::table('job_applications', function (Blueprint $table) {
                $table->boolean('is_candidate')->default(false);
            });
            DB::table('job_applications')->whereNull('job_id')->update(['is_candidate' => true]);
        }

        $marketingColumns = array_diff(
            ['is_marketing', 'marketing_added_at', 'marketing_label'],
            Schema::getColumnListing('job_applications')
        );
        if ($marketingColumns !== []) {
            Schema::table('job_applications', function (Blueprint $table) use ($marketingColumns) {
                if (in_array('is_marketing', $marketingColumns, true)) {
                    $table->boolean('is_marketing')->default(false);
                }
                if (in_array('marketing_added_at', $marketingColumns, true)) {
                    $table->timestamp('marketing_added_at')->nullable();
                }
                if (in_array('marketing_label', $marketingColumns, true)) {
                    $table->string('marketing_label')->nullable();
                }
            });
        }

        if (!Schema::hasColumn('applicant_notes', 'mentions')) {
            Schema::table('applicant_notes', function (Blueprint $table) {
                $table->json('mentions')->nullable();
            });
        }

        if (!Schema::hasTable('job_client_notes')) {
            Schema::create('job_client_notes', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('job_id');
                $table->unsignedInteger('user_id')->nullable();
                $table->text('note_text');
                $table->timestamps();
                $table->foreign('job_id')->references('id')->on('jobs')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('job_application_status_histories')) {
            Schema::create('job_application_status_histories', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('job_application_id');
                $table->unsignedInteger('from_status_id')->nullable();
                $table->unsignedInteger('to_status_id')->nullable();
                $table->unsignedInteger('user_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->foreign('job_application_id')->references('id')->on('job_applications')->cascadeOnDelete();
                $table->foreign('from_status_id')->references('id')->on('application_status')->nullOnDelete();
                $table->foreign('to_status_id')->references('id')->on('application_status')->nullOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Existing installations may already own this schema and its data.
    }
};
