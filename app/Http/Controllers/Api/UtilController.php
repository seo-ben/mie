<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UtilController extends Controller
{

    /**
     * Opérateurs Mobile Money disponibles
     */
    public function mobileMoneyOperators()
    {
        $operators = $this->utilityService->getMobileMoneyOperators();

        return response()->json([
            'success' => true,
            'data' => $operators
        ]);
    }

    /**
     * Vérifier un paiement Mobile Money
     */
    public function verifyMobileMoneyPayment(Request $request)
    {
        $request->validate([
            'operator' => 'required|in:MTN,Orange,Moov',
            'phone_number' => 'required|string',
            'transaction_reference' => 'required|string',
            'amount' => 'required|numeric|min:1'
        ]);

        try {
            $result = $this->utilityService->verifyMobileMoneyPayment(
                $request->get('operator'),
                $request->get('phone_number'),
                $request->get('transaction_reference'),
                $request->get('amount')
            );

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Paramètres système accessibles
     */
    public function systemParameters()
    {
        $user = auth()->user();
        $parameters = $this->utilityService->getAccessibleParameters($user->role);

        return response()->json([
            'success' => true,
            'data' => $parameters
        ]);
    }

    /**
     * Permissions de l'utilisateur connecté
     */
    public function myPermissions()
    {
        $user = auth()->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'role' => $user->role,
                    'agency' => $user->agency->name ?? null
                ],
                'scope' => $this->dataService->getUserScope($user),
                'permissions' => [
                    'clients' => $this->dataService->getUserPermissions($user, 'clients'),
                    'accounts' => $this->dataService->getUserPermissions($user, 'accounts'),
                    'transactions' => $this->dataService->getUserPermissions($user, 'transactions'),
                    'loans' => $this->dataService->getUserPermissions($user, 'loans')
                ],
                'can_impersonate' => in_array($user->role, ['administrateur_systeme', 'administrateur_reglementaire']),
                'accessible_roles' => $this->getAccessibleRoles($user->role)
            ]
        ]);
    }

    /**
     * Valider un numéro de téléphone
     */
    public function validatePhoneNumber(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string'
        ]);

        $validation = $this->utilityService->validateTogoPhoneNumber($request->get('phone_number'));

        return response()->json([
            'success' => true,
            'data' => $validation
        ]);
    }

    /**
     * Calculateur de frais
     */
    public function calculateFees(Request $request)
    {
        $request->validate([
            'service_type' => 'required|in:account_activation,loan,transaction',
            'amount' => 'required|numeric|min:0',
            'account_type' => 'required_if:service_type,account_activation|in:savings,tontine',
            'tontine_amount' => 'required_if:account_type,tontine|numeric'
        ]);

        try {
            $fees = $this->utilityService->calculateFees(
                $request->get('service_type'),
                $request->get('amount'),
                $request->only(['account_type', 'tontine_amount'])
            );

            return response()->json([
                'success' => true,
                'data' => $fees
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de calcul',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convertisseur de devises (futur)
     */
    public function currencyConverter(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3'
        ]);

        // Pour l'instant, principalement FCFA
        $conversion = $this->utilityService->convertCurrency(
            $request->get('amount'),
            $request->get('from_currency'),
            $request->get('to_currency')
        );

        return response()->json([
            'success' => true,
            'data' => $conversion
        ]);
    }

    /**
     * Générateur de QR Code
     */
    public function generateQRCode(Request $request)
    {
        $request->validate([
            'data' => 'required|string',
            'type' => 'required|in:payment,client_info,transaction_receipt',
            'size' => 'nullable|integer|min:100|max:500'
        ]);

        try {
            $qrCode = $this->utilityService->generateQRCode(
                $request->get('data'),
                $request->get('type'),
                $request->get('size', 200)
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'qr_code_url' => $qrCode['url'],
                    'qr_code_base64' => $qrCode['base64']
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du QR Code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Information sur l'état du système
     */
    public function systemStatus()
    {
        $status = $this->utilityService->getSystemStatus();

        return response()->json([
            'success' => true,
            'data' => $status
        ]);
    }

    /**
     * Fuseaux horaires supportés
     */
    public function timezones()
    {
        $timezones = [
            'Africa/Lome' => 'Lomé (GMT+0)',
            'Africa/Accra' => 'Accra (GMT+0)',
            'Africa/Lagos' => 'Lagos (GMT+1)',
            'UTC' => 'UTC (GMT+0)'
        ];

        return response()->json([
            'success' => true,
            'data' => $timezones
        ]);
    }

    private function getAccessibleRoles($userRole)
    {
        $hierarchy = [
            'administrateur_systeme' => ['administrateur_reglementaire', 'gestionnaire_superviseur', 'gestionnaire_credit', 'agent_terrain', 'agent_agence'],
            'administrateur_reglementaire' => ['gestionnaire_superviseur', 'gestionnaire_credit', 'agent_terrain', 'agent_agence'],
            'gestionnaire_superviseur' => ['gestionnaire_credit', 'agent_terrain', 'agent_agence'],
            'gestionnaire_credit' => ['agent_terrain', 'agent_agence'],
            'agent_terrain' => [],
            'agent_agence' => []
        ];

        return $hierarchy[$userRole] ?? [];
    }
}