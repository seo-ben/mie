<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_number' => $this->client_number,
            'title' => $this->title,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->getFullName(),
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'birth_place' => $this->birth_place,
            'nationality' => $this->nationality,
            'id_type' => $this->id_type,
            'id_number' => $this->id_number,
            'id_issue_date' => $this->id_issue_date?->format('Y-m-d'),
            'id_expiry_date' => $this->id_expiry_date?->format('Y-m-d'),
            'id_issue_place' => $this->id_issue_place,
            'phone' => $this->phone,
            'email' => $this->email,
            'profession' => $this->profession,
            'employer' => $this->employer,
            'monthly_income' => $this->monthly_income,
            'marital_status' => $this->marital_status,
            'spouse_name' => $this->spouse_name,
            'dependents_count' => $this->dependents_count,

            // Adresse
            'address' => [
                'street' => $this->street,
                'city' => $this->city,
                'state' => $this->state,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
                'full_address' => $this->getFullAddress(),
            ],

            // Informations de contact d'urgence
            'emergency_contact' => [
                'name' => $this->emergency_contact_name,
                'phone' => $this->emergency_contact_phone,
                'relationship' => $this->emergency_contact_relationship,
            ],

            // Statuts
            'kyc_status' => $this->kyc_status,
            'registration_status' => $this->registration_status,
            'registration_channel' => $this->registration_channel,
            'status' => $this->status,

            // Photos et documents
            'profile_photo_url' => $this->profile_photo_url,
            'id_front_url' => $this->id_front_url,
            'id_back_url' => $this->id_back_url,
            'signature_url' => $this->signature_url,

            // Relations
            'agency' => $this->whenLoaded('agency', function () {
                return [
                    'id' => $this->agency->id,
                    'name' => $this->agency->name,
                    'code' => $this->agency->code,
                ];
            }),

            'registered_by' => $this->whenLoaded('registeredBy', function () {
                return [
                    'id' => $this->registeredBy->id,
                    'name' => $this->registeredBy->name,
                    'email' => $this->registeredBy->email,
                ];
            }),

            'approved_by' => $this->whenLoaded('approvedBy', function () {
                return $this->approvedBy ? [
                    'id' => $this->approvedBy->id,
                    'name' => $this->approvedBy->name,
                    'email' => $this->approvedBy->email,
                ] : null;
            }),

            // Comptes
            'accounts' => AccountResource::collection($this->whenLoaded('accounts')),
            'accounts_count' => $this->whenLoaded('accounts', function () {
                return $this->accounts->count();
            }, $this->accounts_count ?? 0),

            // Prêts
            'loans' => $this->whenLoaded('loans'),
            'loans_count' => $this->whenLoaded('loans', function () {
                return $this->loans->count();
            }, $this->loans_count ?? 0),

            // Documents
            'documents' => $this->whenLoaded('documents'),
            'documents_count' => $this->whenLoaded('documents', function () {
                return $this->documents->count();
            }, $this->documents_count ?? 0),

            // Dates importantes
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'registered_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'kyc_verified_at' => $this->kyc_verified_at?->format('Y-m-d H:i:s'),
            'activated_at' => $this->activated_at?->format('Y-m-d H:i:s'),

            // Métadonnées calculées
            'age' => $this->date_of_birth ? now()->diffInYears($this->date_of_birth) : null,
            'is_kyc_approved' => $this->kyc_status === 'approved',
            'is_active' => $this->status === 'active',
            'has_pending_accounts' => $this->whenLoaded('accounts', function () {
                return $this->accounts->where('status', 'suspended')->count() > 0;
            }, false),
        ];
    }

    /**
     * Customize the outgoing response for the resource.
     *
     * @param Request $request
     * @param \Illuminate\Http\JsonResponse $response
     * @return void
     */
    public function withResponse(Request $request, $response)
    {
        $response->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param Request $request
     * @return array
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => '1.0',
                'api_version' => 'v1',
            ],
        ];
    }
}
