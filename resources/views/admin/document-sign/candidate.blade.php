@extends('layouts.app')

@section('page-subtitle')
    {{ $application->full_name }} · {{ $application->email }}
@endsection

@section('content')
    <div class="mb-4 flex flex-wrap gap-4">
        <a class="font-semibold text-blue-700 hover:underline" href="{{ tenant_route('admin.document-sign.index') }}">All applicants</a>
        @if($application->onboard)
            <a class="font-semibold text-blue-700 hover:underline" href="{{ tenant_route('admin.document-sign.show', ['id' => $application->onboard->id]) }}">View existing offer</a>
        @endif
    </div>

    @if(session('zoho_sign_message'))
        <p class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-3" role="status">{{ session('zoho_sign_message') }}</p>
    @endif
    @if($errors->any())
        <p class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-red-700" role="alert">{{ $errors->first() }}</p>
    @endif

    @if($application->status?->isHired() && $user->cans('add_job_applications'))
        <form id="signing-upload-form" class="mb-5 max-w-2xl rounded-xl border border-gray-200 bg-white p-5" method="POST"
              enctype="multipart/form-data" action="{{ tenant_route('admin.document-sign.store') }}">
            @csrf
            <input type="hidden" name="candidate_id" value="{{ $application->id }}">
            <label class="mb-2 block font-semibold" for="signing-pdf">Upload PDF</label>
            <p class="mb-3 text-sm text-gray-600">In the Zoho editor, place a signature, signing date, or other fields on the PDF. Click Send there to email the signing link to the applicant.</p>
            <input class="form-control mb-3" type="file" id="signing-pdf" name="pdf" accept=".pdf,application/pdf" required>
            <p id="signing-upload-error" class="mb-3 text-red-700" role="alert" hidden></p>
            <button type="submit" class="btn btn-primary">Upload PDF</button>
        </form>
    @endif

    @if($documents->isNotEmpty())
        <div class="jc-table-card overflow-x-auto">
            <table class="jc-cat-table w-full">
                <thead>
                    <tr>
                        <th scope="col">Document</th>
                        <th scope="col">Recipient</th>
                        <th scope="col">Status</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documents as $document)
                        <tr>
                            <td>{{ $document->filename }}</td>
                            <td>{{ $document->recipient_email }}</td>
                            <td>@include('admin.document-sign.status', ['offer' => $document])</td>
                            <td>
                                <div class="flex flex-wrap items-center gap-3">
                                    @if($document->zoho_sign_status === 'completed')
                                        <a class="font-semibold text-blue-700 hover:underline" href="{{ tenant_route('admin.document-sign.documents.pdf', ['id' => $document->id]) }}">Download signed PDF</a>
                                    @elseif($user->cans('add_job_applications'))
                                        @if($document->zoho_sign_status === 'draft' && $application->status?->isHired())
                                            <form method="POST" target="_blank" rel="noopener" action="{{ tenant_route('admin.document-sign.documents.editor', ['id' => $document->id]) }}">
                                                @csrf
                                                <button class="btn btn-primary" type="submit">Open Zoho editor</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ tenant_route('admin.document-sign.documents.sync', ['id' => $document->id]) }}">
                                            @csrf
                                            <button class="btn btn-outline-primary" type="submit">Refresh status</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection

@push('footer-script')
<script>
    (() => {
        const form = document.getElementById('signing-upload-form');
        if (!form) {
            return;
        }

        const button = form.querySelector('button[type="submit"]');
        const error = document.getElementById('signing-upload-error');
        const maxBytes = {{ min(10 * 1024 * 1024, \Illuminate\Http\UploadedFile::getMaxFilesize()) }};
        const showError = message => {
            error.textContent = message;
            error.hidden = false;
        };

        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (button.disabled) {
                return;
            }

            error.hidden = true;
            const file = form.elements.pdf.files[0];
            if (file && file.size > maxBytes) {
                showError('This PDF exceeds the server upload limit. Try a smaller PDF.');
                return;
            }

            button.disabled = true;
            button.textContent = 'Uploading PDF…';
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body: new FormData(form),
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
                button.disabled = false;
                button.textContent = 'Upload PDF';
            }
        });
    })();
</script>
@endpush
