@extends('layouts.app')

@section('page-subtitle', 'Send PDFs to clients and applicants for signature through Zoho Sign.')

@section('content')
    @if(!$zohoEnabled)
        <div class="rounded-xl border border-gray-200 bg-white p-5" role="status">
            Zoho Sign is unavailable for this workspace. Ask your administrator to check the connection.
        </div>
    @else
        @php($activeTab = request('tab') === 'applicants' && !$errors->clientSigning->any() ? 'applicants' : 'clients')
        <div class="sign-overview">
            <nav class="sign-overview-tabs" aria-label="Document categories">
                <a href="{{ tenant_route('admin.document-sign.index', ['tab' => 'clients']) }}" @if($activeTab === 'clients') aria-current="page" @endif>
                    <i class="fa fa-briefcase" aria-hidden="true"></i> Client Documents <span>{{ $clientDocuments->total() }}</span>
                </a>
                <a href="{{ tenant_route('admin.document-sign.index', ['tab' => 'applicants']) }}" @if($activeTab === 'applicants') aria-current="page" @endif>
                    <i class="fa fa-users" aria-hidden="true"></i> Applicant Documents <span>{{ $applications->total() }}</span>
                </a>
            </nav>
            @if($activeTab === 'clients')
                @include('admin.document-sign.clients')
            @else
                <section aria-labelledby="applicant-documents-title">
                    <header class="sign-overview-header">
                        <h2 id="applicant-documents-title">Applicant documents</h2>
                        <a class="sign-overview-action" href="{{ tenant_route('admin.job-applications.table') }}">Open Job Applications <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
                    </header>
                    @if(session('document_sign_candidate_removed'))
                        <p class="mx-5 my-3 rounded-lg bg-green-50 p-3 text-green-800" role="status">{{ session('document_sign_candidate_removed') }}</p>
                    @endif
                    @if($applications->isEmpty())
                        <div class="sign-overview-empty">
                            <i class="fa fa-file-text-o" aria-hidden="true"></i>
                            <h3>No applicants in Document Sign</h3>
                            <p>Open a candidate profile and select <strong>Add Sign Document</strong>.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="sign-overview-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Candidate</th>
                                        <th scope="col">Job</th>
                                        <th scope="col">Signing status</th>
                                        <th scope="col"><span class="sr-only">Actions</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($applications as $application)
                                        @php($offer = $application->onboard)
                                        <tr>
                                            <td>{{ $application->full_name }}</td>
                                            <td>{{ $application->job?->title ?? '—' }}</td>
                                            <td>
                                                @if($application->latestSigningDocument)
                                                    <span class="sign-overview-status" data-status="{{ $application->latestSigningDocument->zoho_sign_status }}">@include('admin.document-sign.status', ['offer' => $application->latestSigningDocument])</span>
                                                @elseif($offer?->zoho_sign_request_id)
                                                    <span class="sign-overview-status" data-status="{{ $offer->zoho_sign_status }}">@include('admin.document-sign.status', ['offer' => $offer])</span>
                                                @else
                                                    <span class="sign-overview-status">Ready to upload</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-2">
                                                    @if($application->latestSigningDocument || $application->document_sign_added_at)
                                                        <a class="sign-overview-action" href="{{ tenant_route('admin.document-sign.prepare', ['id' => $application->id]) }}">
                                                            {{ $application->latestSigningDocument || !$user->cans('add_job_applications') ? 'View documents' : 'Upload PDF' }}<span class="sr-only"> for {{ $application->full_name }}</span>
                                                        </a>
                                                    @elseif($offer)
                                                        <a class="sign-overview-action" href="{{ tenant_route('admin.document-sign.show', ['id' => $offer->id]) }}">
                                                            View signing details<span class="sr-only"> for {{ $application->full_name }}</span>
                                                        </a>
                                                    @endif
                                                    @if($user->cans('add_job_applications'))
                                                        <form method="POST" action="{{ tenant_route('admin.document-sign.candidates.remove', ['id' => $application->id]) }}"
                                                              class="m-0" data-confirm-sign-removal="{{ $application->full_name }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="sign-overview-action sign-overview-remove" disabled
                                                                    aria-label="Remove {{ $application->full_name }} from Document Sign" title="Remove from Document Sign">
                                                                <span aria-hidden="true">×</span>
                                                            </button>
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
                    @if($applications->hasPages())
                        <div class="sign-overview-pagination">{{ $applications->appends(['tab' => 'applicants'])->links() }}</div>
                    @endif
                </section>
            @endif
        </div>
    @endif
@endsection

@push('head-script')
    @include('admin.document-sign.overview-styles')
@endpush

@push('footer-script')
    <script>
        document.querySelectorAll('form[data-confirm-sign-removal]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                var question = 'Remove ' + form.dataset.confirmSignRemoval + ' from Document Sign?';

                if (typeof swal !== 'function') {
                    if (window.confirm(question)) form.submit();
                    return;
                }

                swal({
                    title: question,
                    text: 'Existing signing records will be kept.',
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, remove',
                    confirmButtonColor: '#b91c1c',
                    cancelButtonText: 'Cancel'
                }, function (confirmed) {
                    if (confirmed) form.submit();
                });
            });
            form.querySelector('button[type="submit"]').disabled = false;
        });
    </script>
@endpush
