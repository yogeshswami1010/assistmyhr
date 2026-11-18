<?php

namespace App\Http\Requests;
use App\Job;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;

class StoreJob extends CoreRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'title'              => 'required',
            'job_code'           => 'nullable|string|max:100',
            'job_description'    => 'required',
            'publish_on_jooble'  => 'sometimes|boolean',
            'publish_on_jobbank' => 'sometimes|boolean',
            'jobbank_noc_code' => ['nullable', 'required_if:publish_on_jobbank,1', 'regex:/\A[0-9]{5}\z/'],
            'jobbank_noc_title' => 'nullable|required_if:publish_on_jobbank,1|string|max:191',
            'jobbank_city' => 'nullable|required_if:publish_on_jobbank,1|string|max:191',
            'jobbank_province' => ['nullable', 'required_if:publish_on_jobbank,1', Rule::in(Job::JOB_BANK_PROVINCES)],
            'total_positions'    => 'required|numeric',

            // New manual-entry fields
            'company_new'          => 'nullable|string|max:191',
            'category_new'         => 'nullable|string|max:191',
            'location_new'         => 'nullable|string|max:191',

            // Existing selects
            'company'              => 'nullable',
            'category_id'          => 'nullable',
            'location_id'          => 'nullable|array',

            // Made job_type_id optional
            'job_type_id'          => 'nullable|exists:job_types,id',
            'work_experience_ids' => 'nullable|array|max:30',
            'work_experience_ids.*' => ['bail', 'required', 'string', 'max:195', function ($attribute, $value, $fail) {
                $isNew = str_starts_with($value, 'new:') && trim(substr($value, 4)) !== '';
                $isExisting = ctype_digit($value) && \App\WorkExperience::whereKey($value)->exists();
                if (! $isNew && ! $isExisting) {
                    $fail(__('modules.jobs.workExperience').' '.__('errors.fieldRequired'));
                }
            }],

            // Made pay_type and salary fields optional / conditional
            'pay_type'             => 'nullable|in:Range,Starting,Maximum,Exact Amount',
            'starting_salary'      => 'nullable|required_with:pay_type|numeric|min:0',
            'pay_according'        => 'nullable',
            'company_location'     => 'nullable|string|max:191',
        ];

        if (request('pay_type') == 'Range') {
            $rules['maximum_salary'] = 'required|numeric|gt:starting_salary';
        }

        if ($this->boolean('publish_on_jobbank')) {
            $rules['total_positions'] = 'required|integer|min:1';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (empty(trim((string) $this->company_new)) && empty($this->company)) {
                $validator->errors()->add('company', __('app.company').' '.__('errors.fieldRequired'));
            }

            if (empty(trim((string) $this->category_new)) && (empty($this->category_id) || $this->category_id === 'new')) {
                $validator->errors()->add('category_id', __('menu.jobCategories').' '.__('errors.fieldRequired'));
            }

            $locationIds = array_filter((array) ($this->location_id ?? []), fn ($id) => filled($id) && $id !== 'new');
            if (empty(trim((string) $this->location_new)) && empty($locationIds)) {
                $field = $this->has('location_new') ? 'location_new' : 'location_id';
                $validator->errors()->add($field, __('menu.locations').' '.__('errors.fieldRequired'));
            }
        });
    }

    public function messages()
    {
        $msg = [
            'work_experience_ids.*.required' => __('modules.jobs.workExperience').' '.__('errors.fieldRequired'),
        ];

        if (request('pay_type') == 'Range') {
            $msg['starting_salary.required_with'] = __('modules.jobs.startingSalary').' '.__('errors.fieldRequired');
            $msg['maximum_salary.required']  = __('modules.jobs.maximumSalary').' '.__('errors.fieldRequired');
            $msg['maximum_salary.gt']        = __('modules.jobs.maximumSalary').' must be greater than starting salary.';
        } elseif (request('pay_type') == 'Starting') {
            $msg['starting_salary.required_with'] = __('modules.jobs.startingSalary').' '.__('errors.fieldRequired');
        } elseif (request('pay_type') == 'Maximum') {
            $msg['starting_salary.required_with'] = __('modules.jobs.maximumSalary').' '.__('errors.fieldRequired');
        } elseif (request('pay_type') == 'Exact Amount') {
            $msg['starting_salary.required_with'] = __('modules.jobs.exactSalary').' '.__('errors.fieldRequired');
        }

        return $msg;
    }
}
