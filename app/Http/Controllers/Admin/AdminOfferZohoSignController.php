<?php

namespace App\Http\Controllers\Admin;

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
            $this->offers = Onboard::with('applications.job')
                ->where(fn ($query) => $query->where('hired_status', 'offered')->orWhereNotNull('zoho_sign_request_id'))
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

    public function configure(Request $request, int $id, ZohoSign $zoho)
    {
        abort_unless($this->user->cans('view_job_applications') && $this->user->cans('add_job_applications') && $zoho->enabled(), 403);
        $selected = $request->validate(['file_id' => 'nullable|integer'])['file_id'] ?? null;

        return DB::transaction(function () use ($id, $selected) {
            $offer = Onboard::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_if($offer->hired_status !== 'offered' || $offer->zoho_sign_request_id, 409);

            if ($selected !== null) {
                $file = $offer->files()->findOrFail($selected);
                $path = 'onboard-files/'.$file->hash_name;
                if (strtolower($file->ext) !== 'pdf' || ! Storage::exists($path)) {
                    return back()->withErrors(['zoho_sign' => 'Select an available offer PDF.']);
                }
                try {
                    $text = (new Parser)->parseContent(Storage::get($path))->getText();
                } catch (\Exception $exception) {
                    report($exception);
                    return back()->withErrors(['zoho_sign' => 'The offer PDF could not be read.']);
                }
                if (! str_contains($text, '{{Signature}}') && ! str_contains($text, '{{S}}')) {
                    return back()->withErrors(['zoho_sign' => 'Add the Zoho Sign signature tag to the PDF before selecting it.']);
                }
            }

            $offer->zoho_sign_file_id = $selected;
            $offer->save();

            return back()->with('zoho_sign_message', 'Offer signing settings saved.');
        });
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
