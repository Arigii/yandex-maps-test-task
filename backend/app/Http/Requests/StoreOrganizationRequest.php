<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'url', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'url.required' => 'Вставьте ссылку на карточку организации.',
            'url.url' => 'Это не похоже на корректную ссылку.',
        ];
    }
}
