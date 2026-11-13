<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('job_applications', 'moved_to_trash_at')) {
            Schema::table('job_applications', function (Blueprint $table) {
                $table->timestamp('moved_to_trash_at')->nullable()->index();
            });
        }

        if (!Schema::hasColumn('questions', 'is_knockout')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->boolean('is_knockout')->default(false);
            });
        }

        if (!Schema::hasColumn('questions', 'knockout_answer')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->string('knockout_answer')->nullable();
            });
        }
    }

    public function down(): void
    {
        // These columns may predate this migration and contain applicant data.
    }
};
