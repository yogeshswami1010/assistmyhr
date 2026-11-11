@extends('layouts.app')

@section('page-subtitle')
    {{ $onboard->applications?->full_name }} · {{ $onboard->applications?->job?->title }}
@endsection

@section('content')
    <div class="mb-4 flex flex-wrap gap-4">
        <a class="font-semibold text-blue-700 hover:underline" href="{{ tenant_route('admin.document-sign.index') }}">All signing requests</a>
        <a class="font-semibold text-blue-700 hover:underline" href="{{ tenant_route('admin.job-onboard.show', ['job_onboard' => $onboard->id]) }}">View offer</a>
    </div>

    <div class="max-w-2xl rounded-xl border border-gray-200 bg-white p-5">
        <h2 class="mb-2 text-lg font-semibold">Zoho Sign</h2>
        <p class="mb-4">@include('admin.document-sign.status', ['offer' => $onboard])</p>

        @error('zoho_sign')
            <p class="mb-4 text-danger" role="alert">{{ $message }}</p>
        @enderror
        @error('file_id')
            <p class="mb-4 text-danger" role="alert">{{ $message }}</p>
        @enderror
        @error('offer_pdf')
            <p class="mb-4 text-danger" role="alert">{{ $message }}</p>
        @enderror
        @if(session('zoho_sign_message'))
            <p class="mb-4" role="status">{{ session('zoho_sign_message') }}</p>
        @endif

        @if($onboard->zoho_sign_status === 'completed')
            <a class="btn btn-primary" href="{{ tenant_route('admin.document-sign.pdf', ['id' => $onboard->id]) }}">Download signed PDF</a>
        @elseif($user->cans('add_job_applications') && $onboard->hired_status === 'offered')
            @if($onboard->zoho_sign_request_id)
                <form method="POST" action="{{ tenant_route('admin.document-sign.sync', ['id' => $onboard->id]) }}">
                    @csrf
                    <button class="btn btn-outline-primary" type="submit">Refresh status</button>
                </form>
            @else
                <form method="POST" enctype="multipart/form-data" action="{{ tenant_route('admin.document-sign.configure', ['id' => $onboard->id]) }}">
                    @csrf
                    <label for="zoho-sign-file" class="font-semibold">Offer PDF</label>
                    <p class="mb-2 text-sm text-gray-600">Upload a PDF with the <code>@{{Signature}}</code> tag where the candidate should sign. Zoho emails the signing link after the candidate submits the offer form.</p>
                    <select class="form-control mb-3" id="zoho-sign-file" name="file_id">
                        <option value="">Use the ATS signature pad</option>
                        @foreach($onboard->files->filter(fn ($file) => strtolower($file->ext) === 'pdf') as $file)
                            <option value="{{ $file->id }}" @selected($onboard->zoho_sign_file_id == $file->id)>{{ $file->name }}</option>
                        @endforeach
                    </select>
                    <label for="offer-pdf" class="font-semibold">Or upload an offer PDF</label>
                    <input class="form-control mb-3" id="offer-pdf" name="offer_pdf" type="file" accept=".pdf,application/pdf">
                    <button class="btn btn-outline-primary" type="submit">Save signing method</button>
                </form>
                @if($onboard->zoho_sign_file_id)
                    <form class="mt-4" method="POST" action="{{ tenant_route('admin.document-sign.send', ['id' => $onboard->id]) }}">
                        @csrf
                        <button class="btn btn-primary" type="submit">Send offer link</button>
                    </form>
                @endif
            @endif
        @endif
    </div>
@endsection
