<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->string('status', 50)->nullable()->default('open')->change();
        });

        // Preserve existing states. An empty legacy ENUM value must be reviewed before publishing.
    }

    public function down(): void
    {
        // Keep the wider column so current job statuses are preserved on rollback.
    }
};
