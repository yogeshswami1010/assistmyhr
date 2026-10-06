<style>
    #createJobAlert .required:after {
        content: " *";
        color: #dc2626;
    }

    #createJobAlert .job-alert-select {
        min-height: 132px;
        width: 100%;
        padding: 0.75rem 0.875rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        background: #ffffff;
        color: #1f2937;
        line-height: 1.5;
    }

    #createJobAlert .job-alert-select:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    #createJobAlert .job-alert-select option {
        padding: 0.28rem 0.35rem;
    }
</style>

<form id="createJobAlert">
    {{ csrf_field() }}

    <div class="row">
        <div class="col-12 mb-4">
            <label class="required mb-2 d-block font-medium">@lang('menu.jobCategory')</label>
            <select class="job-alert-select" name="jobCategory[]" id="jobCategory" multiple="multiple" size="5">
                @foreach ($jobCategorys as $jobCategory)
                    <option value="{{ $jobCategory->id }}">{{ ucfirst($jobCategory->name) }}</option>
                @endforeach
            </select>
            <small class="text-muted d-block mt-1">Hold Ctrl/Cmd to select multiple</small>
        </div>

        <div class="col-12 mb-4">
            <label class="required mb-2 d-block font-medium">@lang('app.location')</label>
            <select name="location[]" class="job-alert-select" id="location" multiple="multiple" size="5">
                @foreach ($locations as $location)
                    <option value="{{ $location->id }}">{{ ucfirst($location->location) }}</option>
                @endforeach
            </select>
            <small class="text-muted d-block mt-1">Hold Ctrl/Cmd to select multiple</small>
        </div>

        <div class="col-md-6 mb-4">
            <label class="mb-2 d-block font-medium">@lang('modules.jobs.workExperience')</label>
            <select name="workExperience" id="workExperience" class="form-control">
                <option value="">--</option>
                @foreach ($workExperiences as $workExperience)
                    <option value="{{ $workExperience->id }}">{{ ucfirst($workExperience->work_experience) }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6 mb-4">
            <label class="mb-2 d-block font-medium">@lang('modules.jobs.jobType')</label>
            <select name="jobType" id="jobType" class="form-control">
                <option value="">--</option>
                @foreach ($jobTypes as $jobType)
                    <option value="{{ $jobType->id }}">{{ ucfirst($jobType->job_type) }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-12 mb-3">
            <label class="required mb-2 d-block font-medium">@lang('app.email')</label>
            <input type="email" name="email" class="form-control" value="">
        </div>
    </div>

    <div class="text-center pt-2">
        <button type="button" id="save-job-alert" class="fr-btn-nav px-6 py-2.5">
            <i class="fa fa-check mr-1"></i> @lang('app.submit')
        </button>
    </div>
</form>

<script>
    $('#save-job-alert').click(function () {
        $.easyAjax({
            url: '{{ tenant_route('jobs.saveJobAlert') }}',
            container: '#createJobAlert',
            type: 'POST',
            data: $('#createJobAlert').serialize(),
            success: function () {
                $('#addJobAlert').addClass('hidden');
            }
        });
    });
</script>
