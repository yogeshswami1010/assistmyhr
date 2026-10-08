<?php
namespace App\Http\Controllers\Front;
use App\JobApiIntegration;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
class JobsWidgetController extends Controller {
    public function __invoke(Request $request, JobApiIntegration $integration) {
        abort_unless(\Illuminate\Support\Facades\URL::hasValidSignature($request, true, ['page']),403);
        abort_unless(hash_equals(substr($integration->token_hash,0,16),(string)$request->query('version')),403);
        $all=$integration->feed_scope==='all' && $integration->company_id===null;
        abort_unless($integration->enabled && ($all || $integration->company?->status==='active'),404);
        $request->attributes->set('job_api_all_jobs',$all);
        $request->attributes->set('job_api_company_id',$integration->company_id);
        $apiRequest=$request->duplicate();
        $apiRequest->merge(['per_page'=>100]);
        $feed=(new \App\Http\Controllers\Api\JobController)->index($apiRequest)->getData(true);
        return response()->view('front.jobs-widget',['feed'=>$feed,'request'=>$request])->header('Cache-Control','no-store');
    }
}
