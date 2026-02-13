<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    public function rules(): array
    {
        $passwordRule = Password::defaults();

        if (config('auth.api.password_uncompromised_check', true)) {
            $passwordRule = $passwordRule->uncompromised(config('auth.api.uncompromised_threshold', 1));
        }

        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', $passwordRule],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    // Normalize email before validation to ensure consistent formatting
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim($this->email ?? '')),
        ]);
    }
}
