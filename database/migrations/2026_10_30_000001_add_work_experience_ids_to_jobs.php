<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('jobs', fn (Blueprint $table) => $table->json('work_experience_ids')->nullable());
    }

    public function down(): void
    {
        Schema::table('jobs', fn (Blueprint $table) => $table->dropColumn('work_experience_ids'));
    }
};
