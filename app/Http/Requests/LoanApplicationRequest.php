<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoanApplicationRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'requested_amount' => ['required', 'numeric', 'min:10000', 'max:5000000'],
            'duration_months' => ['required', 'integer', 'min:6', 'max:24'],
            'purpose' => ['required', 'string', 'max:500'],
            'collateral_description' => ['nullable', 'string', 'max:1000'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['file', 'mimes:jpeg,png,pdf', 'max:2048']
        ];
    }

    public function messages()
    {
        return [
            'requested_amount.required' => 'Le montant demandé est obligatoire',
            'requested_amount.min' => 'Le montant minimum est de 10 000 FCFA',
            'requested_amount.max' => 'Le montant maximum est de 5 000 000 FCFA',
            'duration_months.required' => 'La durée est obligatoire',
            'duration_months.min' => 'Durée minimum : 6 mois',
            'duration_months.max' => 'Durée maximum : 24 mois',
            'purpose.required' => 'L\'objet du prêt est obligatoire'
        ];
    }
}