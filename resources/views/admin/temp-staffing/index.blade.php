@extends('layouts.app')
@section('page-title-html') Temp <em>Staffing</em> @endsection
@section('page-subtitle') Candidates selected for temporary staffing @endsection
@section('content')
@include('admin.partials.candidate-communications')
@if(session('success'))<div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[12px] font-semibold text-emerald-700">{{ session('success') }}</div>@endif
<div class="overflow-hidden rounded-2xl border border-[#E8E6E1] bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-[#EEECE8] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div><h2 class="text-[15px] font-bold text-[#1A1E2E]">Temp Staffing Candidates</h2></div>
        <form method="GET" action="{{ route('admin.temp-staffing.index') }}" class="flex w-full gap-2 sm:w-auto">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name, city, email..." class="w-full rounded-xl border border-[#DDE2EA] px-3 py-2 text-[12px] outline-none focus:border-blue-500 sm:w-64">
            <button class="rounded-xl bg-blue-600 px-4 py-2 text-[12px] font-bold text-white"><i class="fa fa-search"></i></button>
            @if(request('search'))<a href="{{ route('admin.temp-staffing.index') }}" class="rounded-xl border border-[#DDE2EA] px-3 py-2 text-[12px] font-semibold text-[#5A6478]">Reset</a>@endif
        </form>
    </div>
    @if($registrations !== null)
    <div class="overflow-x-auto"><table class="w-full text-left"><thead class="bg-[#F8F9FB] text-[10px] uppercase tracking-wider text-[#8892A0]"><tr><th class="px-5 py-3"><input type="checkbox" class="cc-all" aria-label="Select all candidates on this page">Candidate</th><th class="px-5 py-3">Contact</th><th class="px-5 py-3">City</th><th class="px-5 py-3">Availability</th><th class="px-5 py-3 text-right">Action</th></tr></thead><tbody>
    @forelse($registrations as $registration)
        <tr class="border-t border-[#F0EEE9] hover:bg-[#FBFCFE]">
            <td class="px-5 py-4"><div style="display:flex;align-items:center;gap:12px"><input style="flex-shrink:0" type="checkbox" class="cc-select" data-type="registration" value="{{ $registration->id }}" aria-label="Select {{ $registration->first_name }} {{ $registration->last_name }}"><a href="{{ route('admin.consortium-registrations.show', ['registration' => $registration->id, 'from' => 'temp-staffing', 'search' => request('search'), 'page' => request('page')]) }}" class="flex items-center gap-3"><span style="flex-shrink:0" class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-50 text-[12px] font-bold text-blue-600">{{ strtoupper(substr($registration->first_name,0,1).substr($registration->last_name,0,1)) }}</span><span class="text-[13px] font-semibold text-[#1A1E2E] hover:text-blue-600 hover:underline">{{ $registration->first_name }} {{ $registration->last_name }}</span></a></div></td>
            <td class="px-5 py-4 text-[12px] text-[#5A6478]">{{ $registration->email }}<br>{{ $registration->phone }}</td>
            <td class="px-5 py-4 text-[12px] text-[#5A6478]">{{ $registration->city ?: '—' }}</td>
            <td class="px-5 py-4 text-[11px] text-[#5A6478]">{{ $registration->preferred_job_type ?: 'Any' }}<br>{{ $registration->available_weekends ? 'Weekends available' : 'Weekdays' }}</td>
            <td class="px-5 py-4"><div class="flex justify-end gap-2"><a href="{{ route('admin.consortium-registrations.show', ['registration' => $registration->id, 'from' => 'temp-staffing']) }}" class="rounded-lg bg-blue-50 px-3 py-2 text-[11px] font-bold text-blue-600">View Profile</a><form method="POST" action="{{ route('admin.consortium-registrations.temp-staffing', $registration) }}" onsubmit="return confirm('Remove this candidate from Temp Staffing?')">@csrf<input type="hidden" name="add" value="0"><button class="rounded-lg border border-red-200 px-3 py-2 text-[11px] font-bold text-red-600">Remove</button></form></div></td>
        </tr>
    @empty<tr><td colspan="5" class="px-5 py-16 text-center text-sm text-[#8892A0]">No candidates have been added to Temp Staffing.</td></tr>@endforelse
    </tbody></table></div>
    <div class="border-t border-[#EEECE8] px-5 py-4">{{ $registrations->links() }}</div>
    @endif
</div>

<div class="mt-5 overflow-hidden rounded-2xl border border-[#E8E6E1] bg-white shadow-sm">
    <div class="border-b border-[#EEECE8] px-5 py-4"><h2 class="text-[15px] font-bold text-[#1A1E2E]">Job Application & Candidate Database</h2><p class="text-[11px] text-[#8892A0]">Candidates selected from Job Applications or Candidate Database</p></div>
    <div class="overflow-x-auto"><table class="w-full text-left"><thead class="bg-[#F8F9FB] text-[10px] uppercase tracking-wider text-[#8892A0]"><tr><th class="px-5 py-3"><input type="checkbox" class="cc-all" aria-label="Select all candidates on this page">Candidate</th><th class="px-5 py-3">Source</th><th class="px-5 py-3">Job</th><th class="px-5 py-3">Contact</th><th class="px-5 py-3 text-right">Action</th></tr></thead><tbody>
    @forelse($applications as $application)
        <tr class="border-t border-[#F0EEE9] hover:bg-[#FBFCFE]"><td class="px-5 py-4"><div style="display:flex;align-items:center;gap:12px"><input style="flex-shrink:0" type="checkbox" class="cc-select" data-type="application" value="{{ $application->id }}" aria-label="Select {{ $application->full_name }}"><button type="button" class="temp-staffing-show-application text-[13px] font-semibold text-[#1A1E2E] hover:text-blue-600 hover:underline" data-row-id="{{ $application->id }}">{{ $application->full_name }}</button></div></td><td class="px-5 py-4"><span class="rounded-full bg-violet-50 px-2 py-1 text-[10px] font-bold text-violet-600">{{ $application->is_candidate ? 'Candidate Database' : 'Job Application' }}</span></td><td class="px-5 py-4 text-[12px] text-[#5A6478]">{{ $application->job?->title ?: 'No job assigned' }}</td><td class="px-5 py-4 text-[12px] text-[#5A6478]">{{ $application->email }}<br>{{ $application->phone }}</td><td class="px-5 py-4"><div class="flex justify-end gap-2"><button type="button" class="temp-staffing-show-application rounded-lg bg-blue-50 px-3 py-2 text-[11px] font-bold text-blue-600" data-row-id="{{ $application->id }}">View Profile</button><button type="button" onclick="removeTempStaffingApplication({{ $application->id }})" class="rounded-lg border border-red-200 px-3 py-2 text-[11px] font-bold text-red-600">Remove</button></div></td></tr>
    @empty<tr><td colspan="5" class="px-5 py-16 text-center text-sm text-[#8892A0]">No Job Application or Candidate Database candidates have been added.</td></tr>@endforelse
    </tbody></table></div><div class="border-t border-[#EEECE8] px-5 py-4">{{ $applications->links() }}</div>
</div>

@push('footer-script')
<script>
window.removeTempStaffingApplication = function (appId) {
    $.ajax({type:'POST', url:@json(route('admin.job-applications.temp-staffing', ':id')).replace(':id', appId), data:{_token:@json(csrf_token()), add:0}})
        .done(function(){ window.location.reload(); });
};
$(document).on('click', '.temp-staffing-show-application', function () {
    var id = $(this).data('row-id'), $sidebar = $('#right-sidebar'), $backdrop = $('#right-sidebar-backdrop');
    $sidebar.removeClass('translate-x-full').addClass('translate-x-0'); $backdrop.removeClass('hidden').css('display','block');
    $.easyAjax({type:'GET', url:@json(route('admin.job-applications.show', ':id')).replace(':id', id), success:function(response){if(response.status==='success') $('#right-sidebar-content').html(response.view);}});
});
</script>
@endpush
@endsection