<?php

namespace App\Http\Controllers\web\Manager;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Services\KYCService;
use App\Http\Resources\ClientResource;
use Illuminate\Http\Request;

class ManagerKYCController extends Controller
{
    public function __construct(
        private KYCService $kycService
    ) {}

    /**
     * Liste des demandes KYC en attente
     */
    public function pending(Request $request)
    {
        $user = auth()->user();

        $pendingKYC = Client::where('agency_id', $user->agency_id)
            ->where('kyc_status', 'pending')
            ->with(['documents', 'registeredBy'])
            ->when($request->get('search'), function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('client_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at')
            ->paginate($request->get('per_page', 15));

        return ClientResource::collection($pendingKYC);
    }

    /**
     * Détails d'un dossier KYC
     */
    public function show($clientId)
    {
        $user = auth()->user();

        $client = Client::where('agency_id', $user->agency_id)
            ->with(['documents', 'registeredBy', 'accounts'])
            ->findOrFail($clientId);

        $analysis = $this->kycService->analyzeKYCDocuments($clientId);

        return response()->json([
            'success' => true,
            'data' => [
                'client' => new ClientResource($client),
                'kyc_analysis' => $analysis,
                'required_documents' => $this->kycService->getRequiredDocuments(),
                'validation_checklist' => $this->kycService->getValidationChecklist()
            ]
        ]);
    }

    /**
     * Approuver un dossier KYC
     */
    public function approve(Request $request, $clientId)
    {
        $request->validate([
            'comment' => 'nullable|string|max:500'
        ]);

        try {
            $user = auth()->user();
            $client = Client::where('agency_id', $user->agency_id)->findOrFail($clientId);

            $result = $this->kycService->approveKYC(
                $clientId,
                $user->id,
                $request->get('comment')
            );

            return response()->json([
                'success' => true,
                'message' => 'KYC approuvé avec succès',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'approbation KYC',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rejeter un dossier KYC
     */
    public function reject(Request $request, $clientId)
    {
        $request->validate([
            'reasons' => 'required|array|min:1',
            'reasons.*' => 'string',
            'comment' => 'nullable|string|max:500'
        ]);

        try {
            $user = auth()->user();
            $client = Client::where('agency_id', $user->agency_id)->findOrFail($clientId);

            $result = $this->kycService->rejectKYC(
                $clientId,
                $user->id,
                $request->get('reasons'),
                $request->get('comment')
            );

            return response()->json([
                'success' => true,
                'message' => 'KYC rejeté avec succès',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet KYC',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Demander des informations complémentaires
     */
    public function requestInfo(Request $request, $clientId)
    {
        $request->validate([
            'requested_documents' => 'required|array|min:1',
            'requested_documents.*' => 'string',
            'message' => 'required|string|max:1000',
            'deadline' => 'nullable|date|after:today'
        ]);

        try {
            $user = auth()->user();
            $client = Client::where('agency_id', $user->agency_id)->findOrFail($clientId);

            $result = $this->kycService->requestAdditionalInfo(
                $clientId,
                $user->id,
                $request->get('requested_documents'),
                $request->get('message'),
                $request->get('deadline')
            );

            return response()->json([
                'success' => true,
                'message' => 'Demande d\'informations envoyée avec succès',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la demande d\'informations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques KYC de l'agence
     */
    public function statistics(Request $request)
    {
        $user = auth()->user();
        $period = $request->get('period', '30d');

        $stats = $this->kycService->getKYCStatistics($user->agency_id, $period);

        return response()->json([
            'success' => true,
            'data' => $loans,
            'permissions' => $this->dataService->getUserPermissions($user, 'loans')
        ]);
    }

    /**
     * Statistiques basées sur le rôle
     */
    public function roleBasedStats()
    {
        $user = auth()->user();
        $stats = $this->dataService->getRoleBasedStatistics($user);

        return response()->json([
            'success' => true,
            'data' => $stats,
            'scope' => $this->dataService->getUserScope($user)
        ]);
    }

    /**
     * Options de recherche disponibles
     */
    public function searchOptions()
    {
        $user = auth()->user();

        $options = [
            'agencies' => [],
            'client_statuses' => [
                ['value' => 'pending', 'label' => 'En attente'],
                ['value' => 'approved', 'label' => 'Approuvé'],
                ['value' => 'rejected', 'label' => 'Rejeté']
            ],
            'account_types' => [
                ['value' => 'savings', 'label' => 'Épargne'],
                ['value' => 'tontine', 'label' => 'Tontine']
            ],
            'transaction_types' => [
                ['value' => 'deposit', 'label' => 'Dépôt'],
                ['value' => 'withdrawal', 'label' => 'Retrait'],
                ['value' => 'transfer', 'label' => 'Transfert'],
                ['value' => 'fee', 'label' => 'Frais'],
                ['value' => 'interest', 'label' => 'Intérêts'],
                ['value' => 'penalty', 'label' => 'Pénalité'],
                ['value' => 'payout', 'label' => 'Redistribution']
            ],
            'loan_statuses' => [
                ['value' => 'pending', 'label' => 'En attente'],
                ['value' => 'under_review', 'label' => 'En cours d\'évaluation'],
                ['value' => 'approved', 'label' => 'Approuvé'],
                ['value' => 'rejected', 'label' => 'Rejeté'],
                ['value' => 'disbursed', 'label' => 'Décaissé'],
                ['value' => 'active', 'label' => 'Actif'],
                ['value' => 'completed', 'label' => 'Remboursé'],
                ['value' => 'defaulted', 'label' => 'En défaut']
            ],
            'payment_methods' => [
                ['value' => 'cash', 'label' => 'Espèces'],
                ['value' => 'mobile_money', 'label' => 'Mobile Money'],
                ['value' => 'bank_transfer', 'label' => 'Virement bancaire'],
                ['value' => 'system', 'label' => 'Système']
            ]
        ];

        // Ajouter les agences selon les permissions
        if (in_array($user->role, ['administrateur_systeme', 'administrateur_reglementaire'])) {
            $options['agencies'] = \App\Models\Agency::select('id', 'name', 'code')
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn($agency) => [
                    'value' => $agency->id,
                    'label' => $agency->name . ' (' . $agency->code . ')'
                ]);
        }

        return response()->json([
            'success' => true,
            'data' => $options
        ]);
    }

    /**
     * Données de configuration pour l'interface
     */
    public function appConfig()
    {
        $user = auth()->user();

        $config = [
            'user' => [
                'id' => $user->id,
                'name' => $user->full_name,
                'role' => $user->role,
                'agency_id' => $user->agency_id,
                'agency_name' => $user->agency->name ?? null,
                'permissions' => $this->getUserPermissionSummary($user)
            ],
            'app' => [
                'name' => config('app.name'),
                'version' => '1.0.0',
                'currency' => 'FCFA',
                'locale' => app()->getLocale(),
                'timezone' => config('app.timezone')
            ],
            'limits' => [
                'max_file_size' => '10MB',
                'max_loan_amount' => 5000000,
                'min_loan_amount' => 10000,
                'max_transaction_amount' => 10000000
            ],
            'features' => [
                'mobile_money_enabled' => true,
                'biometric_enabled' => true,
                'offline_mode_enabled' => true,
                'multi_language' => false
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $config
        ]);
    }

    /**
     * Données de référence (lookup data)
     */
    public function referenceData()
    {
        $data = [
            'countries' => [
                ['code' => 'TG', 'name' => 'Togo'],
                ['code' => 'BJ', 'name' => 'Bénin'],
                ['code' => 'BF', 'name' => 'Burkina Faso']
            ],
            'regions_togo' => [
                'Maritime', 'Plateaux', 'Centrale', 'Kara', 'Savanes'
            ],
            'id_types' => [
                ['value' => 'cni', 'label' => 'Carte Nationale d\'Identité'],
                ['value' => 'passport', 'label' => 'Passeport'],
                ['value' => 'driving_license', 'label' => 'Permis de Conduire'],
                ['value' => 'other', 'label' => 'Autre']
            ],
            'professions' => [
                'Commerçant', 'Agriculteur', 'Artisan', 'Fonctionnaire',
                'Enseignant', 'Chauffeur', 'Couturier/Couturière',
                'Coiffeur/Coiffeuse', 'Mécanicien', 'Maçon', 'Autre'
            ],
            'mobile_operators' => [
                ['code' => 'MTN', 'name' => 'MTN Togo', 'prefixes' => ['90', '91', '96', '97']],
                ['code' => 'MOOV', 'name' => 'Moov Togo', 'prefixes' => ['93', '94', '95', '98', '99']],
                ['code' => 'ORANGE', 'name' => 'Orange Togo', 'prefixes' => ['92']]
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    private function getUserPermissionSummary($user)
    {
        $permissions = $this->dataService->getUserPermissions($user, 'global');

        return [
            'can_create_clients' => $permissions['can_create'] ?? false,
            'can_approve_kyc' => in_array($user->role, ['gestionnaire_superviseur', 'gestionnaire_credit', 'administrateur_systeme', 'administrateur_reglementaire']),
            'can_approve_loans' => in_array($user->role, ['gestionnaire_credit', 'administrateur_systeme', 'administrateur_reglementaire']),
            'can_validate_transactions' => in_array($user->role, ['agent_agence', 'gestionnaire_superviseur', 'gestionnaire_credit', 'administrateur_systeme', 'administrateur_reglementaire']),
            'can_generate_reports' => in_array($user->role, ['gestionnaire_superviseur', 'gestionnaire_credit', 'administrateur_systeme', 'administrateur_reglementaire']),
            'can_manage_users' => in_array($user->role, ['administrateur_systeme', 'administrateur_reglementaire']),
            'can_configure_system' => $user->role === 'administrateur_systeme',
            'scope' => $permissions['scope'] ?? 'individual'
        ];
    }
}
