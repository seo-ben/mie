<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ClientRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => 'required|string|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone' => 'required|string|unique:users,phone',
            'profile' => 'required|array',
            'profile.date_of_birth' => 'required|date',
            'profile.gender' => 'required|in:M,F',
            'profile.address' => 'required|string',
            'profile.city' => 'required|string',
            'profile.region' => 'required|string',
            'device_info' => 'required|array',
            'device_info.device_id' => 'required|string',
            'device_info.platform' => 'required|in:android,ios'
        ];
    }
}