<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('candidate_client_reviews', function (Blueprint $table) {
            $table->text('client_message')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('candidate_client_reviews', function (Blueprint $table) {
            $table->dropColumn('client_message');
        });
    }
};
