<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddCompanyIdColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('jobs', 'company_id')) {
            Schema::table('jobs', function (Blueprint $table) {
                $table->unsignedInteger('company_id')->nullable()->after('slug');
            });
        }

        $hasCompanyForeignKey = collect(Schema::getForeignKeys('jobs'))->contains(
            fn (array $key) => $key['columns'] === ['company_id'] && $key['foreign_table'] === 'companies'
        );
        if (!$hasCompanyForeignKey) {
            Schema::table('jobs', function (Blueprint $table) {
                $table->foreign('company_id')
                    ->references('id')->on('companies')
                    ->onUpdate('cascade')->onDelete('cascade');
            });
        }

        $companyId = DB::table('companies')->orderBy('id')->value('id');
        DB::table('jobs')->whereNull('company_id')->update(['company_id' => $companyId]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn(['company_id']);
        });
    }
}
