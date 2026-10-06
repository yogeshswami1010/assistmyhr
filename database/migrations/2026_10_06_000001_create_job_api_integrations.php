<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('job_api_integrations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id')->unique();
            $table->string('token_hash', 64)->unique();
            $table->string('token_suffix', 8);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_api_integrations');
    }
};
