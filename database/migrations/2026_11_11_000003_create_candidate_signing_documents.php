<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_signing_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('job_application_id');
            $table->foreign('job_application_id')->references('id')->on('job_applications')->cascadeOnDelete();
            $table->string('filename');
            $table->string('recipient_email', 254);
            $table->string('zoho_sign_request_id', 64)->unique();
            $table->string('zoho_sign_status', 32)->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_signing_documents');
    }
};
