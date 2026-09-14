<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'yandex_url' => [
                'required',
                'string',
                'url',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'yandex_url.required' => 'Yandex Maps URL is required.',
            'yandex_url.string' => 'Yandex Maps URL must be a string.',
            'yandex_url.url' => 'Yandex Maps URL must be a valid URL.',
            'yandex_url.max' => 'Yandex Maps URL is too long.',
        ];
    }
}
