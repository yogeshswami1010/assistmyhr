@php
    $whatJobsSelected = old('publish_on_whatjobs', $isEdit && $job?->publish_on_whatjobs);
    $whatJobsSync = $job?->whatjobs_sync ?? [];
    $whatJobsUrl = \App\Services\WhatJobsPublisher::listingUrl($whatJobsSync['url'] ?? null);
    $whatJobsError = \App\Services\WhatJobsPublisher::visibleError($whatJobsSync);
    $whatJobsDetails = $job ? app(\App\Services\WhatJobsJobDetails::class)->forJob(
        $job,
        $job->jobLocation()->with('country')->orderBy('job_locations.id')->first() ?? $job->location,
        $whatJobsSync['country'] ?? null
    ) : [];
    $whatJobsSaved = $job?->whatjobs_settings ?? [];
    $showWhatJobsField = fn ($key, $errorKey = null) => blank($whatJobsDetails[$key] ?? null)
        || array_key_exists($key, $whatJobsSaved) || str_contains($whatJobsError ?? '', ($errorKey ?? $key).':');
    $workArrangement = empty($whatJobsSync['id']) && $showWhatJobsField('jobWorkFrom');
    $employmentSubtype = $showWhatJobsField('jobEmploymentSubType', 'jobDetailsEmploymentSubType');
    $contractDuration = $showWhatJobsField('jobLength') || $showWhatJobsField('jobPeriod');
    $selectedSubtype = (string) old('whatjobs.jobEmploymentSubType', $whatJobsDetails['jobEmploymentSubType'] ?? '');
@endphp
<div class="mt-3">
    <input type="hidden" name="publish_on_whatjobs" value="0">
    <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" id="publish_on_whatjobs" name="publish_on_whatjobs" value="1" @checked($whatJobsSelected)>
        Publish this job on WhatJobs
    </label>
    <p id="whatjobs-live-check" class="mt-1 text-xs font-semibold text-amber-800" role="status" hidden></p>
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
    @if($whatJobsError !== null && ($whatJobsSelected || ($whatJobsSync['error_command'] ?? null) === 'pause'))
        <p class="mt-1 text-xs font-semibold text-amber-800">
            {{ $whatJobsError }}
        </p>
    @endif
    @if($job && ($workArrangement || $employmentSubtype || ($selectedSubtype === '1' && $contractDuration)))
        <div id="whatjobs-required-details" class="mt-3" @if(!$whatJobsSelected) hidden @endif>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                @foreach([
                    'jobWorkFrom' => [$workArrangement, 'Work arrangement', \App\Services\WhatJobsJobDetails::WORK_ARRANGEMENTS],
                    'jobEmploymentSubType' => [$employmentSubtype, 'Employment subtype', \App\Services\WhatJobsJobDetails::EMPLOYMENT_SUBTYPES],
                ] as $key => [$show, $label, $options])
                    @if($show)
                        <div class="form-group mb-0">
                            <label for="whatjobs_{{ $key }}" class="text-xs font-semibold text-slate-700">{{ $label }}</label>
                            <select id="whatjobs_{{ $key }}" name="whatjobs[{{ $key }}]" class="form-control">
                                <option value="">Select</option>
                                @foreach($options as $value => $option)
                                    <option value="{{ $value }}" @selected((string) old('whatjobs.'.$key, $whatJobsDetails[$key] ?? '') === (string) $value)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                @endforeach
            </div>
            @if($contractDuration)
                <div id="whatjobs-contract-duration" class="mt-3" @if($selectedSubtype !== '1') hidden @endif>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div class="form-group mb-0">
                            <label for="whatjobs_jobLength" class="text-xs font-semibold text-slate-700">Contract duration</label>
                            <input type="number" min="1" id="whatjobs_jobLength" name="whatjobs[jobLength]" class="form-control"
                                value="{{ old('whatjobs.jobLength', $whatJobsDetails['jobLength'] ?? '') }}">
                        </div>
                        <div class="form-group mb-0">
                            <label for="whatjobs_jobPeriod" class="text-xs font-semibold text-slate-700">Duration unit</label>
                            <select id="whatjobs_jobPeriod" name="whatjobs[jobPeriod]" class="form-control">
                                <option value="">Select</option>
                                @foreach(\App\Services\WhatJobsJobDetails::CONTRACT_PERIODS as $value => $option)
                                    <option value="{{ $value }}" @selected((string) old('whatjobs.jobPeriod', $whatJobsDetails['jobPeriod'] ?? '') === (string) $value)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        <script>
            (() => {
                const publish = document.getElementById('publish_on_whatjobs');
                const details = document.getElementById('whatjobs-required-details');
                const subtype = document.getElementById('whatjobs_jobEmploymentSubType');
                const duration = document.getElementById('whatjobs-contract-duration');
                const refresh = () => {
                    details.hidden = !publish.checked;
                    if (duration && subtype) duration.hidden = subtype.value !== '1';
                };
                publish.addEventListener('change', refresh);
                subtype?.addEventListener('change', refresh);
                refresh();
            })();
        </script>
    @endif
</div>
@push('footer-script')
<script>
    (() => {
        const publish = document.getElementById('publish_on_whatjobs');
        const form = publish.form;
        const status = document.getElementById('whatjobs-live-check');
        const fields = ['_token', 'title', 'category_id', 'job_type_id', 'location_new', 'location_country_code', 'location_street_address', 'location_postal_code', 'whatjobs[jobWorkFrom]'];
        const jobId = @json($isEdit ? $job?->id : null);
        let timer;
        let pending;

        async function check() {
            const request = new AbortController();
            pending = request;
            const data = new FormData();
            for (const field of fields) {
                const input = form.elements.namedItem(field);
                if (input) data.set(field, input.value);
            }
            const editor = document.getElementById('job_description_editor');
            data.set('job_description', editor?.innerHTML ?? form.elements.namedItem('job_description')?.value ?? '');
            if (jobId) data.set('job_id', jobId);

            try {
                const result = await fetch(@json(tenant_route('admin.jobs.whatjobs-preview', [], false)), {
                    method: 'POST',
                    headers: {Accept: 'application/json'},
                    body: data,
                    signal: request.signal,
                });
                if (!result.ok) throw new Error();
                const {issues} = await result.json();
                if (request.signal.aborted) return;
                status.textContent = issues.join(' ');
                status.hidden = issues.length === 0;
            } catch {
                if (request.signal.aborted) return;
                status.textContent = @json(__('Live WhatJobs checks are unavailable.'));
                status.hidden = false;
            }
        }

        function queue() {
            clearTimeout(timer);
            pending?.abort();
            status.hidden = true;
            if (publish.checked) timer = setTimeout(check, 350);
        }

        $(form).on('input change', function (event) {
            if (event.target === publish || fields.includes(event.target.name) ||
                event.target.closest('#job_description_editor') || event.target.name === 'job_description') queue();
        });
        queue();
    })();
</script>
@endpush
