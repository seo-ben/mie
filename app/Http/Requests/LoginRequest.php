<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Changed to true since this is a public endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:100'],
            'password' => ['required', 'string', 'min:6'],
            'device_name' => ['sometimes', 'string', 'max:255'],
            'remember' => ['sometimes', 'boolean'],
            'platform' => ['sometimes', 'string', 'in:web,mobile,pos'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'L\'adresse email est requise',
            'email.email' => 'Veuillez entrer une adresse email valide',
            'password.required' => 'Le mot de passe est requis',
            'password.min' => 'Le mot de passe doit contenir au moins 6 caractères',
            'platform.in' => 'La plateforme doit être web, mobile ou pos',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if ($this->device_name === null) {
            $this->merge([
                'device_name' => $this->userAgent()
            ]);
        }
    }

    /**
     * Get sanitized input data after validation.
     *
     * @return array
     */
    public function sanitized(): array
    {
        $sanitized = $this->validated();
        
        // Remove sensitive data
        if (isset($sanitized['password'])) {
            unset($sanitized['password']);
        }

        return $sanitized;
    }
}
