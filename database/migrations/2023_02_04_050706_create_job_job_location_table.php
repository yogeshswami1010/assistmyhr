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
        Schema::create('job_job_locations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('job_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('location_id')->references('id')->on('job_locations')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
        $timestamp = now();
        DB::table('job_job_locations')->insertUsing(
            ['job_id', 'location_id', 'created_at', 'updated_at'],
            DB::table('jobs')->select('id', 'location_id')->selectRaw('?, ?', [$timestamp, $timestamp])
        );
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('job_job_locations');
    }
};
