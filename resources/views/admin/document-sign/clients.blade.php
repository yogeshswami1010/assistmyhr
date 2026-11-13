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
            width: min(560px, calc(100% - 24px));
            max-height: calc(100% - 24px);
            margin: auto;
            padding: 0;
            border: 1px solid #dbe4ef;
            border-radius: 22px;
            background: #fff;
            color: #162541;
            font-size: 13px;
            box-shadow: 0 24px 80px #0f172a40;
        }
        #client-signing-dialog::backdrop { background: #16254199; }
        .client-signing-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 20px;
            border-bottom: 1px solid #dbe4ef;
        }
        .client-signing-close {
            width: 30px;
            height: 30px;
            border: 1px solid #dbe4ef;
            border-radius: 10px;
            background: #f5f7fa;
            color: #64748b;
            font-size: 19px;
            line-height: 1;
        }
        .client-signing-body { padding: 16px 20px; }
        #client-signing-title { font-size: 17px; line-height: 1.35; }
        #client-signing-dialog .client-signing-help { font-size: 12px; line-height: 1.45; }
        #client-signing-dialog .client-signing-actions .btn {
            min-height: 32px;
            padding: 6px 12px;
            font-size: 12px;
            line-height: 1.5;
        }
        .client-signing-field {
            display: block;
            width: 100%;
            margin-top: 6px;
            border: 1px solid #dbe4ef;
            border-radius: 10px;
            padding: 8px 12px;
            font-size: inherit;
        }
        .client-signing-field:focus-visible { outline: 2px solid #2563eb; outline-offset: 1px; }
        #client-signing-dialog .ja-review-editor-wrap { margin-top: 6px; border: 1px solid #cbd5e1; border-radius: 9px; overflow: hidden; }
        #client-signing-dialog .ja-review-format { display: flex; flex-wrap: wrap; gap: 4px; padding: 6px; background: #f5f7fa; border-bottom: 1px solid #dbe4ef; }
        #client-signing-dialog .ja-review-format > * { border: 1px solid #dbe4ef; border-radius: 4px; padding: 3px 6px; background: white; color: #475569; font: 11px Arial, sans-serif; }
        #client-signing-dialog .ja-review-editor { min-height: 88px; max-height: 150px; overflow: auto; padding: 10px 12px; font: 13px/1.5 Arial, sans-serif; white-space: pre-wrap; overflow-wrap: anywhere; }
        #client-signing-dialog .ja-review-editor:focus { outline: 2px solid #93c5fd; outline-offset: -2px; }
        #client-signing-dialog .ja-review-editor:empty::before { content: attr(data-placeholder); color: #94a3b8; pointer-events: none; }
        #client-signing-dialog .ja-review-editor ul { list-style: disc; padding-left: 22px; }
        #client-signing-dialog .ja-review-editor ol { list-style: decimal; padding-left: 22px; }
        .client-signing-upload { background: #f5f7fa; }
        .client-signing-upload input { width: 100%; }
    </style>
    <dialog id="client-signing-dialog" aria-labelledby="client-signing-title">
        <header class="client-signing-header">
            <h2 id="client-signing-title" class="font-semibold">Send document to client</h2>
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
              class="space-y-3">
            @csrf
            <label class="block font-medium">Client email
                <input class="client-signing-field" type="email" name="client_email" placeholder="client@company.com" value="{{ old('client_email') }}" maxlength="254" required autofocus>
            </label>
            <label class="block font-medium">Subject (optional)
                <input class="client-signing-field" name="client_subject" placeholder="Document for signature" value="{{ old('client_subject') }}" maxlength="150">
            </label>
            <div>
                <div class="font-medium">Email body (optional)</div>
                @include('admin.job-applications.partials.client-review-editor', ['editorId' => 'client-signing-message'])
                <textarea name="client_message" hidden>{{ old('client_message') }}</textarea>
                <p id="client-signing-message-error" class="text-sm text-red-700" role="alert" hidden></p>
            </div>
            <label class="block font-medium">PDF attachment (up to 10 MB)
                <input class="client-signing-field client-signing-upload" type="file" name="pdf" accept=".pdf,application/pdf" required>
            </label>
            <p class="client-signing-help text-slate-600">Zoho receives the message as text. Add signing fields in Zoho, then click Send there.</p>
            <div class="client-signing-actions flex justify-end gap-2">
                <button type="button" class="btn btn-outline-primary" onclick="this.closest('dialog').close()">Cancel</button>
                <button type="submit" class="btn btn-primary">Open Zoho editor</button>
            </div>
        </form>
        </div>
    </dialog>
    <script>
        (() => {
            const dialog = document.getElementById('client-signing-dialog');
            const form = dialog.querySelector('form');
            const editor = document.getElementById('client-signing-message');
            const message = form.elements.client_message;
            const error = document.getElementById('client-signing-message-error');
            let selection;
            editor.textContent = message.value;

            document.addEventListener('selectionchange', () => {
                const current = window.getSelection();
                if (current.rangeCount && editor.contains(current.anchorNode) && editor.contains(current.focusNode)) {
                    selection = current.getRangeAt(0).cloneRange();
                }
            });

            dialog.querySelectorAll('[data-review-command]').forEach(control => {
                const isButton = control.tagName === 'BUTTON';
                if (isButton) control.addEventListener('mousedown', event => event.preventDefault());
                control.addEventListener(isButton ? 'click' : 'change', () => {
                    editor.focus();
                    if (selection && editor.contains(selection.commonAncestorContainer)) {
                        const current = window.getSelection();
                        current.removeAllRanges();
                        current.addRange(selection);
                    }
                    document.execCommand(control.dataset.reviewCommand, false, isButton ? null : control.value);
                });
            });

            editor.addEventListener('paste', event => {
                event.preventDefault();
                document.execCommand('insertText', false, event.clipboardData.getData('text/plain'));
            });

            form.addEventListener('submit', event => {
                const copy = editor.cloneNode(true);
                copy.removeAttribute('id');
                copy.style.cssText = 'position:absolute;visibility:hidden;';
                copy.querySelectorAll('li').forEach(item => {
                    const list = item.parentElement;
                    const prefix = list.tagName === 'OL' ? (Array.from(list.children).indexOf(item) + 1) + '. ' : '• ';
                    item.prepend(prefix);
                });
                editor.after(copy);
                // innerText preserves the editor's visual line breaks.
                copy.style.visibility = 'visible';
                copy.style.opacity = '0';
                message.value = copy.innerText.trim();
                copy.remove();
                error.hidden = message.value.length <= 1000;
                if (!error.hidden) {
                    event.preventDefault();
                    error.textContent = 'Use 1,000 characters or fewer in the email body.';
                    editor.focus();
                    return;
                }
                form.querySelector('button[type=submit]').disabled = true;
            });
        })();
    </script>
    @if($errors->clientSigning->any())
        <script>document.getElementById('client-signing-dialog').showModal();</script>
    @endif
@endif
