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
    <style>
        #client-signing-dialog {
            width: min(750px, calc(100% - 24px));
            max-height: calc(100% - 24px);
            margin: auto;
            padding: 0;
            border: 1px solid #dbe4ef;
            border-radius: 22px;
            background: #fff;
            color: #162541;
            box-shadow: 0 24px 80px #0f172a40;
        }
        #client-signing-dialog::backdrop { background: #16254199; }
        .client-signing-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 20px clamp(16px, 4vw, 28px);
            border-bottom: 1px solid #dbe4ef;
        }
        .client-signing-close {
            width: 40px;
            height: 40px;
            border: 1px solid #dbe4ef;
            border-radius: 10px;
            background: #f5f7fa;
            color: #64748b;
            font-size: 24px;
            line-height: 1;
        }
        .client-signing-body { padding: 24px clamp(16px, 4vw, 28px); }
        .client-signing-field {
            display: block;
            width: 100%;
            margin-top: 6px;
            border: 1px solid #dbe4ef;
            border-radius: 10px;
            padding: 11px 14px;
        }
        .client-signing-field:focus-visible { outline: 2px solid #2563eb; outline-offset: 1px; }
        textarea.client-signing-field { min-height: 150px; resize: vertical; }
        .client-signing-upload { background: #f5f7fa; }
        .client-signing-upload input { width: 100%; }
    </style>
    <dialog id="client-signing-dialog" aria-labelledby="client-signing-title">
        <header class="client-signing-header">
            <h2 id="client-signing-title" class="text-xl font-semibold">Send document to client</h2>
            <button type="button" class="client-signing-close" aria-label="Close" onclick="this.closest('dialog').close()">&times;</button>
        </header>
        <div class="client-signing-body">
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
            <label class="block font-medium">Client email
                <input class="client-signing-field" type="email" name="client_email" placeholder="client@company.com" value="{{ old('client_email') }}" maxlength="254" required autofocus>
            </label>
            <label class="block font-medium">Subject (optional)
                <input class="client-signing-field" name="client_subject" placeholder="Document for signature" value="{{ old('client_subject') }}" maxlength="150">
            </label>
            <label class="block font-medium">Email body (optional)
                <textarea class="client-signing-field" name="client_message" rows="5" maxlength="1000" placeholder="Write your message to the client…">{{ old('client_message') }}</textarea>
            </label>
            <label class="block font-medium">PDF attachment (up to 10 MB)
                <input class="client-signing-field client-signing-upload" type="file" name="pdf" accept=".pdf,application/pdf" required>
            </label>
            <p class="text-sm text-slate-600">Your subject names the Zoho signing request. Add signing fields in the editor, then click Send there to email the client.</p>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-outline-primary" onclick="this.closest('dialog').close()">Cancel</button>
                <button type="submit" class="btn btn-primary">Open Zoho editor</button>
            </div>
        </form>
        </div>
    </dialog>
    @if($errors->clientSigning->any())
        <script>document.getElementById('client-signing-dialog').showModal();</script>
    @endif
@endif
