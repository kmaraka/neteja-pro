<?php

namespace App\Modules\Ecommerce\Http\Requests;

use App\Modules\Core\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Le téléphone est normalisé (+225...) avant la validation.
    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge(['phone' => Phone::normalize((string) $this->input('phone'))]);
        }
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^\+\d{8,15}$/'],
            'commune' => ['required', 'string', Rule::exists('delivery_zones', 'commune')->where('is_active', true)],
            'address_line' => ['required', 'string', 'max:255'],
            'additional_info' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return ['commune.exists' => 'Nous ne livrons pas encore dans cette commune.'];
    }
}
