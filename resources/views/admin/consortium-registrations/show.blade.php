@extends('layouts.app')
@section('page-title-html') Registration <em>Profile</em> @endsection
@section('page-subtitle') {{ $registration->first_name }} {{ $registration->last_name }} @endsection
@section('hide-ra-page-header') true @endsection
@section('create-button')
<a href="{{ route('admin.consortium-registrations.index', request()->query()) }}" class="rounded-xl border border-[#DDE2EA] bg-white px-4 py-2 text-[12px] font-semibold text-[#5A6478]"><i class="fa fa-arrow-left mr-1"></i> Back to Registrations</a>
@endsection

@php
    $fullName = trim($registration->first_name.' '.$registration->last_name);
    $initials = strtoupper(substr($registration->first_name, 0, 1).substr($registration->last_name, 0, 1));
    $resumeName = $registration->resume_original_name ?: $registration->resume_file;
    $resumeExtension = strtolower(pathinfo((string) $resumeName, PATHINFO_EXTENSION));
    $canPreviewResume = in_array($resumeExtension, ['pdf', 'jpg', 'jpeg', 'png'], true);
    $assignedApplicationId = $profileApplicationId;
    $infoRows = [
        ['Eligible to Work in Canada', $registration->eligible_to_work_canada ? 'Yes' : 'No', 'fa-check-circle-o'],
        ['Status in Canada', $registration->status_in_canada, 'fa-flag-o'],
        ['Preferred Job Type', $registration->preferred_job_type, 'fa-briefcase'],
        ['Mode of Commute', $registration->commute_mode, 'fa-car'],
        ['Years of Experience', !is_null($registration->years_experience) ? rtrim(rtrim(number_format((float) $registration->years_experience, 1), '0'), '.').' years' : null, 'fa-line-chart'],
        ['Original Experience Response', $registration->legacy_experience_text, 'fa-history'],
        ['Industry / Expertise', $registration->industry_expertise, 'fa-cogs'],
        ['Available Weekends', is_null($registration->available_weekends) ? null : ($registration->available_weekends ? 'Yes' : 'No'), 'fa-calendar-check-o'],
        ['Available Night Shifts', is_null($registration->available_night_shifts) ? null : ($registration->available_night_shifts ? 'Yes' : 'No'), 'fa-moon-o'],
        ['Referral Source', $registration->referral_source, 'fa-share-alt'],
        ['Information Certified', $registration->information_certified ? 'Yes' : 'No', 'fa-certificate'],
        ['Applicant Agreement Accepted', $registration->agreement_accepted ? 'Yes' : 'No', 'fa-file-text-o'],
        ['SMS Consent', $registration->sms_consent ? 'Yes' : 'No', 'fa-commenting-o'],
        ['Submitted', $registration->created_at->timezone($global->timezone)->format('j M Y, g:i A'), 'fa-clock-o'],
        ['Reviewed', $registration->reviewed_at?->timezone($global->timezone)->format('j M Y, g:i A'), 'fa-eye'],
    ];
@endphp

@section('content')
<style>
body.consortium-profile-fullscreen .ra-sidebar,
body.consortium-profile-fullscreen .ra-topbar,
body.consortium-profile-fullscreen .ra-scroll > footer { display:none !important; }
body.consortium-profile-fullscreen .ra-app { width:100vw !important; }
body.consortium-profile-fullscreen .ra-main { width:100vw !important; max-width:none !important; }
body.consortium-profile-fullscreen .ra-scroll { padding:0 !important; overflow:hidden !important; }
body.consortium-profile-fullscreen #consortium-job-application-profile,
body.consortium-profile-fullscreen #consortium-job-application-profile > .ja-two-col-wrap { width:100% !important; height:100vh !important; max-width:none !important; border-radius:0 !important; }
#consortium-job-application-profile .ja-origin-attribution { display: none; }
</style>
<script>document.body.classList.add('consortium-profile-fullscreen');</script>
@if($assignedApplicationId)
<div id="consortium-job-application-profile" style="min-height:650px;display:flex;align-items:center;justify-content:center;color:#8892A0;font-size:13px;">
    <span><i class="fa fa-spinner fa-spin"></i> Loading complete candidate profile...</span>
</div>
@if(app(\App\Saas\TenantContext::class)->featureEnabled('temp_staffing'))
<form id="consortium-temp-staffing-toolbar" method="POST" action="{{ route('admin.consortium-registrations.temp-staffing', $registration) }}" style="display:none">
    @csrf
    <input type="hidden" name="add" value="{{ $registration->is_temp_staffing ? 0 : 1 }}">
    <button type="submit" class="ja-pdf-btn">
        <i class="fa {{ $registration->is_temp_staffing ? 'fa-times' : 'fa-users' }}"></i>
        {{ $registration->is_temp_staffing ? 'Remove Temp Staffing' : 'Temp Staffing' }}
    </button>
</form>
<div id="consortium-temp-staffing-history" style="display:none">
    <div class="ja-card" data-consortium-temp-history-card>
        <div class="ja-card-title"><i class="fa fa-history"></i> Temp Staffing History</div>
        @forelse($tempStaffingHistories as $history)
            <div class="ja-info-row" style="align-items:center">
                <span class="ja-info-label" style="width:auto">
                    <span style="width:8px;height:8px;border-radius:50%;background:{{ $history->action === 'added' ? '#10B981' : '#EF4444' }}"></span>
                    {{ $history->action === 'added' ? 'Added to Temp Staffing' : 'Returned to Consortium Registration' }}
                </span>
                <span class="ja-info-val" style="font-size:11px">{{ $history->user?->name ?? 'Unknown team member' }}<br><span style="color:#A0A8B5;font-weight:500">{{ $history->created_at->timezone('America/Toronto')->format('d M Y, h:i A') }} ET</span></span>
            </div>
        @empty
            <p style="margin:0;color:#A0A8B5;font-size:11.5px">No Temp Staffing activity yet.</p>
        @endforelse
    </div>
</div>
@endif
<div id="consortium-personal-information" style="display:none">
    @if($jobMoves->isEmpty())
        @include('admin.consortium-registrations.partials.information-card', ['registration' => $registration])
    @endif
    @include('admin.consortium-registrations.partials.job-movement')
</div>
@else
<style>
.cr-profile{height:calc(100vh - 132px);min-height:640px;display:flex;flex-direction:column;overflow:hidden;border:1px solid #E8E6E1;border-radius:18px;background:#F8F7F4;box-shadow:0 8px 28px rgba(15,31,61,.08);font-family:'Plus Jakarta Sans',sans-serif}
.cr-header{display:flex;align-items:center;gap:12px;padding:13px 18px;background:#0F1F3D;flex-shrink:0}
.cr-avatar{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.13);border:1.5px solid rgba(255,255,255,.25);font-size:13px;font-weight:800;color:#fff}
.cr-header-meta{flex:1;min-width:0}.cr-header-meta h2{margin:0;color:#fff;font-size:16px;font-weight:700}.cr-header-meta p{margin:3px 0 0;color:rgba(255,255,255,.52);font-size:11.5px}
.cr-pill{display:inline-flex;align-items:center;gap:5px;padding:5px 11px;border-radius:20px;background:#2563EB;color:#fff;font-size:11px;font-weight:700}.cr-pill.reviewed{background:#059669}
.cr-delete{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:1px solid rgba(248,113,113,.35);background:rgba(239,68,68,.12);color:#FCA5A5;cursor:pointer}
.cr-body{flex:1;display:grid;grid-template-columns:minmax(0,1fr) 420px;min-height:0;overflow:hidden}
.cr-resume{display:flex;flex-direction:column;min-width:0;border-right:1px solid #E8E6E1;background:#525659}
.cr-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:9px 14px;background:#fff;border-bottom:1px solid #E8E6E1}.cr-toolbar-title{font-size:12px;font-weight:700;color:#5A6478;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cr-actions{display:flex;gap:6px}.cr-btn{display:inline-flex;align-items:center;gap:5px;padding:6px 11px;border-radius:8px;border:1px solid #E2DED8;background:#fff;color:#5A6478;font-size:12px;font-weight:600}.cr-btn.primary{background:#2563EB;border-color:#2563EB;color:#fff}
.cr-frame{width:100%;height:100%;border:0;background:#525659}.cr-no-resume{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#D1D5DB;text-align:center;padding:35px}.cr-no-resume i{font-size:52px;opacity:.45;margin-bottom:14px}.cr-no-resume h3{font-size:15px;margin:0 0 5px}.cr-no-resume p{font-size:12px;opacity:.7;margin:0}
.cr-side{display:flex;flex-direction:column;min-width:0;overflow:hidden;background:#F8F7F4}.cr-tabs{padding:13px 18px;background:#fff;border-bottom:2.5px solid #2563EB;color:#2563EB;font-size:12.5px;font-weight:700}.cr-scroll{flex:1;overflow-y:auto;padding:12px;scrollbar-color:#111 #E8E6E1}.cr-card{margin-bottom:11px;padding:15px 16px;border:1px solid #E8E6E1;border-radius:14px;background:#fff;box-shadow:0 1px 3px rgba(15,31,61,.04)}
.cr-card-title{display:flex;align-items:center;gap:6px;margin-bottom:10px;color:#A0A8B5;font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:.08em}.cr-row{display:flex;align-items:flex-start;justify-content:space-between;gap:15px;padding:8px 0;border-bottom:1px solid #F0EEE9}.cr-row:last-child{border-bottom:0}.cr-label{display:flex;align-items:center;gap:6px;width:145px;flex-shrink:0;color:#8A94A6;font-size:11.5px}.cr-value{text-align:right;color:#1A1E2E;font-size:12.5px;font-weight:600;overflow-wrap:anywhere}.cr-value a{color:#2563EB}.cr-long{white-space:pre-wrap;line-height:1.65;color:#3D4A5C;font-size:12.5px}
.cr-side .rounded-2xl{border-radius:14px!important}.cr-side .p-5{padding:15px!important}
@media(max-width:1000px){.cr-profile{height:auto;min-height:0;overflow:visible}.cr-body{display:flex;flex-direction:column;overflow:visible}.cr-resume{height:560px;border-right:0;border-bottom:1px solid #E8E6E1}.cr-side{overflow:visible}.cr-scroll{overflow:visible}.cr-header{flex-wrap:wrap}}
</style>

<div class="cr-profile">
    <div class="cr-header">
        <div class="cr-avatar">{{ $initials }}</div>
        <div class="cr-header-meta">
            <h2>{{ ucwords($fullName) }}</h2>
            <p>{{ $registration->city ?: 'Location not provided' }} · Registered {{ $registration->created_at->timezone($global->timezone)->format('d M, Y') }}</p>
        </div>
        <span class="cr-pill {{ $registration->reviewed_at ? 'reviewed' : '' }}"><i class="fa {{ $registration->reviewed_at ? 'fa-check-circle' : 'fa-clock-o' }}"></i>{{ $registration->reviewed_at ? 'Reviewed' : 'New Registration' }}</span>
        @if(auth()->user()->hasRole('admin'))
            <form method="POST" action="{{ route('admin.consortium-registrations.destroy', $registration) }}" onsubmit="return confirm('Move this registration to Trash?')">@csrf @method('DELETE')<button class="cr-delete" type="submit" title="Move to Trash"><i class="fa fa-trash-o"></i></button></form>
        @endif
    </div>

    <div class="cr-body">
        <section class="cr-resume">
            <div class="cr-toolbar">
                <div class="cr-toolbar-title"><i class="fa fa-file-text-o"></i> {{ $resumeName ?: 'Candidate Resume' }}</div>
                @if($registration->resume_file)
                    <div class="cr-actions">
                        <a class="cr-btn" href="{{ route('admin.consortium-registrations.resume', ['registration' => $registration->id, 'inline' => 1]) }}" target="_blank"><i class="fa fa-external-link"></i> View</a>
                        <a class="cr-btn primary" href="{{ route('admin.consortium-registrations.resume', $registration) }}"><i class="fa fa-download"></i> Download</a>
                    </div>
                @endif
            </div>
            @if($registration->resume_file && $canPreviewResume)
                <div style="flex:1;min-height:0"><iframe class="cr-frame" src="{{ route('admin.consortium-registrations.resume', ['registration' => $registration->id, 'inline' => 1]) }}" title="{{ $fullName }} resume"></iframe></div>
            @elseif($registration->resume_file)
                <div class="cr-no-resume"><i class="fa fa-file-word-o"></i><h3>Resume is available</h3><p>This file type opens in a separate viewer. Use View or Download above.</p></div>
            @else
                <div class="cr-no-resume"><i class="fa fa-file-o"></i><h3>No resume uploaded</h3><p>This registration does not currently have a CV.</p></div>
            @endif
        </section>

        <aside class="cr-side">
            <div class="cr-tabs"><i class="fa fa-user"></i> Personal Information</div>
            <div class="cr-scroll">
                @include('admin.consortium-registrations.partials.job-movement')
                <div class="cr-card">
                    <div class="cr-card-title"><i class="fa fa-user-circle-o"></i> Personal Information</div>
                    @foreach($infoRows as $row)
                        <div class="cr-row">
                            <span class="cr-label"><i class="fa {{ $row[2] }}"></i>{{ $row[0] }}</span>
                            <span class="cr-value">
                                @if(!empty($row[3]) && $row[1])<a href="{{ $row[3] }}">{{ $row[1] }}</a>@else{{ filled($row[1]) ? $row[1] : '—' }}@endif
                            </span>
                        </div>
                    @endforeach
                </div>
                <div class="cr-card">
                    <div class="cr-card-title"><i class="fa fa-align-left"></i> Additional Information</div>
                    <div class="cr-long">{{ $registration->additional_information ?: 'No additional information provided.' }}</div>
                </div>
            </div>

        </aside>
    </div>
</div>
@endif
@if($assignedApplicationId)
@push('footer-script')
<script>
(function () {
    var profileUrl = @json(route('admin.job-applications.show', $assignedApplicationId));
    $.ajax({url: profileUrl, type: 'GET', cache: false}).done(function (response) {
        if (!response || response.status !== 'success' || !response.view) {
            $('#consortium-job-application-profile').html('<div>Candidate profile could not be loaded.</div>');
            return;
        }
        var $host = $('#consortium-job-application-profile');
        $host.css({display:'block', minHeight:0}).html(response.view);
        // Remove the generic blue Job Application Temp Staffing control.
        // The Consortium-specific toolbar button remains available.
        $host.find('[id^="ja-temp-staffing-btn-"]').remove();
        // Job assignment is managed by the Consortium placement section, so
        // hide the shared profile's Applied For inline editor on this page.
        $host.find('[id^="ja-job-view-"]').closest('.ja-info-row').remove();
        var $details = $host.find('#ja-tab-details');
        if ($details.length) $details.append($('#consortium-personal-information').html());
        var $toolbar = $host.find('.ja-pdf-toolbar-actions').first();
        if ($toolbar.length) $('#consortium-temp-staffing-toolbar').css('display', 'flex').prependTo($toolbar);
        var appendTempStaffingHistory = function () {
            var $historyPane = $host.find('#ja-tab-history');
            if (!$historyPane.length || $historyPane.find('[data-consortium-temp-history-card]').length) return;
            var historyHtml = $('#consortium-temp-staffing-history').html();
            if (historyHtml) $historyPane.append(historyHtml);
        };
        var historyPane = $host.find('#ja-tab-history').get(0);
        if (historyPane && window.MutationObserver) {
            new MutationObserver(function () { appendTempStaffingHistory(); }).observe(historyPane, {childList:true, subtree:true});
        }
        $host.on('click', '.ja-tab[data-tab="history"]', function () {
            window.setTimeout(appendTempStaffingHistory, 150);
            window.setTimeout(appendTempStaffingHistory, 700);
        });
        var backUrl = @json(request('from') === 'temp-staffing' && app(\App\Saas\TenantContext::class)->featureEnabled('temp_staffing') ? route('admin.temp-staffing.index', request()->except('from')) : route('admin.consortium-registrations.index', request()->query()));
        $host.find('.right-side-toggle').removeClass('right-side-toggle').off('click').on('click', function () { window.location.href = backUrl; });

        // The embedded profile normally navigates JobApplication IDs. On the
        // Consortium page these controls must navigate registration profiles.
        var registrationIds = @json($registrationNavigationIds);
        var currentRegistrationId = {{ (int) $registration->id }};
        var currentIndex = registrationIds.indexOf(currentRegistrationId);
        var registrationUrlTemplate = @json(route('admin.consortium-registrations.show', ['registration' => '__REGISTRATION__']));
        var registrationQuery = @json(http_build_query(request()->query()));
        var $previous = $host.find('#ja-prev-btn');
        var $next = $host.find('#ja-next-btn');
        var $counter = $host.find('#ja-nav-counter');

        function openRegistrationAt(index) {
            if (index < 0 || index >= registrationIds.length) return;
            var url = registrationUrlTemplate.replace('__REGISTRATION__', registrationIds[index]);
            window.location.href = url + (registrationQuery ? '?' + registrationQuery : '');
        }

        window.jaNavigate = function (direction) { openRegistrationAt(currentIndex + (direction === 'prev' ? -1 : 1)); };
        $counter.text(currentIndex >= 0 ? (currentIndex + 1) + ' / ' + registrationIds.length : '—');
        $previous.removeAttr('onclick').prop('disabled', currentIndex <= 0).off('click.consortiumNav')
            .on('click.consortiumNav', function () { openRegistrationAt(currentIndex - 1); });
        $next.removeAttr('onclick').prop('disabled', currentIndex < 0 || currentIndex >= registrationIds.length - 1).off('click.consortiumNav')
            .on('click.consortiumNav', function () { openRegistrationAt(currentIndex + 1); });
    }).fail(function () {
        $('#consortium-job-application-profile').html('<div>Candidate profile could not be loaded. Please refresh and try again.</div>');
    });
})();
</script>
@endpush
@endif
@endsection