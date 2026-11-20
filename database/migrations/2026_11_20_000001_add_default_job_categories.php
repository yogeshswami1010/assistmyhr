<?php

use App\JobCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        JobCategory::addDefaults(DB::connection($this->getConnection()));
    }

    public function down(): void
    {
        // Categories may already be referenced by jobs and skills.
    }
};
