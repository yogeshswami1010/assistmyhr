<?php

namespace App\Http\Requests\Question;

use App\Http\Requests\CoreRequest;

class StoreRequest extends CoreRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'question' => 'required',
            'job_category_id' => 'nullable|integer|exists:job_categories,id',
        ];
        if ($this->boolean('inline_job')) {
            $rules += ['question' => 'required|string|max:1000', 'type' => 'required|in:text,file,radio', 'required' => 'required|in:yes,no', 'radio_options' => 'required_if:type,radio|array|min:2', 'radio_options.*' => 'required|string|max:150|not_regex:/,/', 'is_knockout' => 'nullable|boolean', 'knockout_answer' => ['nullable', 'required_if:is_knockout,1', \Illuminate\Validation\Rule::in((array) $this->input('radio_options', []))]];
            $rules['question'] = 'required|string|max:1000';
        }
        return $rules;
    }
}
