<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dateTime('end_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Jobs reopened without an end date must retain that value on rollback.
    }
};
