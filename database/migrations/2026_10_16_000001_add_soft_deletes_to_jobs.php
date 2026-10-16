<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('jobs') && !Schema::hasColumn('jobs', 'deleted_at')) {
            Schema::table('jobs', fn (Blueprint $table) => $table->softDeletes());
        }
    }
    public function down(): void
    {
        if (Schema::hasColumn('jobs', 'deleted_at')) {
            Schema::table('jobs', fn (Blueprint $table) => $table->dropSoftDeletes());
        }
    }
};
