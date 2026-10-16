<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasColumn('todo_items', 'assigned_to')) {
            Schema::table('todo_items', fn (Blueprint $table) => $table->unsignedInteger('assigned_to')->nullable()->index());
        }
    }
    public function down(): void {
        Schema::table('todo_items', fn (Blueprint $table) => $table->dropColumn('assigned_to'));
    }
};
