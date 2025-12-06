<?php

namespace App\Http\Controllers\web\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Services\ClientService;
use App\Http\Requests\UpdateClientProfileRequest;
use App\Http\Resources\ClientResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ClientProfileController extends Controller
{
    public function __construct(
        private ClientService $clientService
    ) {}

    /**
     * Afficher le profil du client
     */
    public function show()
    {
        $client = auth()->user();
        $client->load(['documents', 'agency', 'accounts']);

        $profileCompleteness = $this->calculateProfileCompleteness($client);

        return response()->json([
            'success' => true,
            'data' => [
                'client' => new ClientResource($client),
                'profile_completeness' => $profileCompleteness,
                'kyc_status' => [
                    'status' => $client->kyc_status,
                    'approved_at' => $client->kyc_approved_at,
                    'can_transact' => $client->kyc_status === 'approved'
                ]
            ]
        ]);
    }

    /**
     * Mettre à jour le profil du client
     */
    public function update(UpdateClientProfileRequest $request)
    {
        try {
            $client = auth()->user();

            // Vérifier si des champs critiques sont modifiés
            $criticalFields = ['phone', 'email', 'address'];
            $hasCriticalChanges = false;

            foreach ($criticalFields as $field) {
                if ($request->has($field) && $request->get($field) !== $client->$field) {
                    $hasCriticalChanges = true;
                    break;
                }
            }

            $updatedClient = $client->update($request->validated());

            // Si modifications critiques, remettre KYC en révision
            if ($hasCriticalChanges && $client->kyc_status === 'approved') {
                $client->update(['kyc_status' => 'pending']);

                // Notifier le client
                \App\Models\Notification::create([
                    'recipient_type' => 'client',
                    'recipient_id' => $client->id,
                    'notification_type' => 'kyc_update',
                    'title' => 'Profil mis à jour',
                    'message' => 'Votre profil a été mis à jour. Une révision de vos documents pourrait être nécessaire.',
                    'channel' => 'push',
                    'priority' => 'normal'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'data' => new ClientResource($client->fresh()),
                'requires_kyc_review' => $hasCriticalChanges
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du profil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload d'un document KYC
     */
    public function uploadDocument(Request $request)
    {
        $request->validate([
            'document_type' => 'required|in:id_front,id_back,photo,proof_address,proof_income,other',
            'file' => 'required|file|mimes:jpeg,png,pdf|max:5120', // 5MB max
            'description' => 'nullable|string|max:255'
        ]);

        try {
            $client = auth()->user();

            $file = $request->file('file');
            $documentType = $request->get('document_type');

            // Générer un nom unique pour le fichier
            $fileName = $client->client_number . '_' . $documentType . '_' . time() . '.' . $file->getClientOriginalExtension();

            // Upload vers S3 ou stockage local
            $path = $file->storeAs('documents/clients/' . $client->id, $fileName, 'public');

            // Créer l'enregistrement du document
            $document = ClientDocument::create([
                'client_id' => $client->id,
                'document_type' => $documentType,
                'file_url' => Storage::url($path),
                'file_name' => $fileName,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'status' => 'pending',
                'uploaded_by' => $client->id
            ]);

            // Si le client avait un KYC rejeté, le remettre en pending
            if ($client->kyc_status === 'rejected') {
                $client->update(['kyc_status' => 'pending']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Document uploadé avec succès',
                'data' => $document
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload du document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un document
     */
    public function deleteDocument($documentId)
    {
        try {
            $client = auth()->user();

            $document = ClientDocument::where('client_id', $client->id)
                ->where('status', '!=', 'approved') // Ne peut pas supprimer un document approuvé
                ->findOrFail($documentId);

            // Supprimer le fichier du stockage
            $filePath = str_replace('/storage/', '', parse_url($document->file_url, PHP_URL_PATH));
            Storage::disk('public')->delete($filePath);

            // Supprimer l'enregistrement
            $document->delete();

            return response()->json([
                'success' => true,
                'message' => 'Document supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statut du processus KYC
     */
    public function kycStatus()
    {
        $client = auth()->user();
        $client->load(['documents']);

        $requiredDocuments = ['id_front', 'id_back', 'photo'];
        $uploadedDocuments = $client->documents->pluck('document_type')->toArray();

        $missingDocuments = array_diff($requiredDocuments, $uploadedDocuments);
        $completeness = (count($requiredDocuments) - count($missingDocuments)) / count($requiredDocuments) * 100;

        $status = [
            'kyc_status' => $client->kyc_status,
            'status_label' => $this->getKYCStatusLabel($client->kyc_status),
            'completeness' => round($completeness, 0),
            'documents' => [
                'required' => $requiredDocuments,
                'uploaded' => $uploadedDocuments,
                'missing' => array_values($missingDocuments),
                'rejected' => $client->documents->where('status', 'rejected')->pluck('document_type')->toArray()
            ],
            'timeline' => [
                'submitted_at' => $client->created_at,
                'approved_at' => $client->kyc_approved_at,
                'estimated_review_time' => '2-3 jours ouvrables'
            ],
            'next_steps' => $this->getNextSteps($client)
        ];

        return response()->json([
            'success' => true,
            'data' => $status
        ]);
    }

    /**
     * Changer le mot de passe
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
            'new_password_confirmation' => 'required'
        ]);

        try {
            $client = auth()->user();

            if (!Hash::check($request->get('current_password'), $client->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mot de passe actuel incorrect'
                ], 422);
            }

            $client->update([
                'password' => Hash::make($request->get('new_password'))
            ]);

            // Logger le changement
            \App\Models\AuditLog::create([
                'user_id' => $client->id,
                'action' => 'PASSWORD_CHANGED',
                'entity_type' => 'client',
                'entity_id' => $client->id,
                'ip_address' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe modifié avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de mot de passe',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer/désactiver l'authentification biométrique
     */
    public function toggleBiometric(Request $request)
    {
        $request->validate([
            'enable' => 'required|boolean',
            'device_id' => 'required|string',
            'biometric_type' => 'nullable|in:fingerprint,face_id'
        ]);

        try {
            $client = auth()->user();

            // Enregistrer les paramètres biométriques
            $biometricData = [
                'enabled' => $request->get('enable'),
                'device_id' => $request->get('device_id'),
                'biometric_type' => $request->get('biometric_type', 'fingerprint'),
                'registered_at' => now()
            ];

            $client->update([
                'biometric_enabled' => $request->get('enable'),
                'biometric_data' => $biometricData
            ]);

            return response()->json([
                'success' => true,
                'message' => $request->get('enable') ? 'Biométrie activée' : 'Biométrie désactivée',
                'data' => [
                    'biometric_enabled' => $request->get('enable'),
                    'device_id' => $request->get('device_id')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la configuration biométrique',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir l'historique des modifications de profil
     */
    public function changeHistory(Request $request)
    {
        $client = auth()->user();

        $history = \App\Models\AuditLog::where('entity_type', 'client')
            ->where('entity_id', $client->id)
            ->whereIn('action', ['UPDATE_PROFILE', 'PASSWORD_CHANGED', 'DOCUMENT_UPLOADED'])
            ->orderBy('created_at', 'desc')
            ->limit($request->get('limit', 20))
            ->get()
            ->map(function($log) {
                return [
                    'action' => $log->action,
                    'date' => $log->created_at,
                    'ip_address' => $log->ip_address,
                    'changes' => $log->old_values && $log->new_values ?
                        $this->getChangedFields($log->old_values, $log->new_values) : []
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }

    private function calculateProfileCompleteness($client)
    {
        $fields = [
            'first_name', 'last_name', 'date_of_birth', 'gender',
            'phone', 'address', 'city', 'region', 'profession',
            'id_type', 'id_number'
        ];

        $completedFields = 0;
        foreach ($fields as $field) {
            if (!empty($client->$field)) {
                $completedFields++;
            }
        }

        $documentsUploaded = $client->documents->whereIn('document_type', ['id_front', 'id_back', 'photo'])->count();
        $documentsScore = ($documentsUploaded / 3) * 20; // 20% pour les documents

        $profileScore = ($completedFields / count($fields)) * 80; // 80% pour les champs

        return round($profileScore + $documentsScore, 0);
    }

    private function getKYCStatusLabel($status)
    {
        return match($status) {
            'pending' => 'En attente de validation',
            'approved' => 'Approuvé',
            'rejected' => 'Rejeté',
            'incomplete' => 'Incomplet',
            default => 'Inconnu'
        };
    }

    private function getNextSteps($client)
    {
        switch ($client->kyc_status) {
            case 'pending':
                return [
                    'Votre dossier est en cours de révision',
                    'Vous serez notifié dès que la validation sera terminée',
                    'Délai estimé: 2-3 jours ouvrables'
                ];
            case 'incomplete':
                return [
                    'Veuillez télécharger les documents manquants',
                    'Assurez-vous que vos documents sont lisibles',
                    'Soumettez votre dossier pour validation'
                ];
            case 'rejected':
                return [
                    'Votre dossier a été rejeté',
                    'Veuillez corriger les documents signalés',
                    'Resoumettez votre dossier après correction'
                ];
            case 'approved':
                return [
                    'Votre profil est validé',
                    'Vous pouvez maintenant effectuer toutes les opérations',
                    'Ouvrez vos comptes et commencez à épargner'
                ];
            default:
                return [];
        }
    }

    private function getChangedFields($oldValues, $newValues)
    {
        $changes = [];
        $oldData = json_decode($oldValues, true);
        $newData = json_decode($newValues, true);

        foreach ($newData as $key => $value) {
            if (isset($oldData[$key]) && $oldData[$key] !== $value) {
                $changes[] = [
                    'field' => $key,
                    'old_value' => $oldData[$key],
                    'new_value' => $value
                ];
            }
        }

        return $changes;
    }
}

