<?php

namespace App\Http\Controllers\Saas;

use App\Saas\TenantContext;
use App\Services\MicrosoftReplyInbox;
use App\Services\MicrosoftInboxException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MicrosoftReplyInboxController extends Controller
{
    public function connect(Request $request, MicrosoftReplyInbox $inbox)
    {
        abort_unless($request->user()->cans('manage_settings'), 403);
        if (!$inbox->configured() || !Schema::hasTable('candidate_reply_mailboxes')) {
            return $this->failure('Outlook connection is not configured yet. Ask the platform administrator to complete Microsoft inbox setup.');
        }
        $state = Str::random(64);
        $verifier = Str::random(96);
        $request->session()->put('microsoft_reply_oauth', [
            'state' => $state, 'verifier' => $verifier,
            'tenant_id' => app(TenantContext::class)->current()?->id,
            'user_id' => $request->user()->id, 'expires_at' => now()->addMinutes(10)->timestamp,
        ]);
        return redirect()->away($inbox->authorizationUrl($state, $verifier));
    }

    public function callback(Request $request, MicrosoftReplyInbox $inbox)
    {
        abort_unless($request->user()->cans('manage_settings'), 403);
        $pending = $request->session()->get('microsoft_reply_oauth');
        $state = $request->query('state');
        if (!is_array($pending) || !is_string($state) || !hash_equals($pending['state'], $state)
            || $pending['expires_at'] < now()->timestamp
            || $pending['tenant_id'] !== app(TenantContext::class)->current()?->id
            || $pending['user_id'] !== $request->user()->id) {
            return $this->failure('The Outlook connection request expired or belongs to another account. Start Connect Outlook again from this workspace.');
        }
        $request->session()->forget('microsoft_reply_oauth');
        $code = $request->query('code');
        if ($request->query('error') || !is_string($code) || $code === '' || strlen($code) > 8192) {
            return $this->failure('Outlook was not connected. Sign in and approve inbox access; your organization may require administrator approval.');
        }
        if (!$inbox->configured()) return $this->failure('Microsoft inbox access is not configured. Contact the platform administrator.');
        try { $inbox->connect($code, $pending['verifier']); }
        catch (MicrosoftInboxException $e) { return $this->failure($e->getMessage()); }
        catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Outlook connection could not be saved.', ['exception_type' => $e::class]);
            return $this->failure('The Outlook connection could not be saved. Try again later or contact the platform administrator.');
        }
        return redirect(tenant_route('saas.integrations'))->with('status', 'Outlook connected. Candidate replies will sync automatically while the server scheduler is running.');
    }

    public function disconnect(Request $request, MicrosoftReplyInbox $inbox)
    {
        abort_unless($request->user()->cans('manage_settings'), 403);
        if (!$inbox->disconnect()) return $this->failure('An inbox sync is in progress. Try disconnecting Outlook again shortly.');
        $request->session()->forget('microsoft_reply_oauth');
        return redirect(tenant_route('saas.integrations'))->with('status', 'Outlook disconnected. Existing email conversations are saved.');
    }

    private function failure(string $message)
    {
        return redirect(tenant_route('saas.integrations'))->withErrors(['outlook' => $message]);
    }
}
