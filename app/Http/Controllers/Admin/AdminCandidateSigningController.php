<?php

namespace App\Http\Controllers\Admin;

use App\CandidateSigningDocument;
use App\JobApplication;
use App\Services\ZohoSign;
use App\Services\ZohoSignException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AdminCandidateSigningController extends AdminBaseController
{
    public function show(Request $request, ZohoSign $zoho)
    {
        $this->authorizeSigning($zoho);
        $data = $request->validate(['id' => 'required|integer']);
        $this->application = JobApplication::with(['status', 'onboard'])->findOrFail($data['id']);
        abort_unless($this->application->status?->isHired() || $this->application->signingDocuments()->exists(), 404);
        $this->documents = $this->application->signingDocuments()->latest('id')->get();
        $this->pageTitle = 'Document Sign';

        return view('admin.document-sign.candidate', $this->data);
    }

    public function store(Request $request, ZohoSign $zoho)
    {
        return $this->storeDraft($request, $zoho, false);
    }

    public function storeClient(Request $request, ZohoSign $zoho)
    {
        return $this->storeDraft($request, $zoho, true);
    }

    private function storeDraft(Request $request, ZohoSign $zoho, bool $client)
    {
        $this->authorizeSigning($zoho, true);
        $data = $request->validateWithBag($client ? 'clientSigning' : 'default', [
            ...($client ? [
                'client_email' => 'required|email:rfc|max:254',
                'client_name' => 'nullable|string|max:150',
                'client_subject' => 'nullable|string|max:150',
                'client_message' => 'nullable|string|max:1000',
            ] : ['candidate_id' => 'required|integer']),
            'pdf' => 'required|file|mimes:pdf|extensions:pdf|max:10240',
        ]);
        $application = $client ? null : JobApplication::with('status')->findOrFail($data['candidate_id']);
        abort_unless($client || $application->status?->isHired(), 403);
        $email = $client ? $data['client_email'] : $application->email;

        if (! $client && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['pdf' => 'Add a valid email address to the applicant before uploading a document.']);
        }

        $file = $data['pdf'];
        $basename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $basename = preg_replace('/[[:cntrl:]]/', '', $basename);
        $filename = Str::limit(trim($basename) ?: 'document', 180, '').'.pdf';
        try {
            $requestId = $client
                ? $zoho->createRecipientDraft(
                    $data['client_name'] ?? $email,
                    $email,
                    $file->getPathname(),
                    $filename,
                    $data['client_message'] ?? null,
                    $data['client_subject'] ?? null,
                )
                : $zoho->createCandidateDraft($application, $file->getPathname(), $filename);
        } catch (ZohoSignException $exception) {
            report($exception);
            throw ValidationException::withMessages(['pdf' => $exception->getMessage()])->errorBag($client ? 'clientSigning' : 'default');
        } catch (ConnectionException $exception) {
            report($exception);
            throw ValidationException::withMessages(['pdf' => 'The ATS server could not reach Zoho. Check outbound HTTPS connectivity and try again.'])->errorBag($client ? 'clientSigning' : 'default');
        } catch (RuntimeException $exception) {
            report($exception);
            throw ValidationException::withMessages(['pdf' => 'Zoho Sign could not create the draft. Check the connection and try again.'])->errorBag($client ? 'clientSigning' : 'default');
        }

        $attributes = [
            'filename' => $filename,
            'recipient_email' => $email,
            'zoho_sign_request_id' => $requestId,
        ];
        if ($client) {
            $document = CandidateSigningDocument::create($attributes);
            $request->session()->flash('zoho_sign_message', 'Client draft saved. You can reopen it from Client documents.');

            return $this->editor($document->id, $zoho);
        }
        $application->signingDocuments()->create($attributes);

        $url = tenant_route('admin.document-sign.prepare', ['id' => $application->id]);
        $request->session()->flash('zoho_sign_message', 'PDF uploaded. Open the Zoho editor to place fields and send it.');

        return $request->expectsJson()
            ? response()->json(['redirect_url' => $url], 201)
            : redirect($url);
    }

    public function editor(int $id, ZohoSign $zoho)
    {
        $this->authorizeSigning($zoho, true);
        $document = CandidateSigningDocument::with('application.status')->findOrFail($id);
        abort_unless($document->job_application_id === null || $document->application?->status?->isHired(), 403);

        try {
            $status = $zoho->status($document);
            if ($status !== 'draft') {
                $document->applyStatus($status);
                return back()->with('zoho_sign_message', 'Zoho Sign status refreshed.');
            }

            return redirect()->away($zoho->editorUrl($document), 303, [
                'Cache-Control' => 'no-store',
                'Referrer-Policy' => 'no-referrer',
            ]);
        } catch (ZohoSignException $exception) {
            report($exception);
            return back()->withErrors(['pdf' => $exception->getMessage()]);
        } catch (RuntimeException | ConnectionException $exception) {
            report($exception);
            return back()->withErrors(['pdf' => 'The Zoho editor could not be opened. Check that embedded sending is available for your Zoho Sign account and try again.']);
        }
    }

    public function sync(int $id, ZohoSign $zoho)
    {
        $this->authorizeSigning($zoho, true);
        $document = CandidateSigningDocument::findOrFail($id);
        try {
            $document->applyStatus($zoho->status($document));
        } catch (RuntimeException | ConnectionException $exception) {
            report($exception);
            return back()->withErrors(['pdf' => 'Zoho Sign status could not be refreshed. Try again shortly.']);
        }

        return back()->with('zoho_sign_message', 'Zoho Sign status refreshed.');
    }

    public function download(int $id, Request $request, ZohoSign $zoho)
    {
        $this->authorizeSigning($zoho);
        $document = CandidateSigningDocument::findOrFail($id);
        abort_unless($document->zoho_sign_status === 'completed', 404);

        try {
            $pdf = $zoho->signedPdf($document);
        } catch (RuntimeException | ConnectionException $exception) {
            report($exception);
            return back()->withErrors(['pdf' => 'The signed PDF could not be downloaded. Try again shortly.']);
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('inline') ? 'inline' : 'attachment').'; filename="signed-document-'.$document->id.'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function authorizeSigning(ZohoSign $zoho, bool $write = false): void
    {
        abort_unless($zoho->enabled()
            && $this->user->cans('view_job_applications')
            && (! $write || $this->user->cans('add_job_applications')), 403);
    }
}
