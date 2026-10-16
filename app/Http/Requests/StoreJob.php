<?php

namespace App\Http\Requests;
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
            'total_positions'    => 'required|numeric',
            'start_date'         => 'required|date',
            'end_date'           => 'required|date',

            // New manual-entry fields
            'company_new'        => 'nullable|string|max:191',
            'category_new'       => 'nullable|string|max:191',
            'location_new'       => 'nullable|string|max:191',

            // Existing selects — now nullable, validated conditionally in withValidator()
            'company'            => 'nullable',
            'category_id'        => 'nullable',
            'location_id'        => 'nullable|array',

            'job_type_id'        => 'required',
            'work_experience_id' => 'required',
            'pay_type'           => 'required',
            'starting_salary'    => 'required',
            'pay_according'      => 'required',
            'company_location' => 'nullable|string|max:191',
        ];

        if (request('pay_type') == 'Range') {
            $rules['maximum_salary'] = 'gt:starting_salary';
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
                $validator->errors()->add('location_id', __('menu.locations').' '.__('errors.fieldRequired'));
            }
        });
    }

    public function messages()
    {
        $msg = [
            'job_type_id.required'        => __('modules.jobs.jobType').' '.__('errors.fieldRequired'),
            'work_experience_id.required' => __('modules.jobs.workExperience').' '.__('errors.fieldRequired'),
        ];

        if (request('pay_type') == 'Range') {
            $msg['starting_salary.required'] = __('modules.jobs.startingSalary').' '.__('errors.fieldRequired');
            $msg['maximum_salary.gt']        = __('modules.jobs.maximumSalary').' '.__('errors.fieldRequired');
        } elseif (request('pay_type') == 'Starting') {
            $msg['starting_salary.required'] = __('modules.jobs.startingSalary').' '.__('errors.fieldRequired');
        } elseif (request('pay_type') == 'Maximum') {
            $msg['starting_salary.required'] = __('modules.jobs.maximumSalary').' '.__('errors.fieldRequired');
        } elseif (request('pay_type') == 'Exact Amount') {
            $msg['starting_salary.required'] = __('modules.jobs.exactSalary').' '.__('errors.fieldRequired');
        }

        return $msg;
    }
}
