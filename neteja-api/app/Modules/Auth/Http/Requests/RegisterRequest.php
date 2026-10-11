<?php

namespace App\Modules\Auth\Http\Requests;

use App\Modules\Core\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // On nettoie les données AVANT la validation (l'unicité porte sur la valeur nettoyée).
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim((string) $this->input('email'))),
            'phone' => $this->filled('phone') ? Phone::normalize($this->input('phone')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'regex:/^\+\d{8,15}$/', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'accept_terms' => ['accepted'],
        ];
    }
}
