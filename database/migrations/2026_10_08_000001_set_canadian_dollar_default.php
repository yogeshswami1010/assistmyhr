<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('currencies')) return;
        $id = DB::table('currencies')->where('currency_code', 'CAD')->value('id');
        $data = ['currency_name'=>'Canadian Dollar','currency_symbol'=>'CA$','currency_code'=>'CAD'];
        if (Schema::hasColumn('currencies','default')) { DB::table('currencies')->update(['default'=>0]); $data['default']=1; }
        if ($id) { DB::table('currencies')->where('id',$id)->update($data); }
        else { $id=DB::table('currencies')->insertGetId($data); }
        if (Schema::hasColumn('company_settings','currency_id')) DB::table('company_settings')->update(['currency_id'=>$id]);
        // Historical currency rows remain for existing onboarding records.
    }
    public function down(): void { /* Previous company defaults cannot be reconstructed. */ }
};
