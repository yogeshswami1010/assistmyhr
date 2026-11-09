<?php
// Isolated in-memory databases and fake Google responses; never uses a live calendar.
$loader = require ($argv[1] ?? __DIR__.'/../vendor/autoload.php');
$loader->setPsr4('App\\', __DIR__.'/../app');
require_once __DIR__.'/../app/Saas/helpers.php';

use App\GoogleCalendarConnection;
use App\Services\GoogleCalendarException;
use App\Services\GoogleCalendarService;
use App\Http\Controllers\Admin\GoogleCalendarController;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

$app = new Illuminate\Foundation\Application(dirname(__DIR__));
$app->instance('config', new Illuminate\Config\Repository([
    'app' => ['key' => 'base64:'.base64_encode(str_repeat('k', 32)), 'cipher' => 'AES-256-CBC', 'url' => 'https://ats.example.test'],
    'database' => ['default' => 'alpha', 'connections' => [
        'alpha' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true],
        'beta' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true],
    ]],
    'cache' => ['default' => 'array', 'stores' => ['array' => ['driver' => 'array']]],
    'services' => ['google_calendar' => ['client_id' => 'FAKE-CLIENT', 'client_secret' => 'FAKE-SECRET']],
    'saas' => ['enabled' => false],
]));
Facade::setFacadeApplication($app);
foreach ([Illuminate\Events\EventServiceProvider::class, Illuminate\Database\DatabaseServiceProvider::class,
    Illuminate\Encryption\EncryptionServiceProvider::class, Illuminate\Cache\CacheServiceProvider::class,
    Illuminate\Routing\RoutingServiceProvider::class] as $provider) $app->register($provider);
$app->boot();
$app->instance('view', Mockery::mock(Illuminate\Contracts\View\Factory::class));
Route::get('/admin/interview-schedule', fn () => 'schedule')->name('admin.interview-schedule.index');
$app['router']->getRoutes()->refreshNameLookups();
foreach (['alpha', 'beta'] as $database) {
    DB::setDefaultConnection($database);
    DB::getSchemaBuilder()->create('users', function ($table) { $table->increments('id'); });
    DB::table('users')->insert([['id' => 1], ['id' => 2]]);
    (require __DIR__.'/../database/migrations/2026_10_31_000001_create_google_calendar_connections_table.php')->up();
}
DB::setDefaultConnection('alpha');
Illuminate\Support\Facades\Log::swap(Mockery::mock(Psr\Log\LoggerInterface::class)->shouldIgnoreMissing());
CarbonImmutable::setTestNow('2026-10-31 10:00:00 UTC');
Carbon\Carbon::setTestNow('2026-10-31 10:00:00 UTC');
if (!trait_exists(\Shanmuga\LaravelEntrust\Traits\LaravelEntrustUserTrait::class)) {
    trait TestCalendarEntrustTrait {}
    class_alias(TestCalendarEntrustTrait::class, \Shanmuga\LaravelEntrust\Traits\LaravelEntrustUserTrait::class);
}
$user = Mockery::mock(App\User::class)->makePartial();
$user->id = 1; $user->email = 'admin@example.test'; $user->shouldReceive('cans')->with('view_schedule')->andReturn(true);
$other = new App\User; $other->id = 2; $other->email = 'other@example.test';
$calendar = new GoogleCalendarService;
Http::preventStrayRequests();
$tokenUrl = 'https://oauth2.googleapis.com/token';
$eventsUrl = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';
$profileUrl = 'https://openidconnect.googleapis.com/v1/userinfo';
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); echo "PASS: {$message}\n"; }
function fakeGoogle(array $responses) { Http::swap(new Illuminate\Http\Client\Factory); Http::preventStrayRequests(); Http::fake($responses); }
function googleRejected(callable $action, string $message) {
    try { $action(); } catch (GoogleCalendarException $e) { check(true, $message); return; }
    throw new RuntimeException($message);
}
$session = new Illuminate\Session\Store('google-test', new Illuminate\Session\ArraySessionHandler(120)); $session->start();
$app->instance('session.store', $session);
$makeRequest = function (array $parameters = []) use ($session, $user, $app) {
    $request = Request::create('https://ats.example.test/admin/interview-schedule/google-calendar/callback', 'GET', $parameters);
    $request->setLaravelSession($session); $request->setUserResolver(fn () => $user);
    $app->instance('request', $request); $app['url']->setRequest($request);
    return $request;
};
$controller = new GoogleCalendarController;
$authorization = $controller->connect($makeRequest(), $calendar);
parse_str(parse_url($authorization->getTargetUrl(), PHP_URL_QUERY), $query);
$pending = $session->get('google_calendar_oauth');
check($query['state'] === $pending['state'] && $query['access_type'] === 'offline', 'OAuth state and offline access are requested');
check($query['code_challenge'] === rtrim(strtr(base64_encode(hash('sha256', $pending['verifier'], true)), '+/', '-_'), '='), 'OAuth uses S256 PKCE');
check(str_contains($query['scope'], 'calendar.events.readonly') && !str_contains($query['scope'], 'calendar.events '), 'Google calendar permission is read-only');
$bad = $controller->callback($makeRequest(['state' => 'forged', 'code' => 'secret']), $calendar);
check($bad->getSession()->get('errors')->has('google_calendar'), 'Forged OAuth callback is rejected'); Http::assertNothingSent();
$session->put('google_calendar_oauth.user_id', 2);
$controller->callback($makeRequest(['state' => $pending['state'], 'code' => 'secret']), $calendar); Http::assertNothingSent();
check(!GoogleCalendarConnection::exists(), 'OAuth is bound to the initiating user');
$session->put('google_calendar_oauth.user_id', 1);
$session->put('google_calendar_oauth.tenant_id', 99);
$controller->callback($makeRequest(['state' => $pending['state'], 'code' => 'secret']), $calendar); Http::assertNothingSent();
check(!GoogleCalendarConnection::exists(), 'OAuth is bound to the initiating workspace');
$session->put('google_calendar_oauth.tenant_id', null);
$session->put('google_calendar_oauth.expires_at', now()->subSecond()->timestamp);
$controller->callback($makeRequest(['state' => $pending['state'], 'code' => 'secret']), $calendar); Http::assertNothingSent();
check(!GoogleCalendarConnection::exists(), 'Expired OAuth cannot connect a calendar');
$controller->connect($makeRequest(), $calendar); $pending = $session->get('google_calendar_oauth');
fakeGoogle([
    $tokenUrl => Http::response(['access_token' => 'ACCESS', 'refresh_token' => 'REFRESH', 'expires_in' => 3600]),
    $profileUrl => Http::response(['email' => $user->email, 'email_verified' => true, 'sub' => 'google-admin']),
]);
$controller->callback($makeRequest(['state' => $pending['state'], 'code' => 'good']), $calendar);
check(!$session->has('google_calendar_oauth') && GoogleCalendarConnection::first()->email === $user->email, 'Valid OAuth connects only the matching account and consumes state');
check(!str_contains(DB::table('google_calendar_connections')->value('refresh_token'), 'REFRESH'), 'Stored tokens are encrypted');
check(!str_contains(GoogleCalendarConnection::first()->toJson(), 'ACCESS'), 'Serialized connections hide tokens');
$sent = Http::recorded()->count();
$controller->callback($makeRequest(['state' => $pending['state'], 'code' => 'replay']), $calendar);
check(Http::recorded()->count() === $sent, 'OAuth cannot be replayed');
fakeGoogle([
    $tokenUrl => Http::response(['access_token' => 'WRONG', 'refresh_token' => 'WRONG-REFRESH', 'expires_in' => 3600]),
    $profileUrl => Http::response(['email' => 'someone-else@example.test', 'email_verified' => true, 'sub' => 'other']),
]);
googleRejected(fn () => $calendar->connect($user, 'code', 'verifier'), 'A different Google account cannot replace the admin calendar');
check(GoogleCalendarConnection::first()->access_token === 'ACCESS', 'Failed reconnection preserves existing tokens');
$start = CarbonImmutable::parse('2026-10-01T00:00:00+05:30'); $end = $start->addMonth();
fakeGoogle([$eventsUrl.'*' => Http::sequence()->push(['items' => [
    ['id' => 'recurring-instance', 'summary' => '<img src=x onerror=alert(1)>', 'start' => ['dateTime' => '2026-10-31T12:00:00+05:30'], 'end' => ['dateTime' => '2026-10-31T13:00:00+05:30']],
    ['id' => 'all-day', 'summary' => 'Holiday', 'start' => ['date' => '2026-10-31'], 'end' => ['date' => '2026-11-02']],
    ['id' => 'cancelled', 'status' => 'cancelled'],
], 'nextPageToken' => 'PAGE2'])->push(['items' => [
    ['id' => 'private', 'summary' => 'Private details', 'visibility' => 'private', 'description' => 'SECRET', 'start' => ['date' => '2026-10-31'], 'end' => ['date' => '2026-11-01']],
]])]);
$events = $calendar->events($user, $start, $end);
check(count($events) === 3 && $events[0]['start'] === '2026-10-31T12:00:00+05:30', 'Pagination includes timed events with original UTC offsets and excludes cancellations');
check($events[1]['allDay'] && $events[1]['end'] === '2026-11-02', 'Multi-day all-day events preserve the exclusive end date');
check($events[2]['title'] === 'Google: Busy' && !str_contains(json_encode($events), 'SECRET'), 'Private event details are hidden');
check($events[0]['sourceType'] === 'google' && !$events[0]['editable'], 'Google events are identified and read-only');
Http::assertSent(fn ($request) => ($request['pageToken'] ?? null) === 'PAGE2' && $request['singleEvents'] === 'true' && $request['timeMin'] === '2026-09-30T18:30:00+00:00');
check($calendar->events($other, $start, $end) === [], 'Other ATS users cannot read the connected calendar');
DB::setDefaultConnection('beta'); check($calendar->events($user, $start, $end) === [], 'Another client with the same user ID cannot read the calendar');
DB::setDefaultConnection('alpha');
GoogleCalendarConnection::first()->update(['token_expires_at' => now()->subMinute()]);
fakeGoogle([
    $tokenUrl => Http::response(['access_token' => 'RENEWED', 'expires_in' => 3600]),
    $eventsUrl.'*' => Http::response(['items' => []]),
]);
$calendar->events($user, $start, $end);
Http::assertSent(fn ($request) => $request->url() === $tokenUrl && $request['refresh_token'] === 'REFRESH');
check(GoogleCalendarConnection::first()->access_token === 'RENEWED' && GoogleCalendarConnection::first()->refresh_token === 'REFRESH', 'Expired access tokens renew without losing the refresh token');
fakeGoogle([$eventsUrl.'*' => Http::response(['error' => ['message' => 'PRIVATE-PROVIDER-DETAILS']], 403)]);
googleRejected(fn () => $calendar->events($user, $start, $end), 'Revoked calendar access returns a recoverable error');
$restricted = $makeRequest(); $restricted->setUserResolver(fn () => new class { public function cans($permission) { return false; } });
try { $controller->connect($restricted, $calendar); throw new RuntimeException('Missing permission accepted'); }
catch (Symfony\Component\HttpKernel\Exception\HttpException $e) { check($e->getStatusCode() === 403, 'Calendar access requires schedule permission'); }
$calendar->disconnect($other->id); check(GoogleCalendarConnection::exists(), 'Another user disconnecting cannot remove the admin connection');
$calendar->disconnect($user->id); check(!GoogleCalendarConnection::exists(), 'Disconnect removes the users calendar tokens');

config(['saas.enabled' => true, 'services.google' => ['client_id' => 'SIGNUP-CLIENT', 'client_secret' => 'SIGNUP-SECRET']]);
config(['services.google_calendar' => ['client_id' => 'CALENDAR-CLIENT', 'client_secret' => null]]);
check(!$calendar->configured(), 'A partial dedicated credential pair does not mix with signup credentials');
config(['services.google_calendar' => []]);
$tenant = (new App\Saas\Tenant)->forceFill(['id' => 10, 'slug' => 'alpha', 'owner_email' => $user->email, 'google_subject' => 'google-owner']);
$app->instance(App\Saas\TenantContext::class, new class($tenant) extends App\Saas\TenantContext {
    public function __construct(private App\Saas\Tenant $selected) {}
    public function current(): ?App\Saas\Tenant { return $this->selected; }
});
$authorization = $controller->connect($makeRequest(), $calendar);
parse_str(parse_url($authorization->getTargetUrl(), PHP_URL_QUERY), $query);
check($query['client_id'] === 'SIGNUP-CLIENT' && $query['login_hint'] === 'google-owner', 'Calendar uses the signup client and hints the linked Google account');
check($query['redirect_uri'] === 'https://ats.example.test/admin/interview-schedule/google-calendar/callback', 'Shared credentials still use the Calendar callback');
$tokenResponse = ['access_token' => 'ACCESS', 'refresh_token' => 'REFRESH', 'expires_in' => 3600];
fakeGoogle([
    $tokenUrl => Http::response($tokenResponse),
    $profileUrl => Http::response(['email' => $user->email, 'email_verified' => true, 'sub' => 'another-account']),
]);
googleRejected(fn () => $calendar->connect($user, 'code', 'verifier'), 'Matching email cannot authorize a different Google account for a linked owner');
fakeGoogle([
    $tokenUrl => Http::response($tokenResponse),
    $profileUrl => Http::response(['email' => $user->email, 'email_verified' => true, 'sub' => 'google-owner']),
    $eventsUrl.'*' => Http::response(['items' => [['id' => 'existing', 'summary' => 'Existing event', 'start' => ['date' => '2026-10-31'], 'end' => ['date' => '2026-11-01']]]]),
]);
$pending = $session->get('google_calendar_oauth');
$controller->callback($makeRequest(['state' => $pending['state'], 'code' => 'good']), $calendar);
Http::assertSent(fn ($request) => $request->url() === $tokenUrl && $request['client_id'] === 'SIGNUP-CLIENT');
check($calendar->events($user, $start, $end)[0]['title'] === 'Google: Existing event', 'Linked signup account sees existing Calendar events after consent');
$user->shouldReceive('hasVerifiedEmail')->once()->andReturn(false);
$user->shouldReceive('markEmailAsVerified')->once()->andReturn(true);
$verification = (new App\Http\Controllers\Saas\VerificationController)->verify($makeRequest(), (string) $user->id, sha1($user->email));
check(str_contains($verification->getTargetUrl(), '/admin/interview-schedule?workspace=alpha'), 'Google registrant is sent to Interview Schedule after email verification');
CarbonImmutable::setTestNow(); Carbon\Carbon::setTestNow();
echo "Google Calendar checks passed.\n";
