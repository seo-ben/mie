<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateClientRequest extends FormRequest
{
    public function authorize()
    {
        // Autoriser la soumission
        return true;
    }

    public function rules()
    {
        return [
            'first_name'       => 'required|string|max:255',
            'last_name'        => 'required|string|max:255',
            'phone'            => 'required|string|max:20|unique:clients,phone',
            'address'          => 'required|string|max:255',
            'email'            => 'nullable|email|unique:clients,email',
            'password'         => 'nullable|string|min:6|confirmed',
            'date_of_birth'    => 'nullable|date',
            'gender'           => 'nullable|in:M,F,Other',
            'profession'       => 'nullable|string|max:255',
            'city'             => 'nullable|string|max:255',
            'region'           => 'nullable|string|max:255',
            'monthly_income'   => 'nullable|numeric',
            'id_type'          => 'nullable|in:cni,passport,driving_license,other',
            'id_number'        => 'nullable|string|max:255',
            'id_expiry_date'   => 'nullable|date',
            'profile_photo'    => 'nullable|image|max:10048',
            'is_leader_or_elected' => 'sometimes|boolean',
        ];
    }

    public function messages()
    {
        return [
            'first_name.required'    => 'Le prénom est obligatoire.',
            'first_name.string'      => 'Le prénom doit être une chaîne de caractères.',
            'first_name.max'         => 'Le prénom ne peut pas dépasser 255 caractères.',

            'last_name.required'     => 'Le nom est obligatoire.',
            'last_name.string'       => 'Le nom doit être une chaîne de caractères.',
            'last_name.max'          => 'Le nom ne peut pas dépasser 255 caractères.',

            'phone.required'         => 'Le numéro de téléphone est obligatoire.',
            'phone.string'           => 'Le numéro de téléphone doit être une chaîne de caractères.',
            'phone.max'              => 'Le numéro de téléphone ne peut pas dépasser 20 caractères.',
            'phone.unique'           => 'Ce numéro de téléphone est déjà utilisé.',

            'email.email'            => 'L’adresse e-mail doit être une adresse valide.',
            'email.unique'           => 'Cette adresse e-mail est déjà utilisée.',

            'password.required'      => 'Le mot de passe est obligatoire.',
            'password.string'        => 'Le mot de passe doit être une chaîne de caractères.',
            'password.min'           => 'Le mot de passe doit contenir au moins 6 caractères.',
            'password.confirmed'     => 'La confirmation du mot de passe ne correspond pas.',

            'date_of_birth.required' => 'La date de naissance est obligatoire.',
            'date_of_birth.date'     => 'La date de naissance doit être une date valide.',

            'gender.required'        => 'Le genre est obligatoire.',
            'gender.in'              => 'Le genre doit être Masculin, Féminin ou Autre.',

            'profession.string'      => 'La profession doit être une chaîne de caractères.',
            'profession.max'         => 'La profession ne peut pas dépasser 255 caractères.',

            'address.required'       => 'L’adresse est obligatoire.',
            'address.string'         => 'L’adresse doit être une chaîne de caractères.',
            'address.max'            => 'L’adresse ne peut pas dépasser 255 caractères.',

            'city.string'            => 'La ville doit être une chaîne de caractères.',
            'city.max'               => 'La ville ne peut pas dépasser 255 caractères.',

            'region.string'          => 'La région doit être une chaîne de caractères.',
            'region.max'             => 'La région ne peut pas dépasser 255 caractères.',

            'monthly_income.numeric' => 'Le revenu mensuel doit être un nombre.',

            'id_type.in'             => 'Le type de pièce sélectionné n’est pas valide.',

            'id_number.string'       => 'Le numéro de pièce doit être une chaîne de caractères.',
            'id_number.max'          => 'Le numéro de pièce ne peut pas dépasser 255 caractères.',

            'id_expiry_date.date'    => 'La date d’expiration doit être une date valide.',

            'profile_photo.image'    => 'La photo de profil doit être une image.',
            'profile_photo.max'      => 'La photo de profil ne peut pas dépasser 2 Mo.',
        ];
    }
}
