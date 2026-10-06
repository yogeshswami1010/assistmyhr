<?php

namespace {
    // Isolated SQLite test harness. No live ATS database, SMTP, or HTTP calls.
    $root = dirname(__DIR__);
    $autoload = $argv[1] ?? $root.'/vendor/autoload.php';
    if (!is_file($autoload)) { fwrite(STDERR, "Provide a Composer vendor/autoload.php path.\n"); exit(1); }
    $loader = require $autoload;
    $loader->setPsr4('App\\', $root.'/app');
}

namespace App\Http\Controllers\Admin {
    // Replace only the legacy base's installation queries and shared layout data.
    // The real settings controller's authorization middleware and actions run below.
    class AdminBaseController extends \Illuminate\Routing\Controller
    {
        public array $data = [];
        public function __construct() {}
        public function __set($name, $value) { $this->data[$name] = $value; }
        public function __get($name) { return $this->data[$name]; }
    }
}

namespace {
    use App\Company;
    use App\JobApiIntegration;
    use Carbon\Carbon;
    use Illuminate\Config\Repository;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Foundation\Application;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Facade;
    use Illuminate\Support\Facades\Schema;
    use Illuminate\Validation\ValidationException;

    $temporary = sys_get_temp_dir().'/ats-jobs-api-'.bin2hex(random_bytes(6));
    mkdir($temporary.'/layouts', 0700, true);
    file_put_contents($temporary.'/layouts/app.blade.php', '<html><body>@yield("content")@stack("footer-script")</body></html>');
    $app = new Application($root);
    $app->instance('config', new Repository([
        'app' => ['url' => 'https://ats.example.test', 'timezone' => 'UTC', 'key' => 'test-only'],
        'database' => ['default' => 'sqlite', 'connections' => ['sqlite' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]],
        'view' => ['paths' => [$temporary, $root.'/resources/views'], 'compiled' => $temporary],
        'session' => ['driver' => 'array'],
    ]));
    Facade::setFacadeApplication($app);
    foreach ([
        Illuminate\Events\EventServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,
        Illuminate\Routing\RoutingServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Cviebrock\EloquentSluggable\ServiceProvider::class,
    ] as $provider) { $app->register($provider); }
    $app->boot();
    $session = $app['session']->driver();
    $session->start();
    $app['view']->share('errors', new Illuminate\Support\ViewErrorBag());
    Request::macro('validate', function ($rules) { return app('validator')->make($this->all(), $rules)->validate(); });
    Illuminate\Pagination\Paginator::currentPageResolver(fn () => (int) app('request')->query('page', 1));
    Carbon::setTestNow(Carbon::parse('2026-10-06 12:00:00', 'UTC'));

    Schema::create('companies', function (Blueprint $table) {
        $table->increments('id'); $table->string('company_name'); $table->string('status');
        $table->string('show_in_frontend')->default('true'); $table->timestamps();
    });
    Schema::create('jobs', function (Blueprint $table) {
        $table->increments('id'); $table->unsignedInteger('company_id'); $table->string('title');
        $table->string('slug'); $table->string('status'); $table->string('job_code')->nullable();
        $table->text('job_description')->nullable(); $table->text('job_requirement')->nullable();
        $table->dateTime('start_date'); $table->dateTime('end_date')->nullable();
        $table->unsignedInteger('category_id')->nullable(); $table->unsignedInteger('job_type_id')->nullable();
        $table->unsignedInteger('work_experience_id')->nullable(); $table->unsignedInteger('currency_id')->nullable();
        $table->boolean('show_salary')->default(false); $table->string('pay_type')->nullable();
        $table->integer('starting_salary')->nullable(); $table->integer('maximum_salary')->nullable();
        $table->string('pay_according')->nullable(); $table->boolean('show_on_consortium')->default(false);
        $table->boolean('show_on_assistmyday')->default(false); $table->text('internal_notes')->nullable();
        $table->timestamps();
    });
    foreach (['job_categories' => 'name', 'job_types' => 'job_type', 'work_experiences' => 'work_experience', 'currencies' => 'currency_symbol', 'job_locations' => 'location'] as $table => $field) {
        Schema::create($table, function (Blueprint $schema) use ($field) { $schema->increments('id'); $schema->string($field); });
    }
    Schema::create('job_job_locations', function (Blueprint $table) {
        $table->increments('id'); $table->unsignedInteger('job_id'); $table->unsignedInteger('location_id');
    });
    $migration = require $root.'/database/migrations/2026_10_06_000001_create_job_api_integrations.php';
    $migration->up();
    DB::table('companies')->insert([
        ['id' => 1, 'company_name' => 'Client One', 'status' => 'active', 'show_in_frontend' => 'true'],
        ['id' => 2, 'company_name' => '<script>Client Two</script>', 'status' => 'active', 'show_in_frontend' => 'false'],
        ['id' => 3, 'company_name' => 'Inactive Client', 'status' => 'inactive', 'show_in_frontend' => 'true'],
    ]);
    $jobs = [
        [1, 1, 'active', '2026-10-01', '2026-10-06 00:00:00'],
        [2, 1, 'active', '2026-10-01', null],
        [3, 1, 'inactive', '2026-10-01', '2026-10-20'],
        [4, 1, 'active', '2026-10-07', '2026-10-20'],
        [5, 1, 'active', '2026-10-01', '2026-10-05'],
        [6, 2, 'active', '2026-10-01', '2026-10-20'],
    ];
    foreach ($jobs as [$id, $company, $status, $start, $end]) {
        DB::table('jobs')->insert([
            'id' => $id, 'company_id' => $company, 'title' => 'Job '.$id, 'slug' => 'job-'.$id,
            'status' => $status, 'start_date' => $start, 'end_date' => $end,
            'show_salary' => $id === 1, 'starting_salary' => 50000, 'maximum_salary' => 70000,
            'pay_type' => 'Range', 'pay_according' => 'year',
            'job_description' => '<p>Public description</p>', 'internal_notes' => 'PRIVATE ATS NOTE',
            'created_at' => '2026-10-01 00:00:00', 'updated_at' => '2026-10-01 00:00:00',
        ]);
    }
    DB::table('job_locations')->insert(['id' => 9, 'location' => 'Toronto']);
    DB::table('job_job_locations')->insert(['job_id' => 1, 'location_id' => 9]);

    $router = $app['router'];
    $router->aliasMiddleware('bindings', Illuminate\Routing\Middleware\SubstituteBindings::class);
    $router->aliasMiddleware('test-auth', fn ($request, $next) => $request->user() ? $next($request) : response()->json([], 401));
    $router->prefix('api')->group($root.'/routes/api.php');
    $router->get('job/{slug}/{location?}', fn () => 'Public job')->name('jobs.jobDetail');
    $router->middleware(['test-auth', 'bindings'])->prefix('admin/settings')->name('admin.')->group(function ($router) {
        $controller = App\Http\Controllers\Admin\AdminJobApiSettingsController::class;
        $router->get('jobs-api', [$controller, 'index'])->name('job-api-settings.index');
        $router->post('jobs-api', [$controller, 'store'])->name('job-api-settings.store');
        $router->post('jobs-api/{integration}/regenerate', [$controller, 'regenerate'])->name('job-api-settings.regenerate');
        $router->put('jobs-api/{integration}', [$controller, 'update'])->name('job-api-settings.update');
        $router->delete('jobs-api/{integration}', [$controller, 'destroy'])->name('job-api-settings.destroy');
    });
    $router->getRoutes()->refreshNameLookups();

    $checks = 0;
    function check($ok, $message) { global $checks; if (!$ok) { throw new RuntimeException($message); } $checks++; }
    function callEndpoint($method, $url, array $data = [], ?string $token = null, ?bool $settingsPermission = null) {
        global $app, $session;
        $request = Request::create('https://ats.example.test'.$url, $method, $data);
        $request->headers->set('Accept', 'application/json');
        if ($token !== null) { $request->headers->set('Authorization', 'Bearer '.$token); }
        $request->setLaravelSession($session);
        $request->setUserResolver(fn () => $settingsPermission === null ? null : new class($settingsPermission) {
            public function __construct(private bool $allowed) {}
            public function cans($permission) { return $this->allowed && $permission === 'manage_settings'; }
        });
        $app->instance('request', $request);
        try { return $app['router']->dispatch($request); }
        catch (ValidationException $e) { return response()->json(['errors' => $e->errors()], 422); }
        catch (Symfony\Component\HttpKernel\Exception\HttpException $e) { return response()->json([], $e->getStatusCode()); }
    }

    $integration = new JobApiIntegration;
    $integration->company_id = 1; $integration->enabled = true;
    $key = $integration->regenerateToken();
    $second = new JobApiIntegration;
    $second->company_id = 2; $second->enabled = true;
    $secondKey = $second->regenerateToken();
    check(!str_contains(json_encode($integration), $key) && !isset($integration->toArray()['token_hash']), 'Key and hash are not serialized');
    check($integration->token_hash === hash('sha256', $key), 'Only key hash is persisted');
    check(callEndpoint('GET', '/api/jobs')->getStatusCode() === 401, 'Missing key rejected');
    check(callEndpoint('GET', '/api/jobs', ['api_key' => $key])->getStatusCode() === 401, 'URL key rejected');
    check(callEndpoint('GET', '/api/jobs', [], 'invalid')->getStatusCode() === 401, 'Malformed key rejected');
    $response = callEndpoint('GET', '/api/jobs', ['company_id' => 2], $key);
    $feed = json_decode($response->getContent(), true);
    check($response->getStatusCode() === 200 && $feed['total_jobs'] === 2, 'Only open active jobs returned despite old flags being false');
    check(array_column($feed['jobs'], 'id') === [2, 1], 'Key fixes company scope and stable order');
    check($feed['jobs'][0]['salary'] === null && $feed['jobs'][1]['salary'] === '50,000 - 70,000 / year', 'Hidden salaries withheld and public salary formatted');
    check($feed['jobs'][0]['end_date'] === null, 'Open-ended job included');
    check($feed['jobs'][1]['apply_url'] === 'https://ats.example.test/job/job-1/9', 'Apply URL points to the matching job location');
    check(!str_contains($response->getContent(), 'PRIVATE ATS NOTE') && !isset($feed['jobs'][0]['company_id']), 'Internal fields excluded');
    check($feed['jobs'][0]['description'] === 'Public description', 'Description returned as text');
    check(str_contains($response->headers->get('Cache-Control'), 'no-store'), 'Keyed responses not cached');
    $feed = json_decode(callEndpoint('GET', '/api/jobs', ['page' => 2, 'per_page' => 1], $key)->getContent(), true);
    check($feed['pagination']['current_page'] === 2 && $feed['total_jobs'] === 2 && count($feed['jobs']) === 1, 'Pagination keeps correct totals');
    check(callEndpoint('GET', '/api/jobs', ['per_page' => 101], $key)->getStatusCode() === 422, 'Page size bounded');
    check(callEndpoint('GET', '/api/jobs', ['page' => 0], $key)->getStatusCode() === 422, 'Invalid page rejected');
    $feed = json_decode(callEndpoint('GET', '/api/jobs', [], $secondKey)->getContent(), true);
    check($feed['total_jobs'] === 1 && $feed['jobs'][0]['id'] === 6 && $feed['jobs'][0]['company'] === '', 'Second company isolated and hidden company name respected');
    check(callEndpoint('GET', '/admin/settings/jobs-api')->getStatusCode() === 401, 'Settings requires login');
    foreach (['GET', 'POST'] as $method) {
        check(callEndpoint($method, '/admin/settings/jobs-api', ['company_id' => 3], null, false)->getStatusCode() === 403, 'Settings permission enforced for '.$method);
    }
    foreach ([['POST', '/regenerate', []], ['PUT', '', ['enabled' => 0]], ['DELETE', '', []]] as [$method, $suffix, $data]) {
        check(callEndpoint($method, '/admin/settings/jobs-api/'.$integration->id.$suffix, $data, null, false)->getStatusCode() === 403, 'Settings permission protects key mutations');
    }
    check(callEndpoint('POST', '/admin/settings/jobs-api', ['company_id' => 3], null, true)->getStatusCode() === 422, 'Inactive company cannot get a key');
    check(callEndpoint('POST', '/admin/settings/jobs-api', ['company_id' => 1], null, true)->getStatusCode() === 422, 'Duplicate company key rejected');
    $response = callEndpoint('GET', '/admin/settings/jobs-api', [], null, true);
    check($response->getStatusCode() === 200 && str_contains($response->getContent(), 'Company integrations'), 'Settings page renders');
    check(str_contains($response->getContent(), '&lt;script&gt;Client Two&lt;/script&gt;') && !str_contains($response->getContent(), $key), 'Settings escapes company names and masks saved keys');
    callEndpoint('POST', '/admin/settings/jobs-api/'.$integration->id.'/regenerate', [], null, true);
    $replacement = $session->get('new_job_api_token');
    check(is_string($replacement) && callEndpoint('GET', '/api/jobs', [], $key)->getStatusCode() === 401, 'Regeneration invalidates old key');
    check(callEndpoint('GET', '/api/jobs', [], $replacement)->getStatusCode() === 200, 'Replacement key works');
    callEndpoint('PUT', '/admin/settings/jobs-api/'.$integration->id, ['enabled' => 0], null, true);
    check(callEndpoint('GET', '/api/jobs', [], $replacement)->getStatusCode() === 401, 'Disabled key rejected');
    callEndpoint('PUT', '/admin/settings/jobs-api/'.$integration->id, ['enabled' => 1], null, true);
    DB::table('companies')->where('id', 1)->update(['status' => 'inactive']);
    check(callEndpoint('GET', '/api/jobs', [], $replacement)->getStatusCode() === 401, 'Inactive company blocks existing key');
    DB::table('companies')->where('id', 1)->update(['status' => 'active']);
    callEndpoint('DELETE', '/admin/settings/jobs-api/'.$integration->id, [], null, true);
    check(callEndpoint('GET', '/api/jobs', [], $replacement)->getStatusCode() === 401, 'Revoked key rejected');
    DB::table('jobs')->where('company_id', 2)->update(['status' => 'inactive']);
    $feed = json_decode(callEndpoint('GET', '/api/jobs', [], $secondKey)->getContent(), true);
    check($feed['status'] === true && $feed['total_jobs'] === 0 && $feed['jobs'] === [], 'Empty company feed succeeds');
    DB::table('companies')->where('id', 2)->delete();
    check(JobApiIntegration::find($second->id) === null, 'Deleting company cascades its integration');
    check(callEndpoint('GET', '/api/jobs', [], $secondKey)->getStatusCode() === 401, 'Deleted company key rejected');
    DB::table('companies')->insert(['id' => 4, 'company_name' => 'New Client', 'status' => 'active']);
    $created = callEndpoint('POST', '/admin/settings/jobs-api', ['company_id' => 4], null, true);
    $newKey = $session->get('new_job_api_token');
    check($created->getStatusCode() === 302 && $session->get('new_job_api_company') === 'New Client', 'Settings creates a key for the selected company');
    $createdPage = callEndpoint('GET', '/admin/settings/jobs-api', [], null, true)->getContent();
    check(str_contains($createdPage, $newKey), 'Fresh key shown for copying');
    $session->ageFlashData();
    $session->ageFlashData();
    check(!str_contains(callEndpoint('GET', '/admin/settings/jobs-api', [], null, true)->getContent(), $newKey), 'Fresh key disappears after flash expiry');
    check(callEndpoint('GET', '/api/jobs', [], $newKey)->getStatusCode() === 200, 'New company key works');
    check(callEndpoint('GET', '/api/assistmyday/jobs', [], $key)->getStatusCode() === 404, 'Legacy branded API retired');
    check(callEndpoint('POST', '/api/consortium-registration', [], $key)->getStatusCode() === 404, 'Legacy registration API retired');
    $migration->down();
    check(!Schema::hasTable('job_api_integrations'), 'Migration rollback succeeds');
    Carbon::setTestNow();
    echo "Jobs API integration checks passed: $checks\n";
}
