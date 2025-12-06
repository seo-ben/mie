<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Loan;
use App\Models\Transaction;
use Illuminate\Http\Request;

class AdminGlobalController extends Controller
{

    /**
     * Accès à TOUS les clients (toutes agences)
     */
    public function allClients(Request $request)
    {
        $clients = Client::with(['agency', 'accounts', 'loans'])
            ->when($request->get('search'), function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('client_number', 'like', "%{$search}%");
                });
            })
            ->when($request->get('agency_id'), function($query, $agencyId) {
                $query->where('agency_id', $agencyId);
            })
            ->when($request->get('kyc_status'), function($query, $status) {
                $query->where('kyc_status', $status);
            })
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $clients,
            'meta' => [
                'total_clients' => Client::count(),
                'kyc_pending' => Client::where('kyc_status', 'pending')->count(),
                'active_clients' => Client::where('is_active', true)->count()
            ]
        ]);
    }

    /**
     * Détail complet d'un client avec historique
     */
    public function clientDetail($clientId)
    {
        $client = Client::with([
            'agency', 'accounts.transactions', 'loans.payments', 
            'documents', 'registeredBy'
        ])->findOrFail($clientId);

        return response()->json([
            'success' => true,
            'data' => [
                'client' => $client,
                'summary' => $this->globalService->getClientCompleteSummary($clientId),
                'timeline' => $this->globalService->getClientTimeline($clientId),
                'risk_assessment' => $this->globalService->assessClientRisk($clientId)
            ]
        ]);
    }

    /**
     * Forcer l'approbation KYC (pouvoir admin)
     */
    public function forceKycApprove(Request $request, $clientId)
    {
        $client = Client::findOrFail($clientId);
        
        $client->update([
            'kyc_status' => 'approved',
            'kyc_approved_at' => now(),
            'kyc_approved_by' => auth()->id()
        ]);

        // Log de l'action admin
        \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'FORCE_KYC_APPROVAL',
            'entity_type' => 'client',
            'entity_id' => $clientId,
            'additional_data' => [
                'reason' => $request->get('reason'),
                'previous_status' => $client->getOriginal('kyc_status')
            ]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'KYC approuvé par l\'administrateur',
            'data' => $client
        ]);
    }

    /**
     * Accès à TOUS les prêts (toutes agences)
     */
    public function allLoans(Request $request)
    {
        $loans = Loan::with(['client.agency'])
            ->when($request->get('status'), function($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->get('risk_level'), function($query, $risk) {
                $query->where('risk_level', $risk);
            })
            ->when($request->get('overdue_only'), function($query) {
                $query->where('days_overdue', '>', 0);
            })
            ->orderBy('application_date', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $loans,
            'meta' => [
                'total_portfolio' => Loan::whereIn('status', ['active', 'disbursed'])->sum('outstanding_principal'),
                'overdue_loans' => Loan::where('days_overdue', '>', 0)->count(),
                'default_rate' => $this->globalService->calculateGlobalDefaultRate()
            ]
        ]);
    }

    /**
     * Forcer l'approbation d'un prêt (cas d'urgence)
     */
    public function forceApproveLoan(Request $request, $loanId)
    {
        $loan = Loan::findOrFail($loanId);
        
        $result = $this->globalService->forceApproveLoan(
            $loanId, 
            $request->get('approved_amount'),
            $request->get('reason'),
            auth()->id()
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'] ?? null
        ]);
    }

    /**
     * Annuler/inverser une transaction (pouvoir admin)
     */
    public function reverseTransaction(Request $request, $transactionId)
    {
        $transaction = Transaction::findOrFail($transactionId);
        
        if ($transaction->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Seules les transactions complétées peuvent être annulées'
            ], 400);
        }

        $result = $this->globalService->reverseTransaction(
            $transactionId,
            $request->get('reason'),
            auth()->id()
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message']
        ]);
    }

    /**
     * Transactions suspectes détectées
     */
    public function suspiciousTransactions()
    {
        $suspiciousTransactions = $this->globalService->detectSuspiciousTransactions();

        return response()->json([
            'success' => true,
            'data' => $suspiciousTransactions
        ]);
    }
}
