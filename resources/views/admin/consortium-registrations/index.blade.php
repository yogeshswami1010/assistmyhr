@extends('layouts.app')

@section('page-title-html') {{ __('Consortium Registrations') }} @endsection

@push('head-script')
    @include('admin.job-applications.partials.application-list-style')
    <style>
        .cr-list { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; }
        .cr-list .ja-filter-form { width: 100%; gap: 10px; }
        .cr-list .registration-multi-filter { flex: 1 1 116px; min-width: 116px; }
        .cr-list .registration-multi-filter[data-filter-name="available_weekends"] { flex-basis: 142px; min-width: 142px; }
        .cr-list .registration-filter-toggle,
        .cr-list #registration-filters-apply,
        .cr-list #reset-filters { min-height: 38px; white-space: nowrap; }
        .cr-list .registration-filter-menu { width: min(280px, calc(100vw - 64px)); }
        .cr-list .ja-act-btn { display: inline-flex; align-items: center; border: 1px solid var(--ja-line); background: #fff; color: #475569; }
        .cr-list .ja-act-btn:hover { background: #eff6ff; color: var(--ja-blue); }
        .cr-list .ja-act-btn.cr-delete:hover { background: #fef2f2; color: #b91c1c; }
    </style>
@endpush

@section('content')
@include('admin.partials.candidate-communications')

@if(session('success'))
    <div role="status" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
        {{ session('success') }}
    </div>
@endif

<div class="ja-applications cr-list">
    <div class="ja-app-toolbar">
        <span class="inline-flex items-center gap-2 text-[13px] font-semibold">
            {{ __('Registrations') }}
            <span class="ja-status-pill ja-status-blue">{{ number_format($registrations->total()) }}</span>
        </span>
        <span class="text-[13px] text-slate-600">{{ __(':count awaiting review', ['count' => $unreviewedCount]) }}</span>
        <a href="{{ tenant_route('consortium-registration.create') }}" target="_blank" rel="noopener"
            class="ml-auto inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-[13px] font-semibold text-white hover:bg-blue-700 focus-visible:ring-2 focus-visible:ring-blue-500">
            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
            {{ __('Open Registration Form') }}
        </a>
    </div>

    <div class="ja-filters">
        @include('admin.consortium-registrations.partials.filters')
    </div>

    <div class="ja-table-overflow">
        <table id="myTable" class="w-full text-left">
            <thead>
                <tr>
                    <th class="w-12"><input type="checkbox" class="cc-all" aria-label="{{ __('Select all candidates on this page') }}"></th>
                    <th>{{ __('Candidate name') }}</th>
                    <th>{{ __('Contact') }}</th>
                    <th>{{ __('City') }}</th>
                    <th>{{ __('Eligibility') }}</th>
                    <th>{{ __('Submitted') }}</th>
                    <th class="text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($registrations as $registration)
                    @php
                        $name = trim($registration->first_name.' '.$registration->last_name);
                        $initials = mb_strtoupper(mb_substr($registration->first_name ?? '', 0, 1).mb_substr($registration->last_name ?? '', 0, 1));
                        $profileUrl = route('admin.consortium-registrations.show', ['registration' => $registration->id] + request()->query());
                    @endphp
                    <tr>
                        <td><input type="checkbox" class="cc-select" data-type="registration" value="{{ $registration->id }}" aria-label="{{ __('Select :name', ['name' => $name]) }}"></td>
                        <td>
                            <div class="ja-applicant">
                                <span class="ja-applicant-avatar" aria-hidden="true">{{ $initials }}</span>
                                <div>
                                    <a href="{{ $profileUrl }}" class="ja-applicant-name">{{ $name }}</a>
                                    @if(!$registration->reviewed_at)
                                        <span class="ml-1 text-[11px] font-semibold text-amber-700">{{ __('New') }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="break-words">{{ $registration->email }}<br>{{ $registration->phone }}</td>
                        <td>{{ $registration->city }}</td>
                        <td>
                            <span class="ja-status-pill {{ $registration->eligible_to_work_canada ? 'ja-status-green' : 'ja-status-red' }}">
                                {{ $registration->eligible_to_work_canada ? __('Eligible') : __('Not eligible') }}
                            </span>
                        </td>
                        <td>{{ $registration->created_at->timezone($global->timezone)->format('M j, Y g:i A') }}</td>
                        <td>
                            <div class="ja-row-actions flex items-center justify-end">
                                <a href="{{ $profileUrl }}" class="ja-act-btn" title="{{ __('View details') }}" aria-label="{{ __('View details') }}">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                </a>
                                @if(auth()->user()->hasRole('admin'))
                                    <form method="POST" action="{{ route('admin.consortium-registrations.destroy', $registration) }}" onsubmit="return confirm('Move this registration to Trash?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ja-act-btn cr-delete" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}">
                                            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">{{ __('No registrations match the current view.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($registrations->hasPages())
        <div class="ja-table-footer">
            <div class="w-full">{{ $registrations->links() }}</div>
        </div>
    @elseif($registrations->count())
        <div class="ja-table-footer">
            <span>{{ __(':first–:last of :total', ['first' => $registrations->firstItem(), 'last' => $registrations->lastItem(), 'total' => $registrations->total()]) }}</span>
        </div>
    @endif
</div>
@endsection
