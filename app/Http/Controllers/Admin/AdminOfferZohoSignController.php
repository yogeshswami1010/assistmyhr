<?php

namespace App\Http\Controllers\Admin;

use App\Helper\Files;
use App\JobApplication;
use App\Notifications\JobOffer;
use App\Onboard;
use App\Services\ZohoSign;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Smalot\PdfParser\Parser;

class AdminOfferZohoSignController extends AdminBaseController
{
    public function index(ZohoSign $zoho)
    {
        abort_unless($this->user->cans('view_job_applications'), 403);

        $this->pageTitle = 'Document Sign';
        $this->zohoEnabled = $zoho->enabled();
        if ($this->zohoEnabled) {
            $this->applications = JobApplication::with(['job', 'onboard'])
                ->where(fn ($query) => $query
                    ->whereHas('status', fn ($status) => $status->where('status', 'hired'))
                    ->orWhereHas('onboard', fn ($offer) => $offer
                        ->where('hired_status', 'offered')->orWhereNotNull('zoho_sign_request_id')))
                ->latest('id')
                ->paginate(25)
                ->appends(tenant_parameters());
        }

        return view('admin.document-sign.index', $this->data);
    }

    public function show(int $id, ZohoSign $zoho)
    {
        abort_unless($this->user->cans('view_job_applications') && $zoho->enabled(), 403);

        $this->pageTitle = 'Document Sign';
        $this->onboard = Onboard::with(['applications.job', 'files'])->findOrFail($id);

        return view('admin.document-sign.show', $this->data);
    }

    public function send(int $id, ZohoSign $zoho)
    {
        abort_unless($this->user->cans('view_job_applications') && $this->user->cans('add_job_applications') && $zoho->enabled(), 403);

        return DB::transaction(function () use ($id) {
            $offer = Onboard::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_if($offer->hired_status !== 'offered' || $offer->zoho_sign_request_id, 409);
            if (! $offer->files()->whereKey($offer->zoho_sign_file_id)->exists()) {
                return back()->withErrors(['zoho_sign' => 'Select and save an offer PDF before sending the offer.']);
            }

            $application = $offer->applications()->with('job')->firstOrFail();
            $application->setRelation('onboard', $offer);
            try {
                $application->notify(new JobOffer($application));
            } catch (\Exception $exception) {
                report($exception);
                return back()->withErrors(['zoho_sign' => 'The offer email could not be sent. Check the workspace email settings and try again.']);
            }

            return back()->with('zoho_sign_message', 'Offer link emailed to the candidate.');
        });
    }

    public function configure(Request $request, int $id, ZohoSign $zoho)
    {
        abort_unless($this->user->cans('view_job_applications') && $this->user->cans('add_job_applications') && $zoho->enabled(), 403);
        $selected = $request->validate([
            'file_id' => 'nullable|integer',
            'offer_pdf' => 'nullable|file|mimes:pdf|extensions:pdf|max:10240',
        ])['file_id'] ?? null;
        $upload = $request->file('offer_pdf');

        return DB::transaction(function () use ($id, $selected, $upload) {
            $offer = Onboard::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_if($offer->hired_status !== 'offered' || $offer->zoho_sign_request_id, 409);

            if ($upload) {
                $tagError = $this->signatureTagError($upload->getContent());
                if ($tagError) {
                    return back()->withErrors(['zoho_sign' => $tagError]);
                }
                $file = $offer->files()->create([
                    'name' => $upload->getClientOriginalName(),
                    'file' => $upload->getClientOriginalName(),
                    'hash_name' => Files::upload($upload, 'onboard-files', null, null, false),
                ]);
                $selected = $file->id;
            } elseif ($selected !== null) {
                $file = $offer->files()->findOrFail($selected);
                $path = 'onboard-files/'.$file->hash_name;
                if (strtolower($file->ext) !== 'pdf' || ! Storage::exists($path)) {
                    return back()->withErrors(['zoho_sign' => 'Select an available offer PDF.']);
                }
                $tagError = $this->signatureTagError(Storage::get($path));
                if ($tagError) {
                    return back()->withErrors(['zoho_sign' => $tagError]);
                }
            }

            $offer->zoho_sign_file_id = $selected;
            $offer->save();

            return back()->with('zoho_sign_message', 'Offer signing settings saved.');
        });
    }

    private function signatureTagError(?string $pdf): ?string
    {
        try {
            $text = (new Parser)->parseContent($pdf ?? '')->getText();
        } catch (\Exception $exception) {
            report($exception);
            return 'The offer PDF could not be read.';
        }

        return str_contains($text, '{{Signature}}') || str_contains($text, '{{S}}')
            ? null
            : 'Add the Zoho Sign signature tag to the PDF before selecting it.';
    }

    public function sync(int $id, ZohoSign $zoho)
    {
        abort_unless($this->user->cans('view_job_applications') && $this->user->cans('add_job_applications') && $zoho->enabled(), 403);
        $offer = Onboard::findOrFail($id);
        abort_unless($offer->zoho_sign_request_id, 404);

        try {
            $zoho->applyStatus($offer, $zoho->status($offer));
        } catch (RuntimeException | ConnectionException $exception) {
            report($exception);
            return back()->withErrors(['zoho_sign' => 'Zoho Sign status could not be refreshed. Try again shortly.']);
        }

        return back()->with('zoho_sign_message', 'Zoho Sign status refreshed.');
    }

    public function download(int $id, ZohoSign $zoho)
    {
        abort_unless($this->user->cans('view_job_applications') && $zoho->enabled(), 403);
        $offer = Onboard::findOrFail($id);
        abort_unless($offer->zoho_sign_request_id && $offer->zoho_sign_status === 'completed', 404);

        try {
            $pdf = $zoho->signedPdf($offer);
        } catch (RuntimeException | ConnectionException $exception) {
            report($exception);
            return back()->withErrors(['zoho_sign' => 'The signed offer is unavailable. Try again shortly.']);
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="signed-offer-'.$offer->id.'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
