<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_service_settings', function (Blueprint $table) {
            $table->id(); $table->string('imap_host')->nullable();
            $table->unsignedInteger('imap_port')->default(993); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('tenant_service_settings'); }
};
