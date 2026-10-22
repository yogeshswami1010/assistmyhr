<?php

use App\Support\Branding;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach ([
            'company_settings' => 'company_name',
            'smtp_settings' => 'mail_from_name',
            'companies' => 'company_name',
            'saas_tenants' => 'name',
        ] as $table => $column) {
            if (!Schema::hasColumn($table, $column)) {
                continue;
            }

            DB::table($table)->select('id', $column)->chunkById(100, function ($rows) use ($table, $column) {
                foreach ($rows as $row) {
                    $name = Branding::displayName($row->{$column});
                    if ($name !== $row->{$column}) {
                        DB::table($table)->where('id', $row->id)->update([$column => $name]);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        // Keep the new brand; the original spelling of each saved name is unknown.
    }
};
