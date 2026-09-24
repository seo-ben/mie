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

        // 1. Récupération exhaustive de tous les comptes actifs
        $accounts = $client->accounts()->where('status', 'active')->get();

        $savingsBalance = (float) $accounts->filter(function ($a) {
            $t = strtolower($a->account_type ?? '');
            return in_array($t, ['savings', 'epargne', 'courant', 'current']);
        })->sum('balance');

        $tontineBalance = (float) $accounts->filter(function ($a) {
            $t = strtolower($a->account_type ?? '');
            return in_array($t, ['tontine']);
        })->sum('balance');

        $totalAssets = (float) $accounts->sum('balance');

        // 2. Analyse des flux de transactions récents (sur 90 jours)
        $accountIds = $accounts->pluck('id');
        $recentDeposits = Transaction::whereIn('account_id', $accountIds)
            ->where('status', 'completed')
            ->where('transaction_type', 'like', '%deposit%')
            ->where('created_at', '>=', now()->subDays(90))
            ->get();

        $depositCount = $recentDeposits->count();
        $totalDeposited = (float) $recentDeposits->sum('amount');

        // 3. Antécédents de prêts
        $hasActiveLoan = $client->loans()->whereIn('status', ['disbursed', 'active'])->exists();
        $pastLoans = $client->loans()->where('status', 'completed')->get();
        $defaultedLoans = $client->loans()->where(function($q) {
            $q->where('status', 'defaulted')->orWhere('days_overdue', '>', 0);
        })->count();

        // 4. Calcul du score d'éligibilité dynamique (sur 100 points)
        $score = 0;

        // A. KYC & Profil (Jusqu'à 25 pts)
        $isKycApproved = ($client->kyc_status === 'approved' || $client->is_active == 1);
        if ($isKycApproved) {
            $score += 20;
        } else {
            $score += 5;
        }
        if (!empty($client->phone) && !empty($client->address)) {
            $score += 5;
        }

        // B. Épargne & Trésorerie Déposée (Jusqu'à 35 pts)
        if ($totalAssets > 0) {
            $score += 10;
            if ($totalAssets >= 10000) $score += 5;
            if ($totalAssets >= 25000) $score += 5;
            if ($totalAssets >= 50000) $score += 5;
            if ($totalAssets >= 100000) $score += 5;
            if ($totalAssets >= 250000) $score += 5;
        }

        // C. Régularité des flux & versements (Jusqu'à 20 pts)
        if ($depositCount >= 1) $score += 5;
        if ($depositCount >= 3) $score += 5;
        if ($depositCount >= 8) $score += 5;
        if ($totalDeposited >= 50000) $score += 5;

        // D. Historique d'emprunt & discipline (Jusqu'à 20 pts)
        if ($pastLoans->count() > 0 && $defaultedLoans === 0) {
            $score += 20; // Excellent historique
        } elseif ($pastLoans->count() === 0 && $defaultedLoans === 0) {
            $score += 10; // Premier emprunt (bonus de confiance)
        }

        // Pénalités de risque
        if ($hasActiveLoan) {
            $score -= 25; // Prêt non encore soldé
        }
        if ($defaultedLoans > 0) {
            $score -= 40; // Défaut de paiement constaté
        }

        $score = max(5, min(100, $score));

        // Plafond d'emprunt conseillé : 3x l'épargne + 50% du volume de flux trimestriel
        $maxCapacity = max(50000, round(($totalAssets * 3) + ($totalDeposited * 0.5)));
        // Arrondi à la tranche de 5 000 FCFA
        $maxCapacity = ceil($maxCapacity / 5000) * 5000;

        $isEligible = ($score >= 50) && !$hasActiveLoan && ($defaultedLoans === 0);

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
                'risk_level' => $score >= 75 ? 'low' : ($score >= 50 ? 'medium' : 'high'),
                'max_borrowing_capacity' => $maxCapacity,
                'savings_balance' => $savingsBalance,
                'tontine_balance' => $tontineBalance,
                'total_assets' => $totalAssets,
                'active_loan_exists' => $hasActiveLoan,
                'completed_loans_count' => $pastLoans->count(),
                'defaulted_loans_count' => $defaultedLoans,
                'recent_deposit_count' => $depositCount,
                'recent_deposit_volume' => $totalDeposited,
                'criteria' => [
                    ['label' => 'Statut KYC & Identité', 'passed' => $isKycApproved],
                    ['label' => 'Aucun crédit en cours', 'passed' => !$hasActiveLoan],
                    ['label' => 'Épargne ou Tontine active', 'passed' => $totalAssets > 0],
                    ['label' => 'Flux de transactions réguliers', 'passed' => $depositCount >= 1],
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

            $rawMonthly = $monthlyRate > 0
                ? $approvedAmount * ($monthlyRate * pow(1 + $monthlyRate, $duration)) / (pow(1 + $monthlyRate, $duration) - 1)
                : $approvedAmount / $duration;
            $monthlyPayment = round($rawMonthly);

            // Générer l'échéancier réel équilibré dans loan_payments
            LoanPayment::where('loan_id', $loan->id)->delete();
            $remaining = $approvedAmount;
            $firstDate = Carbon::now()->addMonth()->startOfMonth();
            $totalDueSum = 0;
            $totalInterestSum = 0;

            for ($i = 1; $i <= $duration; $i++) {
                $interestPart = round($remaining * $monthlyRate);
                if ($i === $duration) {
                    // La dernière échéance absorbe tout résidu de principal
                    $principalPart = $remaining;
                    $expectedPayment = $principalPart + $interestPart;
                } else {
                    $principalPart = max(0, $monthlyPayment - $interestPart);
                    if ($principalPart > $remaining) {
                        $principalPart = $remaining;
                    }
                    $expectedPayment = $principalPart + $interestPart;
                }
                $remaining = max(0, $remaining - $principalPart);
                $totalInterestSum += $interestPart;
                $totalDueSum += $expectedPayment;

                LoanPayment::create([
                    'loan_id' => $loan->id,
                    'payment_number' => $i,
                    'due_date' => (clone $firstDate)->addMonths($i - 1)->format('Y-m-d'),
                    'expected_amount' => $expectedPayment,
                    'principal_amount' => $principalPart,
                    'interest_amount' => $interestPart,
                    'paid_amount' => 0,
                    'status' => 'pending',
                ]);
            }

            $loan->update([
                'approved_amount' => $approvedAmount,
                'monthly_payment' => $monthlyPayment,
                'total_amount_due' => $totalDueSum,
                'outstanding_principal' => $approvedAmount,
                'outstanding_interest' => $totalInterestSum,
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'first_payment_date' => $firstDate,
                'maturity_date' => Carbon::now()->addMonths($duration),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Dossier de prêt approuvé avec succès. Échéancier généré et prêt pour décaissement.',
                'data' => $loan->fresh(['client', 'approvedBy', 'payments']),
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

            if (!$activeSession) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Opération refusée : Votre caisse est fermée. Veuillez d\'abord ouvrir votre session journalière avant de décaisser des fonds.',
                ], 403);
            }

            // Créer la transaction de sortie de caisse
            $txRef = 'DEC-' . strtoupper(date('ymd')) . '-' . rand(1000, 9999);
            $clientAccount = Account::where('client_id', $loan->client_id)->first();

            $transaction = Transaction::create([
                'transaction_reference' => $txRef,
                'account_id' => $clientAccount->id ?? 1,
                'cashier_session_id' => $activeSession->id,
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

            $activeSession->increment('total_withdrawals', $disburseAmount);

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

            if (!$activeSession) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Opération refusée : Votre caisse est fermée. Veuillez ouvrir votre session journalière avant d\'encaisser des remboursements.',
                ], 403);
            }

            $clientAccount = Account::where('client_id', $loan->client_id)->first();
            $txRef = 'REM-' . strtoupper(date('ymd')) . '-' . rand(1000, 9999);

            $transaction = Transaction::create([
                'transaction_reference' => $txRef,
                'account_id' => $clientAccount->id ?? 1,
                'cashier_session_id' => $activeSession->id,
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

            // Répartir le montant sur les échéances en attente, en retard ou partielles
            $remainingPayment = $repayAmount;
            $pendingPayments = LoanPayment::where('loan_id', $loan->id)
                ->whereIn('status', ['pending', 'overdue', 'partial'])
                ->orderBy('payment_number')
                ->get();

            foreach ($pendingPayments as $p) {
                if ($remainingPayment <= 0) break;

                $expected = (float) $p->expected_amount;
                $currentPaid = (float) $p->paid_amount;
                $due = max(0, $expected - $currentPaid);

                if ($due <= 0) {
                    $p->update(['status' => 'paid']);
                    continue;
                }

                // Tolérance de 1 FCFA (évite le décalage causé par les centimes résiduels)
                if ($remainingPayment >= ($due - 1.0)) {
                    $p->update([
                        'paid_amount' => $expected,
                        'status' => 'paid',
                        'paid_date' => now(),
                        'processed_by' => $user->id,
                        'processed_at' => now(),
                    ]);
                    $remainingPayment = max(0, $remainingPayment - $due);
                } else {
                    // Paiement partiel réel
                    $newPaid = $currentPaid + $remainingPayment;
                    if ($newPaid >= ($expected - 1.0)) {
                        $p->update([
                            'paid_amount' => $expected,
                            'status' => 'paid',
                            'paid_date' => now(),
                            'processed_by' => $user->id,
                            'processed_at' => now(),
                        ]);
                    } else {
                        $p->update([
                            'paid_amount' => round($newPaid, 2),
                            'status' => 'partial',
                            'paid_date' => now(),
                            'processed_by' => $user->id,
                            'processed_at' => now(),
                        ]);
                    }
                    $remainingPayment = 0;
                }
            }

            // Recalculer les totaux réels à partir des paiements
            $allPayments = LoanPayment::where('loan_id', $loan->id)->get();
            $actualTotalPaid = (float) $allPayments->sum('paid_amount');
            $hasUnpaidPayments = $allPayments->where('status', '!=', 'paid')->count() > 0;
            $newOutstanding = max(0, (float) $loan->total_amount_due - $actualTotalPaid);
            $isFullyPaid = (!$hasUnpaidPayments) || ($newOutstanding <= 1.0);

            if ($isFullyPaid) {
                $newOutstanding = 0;
            }

            $loan->update([
                'total_paid' => $actualTotalPaid,
                'outstanding_principal' => max(0, (float) $loan->approved_amount - (float) $allPayments->where('status', 'paid')->sum('principal_amount')),
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

        // Auto-guérison des échéances ayant des résidus de centimes <= 1 FCFA
        foreach ($loan->payments as $p) {
            if ($p->status !== 'paid' && (float)$p->paid_amount > 0 && (((float)$p->expected_amount - (float)$p->paid_amount) <= 1.0)) {
                $p->update([
                    'paid_amount' => $p->expected_amount,
                    'status' => 'paid',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'loan' => $loan->fresh(['client']),
                'payments' => $loan->payments()->orderBy('payment_number')->get(),
            ],
        ]);
    }
}
