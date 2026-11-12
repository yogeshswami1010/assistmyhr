<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRequiredColumnsColumnInJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->text('required_columns')->after('status');
        });

        DB::table('jobs')->update(['required_columns' => json_encode([
            'gender' => false,
            'dob' => false,
            'country' => false,
        ], JSON_THROW_ON_ERROR)]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn('required_columns');
        });
    }
}
