@extends('layouts.app')

@section('page-subtitle', 'Manage offer letters signed through Zoho Sign.')

@section('content')
    @if(!$zohoEnabled)
        <div class="rounded-xl border border-gray-200 bg-white p-5" role="status">
            Zoho Sign is unavailable for this workspace. Ask your administrator to check the connection.
        </div>
    @else
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
                    @forelse($offers as $offer)
                        <tr>
                            <td>{{ $offer->applications?->full_name ?? '—' }}</td>
                            <td>{{ $offer->applications?->job?->title ?? '—' }}</td>
                            <td>@include('admin.document-sign.status', ['offer' => $offer])</td>
                            <td>
                                <a class="font-semibold text-blue-700 hover:underline"
                                   href="{{ tenant_route('admin.document-sign.show', ['id' => $offer->id]) }}">
                                    View signing details<span class="sr-only"> for {{ $offer->applications?->full_name ?? 'offer '.$offer->id }}</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">Offers ready for signing will appear here. <a class="text-blue-700 underline" href="{{ tenant_route('admin.job-onboard.index') }}">Go to Onboarding</a></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($offers->hasPages())
            <div class="mt-4">{{ $offers->links() }}</div>
        @endif
    @endif
@endsection
