@extends('layouts.app')

@php($maxUploadBytes = min(10 * 1024 * 1024, \Illuminate\Http\UploadedFile::getMaxFilesize()))

@section('page-subtitle')
    {{ $application->full_name }} · {{ $application->email }}
@endsection

@push('head-script')
<style>
    .sign-workspace { max-width: 1200px; margin-inline: auto; color: #192538; }
    .sign-workspace nav { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 12px; margin-bottom: 20px; }
    .sign-workspace nav a { color: #315078; font-size: .875rem; font-weight: 600; }
    .sign-workspace nav i, .sign-workspace .btn i { margin-right: .4em; }
    .sign-layout { display: grid; grid-template-columns: minmax(0, 1fr); gap: 20px; align-items: start; }
    .sign-panel { min-width: 0; border: 1px solid #dce4ee; border-radius: 16px; background: #fff; box-shadow: 0 2px 5px #16243b08; }
    .sign-panel-heading { padding: 20px; }
    .sign-panel-heading h2 { margin: 0; font-size: 1.05rem; font-weight: 700; }
    .sign-panel-heading p { margin: 6px 0 0; color: #52637a; font-size: .875rem; line-height: 1.5; }
    .sign-upload-body { padding: 0 20px 20px; }
    .sign-picker { display: grid; justify-items: center; gap: 6px; padding: 18px 12px; border: 1px dashed #a8bde0; border-radius: 12px; background: #f5f8ff; text-align: center; }
    .sign-picker i { color: #275bc6; font-size: 1.3rem; }
    .sign-picker { position: relative; }
    .sign-picker label { margin: 4px 0 0; padding: 8px 14px; border: 1px solid #a8bde0; border-radius: 8px; background: #fff; color: #234b99; font-size: .875rem; font-weight: 700; cursor: pointer; }
    .sign-picker input:focus-visible + label { outline: 2px solid #275bc6; outline-offset: 3px; }
    .sign-picker input:disabled + label { opacity: .65; cursor: wait; }
    .sign-picker small { color: #52637a; }
    .sign-picker input { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); white-space: nowrap; }
    .sign-upload-progress { margin: 12px 0 0; color: #52637a; font-size: .875rem; overflow-wrap: anywhere; }
    .sign-panel .btn { border-radius: 8px; font-size: .85rem; font-weight: 600; }
    .sign-documents { margin: 0; padding: 0; list-style: none; }
    .sign-document { padding: 16px 20px; border-top: 1px solid #e4eaf2; }
    .sign-document-header { display: flex; align-items: flex-start; gap: 12px; }
    .sign-file-icon { display: grid; flex: 0 0 36px; height: 36px; place-items: center; border-radius: 9px; background: #edf3ff; color: #275bc6; }
    .sign-file-details { flex: 1; min-width: 0; overflow-wrap: anywhere; }
    .sign-file-details h3 { margin: 0; font-size: .9rem; font-weight: 700; }
    .sign-file-details p { margin: 3px 0 0; color: #52637a; font-size: .8rem; }
    .sign-status { flex-shrink: 0; max-width: 45%; padding: 4px 9px; border-radius: 999px; background: #edf1f6; color: #42536b; font-size: .75rem; font-weight: 700; text-align: center; }
    .sign-status[data-status="inprogress"] { background: #e6eeff; color: #204eaa; }
    .sign-status[data-status="completed"] { background: #e4f5ec; color: #14613a; }
    .sign-status[data-status="declined"], .sign-status[data-status="rejected"] { background: #fcebec; color: #982d37; }
    .sign-document-actions { display: flex; flex-wrap: wrap; gap: 8px; margin: 12px 0 0 48px; }
    .sign-document-actions form { margin: 0; }
    .sign-empty { padding: 20px; border-top: 1px solid #e4eaf2; color: #52637a; font-size: .875rem; }
    .sign-upload-error { margin: 12px 0 0; color: #982d37; font-size: .875rem; }
    @media (min-width: 900px) {
        .sign-layout:has(.sign-upload) { grid-template-columns: minmax(270px, 1fr) minmax(0, 1.8fr); }
    }
    @media (max-width: 480px) {
        .sign-document-header { flex-wrap: wrap; }
        .sign-document-actions { margin-left: 0; }
    }
</style>
@endpush

@section('content')
    <div class="sign-workspace">
        <nav aria-label="Document Sign navigation">
            <a href="{{ tenant_route('admin.document-sign.index') }}"><i class="fa fa-arrow-left" aria-hidden="true"></i>All applicants</a>
            @if($application->onboard)
                <a class="font-semibold text-blue-700 hover:underline" href="{{ tenant_route('admin.document-sign.show', ['id' => $application->onboard->id]) }}">View existing offer</a>
            @endif
        </nav>

        @if(session('zoho_sign_message'))
            <p class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-3" role="status">{{ session('zoho_sign_message') }}</p>
        @endif
        @if($errors->any())
            <p class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-red-700" role="alert">{{ $errors->first() }}</p>
        @endif

        <div class="sign-layout">
            @if($application->status?->isHired() && $user->cans('add_job_applications'))
                <form id="signing-upload-form" class="sign-panel sign-upload" method="POST"
                      enctype="multipart/form-data" action="{{ tenant_route('admin.document-sign.store') }}">
                    @csrf
                    <input type="hidden" name="candidate_id" value="{{ $application->id }}">
                    <div class="sign-panel-heading">
                        <h2>Upload a document</h2>
                        <p>Add signature fields and send the signing link from the Zoho editor after uploading.</p>
                    </div>
                    <div class="sign-upload-body">
                        <div class="sign-picker">
                            <i class="fa fa-cloud-upload" aria-hidden="true"></i>
                            <input type="file" id="signing-pdf" name="pdf" accept=".pdf,application/pdf" aria-describedby="signing-pdf-limit" required>
                            <label for="signing-pdf">Choose PDF to upload</label>
                            <small id="signing-pdf-limit">Up to {{ floor($maxUploadBytes / 104857.6) / 10 }} MB</small>
                        </div>
                        <p id="signing-upload-progress" class="sign-upload-progress" role="status" hidden></p>
                        <p id="signing-upload-error" class="sign-upload-error" role="alert" hidden></p>
                        <noscript><button type="submit" class="btn btn-primary mt-3">Upload PDF</button></noscript>
                    </div>
                </form>
            @endif

            <section class="sign-panel" aria-labelledby="sign-documents-title">
                <div class="sign-panel-heading">
                    <h2 id="sign-documents-title">Documents</h2>
                </div>
                @if($documents->isNotEmpty())
                    <ul class="sign-documents">
                        @foreach($documents as $document)
                            <li class="sign-document">
                                <div class="sign-document-header">
                                    <span class="sign-file-icon" aria-hidden="true"><i class="fa fa-file-pdf-o"></i></span>
                                    <div class="sign-file-details">
                                        <h3>{{ $document->filename }}</h3>
                                        <p>{{ $document->recipient_email }}</p>
                                    </div>
                                    <span class="sign-status" data-status="{{ $document->zoho_sign_status }}">@include('admin.document-sign.status', ['offer' => $document])</span>
                                </div>
                                @if($document->zoho_sign_status === 'completed' || $user->cans('add_job_applications'))
                                    <div class="sign-document-actions">
                                        @if($document->zoho_sign_status === 'completed')
                                            <a class="btn btn-outline-primary" href="{{ tenant_route('admin.document-sign.documents.pdf', ['id' => $document->id]) }}"><i class="fa fa-download" aria-hidden="true"></i>Download signed PDF</a>
                                        @else
                                            @if($document->zoho_sign_status === 'draft' && $application->status?->isHired())
                                                <form method="POST" target="_blank" rel="noopener" action="{{ tenant_route('admin.document-sign.documents.editor', ['id' => $document->id]) }}">
                                                    @csrf
                                                    <button class="btn btn-primary" type="submit"><i class="fa fa-external-link" aria-hidden="true"></i>Open Zoho editor</button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ tenant_route('admin.document-sign.documents.sync', ['id' => $document->id]) }}">
                                                @csrf
                                                <button class="btn btn-outline-primary" type="submit">Refresh status</button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="sign-empty mb-0">No documents uploaded yet.</p>
                @endif
            </section>
        </div>
    </div>
@endsection

@push('footer-script')
<script>
    (() => {
        const form = document.getElementById('signing-upload-form');
        if (!form) {
            return;
        }

        const picker = document.getElementById('signing-pdf');
        const progress = document.getElementById('signing-upload-progress');
        const error = document.getElementById('signing-upload-error');
        const maxBytes = {{ $maxUploadBytes }};
        const showError = message => {
            error.textContent = message;
            error.hidden = false;
        };

        picker.addEventListener('change', async () => {
            const file = picker.files[0];
            if (!file || picker.disabled) {
                return;
            }

            error.hidden = true;
            if (file.size > maxBytes) {
                picker.value = '';
                showError('This PDF exceeds the server upload limit. Try a smaller PDF.');
                return;
            }

            const body = new FormData(form);
            picker.disabled = true;
            progress.textContent = 'Uploading ' + file.name + '…';
            progress.hidden = false;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body,
                });
                const data = response.headers.get('content-type')?.includes('application/json')
                    ? await response.json()
                    : null;
                if (response.status === 201 && data?.redirect_url) {
                    window.location.assign(data.redirect_url);
                    return;
                }

                const messages = {
                    401: 'Your session expired. Reload the page and sign in again.',
                    403: 'You do not have access to upload this document, or the applicant is no longer Hired.',
                    413: 'The server rejected the PDF because it is too large. Try a smaller PDF.',
                    419: 'Your session expired. Reload the page before uploading again.',
                };
                const validationError = Object.values(data?.errors ?? {})[0]?.[0];
                showError(validationError || messages[response.status] || (response.redirected
                    ? 'The upload was redirected. Reload the page and check that you are signed in.'
                    : 'The server could not confirm the upload (HTTP ' + response.status + '). Check the document list before trying again.'));
            } catch {
                showError('The connection was interrupted. Reload and check the document list before trying again.');
            } finally {
                picker.value = '';
                picker.disabled = false;
                progress.hidden = true;
            }
        });
    })();
</script>
@endpush
