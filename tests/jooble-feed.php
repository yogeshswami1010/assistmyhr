<?php

// In-memory feed test. No external requests or ATS data.
$root = dirname(__DIR__);
$loader = require ($argv[1] ?? $root.'/vendor/autoload.php');
$loader->setPsr4('App\\', $root.'/app');
require_once $root.'/app/Saas/helpers.php';

if (!trait_exists(\Cviebrock\EloquentSluggable\Sluggable::class)) {
    trait TestJoobleSluggable {}
    class_alias(TestJoobleSluggable::class, \Cviebrock\EloquentSluggable\Sluggable::class);
}

use App\Http\Controllers\Front\JoobleFeedController;
use App\Job;
use App\Saas\Tenant;
use App\Saas\TenantContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};

$app = new Illuminate\Foundation\Application($root);
$app->instance('config', new Illuminate\Config\Repository([
    'saas' => ['enabled' => true],
    'app' => ['url' => 'https://ats.example.test'],
    'database' => ['default' => 'sqlite', 'connections' => ['sqlite' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]],
]));
Illuminate\Support\Facades\Facade::setFacadeApplication($app);
foreach ([Illuminate\Events\EventServiceProvider::class, Illuminate\Database\DatabaseServiceProvider::class, Illuminate\Routing\RoutingServiceProvider::class] as $provider) {
    $app->register($provider);
}
$app->boot();
$request = Request::create('https://ats.example.test/job-feeds/jooble.xml?workspace=alpha');
$app->instance('request', $request);
$app['router']->get('/job/{slug}/{location?}', fn () => 'Job')->name('jobs.jobDetail');
$app['router']->getRoutes()->refreshNameLookups();
$tenant = (new class extends Tenant { public function hasAccess(): bool { return true; } })
    ->forceFill(['uuid' => 'alpha-uuid', 'slug' => 'alpha']);
$app->instance(TenantContext::class, new class($tenant) extends TenantContext {
    public function __construct(private Tenant $tenant) {}
    public function current(): ?Tenant { return $this->tenant; }
});
Carbon\Carbon::setTestNow('2026-11-10 12:00:00');
Schema::create('jobs', function (Blueprint $table) {
    $table->id();
    $table->enum('status', ['active', 'inactive'])->default('active');
    foreach (['title', 'slug', 'company_location', 'job_description', 'pay_type', 'pay_according'] as $field) {
        $table->string($field)->nullable();
    }
    foreach (['company_id', 'job_type_id', 'currency_id', 'starting_salary', 'maximum_salary'] as $field) {
        $table->integer($field)->nullable();
    }
    $table->boolean('show_job_type')->default(false);
    $table->boolean('show_salary')->default(false);
    $table->date('start_date')->nullable();
    $table->date('end_date')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
foreach (['companies' => ['company_name', 'status', 'show_in_frontend'], 'job_locations' => ['location'], 'job_types' => ['job_type'], 'currencies' => ['currency_code']] as $name => $fields) {
    Schema::create($name, function (Blueprint $table) use ($fields) {
        $table->id();
        foreach ($fields as $field) { $table->string($field); }
    });
}
Schema::create('job_job_locations', function (Blueprint $table) {
    $table->id(); $table->integer('job_id'); $table->integer('location_id');
});
(require $root.'/database/migrations/2026_11_10_000001_add_jooble_publishing_to_jobs.php')->up();
DB::table('jobs')->insert(['id'=>101,'status'=>'active','start_date'=>'2026-11-01']);
DB::table('jobs')->insert(['id'=>102,'status'=>'inactive','start_date'=>'2026-11-01']);
(require $root.'/database/migrations/2026_11_10_000002_expand_job_status_values.php')->up();
check(DB::table('jobs')->orderBy('id')->pluck('status')->all() === ['active', 'inactive'], 'Status migration expands the old enum without changing existing statuses');
(require $root.'/database/migrations/2026_11_10_000003_normalize_legacy_job_statuses.php')->up();
check(DB::table('jobs')->orderBy('id')->pluck('status')->all() === ['open', 'closed'], 'Legacy Active and Inactive become Open and Closed');
DB::table('jobs')->whereIn('id', [101, 102])->delete();
DB::table('companies')->insert(['id'=>1,'company_name'=>'Acme','status'=>'active','show_in_frontend'=>'true']);
DB::table('job_locations')->insert(['id'=>1,'location'=>'Toronto']);
DB::table('job_types')->insert(['id'=>1,'job_type'=>'Full-time']);
DB::table('currencies')->insert(['id'=>1,'currency_code'=>'CAD']);
$open = ['company_id'=>1,'slug'=>'engineer','title'=>'Engineer & R&D ]]>','status'=>'open',
    'start_date'=>'2026-11-01','end_date'=>'2026-11-30','updated_at'=>'2026-11-09 12:00:00','job_description'=>'<p>Build <strong>great tools</strong>.</p>',
    'job_type_id'=>1,'show_job_type'=>true,'currency_id'=>1,'show_salary'=>true,'pay_type'=>'Range','starting_salary'=>50000,'maximum_salary'=>70000,'pay_according'=>'year'];
DB::table('jobs')->insert($open + ['id'=>1]);
DB::table('jobs')->insert(array_replace($open, ['id'=>2,'slug'=>'filled','status'=>'filled','publish_on_jooble'=>true]));
DB::table('jobs')->insert(array_replace($open, ['id'=>3,'slug'=>'closed','status'=>'closed','publish_on_jooble'=>true]));
DB::table('jobs')->insert(array_replace($open, ['id'=>4,'slug'=>'expired','end_date'=>'2026-11-09','publish_on_jooble'=>true]));
DB::table('jobs')->insert(array_replace($open, ['id'=>5,'slug'=>'future','start_date'=>'2026-11-11','publish_on_jooble'=>true]));
DB::table('jobs')->insert(array_replace($open, ['id'=>6,'slug'=>'deleted','deleted_at'=>now(),'publish_on_jooble'=>true]));
DB::table('jobs')->insert(array_replace($open, ['id'=>7,'slug'=>'on-hold','status'=>'on_hold','publish_on_jooble'=>true]));
foreach (range(1, 7) as $id) { DB::table('job_job_locations')->insert(['job_id'=>$id,'location_id'=>1]); }

function check($condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
}

$controller = new JoobleFeedController;
$response = $controller($request);
$xml = simplexml_load_string($response->getContent());
check($response->headers->get('Content-Type') === 'application/xml; charset=UTF-8' && $xml->getName() === 'jobs', 'Jooble receives valid UTF-8 XML');
check(count($xml->job) === 1 && (string) $xml->job['id'] === 'alpha-uuid-1', 'Only current Open jobs appear, regardless of the old checkbox');
check((string) $xml->job->name === $open['title'] && (string) $xml->job->description === Job::findOrFail(1)->job_description, 'CDATA preserves title and full public HTML');
check((string) $xml->job->link === 'https://ats.example.test/job/engineer/1?workspace=alpha' && (string) $xml->job->region === 'Toronto', 'Feed links to the correct workspace and location');
check((string) $xml->job->pubdate === '01.11.2026' && (string) $xml->job->updated === '09.11.2026' && (string) $xml->job->expire === '30.11.2026', 'Dates use Jooble format');
check((string) $xml->job->salary === '50000–70000 CAD / year' && (string) $xml->job->jobtype === 'Full-time' && (string) $xml->job->company === 'Acme', 'Optional public compensation and employer data are included');
check(Job::publicJobPage()->pluck('id')->all() === [1], 'The advertised job is available on the public job page');
try {
    $controller(Request::create('https://ats.example.test/job-feeds/jooble.xml?workspace=beta'));
    throw new RuntimeException('Mismatched workspace could access the feed');
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
    check($exception->getStatusCode() === 404, 'Feed requires the selected workspace');
}
DB::table('jobs')->where('id', 1)->update(['status' => 'closed']);
check(count(simplexml_load_string($controller($request)->getContent())->job) === 0, 'Closing an Open job removes the listing');

DB::table('jobs')->where('id', 1)->update(['status' => 'open', 'start_date' => null, 'end_date' => '0000-00-00', 'created_at' => '2026-11-02 12:00:00']);
$xml = simplexml_load_string($controller($request)->getContent());
check(count($xml->job) === 1 && (string) $xml->job->pubdate === '02.11.2026' && !isset($xml->job->expire), 'A missing start date uses the creation date and a zero expiry is treated as unset');
check(Job::publicJobPage()->whereKey(1)->exists(), 'The recovered listing links to an accessible job detail page');
DB::table('jobs')->where('id', 1)->update(['start_date' => '0000-00-00']);
check(count(simplexml_load_string($controller($request)->getContent())->job) === 1, 'Open jobs with a legacy zero start date still appear');
DB::table('jobs')->where('id', 1)->update(['job_description' => '<p>&nbsp;</p>']);
check(Job::findOrFail(1)->joobleContentIssue() === 'Add a job description.' && count(simplexml_load_string($controller($request)->getContent())->job) === 0, 'Empty rich-text descriptions are excluded with an actionable reason');
DB::table('jobs')->where('id', 1)->update(['status' => '', 'job_description' => $open['job_description']]);
check(count(simplexml_load_string($controller($request)->getContent())->job) === 0, 'Blank status is not treated as permission to publish');
DB::table('jobs')->where('id', 1)->update(['status' => Job::STATUS_OPEN]);
check(count(simplexml_load_string($controller($request)->getContent())->job) === 1, 'Saving a repaired job as Open restores the Jooble listing');
echo "Jooble feed checks passed.\n";
