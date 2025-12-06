<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
{
    public function authorize()
    {
        // Autoriser l'accès selon ton middleware / politique
        return true;
    }

    public function rules()
    {
        return [
            'first_name'       => 'required|string|max:255',
            'last_name'        => 'required|string|max:255',
            'phone'            => 'required|string|max:20',
            'date_of_birth'    => 'required|date',
            'gender'           => 'required|in:M,F,Other',
            'profession'       => 'nullable|string|max:100',
            'address'          => 'nullable|string|max:255',
            'city'             => 'required|string|max:100',
            'region'           => 'nullable|string|max:100',
            'monthly_income'   => 'nullable|numeric|min:0',
            'id_type'          => 'nullable|in:cni,passport,driving_license,other',
            'id_number'        => 'nullable|string|max:50',
            'id_expiry_date'   => 'nullable|date',
            'profile_photo'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10048',
            'is_active'        => 'sometimes|boolean',
        ];
    }
}
