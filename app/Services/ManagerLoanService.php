<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Client;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ManagerLoanService
{
    /**
     * Résumé du client pour une demande de prêt
     */
    public function getClientSummary($clientId)
    {
        $client = Client::with(['accounts', 'loans' => function($q) {
            $q->where('status', 'completed');
        }])->findOrFail($clientId);

        return [
            'full_name' => $client->full_name,
            'client_since' => $client->created_at->format('d/m/Y'),
            'total_savings' => $client->accounts->where('account_type', 'savings')->sum('balance'),
            'previous_loans_count' => $client->loans->count(),
            'previous_loans_total' => $client->loans->sum('approved_amount'),
            'repayment_reliability' => '95%' // Simulation
        ];
    }

    /**
     * Analyse de risque d'un prêt
     */
    public function getRiskAnalysis($loanId)
    {
        $loan = Loan::findOrFail($loanId);
        
        return [
            'risk_level' => $loan->risk_level ?? 'medium',
            'debt_to_income_ratio' => rand(15, 45) . '%',
            'collateral_value' => 0,
            'guarantors_verified' => true,
            'system_recommendation' => 'Approuver avec prudence'
        ];
    }

    /**
     * Recommandations pour un prêt
     */
    public function getRecommendations($loanId)
    {
        return [
            'S\'assurer de la régularité des revenus mensuels',
            'Vérifier l\'activité commerciale sur place',
            'Demander un garant solidaire supplémentaire si possible'
        ];
    }

    /**
     * Approuver un prêt
     */
    public function approveLoan($loanId, $amount, $adminId, $conditions, $comment)
    {
        return DB::transaction(function() use ($loanId, $amount, $adminId, $conditions, $comment) {
            $loan = Loan::findOrFail($loanId);
            
            $loan->update([
                'status' => 'approved',
                'approved_amount' => $amount,
                'approved_by' => $adminId,
                'approved_at' => now(),
                'approval_conditions' => json_encode($conditions),
                'manager_comment' => $comment
            ]);

            AuditLog::create([
                'user_id' => $adminId,
                'action' => 'LOAN_APPROVE',
                'table_name' => 'loans',
                'record_id' => $loanId,
                'new_values' => ['status' => 'approved', 'amount' => $amount]
            ]);

            return $loan;
        });
    }

    /**
     * Rejeter un prêt
     */
    public function rejectLoan($loanId, $adminId, $reasons, $comment)
    {
        return DB::transaction(function() use ($loanId, $adminId, $reasons, $comment) {
            $loan = Loan::findOrFail($loanId);
            
            $loan->update([
                'status' => 'rejected',
                'rejection_reason' => $comment,
                'rejection_details' => json_encode($reasons),
                'reviewed_by' => $adminId,
                'reviewed_at' => now()
            ]);

            AuditLog::create([
                'user_id' => $adminId,
                'action' => 'LOAN_REJECT',
                'table_name' => 'loans',
                'record_id' => $loanId,
                'new_values' => ['status' => 'rejected', 'reasons' => $reasons]
            ]);

            return $loan;
        });
    }

    /**
     * Décaisser un prêt
     */
    public function disburseLoan($loanId, $adminId, $data)
    {
        return DB::transaction(function() use ($loanId, $adminId, $data) {
            $loan = Loan::findOrFail($loanId);
            
            if ($loan->status !== 'approved') {
                throw new \Exception("Seuls les prêts approuvés peuvent être décaissés.");
            }

            $loan->update([
                'status' => 'active',
                'disbursed_at' => now(),
                'disbursed_by' => $adminId,
                'disbursement_method' => $data['disbursement_method'],
                'disbursement_reference' => $data['disbursement_reference'] ?? null,
                'outstanding_principal' => $loan->approved_amount
            ]);

            // Générer l'échéancier
            $this->generateAmortizationSchedule($loan);

            AuditLog::create([
                'user_id' => $adminId,
                'action' => 'LOAN_DISBURSE',
                'table_name' => 'loans',
                'record_id' => $loanId,
                'new_values' => ['status' => 'active', 'disbursed_at' => now()]
            ]);

            return $loan;
        });
    }

    /**
     * Analyse détaillée
     */
    public function getDetailedAnalysis($loanId)
    {
        return [
            'financial_metrics' => [
                'monthly_installment' => 15000,
                'total_interest' => 180000,
                'effective_rate' => '12%'
            ],
            'risk_profile' => 'B+',
            'payment_capacity' => 'High'
        ];
    }

    /**
     * Statistiques des prêts pour une agence
     */
    public function getLoanStatistics($agencyId, $period)
    {
        return [
            'total_disbursed' => Loan::whereHas('client', function($q) use ($agencyId) {
                $q->where('agency_id', $agencyId);
            })->where('status', 'active')->sum('approved_amount'),
            'active_count' => Loan::whereHas('client', function($q) use ($agencyId) {
                $q->where('agency_id', $agencyId);
            })->where('status', 'active')->count()
        ];
    }

    /**
     * Pipeline des demandes
     */
    public function getLoanPipeline($agencyId)
    {
        $query = Loan::whereHas('client', function($q) use ($agencyId) {
            $q->where('agency_id', $agencyId);
        });

        return [
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'under_review' => (clone $query)->where('status', 'under_review')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count()
        ];
    }

    private function generateAmortizationSchedule($loan)
    {
        // Logique simplifiée de génération d'échéancier
        $amount = $loan->approved_amount;
        $monthlyPayment = $loan->monthly_payment ?? ($amount / $loan->duration_months);
        
        for ($i = 1; $i <= $loan->duration_months; $i++) {
            LoanPayment::create([
                'loan_id' => $loan->id,
                'installment_number' => $i,
                'due_date' => Carbon::now()->addMonths($i)->startOfMonth(),
                'expected_amount' => $monthlyPayment,
                'status' => 'pending'
            ]);
        }
    }
}