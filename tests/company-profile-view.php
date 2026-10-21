<?php
// Render the actual company view using Laravel and a minimal layout. No database
// or SaaS helper bootstrap is needed to display ordinary admin navigation links.
$root = dirname(__DIR__);
require ($argv[1] ?? $root.'/vendor/autoload.php');
$temp = sys_get_temp_dir().'/company-profile-view-'.bin2hex(random_bytes(6));
mkdir($temp.'/layouts', 0700, true);
mkdir($temp.'/compiled', 0700, true);
file_put_contents($temp.'/layouts/app.blade.php', '<html>@stack("head-script")<body>@yield("content")@stack("footer-script")</body></html>');
$app = new Illuminate\Foundation\Application($root);
$app->instance('env', 'testing');
$app->instance('config', new Illuminate\Config\Repository([
    'app' => ['url' => 'https://ats.example.test', 'locale' => 'en', 'fallback_locale' => 'en'],
    'view' => ['paths' => [$temp, $root.'/resources/views'], 'compiled' => $temp.'/compiled'],
    'session' => ['driver' => 'array', 'lifetime' => 120],
]));
Illuminate\Support\Facades\Facade::setFacadeApplication($app);
foreach ([Illuminate\Events\EventServiceProvider::class, Illuminate\Filesystem\FilesystemServiceProvider::class,
    Illuminate\Routing\RoutingServiceProvider::class, Illuminate\Translation\TranslationServiceProvider::class,
    Illuminate\Session\SessionServiceProvider::class, Illuminate\View\ViewServiceProvider::class] as $provider) $app->register($provider);
$app->boot();
$request = Illuminate\Http\Request::create('https://ats.example.test/admin/company/1');
$app->instance('request', $request); $app['url']->setRequest($request);
$session = $app['session']->driver(); $session->start();
$app->instance('session.store', $session); $request->setLaravelSession($session);
foreach (['admin.company.index' => 'admin/company', 'admin.jobs.index' => 'admin/jobs',
    'admin.job-applications.table' => 'admin/job-applications/table-view', 'admin.report.index' => 'admin/report',
    'admin.company.update' => 'admin/company/{company}'] as $name => $path) {
    $app['router']->get($path, fn () => null)->name($name);
}
$app['router']->getRoutes()->refreshNameLookups();
$company = (object) [
    'id' => 1, 'company_name' => 'Example Company', 'status' => 'active', 'company_email' => '',
    'company_phone' => '', 'website' => '', 'address' => '', 'logo' => null, 'logo_url' => '',
    'created_at' => Carbon\Carbon::parse('2026-10-01'), 'show_in_frontend' => 'true',
];
$render = function (bool $canEdit, bool $canViewApplications = true) use ($company) {
    $user = new class($canEdit, $canViewApplications) {
        public function __construct(private bool $edit, private bool $applications) {}
        public function cans($permission): bool {
            return match ($permission) { 'edit_company' => $this->edit, 'view_job_applications' => $this->applications, default => true };
        }
    };
    return view('admin.company.show', ['company' => $company, 'user' => $user,
        'userPermissions' => $canEdit ? ['edit_company'] : [],
        'companyJobsCount' => 1, 'companyApplicationsCount' => 3, 'companyHiredCount' => 0])->render();
};
$html = $render(false);
if (!str_contains($html, 'Example Company') || !str_contains($html, 'href="https://ats.example.test/admin/job-applications/table-view"')) {
    throw new RuntimeException('Company profile must render with the requested table-view link.');
}
$html = $render(true);
if (!str_contains($html, 'companyShowEditForm') || !str_contains($html, json_encode('https://ats.example.test/admin/company/1'))) {
    throw new RuntimeException('Editable company profile must render with its update endpoint.');
}
if (str_contains($render(false, false), 'href="https://ats.example.test/admin/job-applications/table-view"')) {
    throw new RuntimeException('Application navigation must respect the existing permission check.');
}
echo "PASS: company profile renders for viewing and editing, opens application table view, and respects permissions.\n";
