<?php

// Isolated backend checks. Optionally write a rendered fixture for job-pipeline.cjs.
$root = dirname(__DIR__);
$loader = require ($argv[1] ?? $root.'/vendor/autoload.php');
$loader->setPsr4('App\\', $root.'/app');

use App\ApplicationStatus;
use App\Http\Controllers\Admin\AdminJobsController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$app = new Illuminate\Foundation\Application($root);
$app->instance('config', new Illuminate\Config\Repository([
    'database' => ['default' => 'sqlite', 'connections' => ['sqlite' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]],
    'view' => ['paths' => [$root.'/resources/views'], 'compiled' => sys_get_temp_dir()],
]));
Illuminate\Support\Facades\Facade::setFacadeApplication($app);
foreach ([Illuminate\Events\EventServiceProvider::class, Illuminate\Database\DatabaseServiceProvider::class,
    Illuminate\Filesystem\FilesystemServiceProvider::class, Illuminate\View\ViewServiceProvider::class,
    Illuminate\Translation\TranslationServiceProvider::class, Illuminate\Validation\ValidationServiceProvider::class] as $provider) {
    $app->register($provider);
}
$app->boot();
Schema::create('application_status', function (Blueprint $table) {
    $table->increments('id'); $table->unsignedInteger('job_id'); $table->string('status');
    $table->string('color'); $table->integer('position'); $table->timestamps();
});
Schema::create('job_applications', function (Blueprint $table) {
    $table->increments('id'); $table->unsignedInteger('job_id'); $table->unsignedInteger('status_id'); $table->softDeletes();
});
Schema::create('job_application_status_histories', function (Blueprint $table) {
    $table->increments('id'); $table->unsignedInteger('from_status_id'); $table->unsignedInteger('to_status_id');
});
$checks = 0;
function check($condition, string $message): void {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}
$expected = ['Applied', 'Interview', 'Hired', 'Rejected'];
check(array_column(ApplicationStatus::DEFAULT_PIPELINE, 'status') === $expected, 'New job defaults have four stages');
ApplicationStatus::ensureDefaultsForJob(1);
ApplicationStatus::ensureDefaultsForJob(1);
$statuses = ApplicationStatus::forJob(1)->orderBy('position')->get();
check($statuses->pluck('status')->all() === $expected, 'New jobs store the four default stages once');
check($statuses->pluck('position')->all() === [1, 2, 3, 4], 'Default positions are contiguous');
check(ApplicationStatus::initialForJob(1)->id === $statuses[0]->id, 'New applications start at Applied');
ApplicationStatus::create(['job_id' => 2, 'status' => 'CSS Phone Screen', 'color' => '#2563EB', 'position' => 1]);
ApplicationStatus::create(['job_id' => 2, 'status' => 'Client Reviewed', 'color' => '#7C3AED', 'position' => 2]);
$legacy = ApplicationStatus::forJob(2)->orderBy('position')->get()->toArray();
ApplicationStatus::initialForJob(2);
check(ApplicationStatus::forJob(2)->orderBy('position')->get()->toArray() === $legacy, 'Existing job stages remain intact');

$byName = $statuses->keyBy('status');
DB::table('job_applications')->insert(['job_id' => 1, 'status_id' => $byName['Interview']->id]);
$controller = (new ReflectionClass(AdminJobsController::class))->newInstanceWithoutConstructor();
$sync = new ReflectionMethod(AdminJobsController::class, 'syncJobStatuses');
$save = function (array $names) use ($controller, $sync, $byName) {
    $sync->invoke($controller, 1, Request::create('/', 'POST', [
        'job_status_name' => $names,
        'job_status_id' => array_map(fn ($name) => $byName[$name]->id, $names),
        'job_status_color' => array_map(fn ($name) => $byName[$name]->color, $names),
    ]));
};
$save(['Rejected', 'Interview', 'Hired', 'Applied']);
$saved = ApplicationStatus::forJob(1)->orderBy('position')->get();
check($saved->pluck('status')->all() === ['Applied', 'Rejected', 'Interview', 'Hired'], 'Submitted drag order is stored, with Applied first');
check($saved->pluck('position')->all() === [1, 2, 3, 4], 'Reordered positions remain contiguous');
check($saved->pluck('id')->sort()->values()->all() === $statuses->pluck('id')->sort()->values()->all(), 'Reordering preserves stage identities');
check(DB::table('job_applications')->value('status_id') === $byName['Interview']->id, 'Assigned applicants retain their stage');
try {
    $save(['Applied', 'Rejected', 'Hired']);
    throw new RuntimeException('Removing an assigned stage should fail');
} catch (Illuminate\Validation\ValidationException $error) {
    check(str_contains($error->errors()['job_status_name'][0], 'cannot be removed'), 'Assigned stages cannot be removed');
}
check(ApplicationStatus::forJob(1)->orderBy('position')->get()->toArray() === $saved->toArray(), 'Rejected removal does not change the pipeline');
check(ApplicationStatus::forJob(2)->orderBy('position')->get()->toArray() === $legacy, 'Editing another job preserves legacy stages');

if (isset($argv[2])) {
    $view = file_get_contents($root.'/resources/views/admin/jobs/partials/job-form.blade.php');
    $start = strpos($view, '{{-- ── Pipeline Statuses');
    $card = substr($view, $start, strpos($view, '{{-- Form actions', $start) - $start);
    $start = strpos($view, '// ── Pipeline status rows');
    $script = substr($view, $start, strpos($view, '</script>', $start) - $start);
    $jobStatuses = collect(ApplicationStatus::DEFAULT_PIPELINE)->map(fn ($stage) => new ApplicationStatus($stage));
    $__env = $app['view'];
    ob_start();
    eval('?>'.$app['blade.compiler']->compileString($card));
    $html = ob_get_clean();
    file_put_contents($argv[2], '<!doctype html><meta charset="utf-8"><style>
        body{font:14px Arial;padding:24px}#job-status-list{width:380px;max-height:280px;overflow-y:auto}
        .job-status-row{display:flex;align-items:center;gap:8px;height:64px;box-sizing:border-box}
        .job-status-row input[type=text]{width:190px}.job-status-row input[type=color]{width:28px}
        svg{width:16px;height:16px}.js-drag-handle{display:inline-flex;width:24px;height:24px;cursor:grab;user-select:none}
        </style><form id="createForm"></form>'.$html.'<button id="save-form">Save</button><script>'.$script.'</script>');
}
echo "Job pipeline backend checks passed: $checks.\n";
