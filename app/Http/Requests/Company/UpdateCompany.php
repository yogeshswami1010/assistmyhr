<?php

namespace App\Http\Requests\Company;

use App\Http\Requests\CoreRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompany extends CoreRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user()->cans('manage_settings');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'company_name' => 'required',
            'company_email' => 'required|email|regex:/(.*)\./i',
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'candidate_calls_enabled' => ['sometimes', 'boolean'],
            'telnyx_voice_credential_id' => ['required_if:candidate_calls_enabled,1', 'nullable', 'string', 'max:191', 'regex:/^[A-Za-z0-9_-]+$/'],
            'telnyx_voice_from_number' => ['required_if:candidate_calls_enabled,1', 'nullable', 'string', 'regex:/^\+[1-9][0-9]{7,14}$/'],
        ];
    }
}
