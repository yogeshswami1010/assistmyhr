<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        
        Schema::table('job_applications', function (Blueprint $table) {
            $table->text('address')->nullable()->after('phone');
        });

        foreach (DB::table('jobs')->select('id', 'required_columns')->lazyById() as $job) {
            $columns = json_decode($job->required_columns ?? '', true);
            $columns = is_array($columns) ? $columns : [];
            if (!isset($columns['address'])) {
                $columns['address'] = false;
                DB::table('jobs')->where('id', $job->id)->update([
                    'required_columns' => json_encode($columns, JSON_THROW_ON_ERROR),
                ]);
            }
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }

};
