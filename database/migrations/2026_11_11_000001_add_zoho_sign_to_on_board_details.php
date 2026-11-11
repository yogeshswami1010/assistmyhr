<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('on_board_details', function (Blueprint $table) {
            $table->unsignedBigInteger('zoho_sign_file_id')->nullable();
            $table->string('zoho_sign_request_id', 64)->nullable()->unique();
            $table->string('zoho_sign_status', 32)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('on_board_details', function (Blueprint $table) {
            $table->dropUnique(['zoho_sign_request_id']);
            $table->dropColumn(['zoho_sign_file_id', 'zoho_sign_request_id', 'zoho_sign_status']);
        });
    }
};
