<?php

// Use the actual controller SQL; replace only the DataTables presentation adapter.
namespace Yajra\DataTables\Facades {
    class DataTables {
        public static function of($query) {
            return new class($query) {
                public function __construct(private $query) {}
                public function __call($method, $arguments) { return $this; }
                public function make($json) { return $this->query->withoutEagerLoads()->get()->pluck('id')->all(); }
            };
        }
    }
}

namespace {
    use App\ApplicationStatus;
    use App\Http\Controllers\Admin\AdminJobApplicationController;
    use App\JobApplication;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Schema;

    $root = dirname(__DIR__);
    $loader = require ($argv[1] ?? $root.'/vendor/autoload.php');
    $loader->setPsr4('App\\', $root.'/app');
    $app = new Illuminate\Foundation\Application($root);
    $app->instance('config', new Illuminate\Config\Repository([
        'app' => ['url' => 'https://ats.example.test', 'locale' => 'eng'],
        'database' => ['default' => 'sqlite', 'connections' => ['sqlite' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]],
        'auth' => ['defaults' => ['guard' => 'web'], 'guards' => ['web' => ['driver' => 'session', 'provider' => 'users']], 'providers' => ['users' => ['driver' => 'eloquent', 'model' => App\User::class]]],
        'session' => ['driver' => 'array', 'lifetime' => 120, 'lottery' => [0, 100], 'path' => '/', 'domain' => null, 'secure' => false, 'same_site' => 'lax'],
    ]));
    Illuminate\Support\Facades\Facade::setFacadeApplication($app);
    foreach ([Illuminate\Events\EventServiceProvider::class, Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class, Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\Routing\RoutingServiceProvider::class, Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Auth\AuthServiceProvider::class, Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class, Illuminate\Filesystem\FilesystemServiceProvider::class] as $provider) $app->register($provider);
    $app->boot();
    $app->instance('request', Request::create('https://ats.example.test'));
    $app->instance('session.store', $app['session']->driver());
    Auth::guard()->setUser(new Illuminate\Auth\GenericUser(['id' => 7, 'name' => 'Recruiter', 'email' => 'recruiter@example.test']));
    Request::macro('validate', function ($rules) { return app('validator')->make($this->all(), $rules)->validate(); });
    $app['router']->get('/admin/job-applications/table-view', fn () => 'Applications')->name('admin.job-applications.table');
    $app['router']->getRoutes()->refreshNameLookups();

    Schema::create('jobs', function (Blueprint $t) { $t->increments('id'); $t->string('title'); $t->unsignedInteger('location_id')->nullable(); $t->softDeletes(); });
    Schema::create('application_status', function (Blueprint $t) { $t->increments('id'); $t->unsignedInteger('job_id')->nullable(); $t->string('status'); $t->string('color'); $t->integer('position'); $t->timestamps(); });
    Schema::create('job_job_locations', function (Blueprint $t) { $t->increments('id'); $t->unsignedInteger('job_id'); $t->unsignedInteger('location_id'); });
    Schema::create('job_applications', function (Blueprint $t) {
        $t->increments('id'); $t->string('full_name')->default('Candidate'); $t->string('email'); $t->string('phone')->nullable();
        $t->unsignedInteger('job_id')->nullable(); $t->unsignedInteger('status_id')->nullable(); $t->unsignedInteger('location_id')->nullable();
        $t->boolean('is_candidate')->default(false); $t->integer('column_priority')->default(0); $t->string('candidate_origin')->nullable();
        $t->unsignedInteger('origin_user_id')->nullable(); $t->text('skills')->nullable(); $t->text('parsed_cv_data')->nullable();
        $t->text('cv_text')->nullable(); $t->text('address')->nullable(); $t->text('cover_letter')->nullable();
        $t->softDeletes(); $t->timestamp('moved_to_trash_at')->nullable(); $t->timestamps();
        $t->foreign('job_id')->references('id')->on('jobs');
        $t->foreign('status_id')->references('id')->on('application_status');
    });
    Schema::create('job_application_status_histories', function (Blueprint $t) {
        $t->increments('id'); $t->unsignedInteger('job_application_id'); $t->unsignedInteger('from_status_id')->nullable();
        $t->unsignedInteger('to_status_id'); $t->unsignedInteger('user_id'); $t->text('notes'); $t->timestamps();
        $t->foreign('from_status_id')->references('id')->on('application_status'); $t->foreign('to_status_id')->references('id')->on('application_status');
    });
    Schema::create('candidate_profile_activities', function (Blueprint $t) { $t->increments('id'); $t->unsignedInteger('job_application_id'); $t->string('action'); $t->unsignedInteger('user_id')->nullable(); $t->timestamps(); });
    Schema::create('documents', function (Blueprint $t) { $t->increments('id'); $t->morphs('documentable'); $t->string('name'); $t->string('hashname'); });
    Schema::create('questions', function (Blueprint $t) { $t->increments('id'); $t->string('type'); $t->boolean('is_knockout'); $t->string('knockout_answer')->nullable(); });
    Schema::create('job_application_answers', function (Blueprint $t) { $t->increments('id'); $t->unsignedInteger('job_application_id'); $t->unsignedInteger('job_id'); $t->unsignedInteger('question_id'); $t->string('answer'); });
    Schema::create('consortium_registration_job_moves', function (Blueprint $t) { $t->increments('id'); $t->unsignedInteger('job_application_id'); });
    Schema::create('application_settings', function (Blueprint $t) { $t->increments('id'); $t->text('mail_setting')->nullable(); });
    DB::table('application_settings')->insert(['id' => 1]);
    foreach ([1 => 'HR Coordinator', 2 => 'Designer', 3 => 'Deleted job'] as $id => $title) {
        DB::table('jobs')->insert(['id' => $id, 'title' => $title, 'deleted_at' => $id === 3 ? '2026-10-01 00:00:00' : null]);
        ApplicationStatus::ensureDefaultsForJob($id);
    }
    foreach ([1 => [10, 11], 2 => [20, 21], 3 => [30]] as $jobId => $locations) {
        foreach ($locations as $location) DB::table('job_job_locations')->insert(['job_id' => $jobId, 'location_id' => $location]);
    }
    $applied = ApplicationStatus::findForJob(1, 'Applied')->id;
    $interview = ApplicationStatus::findForJob(1, 'Interview')->id;
    $designerApplied = ApplicationStatus::findForJob(2, 'Applied')->id;
    $designerInterview = ApplicationStatus::findForJob(2, 'Interview')->id;
    $seed = function ($id, array $data = []) {
        DB::table('job_applications')->insert($data + ['id' => $id, 'email' => 'candidate'.$id.'@example.test', 'is_candidate' => 1,
            'candidate_origin' => 'internal', 'cv_text' => 'Original resume '.$id, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00']);
    };
    $seed(101);
    $seed(102, ['job_id' => 1, 'status_id' => $interview, 'location_id' => 11]);
    $seed(103, ['job_id' => 1, 'location_id' => 20]);
    $seed(104, ['job_id' => 1, 'status_id' => $designerInterview, 'location_id' => 11]);
    $seed(105, ['job_id' => 1, 'deleted_at' => '2026-10-01 00:00:00']);
    $seed(106, ['job_id' => 1, 'deleted_at' => '2026-10-01 00:00:00', 'moved_to_trash_at' => '2026-10-01 00:00:00']);
    $seed(107, ['job_id' => 3, 'status_id' => ApplicationStatus::findForJob(3, 'Applied')->id]);
    $seed(108, ['job_id' => 2, 'status_id' => $designerInterview, 'is_candidate' => 0]);
    $seed(109);
    for ($i = 2000; $i < 2105; $i++) $seed($i, ['job_id' => 1, 'status_id' => $applied, 'location_id' => 10]);
    DB::table('documents')->insert(['documentable_type' => JobApplication::class, 'documentable_id' => 102, 'name' => 'Resume', 'hashname' => 'original-cv.pdf']);
    $original108 = (array) DB::table('job_applications')->find(108);
    $migration = require $root.'/database/migrations/2026_10_22_000002_restore_assigned_database_candidates.php';
    $migration->up(); $migration->up();
    $checks = 0;
    function check($ok, $message) { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
    check(JobApplication::find(101)->is_candidate === 1 && JobApplication::find(101)->job_id === null, 'Unassigned database candidates remain database-only');
    check(JobApplication::find(102)->is_candidate === 0 && JobApplication::find(102)->status_id === $interview, 'Existing assigned candidates become applicants without resetting a valid stage');
    check(JobApplication::find(102)->location_id === 11 && JobApplication::find(102)->cv_text === 'Original resume 102', 'Existing location and resume content are preserved');
    check(DB::table('documents')->where('documentable_id', 102)->value('hashname') === 'original-cv.pdf', 'Repair preserves the candidate ID and uploaded CV');
    check(JobApplication::find(103)->status_id === $applied && JobApplication::find(103)->location_id === 10, 'Repair uses the assigned job stage and location');
    check(JobApplication::find(104)->status_id === $applied && JobApplication::find(104)->location_id === 11, 'Foreign job stages are repaired without changing a valid location');
    check(DB::table('job_applications')->whereIn('id', [105, 106])->where('is_candidate', 1)->whereNotNull('deleted_at')->count() === 2, 'Archived and trashed candidates are never restored');
    check(JobApplication::find(107)->is_candidate === 0 && App\Job::withTrashed()->find(3)->trashed(), 'Deleted-job applicants remain visible without restoring the job');
    check((array) DB::table('job_applications')->find(108) === $original108, 'Existing normal applications are unchanged');
    check(DB::table('job_applications')->whereBetween('id', [2000, 2104])->where('is_candidate', 0)->count() === 105, 'Repair covers more than one chunk and is idempotent');

    $controller = (new ReflectionClass(AdminJobApplicationController::class))->newInstanceWithoutConstructor();
    $actor = new class { public $id = 7; public $role_id = 1; public $allowed = true; public function cans($permission) { return $this->allowed; } };
    (new ReflectionProperty(App\Http\Controllers\Admin\AdminBaseController::class, 'user'))->setValue($controller, $actor);
    $assign = fn ($id, $job) => $controller->assignJob(Request::create('/', 'POST', ['job_id' => $job]), $id);
    $assign(109, 1);
    check(JobApplication::find(109)->is_candidate === 0 && JobApplication::find(109)->status_id === $applied, 'Assigning a database candidate immediately makes them a job applicant');
    check(JobApplication::find(109)->location_id === 10 && DB::table('job_applications')->where('id', 109)->count() === 1, 'Assignment sets a job location and does not duplicate the profile');
    check(DB::table('job_application_status_histories')->where('job_application_id', 109)->value('from_status_id') === null, 'First assignment is recorded with no previous stage');
    $assign(109, 2);
    check(JobApplication::find(109)->job_id === 2 && JobApplication::find(109)->status_id === $designerApplied && JobApplication::find(109)->location_id === 20, 'Changing jobs uses the target job pipeline and location');
    check(DB::table('job_application_status_histories')->where('job_application_id', 109)->orderByDesc('id')->value('from_status_id') === $applied, 'Reassignment history links the old and new stages');
    $historyCount = DB::table('job_application_status_histories')->count();
    $assign(109, 2);
    check(DB::table('job_application_status_histories')->count() === $historyCount, 'Repeated assignment is a no-op');
    $seed(115, ['job_id' => 1, 'status_id' => $interview, 'location_id' => 11]);
    $assign(115, 1);
    check(JobApplication::find(115)->is_candidate === 0 && JobApplication::find(115)->status_id === $interview, 'Selecting the same job repairs the flag and preserves a valid stage');
    $seed(116);
    DB::statement("CREATE TRIGGER reject_assignment_history BEFORE INSERT ON job_application_status_histories WHEN NEW.job_application_id = 116 BEGIN SELECT RAISE(ABORT, 'History test failure'); END");
    try { $assign(116, 1); throw new RuntimeException('History failure should roll back'); }
    catch (Illuminate\Database\QueryException $error) { check(JobApplication::find(116)->job_id === null && JobApplication::find(116)->is_candidate === 1, 'Failed assignment rolls back both the job and visibility flag'); }
    DB::statement('DROP TRIGGER reject_assignment_history');
    foreach ([999, 3] as $badJob) {
        try { $assign(116, $badJob); throw new RuntimeException('Invalid job should fail'); }
        catch (Illuminate\Validation\ValidationException $error) { check(isset($error->errors()['job_id']), 'Missing or deleted jobs cannot be assigned'); }
    }
    $actor->allowed = false;
    try { $assign(116, 1); throw new RuntimeException('Unauthorized assignment should fail'); }
    catch (Symfony\Component\HttpKernel\Exception\HttpException $error) { check($error->getStatusCode() === 403 && JobApplication::find(116)->job_id === null, 'Assignment requires edit permission'); }
    $actor->allowed = true;

    foreach ([110 => [1, null], 111 => [2, null], 112 => [1, '2026-10-01 00:00:00']] as $id => [$job, $deleted]) {
        $seed($id, ['job_id' => $job, 'is_candidate' => 0, 'email' => 'same-person@example.test', 'status_id' => $job === 1 ? $applied : $designerApplied, 'deleted_at' => $deleted]);
    }
    $list = fn ($job = 'all', $knockout = 0) => $controller->data(Request::create('/', 'GET', ['jobs' => $job, 'knockout' => $knockout]));
    check(in_array(102, $list(1), true) && in_array(109, $list(2), true), 'Actual Job Applications table includes repaired and newly assigned candidates');
    check(!in_array(101, $list(), true) && !in_array(105, $list(), true) && !in_array(106, $list(), true), 'Unassigned, archived and trashed profiles stay out of Job Applications');
    check(in_array(110, $list(1), true) && in_array(111, $list(2), true), 'Same-email applicants remain visible in each assigned job despite a newer archived copy');
    DB::table('questions')->insert(['id' => 1, 'type' => 'radio', 'is_knockout' => true, 'knockout_answer' => 'No']);
    DB::table('job_application_answers')->insert(['job_application_id' => 102, 'job_id' => 1, 'question_id' => 1, 'answer' => 'No']);
    check($list(1, 1) === [102], 'Knockout filtering includes repaired job applicants');
    $counts = $controller->stageCounts(Request::create('/', 'GET', ['jobs' => 1]));
    check($counts['counts']->sum() === count($list(1)) && $counts['ko_count'] === 1, 'Stage and knockout counts agree with the visible table');

    $store = function ($job) use ($controller) {
        $request = App\Http\Requests\StoreJobApplication::create('/', 'POST', ['entry_type' => 'candidate', 'job_id' => $job,
            'full_name' => 'New candidate', 'email' => 'new'.($job ?? 'database').'@example.test', 'is_bulk' => true]);
        return $controller->store($request)['id'];
    };
    $createdAssigned = $store(1);
    $createdDatabase = $store(null);
    check(JobApplication::find($createdAssigned)->is_candidate === 0 && in_array($createdAssigned, $list(1), true), 'Creation derives applicant eligibility from the assigned job');
    check(JobApplication::find($createdDatabase)->is_candidate === 1 && !in_array($createdDatabase, $list(), true), 'Database-only creation stays outside Job Applications');
    $controller->update(App\Http\Requests\UpdateJobApplication::create('/', 'POST', ['job_id' => 2, 'status_id' => $designerInterview,
        'full_name' => 'Updated candidate', 'email' => 'updated@example.test']), $createdDatabase);
    check(JobApplication::find($createdDatabase)->is_candidate === 0 && in_array($createdDatabase, $list(2), true), 'Full profile editing also promotes an assigned database candidate');
    check(JobApplication::withTrashed()->whereNull('moved_to_trash_at')->whereKey(109)->exists(), 'Assigned candidates still exist in the Candidate Database pool');
    echo "Assigned candidate checks passed: $checks (isolated SQLite, actual controller actions, table SQL and migration).\n";
}
