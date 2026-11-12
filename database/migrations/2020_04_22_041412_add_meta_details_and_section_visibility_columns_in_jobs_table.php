<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AddMetaDetailsAndSectionVisibilityColumnsInJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->text('meta_details')->after('required_columns');
            $table->text('section_visibility')->after('meta_details');
        });

        $jobs = DB::table('jobs')->select('id', 'title', 'job_description')->lazyById();
        foreach ($jobs as $job) {
            $metaDetails = [
                'title' => $job->title,
                'description' => strip_tags(Str::substr(html_entity_decode($job->job_description), 0, 150))
            ];

            $sectionVisibility = [
                'profile_image' =>'yes',
                'resume' => 'yes',
                'cover_letter' => 'yes',
                'terms_and_conditions' => 'yes'
            ];

            DB::table('jobs')->where('id', $job->id)->update([
                'meta_details' => json_encode($metaDetails, JSON_THROW_ON_ERROR),
                'section_visibility' => json_encode($sectionVisibility, JSON_THROW_ON_ERROR),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn('meta_details');
            $table->dropColumn('section_visibility');
        });
    }
}
