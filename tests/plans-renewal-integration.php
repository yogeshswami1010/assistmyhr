<?php

// In-memory SQLite only. An alternate Composer autoloader may be passed as argv[1].
$root = dirname(__DIR__);
$loader = require ($argv[1] ?? $root.'/vendor/autoload.php');
$loader->setPsr4('App\\', $root.'/app');
require_once $root.'/app/Saas/helpers.php';
use App\Saas\{Plan, Tenant, Subscription, SubscriptionService, PlatformAdmin, PlatformSetting, AuditLog};
use App\Http\Controllers\Saas\PlatformController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, Auth};
use Illuminate\Http\Request;

$app = new Illuminate\Foundation\Application($root);
$app->instance('config', new Illuminate\Config\Repository([
    'app'=>['url'=>'https://example.test','key'=>str_repeat('t',32),'cipher'=>'AES-256-CBC'],
    'database'=>['default'=>'saas_landlord','connections'=>['saas_landlord'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>true]]],
    'auth'=>['defaults'=>['guard'=>'platform'],'guards'=>['platform'=>['driver'=>'session','provider'=>'admins']], 'providers'=>['admins'=>['driver'=>'eloquent','model'=>PlatformAdmin::class]]],
    'hashing'=>['driver'=>'bcrypt'],
    'session'=>['driver'=>'array','lifetime'=>120,'lottery'=>[0,100],'path'=>'/','domain'=>null,'secure'=>false],
    'view'=>['paths'=>[$root.'/resources/views'],'compiled'=>$root.'/storage/framework/views'],
]));
Illuminate\Support\Facades\Facade::setFacadeApplication($app);
foreach ([Illuminate\Events\EventServiceProvider::class, Illuminate\Database\DatabaseServiceProvider::class,
    Illuminate\Filesystem\FilesystemServiceProvider::class, Illuminate\Encryption\EncryptionServiceProvider::class,
    Illuminate\Cookie\CookieServiceProvider::class, Illuminate\Session\SessionServiceProvider::class,
    Illuminate\Auth\AuthServiceProvider::class, Illuminate\Hashing\HashServiceProvider::class, Illuminate\Routing\RoutingServiceProvider::class,
    Illuminate\Translation\TranslationServiceProvider::class, Illuminate\Validation\ValidationServiceProvider::class,
    Illuminate\View\ViewServiceProvider::class] as $provider) { $app->register($provider); }
$app->boot();
$session=$app['session']->driver(); $session->start(); $app->instance('session.store',$session);
$request=Request::create('https://example.test/superadmin/plans'); $request->setLaravelSession($session); $app->instance('request',$request);
Request::macro('validate',function($rules){return app('validator')->make($this->all(),$rules)->validate();});
Carbon\Carbon::setTestNow('2026-11-05 12:00:00');
Schema::create('users',function(Blueprint $t){$t->id();});
(require $root.'/database/migrations/2026_10_06_000003_create_saas_platform.php')->up();
$migration=require $root.'/database/migrations/2026_11_05_000001_add_duration_to_saas_plans.php';
$migration->up();
(require $root.'/database/migrations/2026_11_05_000002_add_seats_to_saas_subscriptions.php')->up();
(require $root.'/database/migrations/2026_11_05_000003_allow_trials_without_a_plan.php')->up();
$admin=PlatformAdmin::create(['name'=>'Admin','email'=>'admin@example.test','password'=>'unused']); Auth::guard('platform')->setUser($admin);
$controller=new PlatformController;
$checks=0;
function check($condition,$message){global $checks;if(!$condition)throw new RuntimeException($message);$checks++;}
function rejected($fn,$message){try{$fn();}catch(Illuminate\Validation\ValidationException $e){check(true,$message);return;}throw new RuntimeException($message);}
$data=['name'=>'Monthly','duration_days'=>30,'price'=>25,'currency'=>'USD','enabled'=>1,'public'=>1];
$controller->savePlan(Request::create('/','POST',$data));
$controller->savePlan(Request::create('/','POST',array_replace($data,['name'=>'Quarterly','duration_days'=>90])));
check(Plan::count()===2,'Multiple plans can be created');
$plan=Plan::where('name','Quarterly')->firstOrFail();
$controller->savePlan(Request::create('/','PUT',array_replace($data,['name'=>'Quarterly updated','duration_days'=>95])),$plan);
check($plan->fresh()->durationDays()===95,'Duration and name can be edited');
foreach ([0,3651,'invalid'] as $days) { rejected(fn()=>$controller->savePlan(Request::create('/','POST',array_replace($data,['duration_days'=>$days]))),'Invalid duration rejected'); }
$tenant=Tenant::create(['uuid'=>'test-uuid','slug'=>'test','name'=>'Client','owner_email'=>'client@example.test','database_name'=>'unused','status'=>'active']);
$subscription=Subscription::create(['tenant_id'=>$tenant->id,'plan_id'=>null,'status'=>'trialing','starts_at'=>now(),'expires_at'=>now()->addDays(14)]);
$change=Request::create('/','POST',['plan_id'=>$plan->id,'seat_count'=>3,'reason'=>'Paid activation']);
rejected(fn()=>(new SubscriptionService)->extend($tenant,$admin,['days'=>30,'reason'=>'No plan selected']),'Standalone trial requires a paid plan for activation');
check($subscription->fresh()->plan_id===null && $subscription->fresh()->userLimit()===3,'Trial has no paid plan and allows three users');
$controller->changePlan($change,$tenant);
$subscription->refresh();
check($subscription->status==='active' && $subscription->expires_at->eq(now()->addDays(95)),'Trial upgrade uses full plan duration');
$controller->changePlan($change,$tenant);
check($subscription->fresh()->expires_at->eq(now()->addDays(95)),'Active plan change preserves expiry');
$subscription->update(['expires_at'=>now()->subDay()]);
check(!$tenant->fresh()->hasAccess(),'Expired plan blocks access');
$service=new SubscriptionService;
$renewed=$service->extend($tenant,$admin,['reason'=>'Approved client renewal']);
check($renewed->hasAccess() && $renewed->expires_at->eq(now()->addDays(95)),'Default renewal uses plan duration from today');
$renewed=$service->extend($tenant,$admin,['reason'=>'Early renewal']);
check($renewed->expires_at->eq(now()->addDays(190)),'Early renewal adds duration to existing expiry');
$subscription->refresh()->update(['expires_at'=>now()->subDay()]);
Illuminate\Support\Facades\DB::table('users')->insert([['id'=>1],['id'=>2],['id'=>3]]);
$app->instance(App\Saas\TenantContext::class,new class($tenant) extends App\Saas\TenantContext {
    public function __construct(private Tenant $selected) {}
    public function current(): ?Tenant { return $this->selected; }
});
$app['router']->get('/account/subscription',fn()=>null)->name('saas.subscription');
$app['router']->getRoutes()->refreshNameLookups();
$clientRequest=Request::create('/account/subscription/request-plan','POST',['plan_id'=>$plan->id,'seat_count'=>3,'reason'=>'Please renew my expired account']);
$clientRequest->setUserResolver(fn()=>new class { public int $id=1;public function cans($permission){return true;} });
(new App\Http\Controllers\Saas\AccountController)->requestPlan($clientRequest);
check(!$tenant->fresh()->hasAccess(),'Renewal request alone does not grant access');
$sent=AuditLog::where('action','subscription.change_requested')->latest('id')->firstOrFail();
check($sent->tenant_id===$tenant->id && $sent->after['duration_days']===95,'Expired client can request renewal with duration recorded');
$plan->update(['public'=>false]);
rejected(fn()=>(new App\Http\Controllers\Saas\AccountController)->requestPlan($clientRequest),'Client cannot request a private plan');
$plan->update(['public'=>true]);
$controller->changePlan($change,$tenant);
check($subscription->fresh()->expires_at->eq(now()->addDays(95)),'Expired activation starts full duration');
rejected(fn()=>$controller->deletePlan($plan),'Assigned plan protected from deletion');
$unused=Plan::where('name','Monthly')->firstOrFail();
PlatformSetting::create(['key'=>'trial_plan_id','value'=>(string)$unused->id]);
check($subscription->fresh()->plan_id!==$unused->id,'Standalone trials do not reference trial setting plan');
PlatformSetting::where('key','trial_plan_id')->delete();
$controller->deletePlan($unused);
check(!Plan::find($unused->id) && AuditLog::where('action','plan.deleted')->exists(),'Unused plan deletion audited');
$controller->extend(Request::create('/','POST',['seat_count'=>3,'days'=>null,'expires_at'=>null,'plan_id'=>$plan->id,'reason'=>'Renew using configured duration']),$tenant);
check($subscription->fresh()->expires_at->eq(now()->addDays(190)),'Admin renewal form uses selected plan duration when custom fields are blank');
rejected(fn()=>$controller->extend(Request::create('/','POST',['seat_count'=>3,'days'=>10,'expires_at'=>now()->addDays(20)->toDateString(),'reason'=>'Invalid']),$tenant),'Conflicting renewal dates rejected');
// Compile the modified Blade templates to catch directive and PHP syntax errors.
foreach (['platform-plans','plan-form','platform-tenant','platform-requests','subscription-content','pricing','public-plans'] as $view) {
    $compiled=$app['blade.compiler']->compileString(file_get_contents($root.'/resources/views/saas/'.$view.'.blade.php'));
    try { token_get_all($compiled,TOKEN_PARSE); } catch (\ParseError $e) { throw new \RuntimeException($view.': '.$e->getMessage()); }
    check(true,$view.' compiles');
}
config(['saas.enabled'=>true]);
$app['router']->get('/register',fn()=>null)->name('register');
$app['router']->getRoutes()->refreshNameLookups();
Plan::query()->update(['public'=>false]);
$monthly=Plan::create(['name'=>'Monthly','slug'=>'monthly-live','price'=>75,'currency'=>'CAD','duration_days'=>30,'public'=>true,'enabled'=>true]);
$yearly=Plan::create(['name'=>'Yearly','slug'=>'yearly-live','price'=>820,'currency'=>'CAD','duration_days'=>365,'public'=>true,'enabled'=>true]);
$account=new App\Http\Controllers\Saas\AccountController;
foreach ([$monthly,$yearly] as $pricedPlan) {
    $clientRequest->merge(['plan_id'=>$pricedPlan->id,'seat_count'=>3,'total_price'=>'0.01']);
    $account->requestPlan($clientRequest);
    $quote=AuditLog::where('action','subscription.change_requested')->latest('id')->firstOrFail()->after;
    check($quote['total_price']===($pricedPlan->id===$monthly->id?'225.00':'2460.00'),'Three-user total calculated from server plan price');
    check($quote['seat_count']===3 && $subscription->fresh()->seat_count===3,'Request records seats without changing approved allowance');
}
$clientRequest->merge(['plan_id'=>$monthly->id,'seat_count'=>5]);
$account->requestPlan($clientRequest);
check(AuditLog::where('action','subscription.change_requested')->latest('id')->firstOrFail()->after['total_price']==='375.00','Additional seat request quotes total five-user subscription');
check($subscription->fresh()->userLimit()===3,'Pending additional seats do not increase quota');
$quota=new App\Saas\QuotaService;
$tenant->unsetRelation('subscription');
rejected(fn()=>$quota->assertCanCreate('users'),'Fourth member blocked before seat approval');
$controller->changePlan(Request::create('/','POST',['plan_id'=>$monthly->id,'seat_count'=>5,'reason'=>'Approved five seats']),$tenant);
$tenant->unsetRelation('subscription');
$quota->assertCanCreate('users');
Illuminate\Support\Facades\DB::table('users')->insert([['id'=>4],['id'=>5]]);
rejected(fn()=>$quota->assertCanCreate('users'),'Sixth member blocked after all five approved seats are used');
check($subscription->fresh()->seat_count===5,'Super admin approval persists seat allowance');
foreach ([0,2,4,1.5,100001] as $seats) {
    $clientRequest->merge(['seat_count'=>$seats]);
    rejected(fn()=>$account->requestPlan($clientRequest),'Invalid or below-current-usage seat request rejected');
}
Plan::create(['name'=>'Hidden disabled','slug'=>'hidden-disabled','price'=>1,'duration_days'=>30,'public'=>true,'enabled'=>false]);
Plan::create(['name'=>'Hidden private','slug'=>'hidden-private','price'=>2,'duration_days'=>30,'public'=>false,'enabled'=>true]);
$pricingView=(new App\Http\Controllers\Saas\SignupController)->pricing();
$published=$pricingView->getData();
check($published['plans']->pluck('id')->all()===[$monthly->id,$yearly->id],'Pricing lists every enabled public plan in duration order');
$markup=view('saas.public-plans',$published)->render();
check(str_contains($markup,'CAD 75.00') && str_contains($markup,'CAD 820.00') && str_contains($markup,'per user / 365 days'),'Public cards render saved monthly and yearly prices with durations');
check(!str_contains($markup,'Hidden') && !str_contains($markup,'720') && !str_contains($markup,'20%'),'Hidden plans and fabricated annual discounts excluded');
$yearly->update(['price'=>850]);
$updated=(new App\Http\Controllers\Saas\SignupController)->pricing()->getData();
check(str_contains(view('saas.public-plans',$updated)->render(),'CAD 850.00'),'Admin price edits appear on the next pricing request');
$app['router']->get('/admin/dashboard',fn()=>null)->name('admin.dashboard');
$app['router']->post('/account/subscription/request-plan',fn()=>null)->name('saas.request-plan');
$app['router']->get('/saas/workspace/{slug}',fn()=>null)->name('saas.workspace');
$app['router']->getRoutes()->refreshNameLookups();
Auth::guard('platform')->setUser(new class extends PlatformAdmin { public function cans($permission){return true;} });
$subscription->refresh()->update(['status'=>'trialing','plan_id'=>null,'expires_at'=>now()->addDays(30)]);
$tenant->unsetRelation('subscription');
$trialData=['tenant'=>$tenant,'plans'=>$updated['plans'],'usage'=>['users'=>3,'jobs'=>0,'candidates'=>0,'storage_bytes'=>0]];
$trialMarkup=view('saas.subscription-content',$trialData)->render();
check(str_contains($trialMarkup,'30-day free trial') && !str_contains($trialMarkup,'type="radio" name="plan_id"') && !str_contains($trialMarkup,'type="radio" name="seat_count"'),'Active trial shows trial information without paid package selection');
$subscription->update(['expires_at'=>now()->subSecond()]);
$tenant->unsetRelation('subscription');
$expiredMarkup=view('saas.subscription-content',$trialData)->render();
check(str_contains($expiredMarkup,'type="radio" name="plan_id"') && str_contains($expiredMarkup,'name="seat_count"') && str_contains($expiredMarkup,'Request renewal'),'Expired trial shows per-user package upgrade request');
Plan::query()->update(['public'=>false]);
$empty=(new App\Http\Controllers\Saas\SignupController)->pricing()->getData();
check(str_contains(view('saas.public-plans',$empty)->render(),'Plans are being updated'),'Pricing handles no published plans gracefully');
echo "Plan and renewal integration checks passed: {$checks}.\n";
