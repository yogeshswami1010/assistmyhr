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
    @error('pdf')
        <p class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-red-700" role="alert">{{ $message }}</p>
    @enderror

    @if($application->status?->isHired() && $user->cans('add_job_applications'))
        <form class="mb-5 max-w-2xl rounded-xl border border-gray-200 bg-white p-5" method="POST"
              enctype="multipart/form-data" action="{{ tenant_route('admin.document-sign.store') }}">
            @csrf
            <input type="hidden" name="candidate_id" value="{{ $application->id }}">
            <label class="mb-2 block font-semibold" for="signing-pdf">Upload PDF</label>
            <p class="mb-3 text-sm text-gray-600">In the Zoho editor, place a signature, signing date, or other fields on the PDF. Click Send there to email the signing link to the applicant.</p>
            <input class="form-control mb-3" type="file" id="signing-pdf" name="pdf" accept=".pdf,application/pdf" required>
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
