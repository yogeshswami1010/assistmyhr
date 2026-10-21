<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('candidate_reply_mailboxes', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('email');
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('token_expires_at');
            $table->text('sync_cursor')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('sync_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('candidate_reply_mailboxes'); }
};
