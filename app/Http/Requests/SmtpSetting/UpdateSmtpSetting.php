<?php

namespace App\Http\Requests\SmtpSetting;

use Froiden\LaravelInstaller\Request\CoreRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSmtpSetting extends CoreRequest
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
        return [
            'mail_driver' => config('saas.enabled') ? 'required|in:smtp' : 'required|in:mail,smtp',
            'mail_host' => 'required_if:mail_driver,smtp|nullable|string|max:255|regex:/^[a-zA-Z0-9.-]+$/',
            'mail_port' => 'required_if:mail_driver,smtp|nullable|integer|between:1,65535',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:8192',
            'mail_from_name' => 'required|string|max:255',
            'mail_from_email' => 'required|email|max:255',
            'mail_encryption' => 'nullable|in:ssl,tls,none,null'
        ];
    }
}
