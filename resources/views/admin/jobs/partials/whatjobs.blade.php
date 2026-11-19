@php
    $whatJobsSelected = old('publish_on_whatjobs', $isEdit && $job?->publish_on_whatjobs);
    $whatJobsSync = $job?->whatjobs_sync ?? [];
    $whatJobsUrl = \App\Services\WhatJobsPublisher::listingUrl($whatJobsSync['url'] ?? null);
    $whatJobsError = \App\Services\WhatJobsPublisher::visibleError($whatJobsSync);
@endphp
<div class="mt-3">
    <input type="hidden" name="publish_on_whatjobs" value="0">
    <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" name="publish_on_whatjobs" value="1" @checked($whatJobsSelected)>
        Publish this job on WhatJobs
    </label>
    @if(!empty($whatJobsSync['id']))
        <p class="mt-1 text-xs font-semibold {{ !empty($whatJobsSync['active']) ? 'text-emerald-700' : 'text-slate-600' }}">
            {{ !empty($whatJobsSync['active']) ? 'Accepted by WhatJobs.' : 'Paused on WhatJobs.' }}
            WhatJobs ID {{ $whatJobsSync['id'] }}.
        </p>
        @if($whatJobsUrl && !empty($whatJobsSync['active']))
            <a class="mt-1 inline-block text-xs text-blue-700 underline" href="{{ $whatJobsUrl }}"
                target="_blank" rel="noopener noreferrer">View on WhatJobs</a>
        @endif
    @elseif($whatJobsSelected && $whatJobsError === null)
        <p class="mt-1 text-xs text-slate-600">Waiting for submission to WhatJobs.</p>
    @endif
    @if($whatJobsError !== null)
        <p class="mt-1 text-xs font-semibold text-amber-800">
            {{ $whatJobsError }}
        </p>
    @endif
</div>
