<?php
// Isolated workspaces and fake Microsoft responses; no live mailbox access.
require __DIR__.'/saas-integration.php';

use App\CandidateEmailMessage;
use App\CandidateReplyMailbox;
use App\Http\Controllers\Saas\MicrosoftReplyInboxController;
use App\Services\MicrosoftReplyInbox;
use App\Services\MicrosoftInboxException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

function fakeMicrosoft(array $responses): void
{
    Http::swap(new Illuminate\Http\Client\Factory);
    Http::preventStrayRequests();
    Http::fake($responses);
}

Carbon\Carbon::setTestNow('2026-10-06 12:00:00');
$checks = 0;
Illuminate\Support\Facades\Log::swap(Mockery::mock(Psr\Log\LoggerInterface::class)->shouldIgnoreMissing());
config(['services.microsoft_reply.client_id' => 'TEST-APP', 'services.microsoft_reply.client_secret' => 'TEST-APP-SECRET']);
$inbox = new MicrosoftReplyInbox;
$controller = new MicrosoftReplyInboxController;
$outlookSession = new Illuminate\Session\Store('outlook-test', new Illuminate\Session\ArraySessionHandler(120));
$outlookSession->start();
foreach ([$main, $alpha, $gamma] as $tenant) {
    $context->activate($tenant);
    if (!Schema::hasColumn('job_applications', 'email')) Schema::table('job_applications', fn ($t) => $t->string('email')->nullable());
    (require $root.'/database/migrations/2026_10_02_000004_create_candidate_email_messages_table.php')->up();
}
$context->activate($alpha);
DB::table('job_applications')->where('id', 1)->update(['email' => 'candidate@example.test']);
DB::table('job_applications')->where('id', 2)->update(['email' => 'candidate@example.test']);
foreach ([[1, 'exact-alpha@example.test'], [2, 'other-alpha@example.test']] as [$id, $messageId]) {
    CandidateEmailMessage::create(['job_application_id' => $id, 'user_id' => 1, 'direction' => 'outbound',
        'from_address' => 'alpha@example.test', 'to_address' => 'candidate@example.test', 'subject' => 'Interview',
        'message_id' => $messageId, 'body' => 'Sent from ATS']);
}
$makeRequest = function (string $method, string $path, array $data = []) use ($outlookSession, $app) {
    $request = Request::create('https://ats.example.test'.$path, $method, $data);
    $request->setLaravelSession($outlookSession);
    $request->setUserResolver(fn () => App\User::find(1));
    $app->instance('request', $request); $app['url']->setRequest($request);
    return $request;
};
$start = fn () => $controller->connect($makeRequest('POST', '/account/integrations/microsoft/connect'), $inbox);
$callback = fn (array $parameters) => $controller->callback($makeRequest('GET', '/account/integrations/microsoft/callback', $parameters), $inbox);
$tokenUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
$deltaUrl = 'https://graph.microsoft.com/v1.0/me/mailFolders/inbox/messages/delta?$deltatoken=next';
$nextUrl = 'https://graph.microsoft.com/v1.0/me/mailFolders/inbox/messages/delta?$skiptoken=page2';
Http::preventStrayRequests();
fakeMicrosoft(['*' => Http::response([], 500)]);
$bad = $callback(['state' => 'made-up', 'code' => 'secret-code']);
check($bad->getSession()->get('errors')->has('outlook'), 'Unsolicited callbacks are rejected');
Http::assertNothingSent();
$authorization = $start();
parse_str(parse_url($authorization->getTargetUrl(), PHP_URL_QUERY), $authQuery);
$pending = $outlookSession->get('microsoft_reply_oauth');
check($authQuery['redirect_uri'] === 'https://ats.example.test/account/integrations/microsoft/callback', 'One fixed registered callback serves all workspaces');
check($authQuery['state'] === $pending['state'] && $pending['tenant_id'] === $alpha->id, 'OAuth state belongs to the initiating workspace');
check($authQuery['code_challenge'] === rtrim(strtr(base64_encode(hash('sha256', $pending['verifier'], true)), '+/', '-_'), '='), 'Microsoft authorization uses S256 PKCE');
check(str_contains($authQuery['scope'], 'Mail.Read') && !str_contains($authQuery['scope'], 'Mail.ReadWrite'), 'Inbox access requests read-only delegated mail permissions');
$context->activate($gamma);
$bad = $callback(['state' => $pending['state'], 'code' => 'wrong-workspace']);
check(!CandidateReplyMailbox::exists() && $bad->getSession()->get('errors')->has('outlook'), 'An OAuth callback cannot write another workspace even with the same local user ID');
Http::assertNothingSent();
$context->activate($alpha);
$outlookSession->put('microsoft_reply_oauth.expires_at', now()->subSecond()->timestamp);
$callback(['state' => $pending['state'], 'code' => 'expired']);
Http::assertNothingSent();
check(!CandidateReplyMailbox::exists(), 'Expired OAuth sessions cannot connect a mailbox');
$start(); $pending = $outlookSession->get('microsoft_reply_oauth');
$callback(['state' => $pending['state'], 'error' => 'access_denied', 'error_description' => 'Do not expose this provider text']);
check(!$outlookSession->has('microsoft_reply_oauth') && !CandidateReplyMailbox::exists(), 'Denied consent clears the pending connection');

$start(); $pending = $outlookSession->get('microsoft_reply_oauth');
fakeMicrosoft([
    $tokenUrl => Http::response(['access_token' => 'ALPHA-ACCESS', 'refresh_token' => 'ALPHA-REFRESH', 'expires_in' => 3600]),
    'https://graph.microsoft.com/v1.0/me?*' => Http::response(['mail' => 'alpha@example.test']),
    'https://graph.microsoft.com/v1.0/me/mailFolders/inbox/messages?*' => Http::response(['value' => []]),
]);
$connected = $callback(['state' => $pending['state'], 'code' => 'authorization-code']);
check($connected->isRedirect() && str_contains($connected->getTargetUrl(), 'workspace=alpha'), 'Successful connection returns to its client integration settings');
$mailbox = CandidateReplyMailbox::findOrFail(1);
check($mailbox->email === 'alpha@example.test' && $mailbox->refresh_token === 'ALPHA-REFRESH', 'Microsoft mailbox and refresh token are saved for this client');
check(!$outlookSession->has('microsoft_reply_oauth'), 'Successful authorization consumes OAuth state');
Http::assertSent(fn ($r) => $r->url() === $tokenUrl && $r['code_verifier'] === $pending['verifier'] && $r['client_secret'] === 'TEST-APP-SECRET');
check(!str_contains(DB::table('candidate_reply_mailboxes')->value('access_token'), 'ALPHA-ACCESS') && !str_contains(DB::table('candidate_reply_mailboxes')->value('refresh_token'), 'ALPHA-REFRESH'), 'Mailbox tokens are encrypted at rest');
check(!str_contains($mailbox->toJson(), 'ALPHA-ACCESS') && !array_key_exists('refresh_token', $mailbox->toArray()), 'Mailbox tokens are hidden from serialized settings');
$settingsView = view('saas.integrations-content', (new App\Http\Controllers\Saas\AccountController)->integrations($makeRequest('GET', '/account/integrations'))->getData())->render();
check(str_contains($settingsView, 'Connected:') && str_contains($settingsView, 'alpha@example.test') && !str_contains($settingsView, 'ALPHA-REFRESH'), 'Client UI shows connection state without tokens');
$context->activate($gamma);
check(!CandidateReplyMailbox::exists(), 'Another workspace never inherits a connected mailbox');
$context->activate($alpha);
$requestsBeforeReplay = Http::recorded()->count();
$callback(['state' => $pending['state'], 'code' => 'authorization-code']);
check(Http::recorded()->count() === $requestsBeforeReplay, 'OAuth authorization cannot be replayed');

$reply = [
    'id' => 'graph-reply', 'internetMessageId' => '<reply@example.test>', 'from' => ['emailAddress' => ['address' => 'alternate@example.test']],
    'subject' => 'Changed subject', 'body' => ['contentType' => 'html', 'content' => '<p>I am available.</p><script>unsafe()</script>'],
    'receivedDateTime' => '2026-10-06T12:01:00Z',
    'internetMessageHeaders' => [['name' => 'In-Reply-To', 'value' => '<exact-alpha@example.test>']],
];
fakeMicrosoft(['*' => Http::sequence()
    ->push(['value' => [$reply, ['id' => 'deleted', '@removed' => ['reason' => 'deleted']]], '@odata.nextLink' => $nextUrl])
    ->push(['value' => [], '@odata.deltaLink' => $deltaUrl])]);
check($inbox->sync($mailbox) === 1, 'Initial Outlook sync follows pages and imports the reply');
$stored = CandidateEmailMessage::where('direction', 'inbound')->firstOrFail();
check($stored->job_application_id === 1 && $stored->from_address === 'alternate@example.test', 'Exact reply headers identify the right application despite alternate sender and changed subject');
check($stored->body === 'I am available.' && $stored->received_at->format('H:i') === '12:01', 'Outlook HTML becomes safe text and preserves message time');
check(CandidateReplyMailbox::find(1)->sync_cursor === $deltaUrl && CandidateReplyMailbox::find(1)->last_synced_at !== null, 'Completed delta cursor and sync time are saved');
Http::assertSent(fn ($r) => $r->url() === $nextUrl && $r->hasHeader('Authorization', 'Bearer ALPHA-ACCESS'));
Http::assertSentCount(2);
fakeMicrosoft(['*' => Http::response(['value' => [$reply], '@odata.deltaLink' => $deltaUrl])]);
check($inbox->sync($mailbox) === 0 && CandidateEmailMessage::where('direction', 'inbound')->count() === 1, 'Repeated Microsoft deliveries do not duplicate conversation messages');
Http::assertSent(fn ($r) => $r->url() === $deltaUrl);

CandidateReplyMailbox::find(1)->update(['token_expires_at' => now()->subMinute()]);
fakeMicrosoft([
    $tokenUrl => Http::response(['access_token' => 'RENEWED-ACCESS', 'refresh_token' => 'ROTATED-REFRESH', 'expires_in' => 3600]),
    'https://graph.microsoft.com/*' => Http::response(['value' => [], '@odata.deltaLink' => $deltaUrl]),
]);
$inbox->sync($mailbox);
check(CandidateReplyMailbox::find(1)->refresh_token === 'ROTATED-REFRESH', 'Background sync renews and stores rotated Microsoft tokens');
Http::assertSent(fn ($r) => $r->url() === $tokenUrl && $r['grant_type'] === 'refresh_token' && $r['refresh_token'] === 'ALPHA-REFRESH');
Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer RENEWED-ACCESS'));

$runCommand = function () use ($app) {
    $command = new App\Console\Commands\ImportCandidateEmailReplies;
    $command->setLaravel($app);
    $output = new Symfony\Component\Console\Output\BufferedOutput;
    $command->setOutput(new Illuminate\Console\OutputStyle(new Symfony\Component\Console\Input\ArrayInput([]), $output));
    $status = $command->handle();
    return [$status, $output->fetch()];
};
fakeMicrosoft(['*' => Http::response(['value' => [], '@odata.deltaLink' => $deltaUrl])]);
check($runCommand()[0] === 0, 'The scheduled importer uses Microsoft without requiring PHP IMAP or an IMAP password');
fakeMicrosoft(['*' => Http::response(['error' => ['message' => 'private provider data ALPHA-ACCESS']], 403)]);
[$status, $output] = $runCommand();
check($status === 1 && str_contains($output, 'Microsoft denied inbox access') && !str_contains($output, 'private provider data'), 'Inbox failures are actionable and do not expose provider responses');
check(str_contains(CandidateReplyMailbox::find(1)->sync_error, 'Microsoft denied inbox access'), 'Scheduled failures appear on the client connection settings');
fakeMicrosoft(['*' => Http::response(['value' => [], '@odata.deltaLink' => $deltaUrl])]);
$runCommand();
check(CandidateReplyMailbox::find(1)->sync_error === null, 'Successful automatic retry clears the previous connection error');

fakeMicrosoft(['*' => Http::response(['value' => [], '@odata.nextLink' => 'https://external.example.test/steal-token'])]);
try { $inbox->sync($mailbox); throw new RuntimeException('Untrusted Graph cursor accepted'); }
catch (MicrosoftInboxException $e) { check(str_contains($e->getMessage(), 'invalid sync cursor'), 'Foreign pagination URLs cannot receive the OAuth token'); }
check(CandidateReplyMailbox::find(1)->sync_cursor === $deltaUrl, 'A failed page never advances its sync cursor');
Http::assertSentCount(1);
fakeMicrosoft(['*' => Http::response([], 410)]);
try { $inbox->sync($mailbox); } catch (MicrosoftInboxException $e) {}
check(CandidateReplyMailbox::find(1)->sync_cursor === null, 'Expired Microsoft cursors restart safely without losing stored replies');

fakeMicrosoft(['*' => Http::response(['value' => [], '@odata.deltaLink' => $deltaUrl])]);
$lock = Illuminate\Support\Facades\Cache::lock('candidate-outlook-inbox', 300); $lock->get();
check($inbox->sync($mailbox) === 0 && !$inbox->disconnect(), 'Concurrent sync and disconnect cannot race token or cursor writes');
Http::assertNothingSent(); $lock->release();
$context->activate($gamma);
CandidateReplyMailbox::create(['id' => 1, 'email' => 'gamma@example.test', 'access_token' => 'GAMMA-ACCESS', 'refresh_token' => 'GAMMA-REFRESH', 'token_expires_at' => now()->addHour()]);
$inbox->sync(CandidateReplyMailbox::find(1));
Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer GAMMA-ACCESS'));
check(CandidateEmailMessage::count() === 0, 'Other clients never receive this workspace conversation');
$controller->disconnect($makeRequest('DELETE', '/account/integrations/microsoft'), $inbox);
check(!CandidateReplyMailbox::exists(), 'Disconnect deletes only the current workspace tokens');
$context->activate($alpha);
check(CandidateReplyMailbox::exists() && CandidateEmailMessage::where('direction', 'inbound')->count() === 1, 'Another client disconnecting preserves the connected client and conversation');
$restricted = $makeRequest('POST', '/account/integrations/microsoft/connect');
$restricted->setUserResolver(fn () => new class { public function cans($permission) { return false; } });
try { $controller->connect($restricted, $inbox); throw new RuntimeException('Missing permission accepted'); }
catch (Symfony\Component\HttpKernel\Exception\HttpException $e) { check($e->getStatusCode() === 403, 'Only users who manage settings can connect a mailbox'); }
$context->reset();
check(!CandidateReplyMailbox::exists(), 'Mailbox operations leave the platform database untouched');
Carbon\Carbon::setTestNow();
echo "Outlook reply integration checks passed: {$checks}\n";
