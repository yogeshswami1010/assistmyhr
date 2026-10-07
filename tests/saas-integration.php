<?php

// Isolated SQLite integration suite; never connects to the live ATS database.
$root = dirname(__DIR__);
$loader = require ($argv[1] ?? $root.'/vendor/autoload.php');
$loader->setPsr4('App\\', $root.'/app');
require_once $root.'/app/Saas/helpers.php';

use App\Saas\AuditLog;
use App\Saas\Plan;
use App\Saas\PlatformAdmin;
use App\Saas\PlatformSetting;
use App\Saas\QuotaService;
use App\Saas\Subscription;
use App\Saas\SubscriptionService;
use App\Saas\Tenant;
use App\Saas\TenantContext;
use App\Saas\TenantProvisioner;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

$temp = sys_get_temp_dir().'/ats-saas-'.bin2hex(random_bytes(6));
mkdir($temp, 0700, true); touch($temp.'/landlord.sqlite');
$app = new Illuminate\Foundation\Application($root);
$app->instance('env', 'testing');
$app->instance('config', new Illuminate\Config\Repository([
    'app' => ['url' => 'https://ats.example.test', 'timezone' => 'UTC', 'key' => 'base64:'.base64_encode(str_repeat('t',32)), 'cipher' => 'AES-256-CBC'],
    'database' => ['default' => 'sqlite', 'connections' => ['sqlite' => ['driver' => 'sqlite', 'database' => $temp.'/landlord.sqlite', 'prefix' => '', 'foreign_key_constraints' => true]]],
    'saas' => ['enabled'=>true, 'database_prefix'=>'assistmyhr', 'storage_root'=>$temp.'/private'],
    'auth' => require $root.'/config/auth.php',
    'session' => ['driver'=>'array', 'cookie'=>'test_session', 'lottery'=>[0,100], 'lifetime'=>120, 'path'=>'/', 'domain'=>null, 'secure'=>false, 'http_only'=>true],
    'cache' => ['default'=>'file', 'prefix'=>'test', 'stores'=>['file'=>['driver'=>'file','path'=>$temp.'/cache']]],
    'hashing'=>['driver'=>'bcrypt','bcrypt'=>['rounds'=>4,'verify'=>true]],
    'view'=>['paths'=>[$root.'/resources/views'],'compiled'=>$temp],
    'filesystems'=>['default'=>'local','disks'=>['local'=>['driver'=>'local','root'=>$temp.'/original'], 'public'=>['driver'=>'local','root'=>$temp.'/public'], 'candidate_call_audio'=>['driver'=>'local','root'=>$temp.'/calls']]],
    'mail'=>['driver'=>'log','from'=>['address'=>'platform@example.test','name'=>'Platform'], 'ai_search_smtp'=>['username'=>'PLATFORM-MAIL','password'=>'PLATFORM-SECRET']],
    'services'=>['deepseek'=>['key'=>'PLATFORM-AI'], 'candidate_email_imap'=>['host'=>'platform.example.test']],
    'entrust'=>['models'=>['role'=>App\Role::class,'permission'=>App\Permission::class], 'tables'=>['roles'=>'roles','permissions'=>'permissions','role_user'=>'role_user','permission_role'=>'permission_role'], 'foreign_keys'=>['user'=>'user_id','role'=>'role_id','permission'=>'permission_id']],
]));
Illuminate\Support\Facades\Facade::setFacadeApplication($app);
foreach ([Illuminate\Events\EventServiceProvider::class, Illuminate\Database\DatabaseServiceProvider::class,
    Illuminate\Filesystem\FilesystemServiceProvider::class, Illuminate\Cache\CacheServiceProvider::class,
    Illuminate\Hashing\HashServiceProvider::class, Illuminate\Auth\AuthServiceProvider::class,
    Illuminate\Encryption\EncryptionServiceProvider::class, Illuminate\Cookie\CookieServiceProvider::class,
    Illuminate\Session\SessionServiceProvider::class, Illuminate\Routing\RoutingServiceProvider::class,
    Illuminate\View\ViewServiceProvider::class, Illuminate\Translation\TranslationServiceProvider::class,
    Illuminate\Pagination\PaginationServiceProvider::class,
    Illuminate\Notifications\NotificationServiceProvider::class,
    Illuminate\Validation\ValidationServiceProvider::class, Cviebrock\EloquentSluggable\ServiceProvider::class,
    App\Providers\SaasServiceProvider::class] as $provider) { $app->register($provider); }
$app->boot();
if (!class_exists('File', false)) { class_alias(Illuminate\Support\Facades\File::class, 'File'); }
if (!class_exists('Storage', false)) { class_alias(Illuminate\Support\Facades\Storage::class, 'Storage'); }
$session = $app['session']->driver(); $session->start();
$app->instance('session.store', $session);
$initialRequest=Request::create('https://ats.example.test/');$initialRequest->setLaravelSession($session);$app->instance('request',$initialRequest);
$app['view']->share('errors', new Illuminate\Support\ViewErrorBag);
Request::macro('validate', function ($rules) { return app('validator')->make($this->all(), $rules)->validate(); });
Carbon\Carbon::setTestNow('2026-10-06 12:00:00');

Schema::create('users', function(Blueprint $t){$t->increments('id');$t->string('name');$t->string('email')->unique();$t->string('password');$t->rememberToken();$t->timestamps();});
Schema::create('companies', function(Blueprint $t){$t->increments('id');$t->string('company_name');$t->string('company_email')->nullable();$t->string('status')->default('active');$t->string('show_in_frontend')->default('true');$t->timestamps();});
Schema::create('jobs', function(Blueprint $t){$t->increments('id');$t->unsignedInteger('company_id');$t->string('title');$t->string('slug')->nullable();$t->timestamps();});
Schema::create('job_applications', function(Blueprint $t){$t->increments('id');$t->string('full_name');$t->timestamps();});
foreach(['roles','permissions'] as $table){Schema::create($table,function(Blueprint $t){$t->increments('id');$t->string('name');$t->string('display_name')->nullable();$t->timestamps();});}
Schema::create('role_user',function(Blueprint $t){$t->unsignedInteger('user_id');$t->unsignedInteger('role_id');});
Schema::create('permission_role',function(Blueprint $t){$t->unsignedInteger('permission_id');$t->unsignedInteger('role_id');});
Schema::create('migrations',function(Blueprint $t){$t->increments('id');$t->string('migration');$t->integer('batch');});
Schema::create('company_settings',function(Blueprint $t){$t->increments('id');$t->string('company_name');$t->string('company_email');$t->string('locale')->default('eng');$t->string('timezone')->default('UTC');});
foreach(['theme_settings','application_settings','google_captcha_settings','sms_settings','linked_in_settings','zoom_settings'] as $table){Schema::create($table,function(Blueprint $t){$t->increments('id');$t->string('secret')->nullable();});}
Schema::create('smtp_settings',function(Blueprint $t){$t->increments('id');$t->string('mail_host')->default('smtp.example.test');$t->integer('mail_port')->default(587);$t->string('mail_encryption')->default('tls');$t->string('mail_username')->default('placeholder');$t->string('mail_password')->default('placeholder');$t->string('mail_from_email')->default('test@example.test');$t->string('mail_from_name')->default('test');});
Schema::create('ai_api_keys',function(Blueprint $t){$t->increments('id');$t->string('name');$t->string('provider')->nullable();$t->text('api_key');$t->boolean('is_active')->default(true);$t->integer('sort_order')->default(0);$t->timestamps();});
$modelMigration=require $root.'/database/migrations/2026_10_07_000001_add_model_to_ai_api_keys.php';$modelMigration->up();$modelMigration->up();
Schema::create('language_settings',function(Blueprint $t){$t->increments('id');$t->string('language_code');$t->string('language_name');$t->string('status');});
Schema::create('job_api_integrations',function(Blueprint $t){$t->increments('id');$t->string('token_hash');$t->timestamps();});
DB::table('users')->insert(['id'=>1,'name'=>'Old owner','email'=>'same@example.test','password'=>Hash::make('old-password')]);
DB::table('companies')->insert(['company_name'=>'Original employer']);
DB::table('jobs')->insert(['company_id'=>1,'title'=>'Original private job']);
DB::table('job_applications')->insert(['full_name'=>'Original private candidate']);
DB::table('permissions')->insert(['id'=>1,'name'=>'manage_settings']);
DB::table('company_settings')->insert(['company_name'=>'Original','company_email'=>'same@example.test']);
DB::table('smtp_settings')->insert(['mail_password'=>'ORIGINAL-MAIL-SECRET']);
DB::table('google_captcha_settings')->insert(['secret'=>'ORIGINAL-CAPTCHA-SECRET']);
$migration=require $root.'/database/migrations/2026_10_06_000003_create_saas_platform.php';$migration->up();
$serviceMigration=require $root.'/database/migrations/2026_10_06_000004_create_tenant_service_settings.php';$serviceMigration->up();
$checks=0;
function check($ok,$message){global $checks;if(!$ok){throw new RuntimeException($message);} $checks++;}
function rejected(callable $call,string $message){try{$call();}catch(Illuminate\Validation\ValidationException $e){check(true,$message);return;}throw new RuntimeException($message);}
check(DB::table('users')->value('email_verified_at')!==null,'Existing users preserved and verified');
$plan=Plan::create(['name'=>'Starter','slug'=>'starter','max_users'=>2,'max_jobs'=>2,'max_candidates'=>2,'storage_mb'=>1]);
PlatformSetting::create(['key'=>'trial_plan_id','value'=>$plan->id]);PlatformSetting::create(['key'=>'trial_days','value'=>'14']);
$admin=PlatformAdmin::create(['name'=>'Platform owner','email'=>'platform@example.test','password'=>Hash::make('long-admin-password')]);
$context=app(TenantContext::class);
$main=Tenant::create(['uuid'=>(string) Illuminate\Support\Str::uuid(),'slug'=>'main','name'=>'Original','owner_email'=>'same@example.test','database_name'=>$temp.'/landlord.sqlite','database_driver'=>'sqlite','status'=>'active']);
$context->activate($main);
check(config('mail.mailers.tenant.password')==='ORIGINAL-MAIL-SECRET'&&config('services.deepseek.key')===null,'Main ATS also uses saved client settings');
$context->reset();
$data=['company_name'=>'Client Alpha','slug'=>'alpha','name'=>'Alpha owner','email'=>'same@example.test','password'=>'long-owner-password'];
$alpha=app(TenantProvisioner::class)->create($data);
$beta=app(TenantProvisioner::class)->create(array_replace($data,['company_name'=>'Client Beta','slug'=>'beta']));
check($alpha->database_name!==$beta->database_name,'Every workspace receives a separate database');
check(!str_contains(tenant_html('<p onclick="evil()">Hiring</p><script>evil()</script><a href="javascript:evil()">X</a>'),'evil'),'Client rich text cannot inject scripts or event handlers');
check(tenant_external_url('javascript:evil()')===''&&tenant_external_url('https://company.test')==='https://company.test','External links exclude active URL schemes');
check($alpha->status==='active'&&$alpha->hasAccess(),'Signup creates accessible trial');
check($alpha->subscription->expires_at->eq(now()->addDays(14)),'Trial duration applied');
check($context->current()===null&&DB::getDefaultConnection()==='sqlite','Provisioning restores landlord context');
$context->activate($alpha);
check(DB::table('users')->count()===1&&DB::table('users')->value('email_verified_at')===null,'Only new unverified owner seeded');
check(DB::table('jobs')->count()===0&&DB::table('job_applications')->count()===0,'Source jobs and candidates never copied');
check(DB::table('companies')->value('company_name')==='Client Alpha','Only client employer seeded');
check(DB::table('google_captcha_settings')->value('secret')===null,'Source integration secrets not copied');
check(DB::table('smtp_settings')->value('mail_password')==='','New client does not inherit SMTP password');
check(config('services.deepseek.key')===null&&config('mail.ai_search_smtp.password')==='','No platform AI or email credentials inherited');
App\AiApiKey::create(['name'=>'DeepSeek Alpha','provider'=>'deepseek','model'=>'alpha-model','api_key'=>'ALPHA-AI','is_active'=>true]);
DB::table('smtp_settings')->update(['mail_username'=>'alpha@example.test','mail_password'=>'ALPHA-SMTP']);
$context->activate($alpha);
check(config('services.deepseek.key')==='ALPHA-AI'&&config('services.deepseek.model')==='alpha-model','Client AI key and model loaded together');
check(config('mail.default')==='tenant'&&config('mail.mailers.tenant.password')==='ALPHA-SMTP'&&config('mail.ai_search_smtp.password')==='ALPHA-SMTP','All client mail transports use saved SMTP');
$message=call_user_func(Illuminate\Auth\Notifications\VerifyEmail::$toMailCallback, App\User::first(), 'https://ats.example.test/verify');
check($message->mailer==='platform'&&$message->from[0]==='platform@example.test','Signup verification retains platform SMTP and sender');
$context->activate($beta);
check(config('services.deepseek.key')===null&&config('mail.mailers.tenant.password')==='','Client settings never leak to another client');
$context->activate($alpha);
check(!DB::getSchemaBuilder()->hasTable('saas_platform_admins'),'Platform administrators never cloned into client database');
check(DB::table('permission_role')->count()===1,'Owner receives local ATS permissions');
check(Hash::check($data['password'],DB::table('users')->value('password')),'Owner password securely hashed');
DB::table('jobs')->insert(['company_id'=>1,'title'=>'Alpha secret']);
DB::table('job_applications')->insert(['full_name'=>'Alpha candidate']);
$alphaRoot=$context->root();file_put_contents($alphaRoot.'/uploads/resume.txt','ALPHA PRIVATE RESUME');
$context->activate($beta);
check(DB::table('jobs')->count()===0&&DB::table('job_applications')->count()===0,'Global legacy queries cannot see another client');
check(!file_exists($context->root().'/uploads/resume.txt'),'File storage separated by workspace');
DB::table('jobs')->insert(['company_id'=>1,'title'=>'Beta secret']);
check(PlatformAdmin::count()===1&&Tenant::count()===3,'Central models stay on landlord while client active');
check(Auth::guard('platform')->attempt(['email'=>'same@example.test','password'=>$data['password']])===false,'Client credentials cannot authenticate as platform administrator');
check(Auth::guard('platform')->attempt(['email'=>'platform@example.test','password'=>'long-admin-password']),'Separate platform guard authenticates');
$context->activate($alpha);
check(DB::table('jobs')->value('title')==='Alpha secret','Switch restores correct client data');
$quota=app(QuotaService::class);$quota->assertCanCreate('jobs');
DB::table('jobs')->insert(['company_id'=>1,'title'=>'Second alpha job']);
rejected(fn()=>$quota->assertCanCreate('jobs'),'Job limit enforced');
App\User::create(['name'=>'Second','email'=>'second@example.test','password'=>Hash::make('password')]);
rejected(fn()=>App\User::create(['name'=>'Third','email'=>'third@example.test','password'=>'x']),'Model observer enforces user quota');
DB::table('job_applications')->insert(['full_name'=>'Second candidate']);
rejected(fn()=>$quota->assertCanCreate('job_applications'),'Candidate limit enforced');
rejected(fn()=>$quota->assertUploadFits(1048576),'Upload storage quota enforced');
check($quota->usage()['jobs']===2&&$quota->usage()['users']===2,'Usage is tenant scoped');
file_put_contents($alphaRoot.'/uploads/temp/in-progress.tmp',str_repeat('x',1048576));
check($quota->storageBytes()<1048576,'Temporary files excluded from permanent usage');
file_put_contents($temp.'/incoming.txt','PRIVATE UPLOAD');
$uploaded=new Illuminate\Http\UploadedFile($temp.'/incoming.txt','resume.txt','text/plain',null,true);
$filename=App\Helper\Files::upload($uploaded,'documents',false,false,false);
check(file_get_contents($alphaRoot.'/uploads/documents/'.$filename)==='PRIVATE UPLOAD','Upload helper writes to private client directory');
check(!is_file($alphaRoot.'/uploads/temp/'.$filename),'Upload temporary file cleaned');
$app['router']->get('jobapply/{slug}/{location?}',fn()=>null)->name('jobs.jobApply');
$app['router']->get('login',fn()=>null)->name('login');
$app['router']->get('admin/dashboard',fn()=>null)->name('admin.dashboard');
$app['router']->get('logout',fn()=>null)->name('logout');
$app['router']->get('admin/settings/smtp-settings',fn()=>null)->name('admin.smtp-settings.index');
$app['router']->get('admin/settings/ai-settings',fn()=>null)->name('admin.ai-settings.index');
$app['router']->group([], $root.'/routes/saas.php');$app['router']->getRoutes()->refreshNameLookups();
$request=Request::create('https://ats.example.test/login');$request->setLaravelSession($session);$app->instance('request',$request);$app['url']->setRequest($request);
check(str_contains(tenant_route('jobs.jobApply',['engineer',1]),'workspace=alpha'),'External job apply URLs preserve workspace');
$signed=tenant_asset_url('resume.txt');$fileRequest=Request::create($signed);$fileRequest->setLaravelSession($session);$app->instance('request',$fileRequest);$app['url']->setRequest($fileRequest);
check(Illuminate\Support\Facades\URL::hasValidSignature($fileRequest),'Private file URL signed');
$fileController=new App\Http\Controllers\Saas\FileController;
check($fileController->show($fileRequest,'alpha','resume.txt')->getStatusCode()===200,'Valid signed file accessible');
try{$fileController->show($fileRequest,'beta','resume.txt');throw new RuntimeException('Wrong workspace accepted');}catch(Symfony\Component\HttpKernel\Exception\HttpException $e){check($e->getStatusCode()===403,'Signed file bound to workspace');}
$context->reset();
check(config('services.deepseek.key')==='PLATFORM-AI'&&config('mail.ai_search_smtp.password')==='PLATFORM-SECRET','Reset restores platform configuration');
$svc=app(SubscriptionService::class);
$extended=$svc->extend($alpha,$admin,['days'=>30,'reason'=>'Paid offline']);
check($extended->expires_at->eq(now()->addDays(44)),'Manual extension adds days to current future expiry');
check(AuditLog::where('action','subscription.extended')->value('reason')==='Paid offline','Extension reason recorded');
rejected(fn()=>$svc->extend($alpha,$admin,['expires_at'=>now()->addDays(10)->toDateString(),'reason'=>'Invalid']),'Extensions cannot shorten access');
Subscription::where('tenant_id',$beta->id)->update(['expires_at'=>now()->subDay()]);
$extended=$svc->extend($beta,$admin,['days'=>7,'reason'=>'Renewal']);
check($extended->expires_at->eq(now()->addDays(7)),'Expired renewals begin today');
$svc->extend($beta,$admin,['expires_at'=>now()->addDays(20)->toDateString(),'reason'=>'Custom expiry']);
check($beta->fresh()->subscription->expires_at->format('Y-m-d H:i:s')===now()->addDays(20)->endOfDay()->format('Y-m-d H:i:s'),'Exact expiry date supported');
$beta->status='suspended';$beta->save();check(!$beta->fresh()->hasAccess(),'Suspension overrides subscription');
$middleware=new App\Http\Middleware\InitializeSaasTenant;
function middlewareRequest($url,$session){global $app;$r=Request::create('https://ats.example.test'.$url);$r->setLaravelSession($session);$app->instance('request',$r);$app['url']->setRequest($r);return $r;}
$session->put('saas_tenant_id',$alpha->id);$session->put(Auth::guard('web')->getName(),1);$session->put('user','OLD CACHE');
$response=$middleware->handle(middlewareRequest('/login?workspace=beta',$session),function(){check(!Auth::guard('web')->check(),'Changing workspace clears client authentication');check(!session()->has('user'),'Changing workspace clears cached user');return response('Login');});
check($response->getStatusCode()===200&&$session->get('saas_tenant_id')===$beta->id,'Suspended clients can access login/billing');
check($context->current()===null,'HTTP middleware always restores database context');
$response=$middleware->handle(middlewareRequest('/admin/dashboard?workspace=beta',$session),fn()=>throw new RuntimeException('Suspended request reached ATS'));
check($response->isRedirect()&&str_ends_with($response->headers->get('Location'),'/account/subscription'),'Suspended ATS access blocked');
$beta->status='active';$beta->save();Subscription::where('tenant_id',$beta->id)->update(['expires_at'=>now()->subDay()]);
$response=$middleware->handle(middlewareRequest('/jobs?workspace=beta',$session),fn()=>throw new RuntimeException('Expired public jobs reached ATS'));
check($response->getStatusCode()===410,'Expired public jobs blocked');
App\Saas\ApiKey::create(['tenant_id'=>$alpha->id,'integration_id'=>1,'token_hash'=>hash('sha256','ALPHA-KEY')]);
$r=middlewareRequest('/api/jobs',$session);$r->headers->set('Authorization','Bearer ALPHA-KEY');
$response=$middleware->handle($r,function(){check(app(TenantContext::class)->current()->slug==='alpha','API key selects correct client');check(DB::table('jobs')->count()===2,'API database isolated');return response()->json([]);});
check($response->getStatusCode()===200,'Valid active tenant API accessible');
$r=middlewareRequest('/api/jobs',$session);$r->headers->set('Authorization','Bearer BAD');
check($middleware->handle($r,fn()=>null)->getStatusCode()===401,'Unknown API key rejected before querying ATS');
check(DB::table('jobs')->value('title')==='Original private job','Original ATS data remains untouched');

// Exercise actual controller actions and render their complete Blade pages.
$platform=new App\Http\Controllers\Saas\PlatformController;
$request=middlewareRequest('/superadmin',$session);Auth::guard('platform')->login($admin);
check(str_contains($platform->dashboard()->render(),'Client overview'),'Platform dashboard renders actual client records');
check(str_contains($platform->tenant($alpha)->render(),'Extend subscription manually'),'Client management page renders');
check(str_contains($platform->plans()->render(),'Create plan'),'Plan editor renders');
check(str_contains($platform->settings()->render(),'Signup and trial settings'),'Platform settings render');
check(str_contains($platform->profile()->render(),'Profile details'),'Super admin profile renders');
check(str_contains($platform->admins()->render(),'Add super admin'),'Super admin account management renders');
$adminRequest=Request::create('https://ats.example.test/superadmin/admins','POST',['name'=>'Second admin','email'=>'SECOND@example.test','password'=>'second-admin-password','password_confirmation'=>'second-admin-password','current_password'=>'incorrect']);
$adminRequest->setLaravelSession($session);
try{$platform->createAdmin($adminRequest);throw new RuntimeException('Admin creation accepted invalid current password');}catch(Illuminate\Validation\ValidationException $e){check(isset($e->errors()['current_password']),'Adding admin requires current password');}
$adminRequest->merge(['current_password'=>'long-admin-password']);$platform->createAdmin($adminRequest);
$secondAdmin=PlatformAdmin::where('email','second@example.test')->firstOrFail();
check(Hash::check('second-admin-password',$secondAdmin->password),'New super admin password hashed');
check(AuditLog::where('action','admin.created')->exists(),'Super admin creation audited');
try{$platform->createAdmin($adminRequest);throw new RuntimeException('Duplicate administrator accepted');}catch(Illuminate\Validation\ValidationException $e){check(isset($e->errors()['email']),'Duplicate super admin email rejected');}
$profileRequest=Request::create('https://ats.example.test/superadmin/profile','PUT',['name'=>'Updated platform owner','email'=>'OWNER@example.test','current_password'=>'long-admin-password']);
$profileRequest->setLaravelSession($session);$platform->updateProfile($profileRequest);
check($admin->fresh()->email==='owner@example.test','Profile updates own normalized email');
$passwordRequest=Request::create('https://ats.example.test/superadmin/profile/password','PUT',['current_password'=>'long-admin-password','password'=>'replacement-admin-password','password_confirmation'=>'replacement-admin-password']);
$passwordRequest->setLaravelSession($session);$platform->updatePassword($passwordRequest);
check(Hash::check('replacement-admin-password',$admin->fresh()->password),'Own password changes securely');
check(!str_contains(json_encode(AuditLog::whereIn('action',['admin.created','admin.password_changed'])->get()),'replacement-admin-password'),'Audit logs exclude administrator passwords');
$request=Request::create('https://ats.example.test/superadmin/tenants/'.$alpha->id,'PUT',['status'=>'suspended','reason'=>'Test suspension']);$request->setLaravelSession($session);$app->instance('request',$request);
$platform->updateTenant($request,$alpha->fresh());
check($alpha->fresh()->status==='suspended'&&AuditLog::where('action','tenant.status_changed')->exists(),'Super admin status action audited');
$request->merge(['status'=>'active']);$platform->updateTenant($request,$alpha->fresh());
$request->merge(['reason'=>'Independently confirmed owner']);$platform->verifyOwner($request,$alpha->fresh());
$context->activate($alpha);check(App\User::find(1)->hasVerifiedEmail(),'Super admin can approve owner email in correct tenant');
Auth::guard('web')->login(App\User::find(1));
check(App\User::find(1)->cans('manage_settings'),'Seeded owner has settings permission');
$request=middlewareRequest('/account/subscription?workspace=alpha',$session);$request->setUserResolver(fn()=>Auth::guard('web')->user());
$account=new App\Http\Controllers\Saas\AccountController;
check(str_contains(view('saas.subscription-content', $account->subscription()->getData())->render(),'Request a plan or renewal'),'Client billing page renders with renewal form');
check(str_contains(view('saas.integrations-content', $account->integrations($request)->getData())->render(),'Candidate reply inbox'),'Client integrations page renders');
$request->merge(['imap_host'=>'imap.client.example.test','imap_port'=>993]);$account->saveIntegrations($request);
$context->activate($alpha);check(config('services.candidate_email_imap.host')==='imap.client.example.test','Own IMAP setting loaded in own workspace');
$integration=new App\JobApiIntegration;$integration->token_hash=hash('sha256','ROTATED-KEY');$integration->save();
check(App\Saas\ApiKey::where('token_hash',hash('sha256','ROTATED-KEY'))->where('tenant_id',$alpha->id)->exists(),'Creating jobs key updates central lookup');
$integration=App\JobApiIntegration::first();$integration->token_hash=hash('sha256','NEW-KEY');$integration->save();
check(!App\Saas\ApiKey::where('token_hash',hash('sha256','ROTATED-KEY'))->exists(),'Rotation revokes previous central key');
$integration->delete();check(!App\Saas\ApiKey::where('token_hash',hash('sha256','NEW-KEY'))->exists(),'Deletion revokes central key');
$context->reset();
PlatformSetting::create(['key'=>'signup_enabled','value'=>'1']);
$request=middlewareRequest('/register',$session);$signup=new App\Http\Controllers\Saas\SignupController;
check(str_contains($signup->form()->render(),'Create your ATS workspace'),'Signup page renders');
check(str_contains($signup->pricing()->render(),'Plans for your recruiting team'),'Pricing page renders');
$context->activate($beta);check(config('services.candidate_email_imap.host')===null,'Other client cannot inherit IMAP host');$context->reset();
Illuminate\Support\Facades\Notification::fake();
$signupRequest=Request::create('https://ats.example.test/register','POST',array_replace($data,['company_name'=>'Client Gamma','email'=>'gamma@example.test','password_confirmation'=>$data['password']]));
$signupRequest->setLaravelSession($session);$app->instance('request',$signupRequest);$app['url']->setRequest($signupRequest);
$response=$middleware->handle($signupRequest,fn($request)=>$signup->store($request));
check($response->isRedirect()&&str_ends_with($response->headers->get('Location'),'/email/verify'),'Signup controller creates workspace and directs owner to verification');
$gamma=Tenant::where('owner_email','gamma@example.test')->firstOrFail();
check($session->get('saas_tenant_id')===$gamma->id&&$context->current()===null,'Signup persists correct workspace and resets global context');
check(str_starts_with($gamma->slug,'client-gamma-'),'Registration generates workspace automatically');
$session->put('saas_tenant_id', $alpha->id);
$session->regenerateToken();
$loginToken=$session->token();
$previousLoginSessionId=$session->getId();
$loginRequest=Request::create('https://ats.example.test/login','POST',['email'=>'GAMMA@example.test','password'=>$data['password'],'workspace'=>'main']);
$loginRequest->setLaravelSession($session);$app->instance('request',$loginRequest);
$response=$middleware->handle($loginRequest,function($request)use($gamma,$loginToken,$previousLoginSessionId){
    check($request->session()->token()===$loginToken,'Changing workspace preserves CSRF token until validation');
    check($request->session()->getId()!==$previousLoginSessionId,'Changing workspace still rotates session identifier');
    check(app(TenantContext::class)->current()->id===$gamma->id,'Email selects owner workspace even with stale hidden workspace');
    check(Auth::guard('web')->attempt(['email'=>$request->input('email'),'password'=>$request->input('password')]),'Owner can authenticate with email and password');
    return response('Signed in');
});
check($response->isOk(),'Email-only workspace login succeeds');
foreach(['missing-workspace','INVALID WORKSPACE'] as $badWorkspace){
    $badLogin=Request::create('https://ats.example.test/login','POST',['email'=>'unknown@example.test','password'=>'wrong-password','workspace'=>$badWorkspace]);
    $badLogin->setLaravelSession($session);$app->instance('request',$badLogin);$app['url']->setRequest($badLogin);
    $failedResponse=$middleware->handle($badLogin,fn()=>throw new RuntimeException('Invalid workspace reached login controller'));
    check($failedResponse->isRedirect()&&str_ends_with($failedResponse->headers->get('Location'),'/login'),'Invalid login workspace returns to login instead of 404');
    check($session->get('errors')->first('email')==='The email address or password is incorrect.','Failed login has readable credential message');
    check(!array_key_exists('password',$session->get('_old_input',[])),'Failed login never flashes password');
}
$duplicateRequest=Request::create('https://ats.example.test/register','POST',array_replace($data,['email'=>'gamma@example.test','password_confirmation'=>$data['password']]));
try{$signup->store($duplicateRequest);throw new RuntimeException('Duplicate owner accepted');}catch(Illuminate\Validation\ValidationException $e){check(isset($e->errors()['email']),'Duplicate owner email rejected');}
check(!str_contains($signup->form()->render(),'name="slug"'),'Registration has no workspace input');
$context->activate($gamma);
check(Illuminate\Support\Facades\Notification::sent(App\User::find(1),Illuminate\Auth\Notifications\VerifyEmail::class)->count()===1,'Signup sends verification notification');
$verificationRequest=middlewareRequest('/email/verify?workspace='.$gamma->slug,$session);$verificationRequest->setUserResolver(fn()=>Auth::guard('web')->user());
check(str_contains((new App\Http\Controllers\Saas\VerificationController)->show($verificationRequest)->render(),'Verify your email'),'Verification page renders for unverified owner');
$context->reset();
$response=$middleware->handle(middlewareRequest('/admin/dashboard?workspace='.$gamma->slug,$session),fn()=>throw new RuntimeException('Unverified owner reached ATS'));
check($response->isRedirect()&&str_ends_with($response->headers->get('Location'),'/email/verify'),'Unverified owner cannot enter ATS');
$context->activate($gamma);$verificationRequest=middlewareRequest('/email/verify?workspace='.$gamma->slug,$session);$verificationRequest->setUserResolver(fn()=>Auth::guard('web')->user());
(new App\Http\Controllers\Saas\VerificationController)->verify($verificationRequest,'1',sha1('gamma@example.test'));
check(App\User::find(1)->hasVerifiedEmail(),'Owner verification updates only their workspace');
$context->reset();
$app['router']->aliasMiddleware('auth',Illuminate\Auth\Middleware\Authenticate::class);
$deleteRequest=Request::create('https://ats.example.test/superadmin/tenants/'.$beta->id,'DELETE');
$deleteRequest->setLaravelSession($session);
$betaDatabase=$beta->database_name;
$platform->deleteTenant($deleteRequest,$beta);
check(!Tenant::find($beta->id)&&!Subscription::where('tenant_id',$beta->id)->exists(),'Deletion removes client and subscription');
check(is_file($betaDatabase),'Deletion retains tenant database for recovery');
check(AuditLog::where('action','tenant.deleted')->exists(),'Deletion audited');
try{$platform->deleteTenant($deleteRequest,Tenant::where('slug','main')->firstOrFail());throw new RuntimeException('Main workspace deletion accepted');}catch(Symfony\Component\HttpKernel\Exception\HttpException $e){check($e->getStatusCode()===403,'Main workspace cannot be deleted');}
Auth::guard('platform')->logout();
$platformRequest=middlewareRequest('/superadmin',$session);
try{$app['router']->dispatch($platformRequest);throw new RuntimeException('Unauthenticated platform access accepted');}catch(Illuminate\Auth\AuthenticationException $e){check(in_array('platform',$e->guards(),true),'Platform routes enforce separate authentication guard');}
config(['saas.enabled'=>false]);
try{$middleware->handle(middlewareRequest('/superadmin',$session),fn()=>null);throw new RuntimeException('Disabled feature accessible');}catch(Symfony\Component\HttpKernel\Exception\HttpException $e){check($e->getStatusCode()===404,'Disabled SaaS hides platform routes');}
config(['saas.enabled'=>true]);
foreach(['signup','pricing','platform-login','platform-dashboard','platform-tenant','platform-plans','platform-settings','subscription','verify'] as $view){check(is_file($root.'/resources/views/saas/'.$view.'.blade.php'),'Required page exists: '.$view);}
foreach(glob($root.'/resources/views/saas/*.blade.php') as $view){$app['blade.compiler']->compileString(file_get_contents($view));check(true,'Blade compiles: '.basename($view));}
$app['blade.compiler']->compileString(file_get_contents($root.'/resources/views/admin/ai-settings/index.blade.php'));check(true,'AI settings model form compiles');
Carbon\Carbon::setTestNow();
echo "SaaS integration checks passed: $checks\n";
