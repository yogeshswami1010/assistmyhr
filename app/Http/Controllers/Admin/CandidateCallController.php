<?php

namespace App\Http\Controllers\Admin;

use App\CandidateCall;
use App\JobApplication;
use App\Services\CandidateCallService;
use App\Services\TelnyxSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CandidateCallController extends AdminBaseController
{
    private function candidate(int $id, bool $write = false): JobApplication
    {
        abort_unless($this->user->cans('view_job_applications'), 403);
        if ($write) abort_unless($this->user->cans('edit_job_applications'), 403);
        return JobApplication::withTrashed()->whereNull('moved_to_trash_at')->findOrFail($id);
    }

    private function ownedCall(int $id, int $callId): CandidateCall
    {
        $this->candidate($id, true);
        return CandidateCall::where('job_application_id', $id)->where('user_id', $this->user->id)->findOrFail($callId);
    }

    public function index(int $id)
    {
        $this->application = $this->candidate($id);
        $this->pageTitle = 'Candidate calls';
        $this->canCall = $this->user->cans('edit_job_applications');
        $this->calls = CandidateCall::where('job_application_id', $id)->with('user:id,name')->latest('id')->paginate(20);
        return view('admin.job-applications.calls', $this->data);
    }

    public function start(int $id, CandidateCallService $service, TelnyxSmsService $phones)
    {
        $candidate = $this->candidate($id, true);
        try {
            $phone = $phones->normalizePhone((string) $candidate->phone);
            $session = $service->session();
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Calling service is unavailable. Please try again.'], 503);
        }
        $call = CandidateCall::create(['job_application_id' => $id, 'user_id' => $this->user->id, 'phone' => $phone]);
        return response()->json($session + ['id' => $call->id, 'phone' => $phone])->header('Cache-Control', 'no-store');
    }

    public function finish(Request $request, int $id, int $callId)
    {
        $call = $this->ownedCall($id, $callId);
        $data = $request->validate([
            'duration_seconds' => ['required', 'integer', 'min:0', 'max:14400'],
            'recording_consent' => ['sometimes', 'accepted'],
            'audio' => ['nullable', 'file', 'mimetypes:audio/webm,video/webm,audio/mp4,video/mp4,audio/ogg,application/ogg', 'max:24576'],
        ]);
        if ($request->hasFile('audio')) abort_unless($request->boolean('recording_consent'), 422, 'Recording consent is required.');
        DB::transaction(function () use ($call, $request, $data) {
            $locked = CandidateCall::whereKey($call->id)->lockForUpdate()->firstOrFail();
            // Retries must never duplicate or replace a previously accepted recording.
            if ($locked->status !== 'initiated') return;
            if (config('saas.enabled') && $request->hasFile('audio')) { app(\App\Saas\QuotaService::class)->assertUploadFits((int) $request->file('audio')->getSize()); }
            $path = $request->hasFile('audio') ? $request->file('audio')->store('calls', 'candidate_call_audio') : null;
            if ($request->hasFile('audio') && !$path) throw new \RuntimeException('Could not save recording.');
            $locked->update([
                'duration_seconds' => $data['duration_seconds'], 'audio_path' => $path,
                'recording_consent_at' => $path ? now() : null,
                'status' => $path ? 'pending' : 'not_recorded',
            ]);
        });
        return response()->json(['status' => $call->fresh()->status]);
    }

    public function process(int $id, int $callId, CandidateCallService $service)
    {
        $call = $this->ownedCall($id, $callId);
        if ($call->status === 'completed') return response()->json(['status' => 'completed']);
        abort_unless($call->audio_path, 422, 'No saved recording is available.');
        // Atomic lease allows retry after a terminated request without concurrent AI work.
        $claimed = CandidateCall::whereKey($call->id)->where(function ($query) {
            $query->whereIn('status', ['pending', 'failed'])
                ->orWhere(function ($query) { $query->where('status', 'processing')->where('updated_at', '<', now()->subMinutes(5)); });
        })->update(['status' => 'processing', 'updated_at' => now()]);
        if (!$claimed) return response()->json(['message' => 'Summary is already processing. Try again in a few minutes.'], 409);
        try {
            $service->process($call->fresh());
            return response()->json(['status' => 'completed']);
        } catch (\Throwable $e) {
            CandidateCall::whereKey($call->id)->update(['status' => 'failed']);
            Log::warning('Candidate call summary failed.', ['call_id' => $call->id, 'exception' => get_class($e)]);
            return response()->json(['message' => 'Recording saved, but the summary could not be generated. Retry from call history.'], 502);
        }
    }
}
