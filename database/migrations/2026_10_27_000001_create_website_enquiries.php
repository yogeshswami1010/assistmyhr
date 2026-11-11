<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDefaultConnection() === 'saas_tenant') return;

        $schema = Schema::connection('saas_landlord');
        if ($schema->hasTable('saas_website_enquiries')) return;
        $schema->create('saas_website_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20);
            $table->string('name', 150);
            $table->string('email', 254);
            $table->string('phone', 60)->nullable();
            $table->string('company', 200)->nullable();
            $table->string('topic', 60);
            $table->text('message');
            $table->string('notification_status', 20)->default('pending');
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
            $table->index(['kind', 'created_at']);
        });
    }

    public function down(): void
    {
        if (DB::getDefaultConnection() === 'saas_tenant') return;

        Schema::connection('saas_landlord')->dropIfExists('saas_website_enquiries');
    }
};
