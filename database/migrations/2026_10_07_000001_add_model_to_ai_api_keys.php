<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('ai_api_keys') && !Schema::hasColumn('ai_api_keys', 'model')) {
            Schema::table('ai_api_keys', fn (Blueprint $table) => $table->string('model', 191)->nullable());
        }
    }
    public function down(): void
    {
        if (Schema::hasTable('ai_api_keys') && Schema::hasColumn('ai_api_keys', 'model')) {
            Schema::table('ai_api_keys', fn (Blueprint $table) => $table->dropColumn('model'));
        }
    }
};
