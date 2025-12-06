<?php
namespace App\Services;

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Client;
use App\Models\SystemParameter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LoanService
{
    public function checkEligibility($clientId)
    {
        $client = Client::with(['savingsAccounts', 'loans'])->findOrFail($clientId);
        
        $criteria = [
            'kyc_approved' => $client->kyc_status === 'approved',
            'minimum_savings' => $client->total_savings >= $this->getParameter('min_savings_for_loan', 50000),
            'no_active_loans' => $client->loans()->whereIn('status', ['active', 'disbursed'])->count() === 0,
            'good_credit_score' => $client->credit_score >= 60
        ];

        $eligible = array_reduce($criteria, fn($carry, $item) => $carry && $item, true);

        return [
            'eligible' => $eligible,
            'criteria' => $criteria,
            'max_loan_amount' => $this->calculateMaxLoanAmount($client),
            'recommended_terms' => $this->getRecommendedTerms($client)
        ];
    }

    public function simulateLoan($amount, $duration, $clientId = null)
    {
        $interestRate = $this->getApplicableRate($clientId, $amount);
        $monthlyRate = $interestRate / 12;
        
        // Calcul de la mensualité (formule d'amortissement)
        $monthlyPayment = $amount * ($monthlyRate * pow(1 + $monthlyRate, $duration)) / 
                         (pow(1 + $monthlyRate, $duration) - 1);
        
        $totalAmount = $monthlyPayment * $duration;
        $totalInterest = $totalAmount - $amount;
        
        // Génération de l'échéancier
        $schedule = $this->generateLoanSchedule($amount, $monthlyPayment, $interestRate, $duration);

        return [
            'loan_amount' => $amount,
            'duration_months' => $duration,
            'interest_rate' => $interestRate,
            'monthly_payment' => round($monthlyPayment, 2),
            'total_amount' => round($totalAmount, 2),
            'total_interest' => round($totalInterest, 2),
            'schedule' => $schedule
        ];
    }

    public function createLoanApplication($clientId, $data)
    {
        DB::beginTransaction();
        try {
            // Vérifier l'éligibilité
            $eligibility = $this->checkEligibility($clientId);
            if (!$eligibility['eligible']) {
                throw new \Exception('Client non éligible pour un prêt');
            }

            // Calculer le score et le risque
            $riskAssessment = $this->assessRisk($clientId, $data['requested_amount']);

            $loan = Loan::create([
                'client_id' => $clientId,
                'requested_amount' => $data['requested_amount'],
                'duration_months' => $data['duration_months'],
                'purpose' => $data['purpose'] ?? null,
                'collateral_description' => $data['collateral_description'] ?? null,
                'eligibility_score' => $riskAssessment['score'],
                'risk_level' => $riskAssessment['level'],
                'interest_rate' => $this->getApplicableRate($clientId, $data['requested_amount']),
                'status' => 'pending'
            ]);

            DB::commit();
            return $loan;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function approveLoan($loanId, $approvedAmount, $approverId)
    {
        DB::beginTransaction();
        try {
            $loan = Loan::findOrFail($loanId);
            
            if ($loan->status !== 'pending' && $loan->status !== 'under_review') {
                throw new \Exception('Ce prêt ne peut plus être approuvé');
            }

            // Simulation avec le montant approuvé
            $simulation = $this->simulateLoan($approvedAmount, $loan->duration_months, $loan->client_id);

            $loan->update([
                'approved_amount' => $approvedAmount,
                'monthly_payment' => $simulation['monthly_payment'],
                'total_amount_due' => $simulation['total_amount'],
                'outstanding_principal' => $approvedAmount,
                'outstanding_interest' => $simulation['total_interest'],
                'status' => 'approved',
                'approved_by' => $approverId,
                'approved_at' => now(),
                'first_payment_date' => Carbon::now()->addMonth()->startOfMonth(),
                'maturity_date' => Carbon::now()->addMonths($loan->duration_months)
            ]);

            // Générer l'échéancier
            $this->generateLoanPayments($loan);

            DB::commit();
            return $loan;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function disburseLoan($loanId, $disbursementData)
    {
        DB::beginTransaction();
        try {
            $loan = Loan::findOrFail($loanId);
            
            if ($loan->status !== 'approved') {
                throw new \Exception('Ce prêt n\'est pas approuvé pour décaissement');
            }

            $loan->update([
                'status' => 'disbursed',
                'disbursed_by' => auth()->id(),
                'disbursed_at' => now(),
                'disbursement_method' => $disbursementData['method'],
                'disbursement_reference' => $disbursementData['reference'] ?? null
            ]);

            // Après 24h, le statut passe à 'active'
            // Ceci devrait être géré par un job programmé
            
            DB::commit();
            return $loan;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function generateLoanPayments($loan)
    {
        $simulation = $this->simulateLoan($loan->approved_amount, $loan->duration_months, $loan->client_id);
        
        foreach ($simulation['schedule'] as $index => $payment) {
            LoanPayment::create([
                'loan_id' => $loan->id,
                'payment_number' => $index + 1,
                'due_date' => $payment['due_date'],
                'expected_amount' => $payment['payment_amount'],
                'principal_amount' => $payment['principal'],
                'interest_amount' => $payment['interest'],
                'status' => 'pending'
            ]);
        }
    }

    private function generateLoanSchedule($amount, $monthlyPayment, $annualRate, $duration)
    {
        $monthlyRate = $annualRate / 12;
        $remainingBalance = $amount;
        $schedule = [];
        
        for ($month = 1; $month <= $duration; $month++) {
            $interestPayment = $remainingBalance * $monthlyRate;
            $principalPayment = $monthlyPayment - $interestPayment;
            $remainingBalance -= $principalPayment;
            
            $schedule[] = [
                'month' => $month,
                'due_date' => Carbon::now()->addMonths($month)->startOfMonth()->format('Y-m-d'),
                'payment_amount' => round($monthlyPayment, 2),
                'principal' => round($principalPayment, 2),
                'interest' => round($interestPayment, 2),
                'remaining_balance' => round(max(0, $remainingBalance), 2)
            ];
        }
        
        return $schedule;
    }

    private function calculateMaxLoanAmount($client)
    {
        $maxBasedOnSavings = $client->total_savings * 3; // 3x l'épargne
        $maxBasedOnIncome = ($client->monthly_income ?? 100000) * 12; // 12x le revenu mensuel
        $systemMax = $this->getParameter('max_loan_amount', 5000000);
        
        return min($maxBasedOnSavings, $maxBasedOnIncome, $systemMax);
    }

    private function getApplicableRate($clientId, $amount)
    {
        $baseRate = $this->getParameter('loan_interest_rate_min', 0.08);
        
        if ($clientId) {
            $client = Client::find($clientId);
            // Ajuster le taux selon le score de crédit
            if ($client && $client->credit_score > 80) {
                $baseRate -= 0.01; // -1% pour excellent score
            } elseif ($client && $client->credit_score < 60) {
                $baseRate += 0.02; // +2% pour faible score
            }
        }
        
        return $baseRate;
    }

    private function assessRisk($clientId, $amount)
    {
        $client = Client::findOrFail($clientId);
        
        $score = 0;
        
        // Score basé sur l'épargne
        if ($client->total_savings >= 100000) $score += 30;
        elseif ($client->total_savings >= 50000) $score += 20;
        else $score += 10;
        
        // Score basé sur la régularité (simulation)
        $score += 25; // À calculer selon l'historique réel
        
        // Score basé sur l'ancienneté
        $accountAge = Carbon::parse($client->created_at)->diffInMonths();
        if ($accountAge >= 12) $score += 25;
        elseif ($accountAge >= 6) $score += 15;
        else $score += 5;
        
        // Score basé sur le montant demandé vs capacité
        $maxAmount = $this->calculateMaxLoanAmount($client);
        if ($amount <= $maxAmount * 0.5) $score += 20;
        elseif ($amount <= $maxAmount * 0.8) $score += 10;
        
        // Déterminer le niveau de risque
        $riskLevel = 'high';
        if ($score >= 80) $riskLevel = 'low';
        elseif ($score >= 60) $riskLevel = 'medium';
        
        return [
            'score' => $score,
            'level' => $riskLevel
        ];
    }

    private function getParameter($key, $default = null)
    {
        return SystemParameter::where('parameter_key', $key)->value('parameter_value') ?? $default;
    }
}