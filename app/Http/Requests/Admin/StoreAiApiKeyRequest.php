<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAiApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->cans('manage_settings');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'model' => ['nullable', 'string', 'max:191', 'regex:/\A[a-zA-Z0-9._:\/-]+\z/'],
            'provider' => ['nullable', 'string', 'max:191'],
            'api_key' => ['required', 'string', 'max:8192'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ];
    }
}
