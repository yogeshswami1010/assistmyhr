@php
    use App\Services\WhatJobsPublisher;

    $whatJobsSelected = old('publish_on_whatjobs', $isEdit && $job?->publish_on_whatjobs);
    $whatJobsSettings = $job?->whatjobs_settings ?? [];
    $whatJobsSync = $job?->whatjobs_sync ?? [];
    $whatJobsFields = [
        'countryCode' => ['Country code (e.g. US, GB, IN)', null, 2],
        'jobWorkFrom' => ['Work arrangement', WhatJobsPublisher::WORK_FROM],
        'city' => ['City', null, 100],
        'state' => ['State or province', null, 100],
        'jobLocationAddress' => ['Street address', null, 64],
        'zip' => ['Postal code', null, 9],
        'jobEmploymentType' => ['Employment type', WhatJobsPublisher::EMPLOYMENT_TYPE],
        'jobEmploymentSubType' => ['Employment subtype', WhatJobsPublisher::EMPLOYMENT_SUBTYPE],
        'jobPeriod' => ['Contract duration unit', WhatJobsPublisher::CONTRACT_PERIOD],
        'jobLength' => ['Contract duration', null],
        'jobScheduleType' => ['Schedule', WhatJobsPublisher::SCHEDULE],
        'jobSector' => ['WhatJobs sector', null, 120],
    ];
@endphp
<div class="mt-3">
    <input type="hidden" name="publish_on_whatjobs" value="0">
    <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" id="publish_on_whatjobs" name="publish_on_whatjobs" value="1"
            aria-controls="whatjobs-settings" @checked($whatJobsSelected)
            onchange="document.getElementById('whatjobs-settings').hidden = !this.checked">
        Publish this job on WhatJobs
    </label>
    @if(!empty($whatJobsSync['error']))
        <p class="mt-1 text-xs font-semibold text-amber-800">{{ $whatJobsSync['error'] }}</p>
    @elseif(!empty($whatJobsSync['active']))
        <p class="mt-1 text-xs font-semibold text-emerald-700">Accepted by WhatJobs.</p>
    @elseif(!empty($whatJobsSync['id']))
        <p class="mt-1 text-xs text-slate-600">Paused on WhatJobs.</p>
    @elseif($whatJobsSelected)
        <p class="mt-1 text-xs text-slate-600">Waiting for the job to be publicly available.</p>
    @endif

    <div id="whatjobs-settings" @if(!$whatJobsSelected) hidden @endif>
        <p class="my-2 text-xs text-slate-600">
            Address fields are required except for Remote jobs. Duration is required for contracts.
            Candidates apply through AssistMyHR.
            <a class="text-blue-700 underline" href="https://www.whatjobs.com/ats/api" target="_blank" rel="noopener">WhatJobs sector list</a>
        </p>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            @foreach($whatJobsFields as $key => $field)
                @php
                    [$label, $options] = $field;
                    $value = old('whatjobs.'.$key, $whatJobsSettings[$key] ?? '');
                @endphp
                <div>
                    <label for="whatjobs_{{ $key }}" class="text-xs font-semibold text-slate-700">{{ $label }}</label>
                    @if($options)
                        <select id="whatjobs_{{ $key }}" name="whatjobs[{{ $key }}]" class="form-control">
                            <option value="">Select</option>
                            @foreach($options as $option => $optionLabel)
                                <option value="{{ $option }}" @selected((string) $value === (string) $option)>{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                    @else
                        <input id="whatjobs_{{ $key }}" name="whatjobs[{{ $key }}]" class="form-control"
                            value="{{ $key === 'countryCode' ? ($whatJobsSync['country'] ?? $value) : $value }}"
                            @if($key === 'countryCode' && isset($whatJobsSync['country'])) readonly @endif
                            @if($key === 'jobLength') type="number" min="1" @else type="text" maxlength="{{ $field[2] }}" @endif>
                    @endif
                </div>
            @endforeach
        </div>
        <label for="whatjobs_jobScheduleTypeOther" class="mt-3 text-xs font-semibold text-slate-700">Custom schedule (required for Other)</label>
        <textarea id="whatjobs_jobScheduleTypeOther" name="whatjobs[jobScheduleTypeOther]"
            class="form-control" rows="3">{{ old('whatjobs.jobScheduleTypeOther', $whatJobsSettings['jobScheduleTypeOther'] ?? '') }}</textarea>
    </div>
</div>
