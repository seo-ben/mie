<?php
namespace App\Services;

use App\Models\Client;
use App\Models\Loan;
use App\Models\Transaction;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminGlobalService
{
    public function getClientCompleteSummary($clientId)
    {
        $client = Client::with(['accounts', 'loans'])->findOrFail($clientId);
        
        return [
            'registration_date' => $client->created_at,
            'kyc_completion_date' => $client->kyc_approved_at,
            'total_deposits' => $client->accounts->sum('balance'),
            'total_loans_requested' => $client->loans->sum('requested_amount'),
            'total_loans_approved' => $client->loans->whereIn('status', ['approved', 'disbursed', 'active', 'completed'])->sum('approved_amount'),
            'current_outstanding' => $client->loans->whereIn('status', ['active', 'disbursed'])->sum('outstanding_principal'),
            'total_repaid' => $client->loans->sum('total_paid'),
            'credit_score_history' => $this->getCreditScoreHistory($clientId),
            'account_activity_level' => $this->calculateActivityLevel($clientId)
        ];
    }

    public function getClientTimeline($clientId)
    {
        $timeline = collect();

        // Événements client
        $client = Client::findOrFail($clientId);
        $timeline->push([
            'date' => $client->created_at,
            'type' => 'registration',
            'title' => 'Inscription client',
            'description' => 'Client enregistré dans le système'
        ]);

        if ($client->kyc_approved_at) {
            $timeline->push([
                'date' => $client->kyc_approved_at,
                'type' => 'kyc_approval',
                'title' => 'KYC approuvé',
                'description' => 'Documents validés'
            ]);
        }

        // Événements comptes
        $client->accounts->each(function($account) use ($timeline) {
            $timeline->push([
                'date' => $account->created_at,
                'type' => 'account_created',
                'title' => 'Compte créé',
                'description' => ucfirst($account->account_type) . ' - ' . $account->account_number
            ]);

            if ($account->activated_at) {
                $timeline->push([
                    'date' => $account->activated_at,
                    'type' => 'account_activated',
                    'title' => 'Compte activé',
                    'description' => ucfirst($account->account_type) . ' - ' . $account->account_number
                ]);
            }
        });

        // Événements prêts
        $client->loans->each(function($loan) use ($timeline) {
            $timeline->push([
                'date' => $loan->application_date,
                'type' => 'loan_application',
                'title' => 'Demande de prêt',
                'description' => number_format($loan->requested_amount, 0) . ' FCFA'
            ]);

            if ($loan->approved_at) {
                $timeline->push([
                    'date' => $loan->approved_at,
                    'type' => 'loan_approved',
                    'title' => 'Prêt approuvé',
                    'description' => number_format($loan->approved_amount, 0) . ' FCFA'
                ]);
            }

            if ($loan->disbursed_at) {
                $timeline->push([
                    'date' => $loan->disbursed_at,
                    'type' => 'loan_disbursed',
                    'title' => 'Prêt décaissé',
                    'description' => number_format($loan->approved_amount, 0) . ' FCFA'
                ]);
            }
        });

        return $timeline->sortBy('date')->values();
    }

    public function forceApproveLoan($loanId, $approvedAmount, $reason, $adminId)
    {
        DB::beginTransaction();
        try {
            $loan = Loan::findOrFail($loanId);
            
            // Vérifier que le prêt peut être approuvé
            if (!in_array($loan->status, ['pending', 'under_review', 'rejected'])) {
                return ['success' => false, 'message' => 'Ce prêt ne peut plus être approuvé'];
            }

            // Approuver avec les pouvoirs admin
            $loan->update([
                'status' => 'approved',
                'approved_amount' => $approvedAmount,
                'approved_by' => $adminId,
                'approved_at' => now(),
                'outstanding_principal' => $approvedAmount,
                'first_payment_date' => Carbon::now()->addMonth()->startOfMonth(),
                'maturity_date' => Carbon::now()->addMonths($loan->duration_months)
            ]);

            // Logger l'action administrative
            AuditLog::create([
                'user_id' => $adminId,
                'action' => 'FORCE_LOAN_APPROVAL',
                'entity_type' => 'loan',
                'entity_id' => $loanId,
                'additional_data' => [
                    'reason' => $reason,
                    'original_status' => $loan->getOriginal('status'),
                    'approved_amount' => $approvedAmount,
                    'bypass_eligibility' => true
                ]
            ]);

            // Générer l'échéancier
            app(\App\Services\LoanService::class)->generateLoanPayments($loan);

            DB::commit();
            return [
                'success' => true, 
                'message' => 'Prêt approuvé par l\'administrateur',
                'data' => $loan
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'approbation: ' . $e->getMessage()
            ];
        }
    }

    public function reverseTransaction($transactionId, $reason, $adminId)
    {
        DB::beginTransaction();
        try {
            $transaction = Transaction::findOrFail($transactionId);
            $account = $transaction->account;

            // Créer la transaction d'annulation
            $reversalTransaction = Transaction::create([
                'account_id' => $account->id,
                'transaction_type' => 'reversal',
                'amount' => -$transaction->amount, // Montant négatif
                'balance_before' => $account->balance,
                'balance_after' => $account->balance - $transaction->amount,
                'payment_method' => 'system',
                'description' => "Annulation transaction #{$transaction->id} - Raison: {$reason}",
                'status' => 'completed',
                'processed_by' => $adminId,
                'transaction_date' => now()
            ]);

            // Mettre à jour le solde du compte
            $account->update([
                'balance' => $account->balance - $transaction->amount
            ]);

            // Marquer la transaction originale comme annulée
            $transaction->update(['status' => 'reversed']);

            // Logger l'action
            AuditLog::create([
                'user_id' => $adminId,
                'action' => 'REVERSE_TRANSACTION',
                'entity_type' => 'transaction',
                'entity_id' => $transactionId,
                'additional_data' => [
                    'reason' => $reason,
                    'original_amount' => $transaction->amount,
                    'reversal_transaction_id' => $reversalTransaction->id
                ]
            ]);

            DB::commit();
            return [
                'success' => true,
                'message' => 'Transaction annulée avec succès'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'annulation: ' . $e->getMessage()
            ];
        }
    }

    public function detectSuspiciousTransactions()
    {
        $suspicious = [];

        // Transactions avec montants inhabituels (>500k en une fois)
        $highAmountTransactions = Transaction::where('amount', '>', 500000)
            ->where('created_at', '>', Carbon::now()->subDays(7))
            ->with('account.client')
            ->get();

        foreach ($highAmountTransactions as $transaction) {
            $suspicious[] = [
                'type' => 'high_amount',
                'transaction_id' => $transaction->id,
                'client_name' => $transaction->account->client->full_name,
                'amount' => $transaction->amount,
                'date' => $transaction->transaction_date,
                'risk_level' => 'medium'
            ];
        }

        // Plusieurs transactions rapprochées
        $rapidTransactions = DB::select("
            SELECT account_id, COUNT(*) as count, MAX(amount) as max_amount
            FROM transactions 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            AND status = 'completed'
            GROUP BY account_id 
            HAVING count > 5
        ");

        foreach ($rapidTransactions as $rapid) {
            $account = \App\Models\Account::with('client')->find($rapid->account_id);
            $suspicious[] = [
                'type' => 'rapid_transactions',
                'account_id' => $rapid->account_id,
                'client_name' => $account->client->full_name,
                'transaction_count' => $rapid->count,
                'max_amount' => $rapid->max_amount,
                'risk_level' => 'high'
            ];
        }

        return $suspicious;
    }

    public function calculateGlobalDefaultRate()
    {
        $totalLoans = Loan::whereIn('status', ['active', 'completed', 'defaulted'])->count();
        $defaultedLoans = Loan::where('status', 'defaulted')->count();

        return $totalLoans > 0 ? round(($defaultedLoans / $totalLoans) * 100, 2) : 0;
    }

    private function getCreditScoreHistory($clientId)
    {
        // Simuler un historique - dans un vrai système, on aurait une table credit_score_history
        return [
            ['date' => '2024-01-01', 'score' => 60],
            ['date' => '2024-06-01', 'score' => 75],
            ['date' => '2024-12-01', 'score' => 80],
        ];
    }

    private function calculateActivityLevel($clientId)
    {
        $transactionCount = Transaction::join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->where('accounts.client_id', $clientId)
            ->where('transactions.created_at', '>', Carbon::now()->subMonths(3))
            ->count();

        if ($transactionCount > 20) return 'high';
        if ($transactionCount > 10) return 'medium';
        return 'low';
    }
}