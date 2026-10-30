<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $cadId = DB::table('currencies')->where('currency_code', 'CAD')->value('id');
        if (! $cadId) {
            $cadId = DB::table('currencies')->insertGetId([
                'currency_name' => 'Canadian Dollar',
                'currency_symbol' => 'CA$',
                'currency_code' => 'CAD',
                'default' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        DB::table('currencies')->update(['default' => 0]);
        DB::table('currencies')->where('id', $cadId)->update(['default' => 1]);
        DB::table('company_settings')->update(['currency_id' => $cadId, 'locale' => 'en', 'front_language' => 0]);
    }

    public function down(): void
    {
        // Preserve configured defaults and existing monetary data on rollback.
    }
};
