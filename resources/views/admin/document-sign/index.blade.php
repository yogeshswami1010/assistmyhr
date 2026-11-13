@extends('layouts.app')

@section('page-subtitle', 'Send PDFs to clients and applicants for signature through Zoho Sign.')

@section('content')
    @if(!$zohoEnabled)
        <div class="rounded-xl border border-gray-200 bg-white p-5" role="status">
            Zoho Sign is unavailable for this workspace. Ask your administrator to check the connection.
        </div>
    @else
        @include('admin.document-sign.clients')
        <h2 class="mb-3 mt-6 text-lg font-semibold">Applicant documents</h2>
        <div class="jc-table-card overflow-x-auto">
            <table class="jc-cat-table w-full">
                <thead>
                    <tr>
                        <th scope="col">Candidate</th>
                        <th scope="col">Job</th>
                        <th scope="col">Signing status</th>
                        <th scope="col"><span class="sr-only">Action</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $application)
                        @php($offer = $application->onboard)
                        <tr>
                            <td>{{ $application->full_name }}</td>
                            <td>{{ $application->job?->title ?? '—' }}</td>
                            <td>
                                @if($application->latestSigningDocument)
                                    @include('admin.document-sign.status', ['offer' => $application->latestSigningDocument])
                                @elseif($offer)
                                    @include('admin.document-sign.status', ['offer' => $offer])
                                @else
                                    Ready to upload
                                @endif
                            </td>
                            <td>
                                @if($application->latestSigningDocument || $application->status?->isHired())
                                    <a class="font-semibold text-blue-700 hover:underline" href="{{ tenant_route('admin.document-sign.prepare', ['id' => $application->id]) }}">
                                        {{ $application->latestSigningDocument ? 'View documents' : 'Upload PDF' }}<span class="sr-only"> for {{ $application->full_name }}</span>
                                    </a>
                                @elseif($offer)
                                    <a class="font-semibold text-blue-700 hover:underline" href="{{ tenant_route('admin.document-sign.show', ['id' => $offer->id]) }}">
                                        View signing details<span class="sr-only"> for {{ $application->full_name }}</span>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">Applicants appear here when their status is set to Hired. <a class="text-blue-700 underline" href="{{ tenant_route('admin.job-applications.table') }}">Go to Job Applications</a></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($applications->hasPages())
            <div class="mt-4">{{ $applications->links() }}</div>
        @endif
    @endif
@endsection
