<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('jobs')->where('status', 'active')->update(['status' => 'open']);
        DB::table('jobs')->whereIn('status', ['inactive', 'expired'])->update(['status' => 'closed']);
    }

    public function down(): void
    {
        // Existing Open and Closed jobs cannot be distinguished from converted jobs.
    }
};
