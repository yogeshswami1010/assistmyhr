<?php

namespace App\Http\Controllers\Admin;

use App\Saas\TenantContext;
use App\Services\GoogleCalendarException;
use App\Services\GoogleCalendarService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GoogleCalendarController extends Controller
{
    public function connect(Request $request, GoogleCalendarService $calendar)
    {
        $this->authorizeCalendar($request);
        if (!$calendar->configured()) return $this->failure('Google Calendar needs platform setup. Contact the platform administrator.');
        $state = Str::random(64);
        $verifier = Str::random(96);
        $request->session()->put('google_calendar_oauth', [
            'state' => $state, 'verifier' => $verifier, 'user_id' => $request->user()->id,
            'tenant_id' => $this->tenantId(), 'expires_at' => now()->addMinutes(10)->timestamp,
            'fingerprint' => $calendar->fingerprint(),
        ]);
        return redirect()->away($calendar->authorizationUrl($state, $verifier, $request->user()->email));
    }

    public function callback(Request $request, GoogleCalendarService $calendar)
    {
        $this->authorizeCalendar($request);
        $pending = $request->session()->get('google_calendar_oauth');
        $state = $request->query('state');
        if (!is_array($pending) || !is_string($state) || !is_string($pending['state'] ?? null)
            || !hash_equals($pending['state'], $state) || ($pending['expires_at'] ?? 0) < now()->timestamp
            || ($pending['user_id'] ?? null) !== $request->user()->id || ($pending['tenant_id'] ?? null) !== $this->tenantId()) {
            return $this->failure('Google Calendar sign-in expired or belongs to another workspace or user. Connect again.');
        }
        $request->session()->forget('google_calendar_oauth');
        if (!hash_equals($pending['fingerprint'] ?? '', $calendar->fingerprint())) return $this->failure('Google Calendar settings changed during sign-in. Connect again.');
        $code = $request->query('code');
        if ($request->query('error') || !is_string($code) || $code === '' || strlen($code) > 8192) {
            return $this->failure('Google Calendar was not connected. Approve calendar access to show your existing events.');
        }
        try { $calendar->connect($request->user(), $code, $pending['verifier']); }
        catch (GoogleCalendarException $e) { return $this->failure($e->getMessage()); }
        catch (\Throwable $e) {
            Log::warning('Google Calendar connection failed.', ['exception_type' => $e::class]);
            return $this->failure('Google Calendar could not be connected. Try again later.');
        }
        return $this->schedulePage()->with('status', 'Google Calendar connected. Your existing events now appear alongside ATS interviews.');
    }

    public function events(Request $request, GoogleCalendarService $calendar)
    {
        $this->authorizeCalendar($request);
        $data = $request->validate(['start' => ['required', 'date'], 'end' => ['required', 'date', 'after:start']]);
        $start = CarbonImmutable::parse($data['start']);
        $end = CarbonImmutable::parse($data['end']);
        abort_if($start->diffInDays($end) > 63, 422, 'Choose a calendar range of 63 days or less.');
        try {
            return response()->json($calendar->events($request->user(), $start, $end))->header('Cache-Control', 'private, no-store');
        } catch (GoogleCalendarException $e) {
            return response()->json(['message' => $e->getMessage()], 503)->header('Cache-Control', 'private, no-store');
        } catch (\Throwable $e) {
            Log::warning('Google Calendar events failed.', ['exception_type' => $e::class]);
            return response()->json(['message' => 'Google Calendar could not be loaded. Try reconnecting.'], 503)->header('Cache-Control', 'private, no-store');
        }
    }

    public function disconnect(Request $request, GoogleCalendarService $calendar)
    {
        $this->authorizeCalendar($request);
        try { $calendar->disconnect($request->user()->id); }
        catch (GoogleCalendarException $e) { return $this->failure($e->getMessage()); }
        $request->session()->forget('google_calendar_oauth');
        return $this->schedulePage()->with('status', 'Google Calendar disconnected from ATS.');
    }

    private function authorizeCalendar(Request $request): void
    {
        abort_unless($request->user()?->cans('view_schedule'), 403);
        abort_unless(Schema::hasTable('google_calendar_connections'), 503, 'Google Calendar needs a database update. Contact the platform administrator.');
    }

    private function tenantId(): ?int
    {
        return config('saas.enabled') ? app(TenantContext::class)->current()?->id : null;
    }

    private function schedulePage() { return redirect(tenant_route('admin.interview-schedule.index')); }
    private function failure(string $message) { return $this->schedulePage()->withErrors(['google_calendar' => $message]); }
}
