<?php

namespace App\Http\Controllers\Saas;

use App\Saas\AuditLog;
use App\Saas\Tenant;
use App\Saas\TenantContext;
use App\Services\MicrosoftReplyInbox;
use App\Services\MicrosoftInboxException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MicrosoftReplyInboxController extends Controller
{
    public function connect(Request $request, MicrosoftReplyInbox $inbox, Tenant $tenant)
    {
        abort_unless(Auth::guard('platform')->check(), 403);
        return $this->inTenant($tenant, function () use ($request, $inbox, $tenant) {
            if (!$inbox->configured() || !Schema::hasTable('candidate_reply_mailboxes')) {
                return $this->failure('Save Microsoft app settings before connecting Outlook.', $tenant);
            }
            $state = Str::random(64);
            $verifier = Str::random(96);
            $request->session()->put('microsoft_reply_oauth', [
                'state' => $state, 'verifier' => $verifier, 'tenant_id' => $tenant->id,
                'admin_id' => Auth::guard('platform')->id(), 'expires_at' => now()->addMinutes(10)->timestamp,
                'credential_fingerprint' => $inbox->credentialFingerprint(),
            ]);
            return redirect()->away($inbox->authorizationUrl($state, $verifier));
        });
    }

    public function callback(Request $request, MicrosoftReplyInbox $inbox)
    {
        abort_unless(Auth::guard('platform')->check(), 403);
        $pending = $request->session()->get('microsoft_reply_oauth');
        $state = $request->query('state');
        if (!is_array($pending) || !is_string($state) || !is_string($pending['state'] ?? null)
            || !hash_equals($pending['state'], $state)
            || ($pending['expires_at'] ?? 0) < now()->timestamp
            || ($pending['admin_id'] ?? null) !== Auth::guard('platform')->id()) {
            return $this->failure('The Outlook connection request expired or belongs to another administrator. Start Connect Outlook again from the client page.');
        }
        $request->session()->forget('microsoft_reply_oauth');
        $tenant = Tenant::find($pending['tenant_id'] ?? null);
        if (!$tenant) return $this->failure('This client is no longer available.');
        return $this->inTenant($tenant, function () use ($request, $inbox, $pending, $tenant) {
            if (!hash_equals($pending['credential_fingerprint'] ?? '', $inbox->credentialFingerprint())) {
                return $this->failure('Microsoft app settings changed during sign-in. Start Connect Outlook again.', $tenant);
            }
            $code = $request->query('code');
            if ($request->query('error') || !is_string($code) || $code === '' || strlen($code) > 8192) {
                return $this->failure('Outlook was not connected. Sign in and approve inbox access; your organization may require administrator approval.', $tenant);
            }
            if (!$inbox->configured()) return $this->failure('Configure Microsoft app settings before connecting Outlook.', $tenant);
            try { $inbox->connect($code, $pending['verifier']); }
            catch (MicrosoftInboxException $e) { return $this->failure($e->getMessage(), $tenant); }
            catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Outlook connection could not be saved.', ['exception_type' => $e::class]);
                return $this->failure('The Outlook connection could not be saved. Try again later.', $tenant);
            }
            $this->audit($tenant, 'email.outlook_connected');
            return $this->clientPage($tenant)->with('status', 'Outlook connected. Candidate replies will sync automatically while the server scheduler is running.');
        });
    }

    public function disconnect(Request $request, MicrosoftReplyInbox $inbox, Tenant $tenant)
    {
        abort_unless(Auth::guard('platform')->check(), 403);
        return $this->inTenant($tenant, function () use ($request, $inbox, $tenant) {
            if (!$inbox->disconnect()) return $this->failure('An inbox sync is in progress. Try disconnecting Outlook again shortly.', $tenant);
            if (($request->session()->get('microsoft_reply_oauth')['tenant_id'] ?? null) === $tenant->id) {
                $request->session()->forget('microsoft_reply_oauth');
            }
            $this->audit($tenant, 'email.outlook_disconnected');
            return $this->clientPage($tenant)->with('status', 'Outlook disconnected. Existing email conversations are saved.');
        });
    }

    private function inTenant(Tenant $tenant, callable $action)
    {
        abort_if(in_array($tenant->status, ['failed', 'provisioning']), 422, 'Repair this workspace before configuring Outlook.');
        $context = app(TenantContext::class);
        $previous = $context->current();
        try {
            $context->activate($tenant);
            return $action();
        } finally {
            $previous ? $context->activate($previous) : $context->reset();
        }
    }

    private function audit(Tenant $tenant, string $action): void
    {
        AuditLog::create(['tenant_id' => $tenant->id, 'admin_id' => Auth::guard('platform')->id(),
            'action' => $action, 'reason' => 'Client Outlook inbox connection updated by super admin.']);
    }

    private function clientPage(Tenant $tenant)
    {
        return redirect()->route('superadmin.tenants.show', $tenant)->with('client_tab', 'outlook');
    }

    private function failure(string $message, ?Tenant $tenant = null)
    {
        return ($tenant ? $this->clientPage($tenant) : redirect()->route('superadmin.dashboard'))->withErrors(['outlook' => $message]);
    }
}
