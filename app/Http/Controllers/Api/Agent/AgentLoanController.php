<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Client;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\CashierSession;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AgentLoanController extends Controller
{
    /**
     * Liste et suivi de tous les prêts avec filtres et progression.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->get('status', 'all');
        $search = $request->get('search');

        // Récupérer les IDs clients autorisés (agence du caissier ou enregistrés)
        $clientIds = $user->role === 'caissier'
            ? Client::where('agency_id', $user->agency_id)->pluck('id')
            : Client::where('registered_by', $user->id)->pluck('id');

        $query = Loan::with(['client', 'approvedBy', 'disbursedBy'])
            ->whereIn('client_id', $clientIds);

        if ($status !== 'all' && !empty($status)) {
            if ($status === 'in_progress') {
                $query->whereIn('status', ['disbursed', 'active']);
            } elseif ($status === 'overdue') {
                $query->whereIn('status', ['disbursed', 'active'])
                    ->where('days_overdue', '>', 0);
            } else {
                $query->where('status', $status);
            }
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('loan_number', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($cq) use ($search) {
                      $cq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $loans = $query->latest('created_at')->paginate(20);

        // Métriques globales pour le tableau de bord des prêts
        $metricsQuery = Loan::whereIn('client_id', $clientIds);
        $totalDisbursed = (float) (clone $metricsQuery)->whereIn('status', ['disbursed', 'active', 'completed'])->sum('approved_amount');
        $totalRepaid = (float) (clone $metricsQuery)->whereIn('status', ['disbursed', 'active', 'completed'])->sum('total_paid');
        $activeLoansCount = (clone $metricsQuery)->whereIn('status', ['disbursed', 'active'])->count();
        $pendingLoansCount = (clone $metricsQuery)->where('status', 'pending')->count();
        $totalOutstanding = max(0, $totalDisbursed - $totalRepaid);

        return response()->json([
            'success' => true,
            'data' => $loans,
            'metrics' => [
                'total_disbursed' => $totalDisbursed,
                'total_repaid' => $totalRepaid,
                'total_outstanding' => $totalOutstanding,
                'active_loans_count' => $activeLoansCount,
                'pending_loans_count' => $pendingLoansCount,
                'repayment_rate' => $totalDisbursed > 0 ? round(($totalRepaid / $totalDisbursed) * 100, 1) : 0,
            ],
        ]);
    }

    /**
     * Vérifier l'éligibilité financière complète d'un client.
     */
    public function checkEligibility($clientId): JsonResponse
    {
        $client = Client::with(['accounts', 'loans'])->findOrFail($clientId);

        $savingsBalance = (float) $client->accounts()->where('account_type', 'savings')->where('status', 'active')->sum('balance');
        $tontineBalance = (float) $client->accounts()->where('account_type', 'tontine')->where('status', 'active')->sum('balance');
        $totalAssets = $savingsBalance + $tontineBalance;

        $hasActiveLoan = $client->loans()->whereIn('status', ['disbursed', 'active'])->exists();
        $pastLoans = $client->loans()->where('status', 'completed')->get();
        $defaultedLoans = $client->loans()->where('status', 'defaulted')->count();

        // Calcul du score d'éligibilité (sur 100)
        $score = 50; // base
        if ($client->kyc_status === 'approved') $score += 20;
        if ($totalAssets >= 50000) $score += 15;
        if ($pastLoans->count() > 0 && $defaultedLoans === 0) $score += 15;
        if ($defaultedLoans > 0) $score -= 40;
        if ($hasActiveLoan) $score -= 30;

        $score = max(0, min(100, $score));

        // Plafond maximum conseillé : jusqu'à 3x l'épargne ou 500 000 FCFA minimum
        $maxCapacity = max(100000, $totalAssets * 3);
        $isEligible = $score >= 60 && !$hasActiveLoan && $defaultedLoans === 0;

        return response()->json([
            'success' => true,
            'data' => [
                'client' => [
                    'id' => $client->id,
                    'full_name' => $client->full_name,
                    'phone' => $client->phone,
                    'kyc_status' => $client->kyc_status,
                ],
                'is_eligible' => $isEligible,
                'eligibility_score' => $score,
                'risk_level' => $score >= 80 ? 'low' : ($score >= 60 ? 'medium' : 'high'),
                'max_borrowing_capacity' => $maxCapacity,
                'savings_balance' => $savingsBalance,
                'tontine_balance' => $tontineBalance,
                'total_assets' => $totalAssets,
                'active_loan_exists' => $hasActiveLoan,
                'completed_loans_count' => $pastLoans->count(),
                'defaulted_loans_count' => $defaultedLoans,
                'criteria' => [
                    ['label' => 'Statut KYC Validé', 'passed' => $client->kyc_status === 'approved'],
                    ['label' => 'Aucun crédit en cours', 'passed' => !$hasActiveLoan],
                    ['label' => 'Garantie épargne / tontine active', 'passed' => $totalAssets > 0],
                    ['label' => 'Aucun incident de paiement', 'passed' => $defaultedLoans === 0],
                ],
            ],
        ]);
    }

    /**
     * Simulation d'un prêt avec tableau d'amortissement prévisionnel.
     */
    public function simulate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:10000',
            'duration_months' => 'required|integer|min:1|max:36',
            'interest_rate' => 'nullable|numeric|min:0|max:100', // Taux annuel en % (ex: 12%)
        ]);

        $amount = (float) $validated['amount'];
        $duration = (int) $validated['duration_months'];
        $annualRate = isset($validated['interest_rate']) && $validated['interest_rate'] > 0
            ? (float) $validated['interest_rate']
            : 12.0; // 12% standard par an

        $monthlyRate = ($annualRate / 100) / 12;

        if ($monthlyRate > 0) {
            $monthlyPayment = $amount * ($monthlyRate * pow(1 + $monthlyRate, $duration)) / (pow(1 + $monthlyRate, $duration) - 1);
        } else {
            $monthlyPayment = $amount / $duration;
        }

        $totalDue = $monthlyPayment * $duration;
        $totalInterest = $totalDue - $amount;

        // Génération prévisionnelle des échéances
        $schedule = [];
        $remainingPrincipal = $amount;
        $now = Carbon::now()->addMonth()->startOfMonth();

        for ($i = 1; $i <= $duration; $i++) {
            $interestForMonth = $remainingPrincipal * $monthlyRate;
            $principalForMonth = $monthlyPayment - $interestForMonth;
            $remainingPrincipal = max(0, $remainingPrincipal - $principalForMonth);

            $schedule[] = [
                'payment_number' => $i,
                'due_date' => (clone $now)->addMonths($i - 1)->format('Y-m-d'),
                'monthly_payment' => round($monthlyPayment, 2),
                'principal_part' => round($principalForMonth, 2),
                'interest_part' => round($interestForMonth, 2),
                'remaining_principal' => round($remainingPrincipal, 2),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'amount' => $amount,
                'duration_months' => $duration,
                'annual_interest_rate' => $annualRate,
                'monthly_payment' => round($monthlyPayment, 2),
                'total_interest' => round($totalInterest, 2),
                'total_amount_due' => round($totalDue, 2),
                'schedule' => $schedule,
            ],
        ]);
    }

    /**
     * Enregistrer une nouvelle demande de prêt.
     */
    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'requested_amount' => 'required|numeric|min:10000',
            'duration_months' => 'required|integer|min:1|max:36',
            'purpose' => 'nullable|string|max:500',
            'collateral_description' => 'nullable|string|max:500',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $user = $request->user();
        $clientId = $validated['client_id'];
        $amount = (float) $validated['requested_amount'];
        $duration = (int) $validated['duration_months'];
        $rate = isset($validated['interest_rate']) && $validated['interest_rate'] > 0
            ? (float) $validated['interest_rate']
            : 12.0;

        $monthlyRate = ($rate / 100) / 12;
        $monthlyPayment = $monthlyRate > 0
            ? $amount * ($monthlyRate * pow(1 + $monthlyRate, $duration)) / (pow(1 + $monthlyRate, $duration) - 1)
            : $amount / $duration;
        $totalDue = $monthlyPayment * $duration;

        $loanNumber = 'PRT-' . strtoupper(date('ymd')) . '-' . rand(1000, 9999);

        try {
            $loan = Loan::create([
                'loan_number' => $loanNumber,
                'client_id' => $clientId,
                'requested_amount' => $amount,
                'approved_amount' => $amount,
                'interest_rate' => $rate,
                'duration_months' => $duration,
                'monthly_payment' => round($monthlyPayment, 2),
                'total_amount_due' => round($totalDue, 2),
                'outstanding_principal' => $amount,
                'outstanding_interest' => round($totalDue - $amount, 2),
                'total_paid' => 0,
                'purpose' => $validated['purpose'] ?? 'Micro-crédit d\'activité / fonds de roulement',
                'collateral_description' => $validated['collateral_description'] ?? 'Garantie morale & épargne guichet',
                'status' => 'pending',
                'application_date' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Demande de prêt enregistrée avec succès.',
                'data' => $loan->load('client'),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur enregistrement prêt', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Impossible d\'enregistrer le prêt : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Valider et approuver un prêt.
     */
    public function approve($loanId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'approved_amount' => 'nullable|numeric|min:10000',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $loan = Loan::findOrFail($loanId);

        if (!in_array($loan->status, ['pending', 'under_review'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ce prêt ne peut plus être approuvé (statut actuel : ' . $loan->status . ').',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $approvedAmount = isset($validated['approved_amount']) && $validated['approved_amount'] > 0
                ? (float) $validated['approved_amount']
                : (float) $loan->requested_amount;

            $duration = (int) $loan->duration_months;
            $rate = (float) $loan->interest_rate;
            $monthlyRate = ($rate / 100) / 12;

            $monthlyPayment = $monthlyRate > 0
                ? $approvedAmount * ($monthlyRate * pow(1 + $monthlyRate, $duration)) / (pow(1 + $monthlyRate, $duration) - 1)
                : $approvedAmount / $duration;
            $totalDue = $monthlyPayment * $duration;
            $totalInterest = $totalDue - $approvedAmount;

            $loan->update([
                'approved_amount' => $approvedAmount,
                'monthly_payment' => round($monthlyPayment, 2),
                'total_amount_due' => round($totalDue, 2),
                'outstanding_principal' => $approvedAmount,
                'outstanding_interest' => round($totalInterest, 2),
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'first_payment_date' => Carbon::now()->addMonth()->startOfMonth(),
                'maturity_date' => Carbon::now()->addMonths($duration),
            ]);

            // Générer l'échéancier réel dans loan_payments
            LoanPayment::where('loan_id', $loan->id)->delete();
            $remaining = $approvedAmount;
            $firstDate = Carbon::now()->addMonth()->startOfMonth();

            for ($i = 1; $i <= $duration; $i++) {
                $interestPart = $remaining * $monthlyRate;
                $principalPart = $monthlyPayment - $interestPart;
                $remaining = max(0, $remaining - $principalPart);

                LoanPayment::create([
                    'loan_id' => $loan->id,
                    'payment_number' => $i,
                    'due_date' => (clone $firstDate)->addMonths($i - 1)->format('Y-m-d'),
                    'expected_amount' => round($monthlyPayment, 2),
                    'principal_amount' => round($principalPart, 2),
                    'interest_amount' => round($interestPart, 2),
                    'status' => 'pending',
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Dossier de prêt approuvé avec succès. Prêt pour décaissement.',
                'data' => $loan->fresh(['client', 'approvedBy']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur approbation prêt', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'approbation : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Décaissement effectif du prêt au guichet (Sortie d'espèces).
     */
    public function disburse($loanId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => 'nullable|in:cash,mobile_money,bank_transfer',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $loan = Loan::with('client')->findOrFail($loanId);

        if ($loan->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Ce prêt doit d\'abord être approuvé avant tout décaissement (statut : ' . $loan->status . ').',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $disburseAmount = (float) $loan->approved_amount;

            // Identifier ou vérifier la session de caisse
            $activeSession = CashierSession::where('user_id', $user->id)
                ->where('status', 'open')
                ->latest('opened_at')
                ->first();

            // Créer la transaction de sortie de caisse
            $txRef = 'DEC-' . strtoupper(date('ymd')) . '-' . rand(1000, 9999);
            $clientAccount = Account::where('client_id', $loan->client_id)->first();

            $transaction = Transaction::create([
                'transaction_reference' => $txRef,
                'account_id' => $clientAccount->id ?? 1,
                'cashier_session_id' => $activeSession->id ?? null,
                'transaction_type' => 'withdrawal',
                'amount' => $disburseAmount,
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'fee_amount' => 0,
                'description' => 'Décaissement Prêt N° ' . $loan->loan_number . ' à ' . $loan->client->full_name,
                'status' => 'completed',
                'balance_before' => 0,
                'balance_after' => 0,
                'processed_by' => $user->id,
                'agency_id' => $user->agency_id ?? 1,
                'processed_at' => now(),
                'transaction_date' => now(),
            ]);

            // Mettre à jour le prêt
            $loan->update([
                'status' => 'disbursed',
                'disbursed_by' => $user->id,
                'disbursed_at' => now(),
                'disbursement_method' => $validated['payment_method'] ?? 'cash',
                'disbursement_reference' => $txRef,
            ]);

            if ($activeSession) {
                $activeSession->increment('total_withdrawals', $disburseAmount);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fonds décaissés avec succès. ' . number_format($disburseAmount, 0, ',', ' ') . ' FCFA remis au client.',
                'data' => [
                    'loan' => $loan->fresh(['client', 'disbursedBy']),
                    'transaction' => $transaction,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur décaissement prêt', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du décaissement : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Encaisser le remboursement d'une échéance de prêt.
     */
    public function repay($loanId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:500',
            'payment_method' => 'nullable|in:cash,mobile_money,bank_transfer',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $loan = Loan::with(['client', 'payments'])->findOrFail($loanId);
        $repayAmount = (float) $validated['amount'];

        if (!in_array($loan->status, ['disbursed', 'active'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ce prêt n\'est pas en cours de remboursement (statut : ' . $loan->status . ').',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $activeSession = CashierSession::where('user_id', $user->id)
                ->where('status', 'open')
                ->latest('opened_at')
                ->first();

            $clientAccount = Account::where('client_id', $loan->client_id)->first();
            $txRef = 'REM-' . strtoupper(date('ymd')) . '-' . rand(1000, 9999);

            $transaction = Transaction::create([
                'transaction_reference' => $txRef,
                'account_id' => $clientAccount->id ?? 1,
                'cashier_session_id' => $activeSession->id ?? null,
                'transaction_type' => 'loan_repayment',
                'amount' => $repayAmount,
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'fee_amount' => 0,
                'description' => 'Remboursement échéance Prêt N° ' . $loan->loan_number . ' (' . $loan->client->full_name . ')',
                'status' => 'completed',
                'balance_before' => 0,
                'balance_after' => 0,
                'processed_by' => $user->id,
                'agency_id' => $user->agency_id ?? 1,
                'processed_at' => now(),
                'transaction_date' => now(),
            ]);

            // Répartir le montant sur les échéances en attente
            $remainingPayment = $repayAmount;
            $pendingPayments = LoanPayment::where('loan_id', $loan->id)
                ->whereIn('status', ['pending', 'overdue', 'partial'])
                ->orderBy('due_date')
                ->get();

            foreach ($pendingPayments as $p) {
                if ($remainingPayment <= 0) break;

                $due = (float) $p->expected_amount - (float) $p->paid_amount;
                if ($remainingPayment >= $due) {
                    $p->update([
                        'paid_amount' => $p->expected_amount,
                        'status' => 'paid',
                        'paid_date' => now(),
                        'processed_by' => $user->id,
                        'processed_at' => now(),
                    ]);
                    $remainingPayment -= $due;
                } else {
                    $p->update([
                        'paid_amount' => (float) $p->paid_amount + $remainingPayment,
                        'status' => 'partial',
                        'paid_date' => now(),
                        'processed_by' => $user->id,
                        'processed_at' => now(),
                    ]);
                    $remainingPayment = 0;
                }
            }

            // Mettre à jour le prêt
            $newTotalPaid = (float) $loan->total_paid + $repayAmount;
            $newOutstanding = max(0, (float) $loan->total_amount_due - $newTotalPaid);
            $isFullyPaid = $newOutstanding <= 0.01;

            $loan->update([
                'total_paid' => $newTotalPaid,
                'outstanding_principal' => max(0, (float) $loan->approved_amount - $newTotalPaid),
                'status' => $isFullyPaid ? 'completed' : 'active',
            ]);

            if ($activeSession) {
                $activeSession->increment('total_deposits', $repayAmount);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Remboursement de ' . number_format($repayAmount, 0, ',', ' ') . ' FCFA validé.' . ($isFullyPaid ? ' 🎉 Ce prêt est désormais 100% SOLDÉ !' : ''),
                'data' => [
                    'loan' => $loan->fresh(['client']),
                    'transaction' => $transaction,
                    'is_fully_paid' => $isFullyPaid,
                    'remaining_due' => $newOutstanding,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur remboursement prêt', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du remboursement : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Consulter l'échéancier détaillé d'un prêt.
     */
    public function schedule($loanId): JsonResponse
    {
        $loan = Loan::with(['client', 'payments'])->findOrFail($loanId);

        return response()->json([
            'success' => true,
            'data' => [
                'loan' => $loan,
                'payments' => $loan->payments()->orderBy('payment_number')->get(),
            ],
        ]);
    }
}
