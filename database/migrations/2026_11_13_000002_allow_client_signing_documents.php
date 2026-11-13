<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_signing_documents', function (Blueprint $table) {
            $table->unsignedInteger('job_application_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Keep client signing records available after a code rollback.
    }
};
