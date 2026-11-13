<section aria-labelledby="client-documents-title">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <h2 id="client-documents-title" class="text-lg font-semibold">Client documents</h2>
        @if($user->cans('add_job_applications'))
            <button type="button" class="btn btn-primary" onclick="document.getElementById('client-signing-dialog').showModal()">
                Send to client
            </button>
        @endif
    </div>

    @if(session('zoho_sign_message'))
        <p class="mb-3 rounded-lg bg-green-50 p-3 text-green-800" role="status">{{ session('zoho_sign_message') }}</p>
    @endif
    @error('pdf')
        <p class="mb-3 rounded-lg bg-red-50 p-3 text-red-700" role="alert">{{ $message }}</p>
    @enderror

    <div class="jc-table-card overflow-x-auto">
        <table class="jc-cat-table w-full">
            <thead><tr>
                <th scope="col">Document</th>
                <th scope="col">Recipient</th>
                <th scope="col">Signing status</th>
                <th scope="col">Actions</th>
            </tr></thead>
            <tbody>
                @forelse($clientDocuments as $document)
                    <tr>
                        <td>{{ $document->filename }}</td>
                        <td>{{ $document->recipient_email }}</td>
                        <td>@include('admin.document-sign.status', ['offer' => $document])</td>
                        <td>
                            <div class="flex flex-wrap items-center gap-2">
                                @if($document->zoho_sign_status === 'completed')
                                    <a class="btn btn-outline-primary" target="_blank" rel="noopener" href="{{ tenant_route('admin.document-sign.documents.pdf', ['id' => $document->id, 'inline' => 1]) }}">View signed PDF</a>
                                    <a class="btn btn-outline-primary" href="{{ tenant_route('admin.document-sign.documents.pdf', ['id' => $document->id]) }}">Download</a>
                                @elseif($user->cans('add_job_applications'))
                                    @if($document->zoho_sign_status === 'draft')
                                        <form method="POST" action="{{ tenant_route('admin.document-sign.documents.editor', ['id' => $document->id]) }}">
                                            @csrf
                                            <button class="btn btn-primary">Open Zoho editor</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ tenant_route('admin.document-sign.documents.sync', ['id' => $document->id]) }}">
                                        @csrf
                                        <button class="btn btn-outline-primary">Refresh status</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">No client documents yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($clientDocuments->hasPages())
        <div class="mt-4">{{ $clientDocuments->links() }}</div>
    @endif
</section>

@if($user->cans('add_job_applications'))
    <dialog id="client-signing-dialog" aria-labelledby="client-signing-title"
            style="width:min(34rem, calc(100% - 2rem));max-height:calc(100% - 2rem);margin:auto;border:0;border-radius:16px;padding:24px;background:white;box-shadow:0 24px 80px #0f172a55">
        <h2 id="client-signing-title" class="text-xl font-semibold">Send to client</h2>
        <p class="mb-4 mt-2 text-sm text-slate-600">Add signing fields in the Zoho editor, then click Send there to email the client.</p>
        @if($errors->clientSigning->any())
            <ul class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700" role="alert">
                @foreach($errors->clientSigning->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
        <form method="POST" enctype="multipart/form-data" action="{{ tenant_route('admin.document-sign.clients.store') }}"
              class="space-y-4" onsubmit="this.querySelector('button[type=submit]').disabled = true">
            @csrf
            <label class="block">Client email
                <input class="mt-1 w-full rounded-lg border-gray-300" type="email" name="client_email" value="{{ old('client_email') }}" maxlength="254" required autofocus>
            </label>
            <label class="block">Client name (optional)
                <input class="mt-1 w-full rounded-lg border-gray-300" name="client_name" value="{{ old('client_name') }}" maxlength="150">
            </label>
            <label class="block">Message (optional)
                <textarea class="mt-1 w-full rounded-lg border-gray-300" name="client_message" rows="3" maxlength="1000">{{ old('client_message') }}</textarea>
            </label>
            <label class="block">PDF (up to 10 MB)
                <input class="mt-1 block w-full" type="file" name="pdf" accept=".pdf,application/pdf" required>
            </label>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-outline-primary" onclick="this.closest('dialog').close()">Cancel</button>
                <button type="submit" class="btn btn-primary">Open Zoho editor</button>
            </div>
        </form>
    </dialog>
    @if($errors->clientSigning->any())
        <script>document.getElementById('client-signing-dialog').showModal();</script>
    @endif
@endif
