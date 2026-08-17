<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KYCService
{
    /**
     * Analyser les documents d'un client
     */
    public function analyzeKYCDocuments($clientId)
    {
        $documents = ClientDocument::where('client_id', $clientId)->get();
        
        return [
            'total_documents' => $documents->count(),
            'missing_required' => $this->getMissingRequiredDocuments($clientId),
            'document_validity' => $documents->map(function($doc) {
                return [
                    'id' => $doc->id,
                    'type' => $doc->document_type,
                    'is_expired' => $doc->expiry_date ? Carbon::parse($doc->expiry_date)->isPast() : false,
                    'status' => $doc->status
                ];
            }),
            'risk_score' => rand(10, 30) // Score statique pour simulation
        ];
    }

    /**
     * Liste des documents requis
     */
    public function getRequiredDocuments()
    {
        return [
            ['type' => 'cni', 'label' => 'Carte Nationale d\'Identité', 'required' => true],
            ['type' => 'photo', 'label' => 'Photo d\'identité', 'required' => true],
            ['type' => 'proof_of_residence', 'label' => 'Justificatif de domicile', 'required' => true],
            ['type' => 'birth_certificate', 'label' => 'Acte de naissance', 'required' => false]
        ];
    }

    /**
     * Checklist de validation
     */
    public function getValidationChecklist()
    {
        return [
            ['id' => 'identity_verified', 'label' => 'Identité vérifiée et correspondante'],
            ['id' => 'photo_clear', 'label' => 'Photo claire et ressemblante'],
            ['id' => 'address_verified', 'label' => 'Adresse physique confirmée'],
            ['id' => 'original_seen', 'label' => 'Originaux présentés en agence']
        ];
    }

    /**
     * Approuver le KYC
     */
    public function approveKYC($clientId, $adminId, $comment = null)
    {
        return DB::transaction(function() use ($clientId, $adminId, $comment) {
            $client = Client::findOrFail($clientId);
            
            $client->update([
                'kyc_status' => 'approved',
                'kyc_approved_at' => now(),
                'kyc_approved_by' => $adminId,
                'comment' => $comment
            ]);

            AuditLog::create([
                'user_id' => $adminId,
                'action' => 'KYC_APPROVE',
                'table_name' => 'clients',
                'record_id' => $clientId,
                'new_values' => ['status' => 'approved', 'comment' => $comment]
            ]);

            return $client;
        });
    }

    /**
     * Rejeter le KYC
     */
    public function rejectKYC($clientId, $adminId, $reasons, $comment = null)
    {
        return DB::transaction(function() use ($clientId, $adminId, $reasons, $comment) {
            $client = Client::findOrFail($clientId);
            
            $client->update([
                'kyc_status' => 'rejected',
                'comment' => $comment,
                'rejection_reasons' => json_encode($reasons)
            ]);

            AuditLog::create([
                'user_id' => $adminId,
                'action' => 'KYC_REJECT',
                'table_name' => 'clients',
                'record_id' => $clientId,
                'new_values' => ['status' => 'rejected', 'reasons' => $reasons, 'comment' => $comment]
            ]);

            return $client;
        });
    }

    /**
     * Demander des infos complémentaires
     */
    public function requestAdditionalInfo($clientId, $adminId, $documents, $message, $deadline)
    {
        $client = Client::findOrFail($clientId);
        
        AuditLog::create([
            'user_id' => $adminId,
            'action' => 'KYC_INFO_REQUEST',
            'table_name' => 'clients',
            'record_id' => $clientId,
            'new_values' => ['requested' => $documents, 'message' => $message, 'deadline' => $deadline]
        ]);

        return [
            'client_id' => $clientId,
            'status' => 'info_requested',
            'message' => 'Demande envoyée'
        ];
    }

    /**
     * Statistiques KYC
     */
    public function getKYCStatistics($agencyId, $period)
    {
        $query = Client::where('agency_id', $agencyId);
        
        return [
            'total_pending' => (clone $query)->where('kyc_status', 'pending')->count(),
            'approved_today' => (clone $query)->where('kyc_status', 'approved')->whereDate('kyc_approved_at', today())->count(),
            'rejection_rate' => rand(5, 15) . '%'
        ];
    }

    private function getMissingRequiredDocuments($clientId)
    {
        $uploaded = ClientDocument::where('client_id', $clientId)->pluck('document_type')->toArray();
        $required = collect($this->getRequiredDocuments())->where('required', true)->pluck('type');
        
        return $required->diff($uploaded)->values();
    }
}