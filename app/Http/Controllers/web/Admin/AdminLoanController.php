<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Client;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AdminLoanController extends Controller
{
    /**
     * Liste tous les prêts
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Loan::with(['client', 'approvedBy', 'disbursedBy']);

        // Filtrage selon le rôle
        if ($user->role !== 'administrateur_systeme') {
            $query->whereHas('client', function($q) use ($user) {
                $q->where('registered_by', $user->id)
                  ->orWhere('agency_id', $user->agency_id);
            });
        }

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('loan_number', 'like', "%{$search}%")
                  ->orWhereHas('client', function($q2) use ($search) {
                      $q2->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('client_number', 'like', "%{$search}%");
                  });
            });
        }

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtre par niveau de risque
        if ($request->filled('risk_level')) {
            $query->where('risk_level', $request->risk_level);
        }

        // Filtre par date
        if ($request->filled('date_from')) {
            $query->whereDate('application_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('application_date', '<=', $request->date_to);
        }

        $loans = $query->latest('application_date')->paginate(15);

        // Statistiques
        $stats = $this->getLoanStats($user);

        return view('admin.loans.index', compact('loans', 'stats'));
    }

    public function search(Request $request)
    {
        $query = $request->input('query');

        $clients = Client::where('first_name', 'LIKE', "%{$query}%")
            ->orWhere('last_name', 'LIKE', "%{$query}%")
            ->orWhere('phone', 'LIKE', "%{$query}%")
            ->orWhere('client_number', 'LIKE', "%{$query}%")
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'phone', 'client_number', 'kyc_status']);

        return response()->json([
            'success' => true,
            'data' => $clients
        ]);
    }
    /**
     * Afficher le formulaire de création
     */
    public function create(Request $request)
    {
        $clientId = $request->get('client_id');
        $client = null;

        if ($clientId) {
            $client = Client::with(['accounts' => function($q) {
                $q->where('status', 'active');
            }])->findOrFail($clientId);

            // Vérifier l'éligibilité
            if ($client->kyc_status !== 'approved') {
                return redirect()
                    ->route('admin.clients.show', $clientId)
                    ->with('error', 'Le client doit avoir un KYC approuvé pour demander un prêt.');
            }
        }

        return view('admin.loans.create', compact('client'));
    }

    /**
     * Créer une nouvelle demande de prêt
     */
    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'requested_amount' => 'required|numeric|min:1000|max:5000000',
            'duration_months' => 'required|integer|min:3|max:24',
            'purpose' => 'required|string|max:500',
            'collateral_description' => 'nullable|string|max:1000',
            'guarantor_name' => 'nullable|string|max:255',
            'guarantor_phone' => 'nullable|string|max:20',
            'guarantor_relationship' => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            $client = Client::findOrFail($request->client_id);

            // Calculer le score d'éligibilité
            $eligibilityScore = $this->calculateEligibilityScore($client, $request->requested_amount);

            // Déterminer le niveau de risque
            $riskLevel = $this->determineRiskLevel($eligibilityScore);

            // Taux d'intérêt selon le risque
            $interestRate = $this->getInterestRateByRisk($riskLevel);

            // Créer le prêt
            $loan = Loan::create([
                'loan_number' => $this->generateLoanNumber(),
                'client_id' => $request->client_id,
                'requested_amount' => $request->requested_amount,
                'interest_rate' => $interestRate,
                'duration_months' => $request->duration_months,
                'purpose' => $request->purpose,
                'collateral_description' => $request->collateral_description,
                'guarantor_name' => $request->guarantor_name,
                'guarantor_phone' => $request->guarantor_phone,
                'guarantor_relationship' => $request->guarantor_relationship,
                'status' => 'pending',
                'risk_level' => $riskLevel,
                'eligibility_score' => $eligibilityScore,
                'application_date' => now(),
                'submitted_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()
                ->route('admin.loans.show', $loan->id)
                ->with('success', 'Demande de prêt créée avec succès. Score d\'éligibilité: ' . round($eligibilityScore, 2) . '%');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la création: ' . $e->getMessage());
        }
    }

    /**
     * Afficher les détails d'un prêt
     */
    public function show($loanId)
    {
        $loan = Loan::with([
            'client.accounts',
            'client.documents',
            'payments',
            'approvedBy',
            'disbursedBy',
            'reviewedBy'
        ])->findOrFail($loanId);

        // Statistiques du prêt
        $stats = [
            'paid_payments' => $loan->payments->where('status', 'paid')->count(),
            'pending_payments' => $loan->payments->where('status', 'pending')->count(),
            'overdue_payments' => $loan->payments->where('status', 'overdue')->count(),
            'total_paid' => $loan->payments->where('status', 'paid')->sum('paid_amount'),
            'remaining' => $loan->outstanding_principal + $loan->outstanding_interest,
        ];

        return view('admin.loans.show', compact('loan', 'stats'));
    }

    /**
     * Analyser une demande de prêt
     */
    public function analyze($loanId)
    {
        $loan = Loan::with([
            'client.accounts.transactions',
            'client.loans' => function($q) {
                $q->whereIn('status', ['active', 'disbursed', 'completed']);
            }
        ])->findOrFail($loanId);

        if ($loan->status !== 'pending') {
            return redirect()
                ->route('admin.loans.show', $loanId)
                ->with('error', 'Seules les demandes en attente peuvent être analysées.');
        }

        // Analyse détaillée
        $analysis = $this->performLoanAnalysis($loan);

        return view('admin.loans.analyze', compact('loan', 'analysis'));
    }

    /**
     * Approuver un prêt
     */
    public function approve(Request $request, $loanId)
    {
        $request->validate([
            'approved_amount' => 'required|numeric|min:1000',
            'interest_rate' => 'required|numeric|min:0|max:50',
            'duration_months' => 'required|integer|min:3|max:24',
            'approval_notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $loan = Loan::findOrFail($loanId);

            if ($loan->status !== 'pending') {
                throw new \Exception('Seules les demandes en attente peuvent être approuvées.');
            }

            // Calculer les montants
            $monthlyPayment = $this->calculateMonthlyPayment(
                $request->approved_amount,
                $request->interest_rate,
                $request->duration_months
            );

            $totalAmountDue = $monthlyPayment * $request->duration_months;

            // Mettre à jour le prêt
            $loan->update([
                'status' => 'approved',
                'approved_amount' => $request->approved_amount,
                'interest_rate' => $request->interest_rate,
                'duration_months' => $request->duration_months,
                'monthly_payment' => $monthlyPayment,
                'total_amount_due' => $totalAmountDue,
                'outstanding_principal' => $request->approved_amount,
                'outstanding_interest' => $totalAmountDue - $request->approved_amount,
                'approval_notes' => $request->approval_notes,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()
                ->route('admin.loans.show', $loanId)
                ->with('success', 'Prêt approuvé avec succès. Montant: ' . number_format($request->approved_amount, 0, ',', ' ') . ' FCFA');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de l\'approbation: ' . $e->getMessage());
        }
    }

    /**
     * Rejeter un prêt
     */
    public function reject(Request $request, $loanId)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $loan = Loan::findOrFail($loanId);

            if (!in_array($loan->status, ['pending', 'under_review'])) {
                throw new \Exception('Ce prêt ne peut pas être rejeté.');
            }

            $loan->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'reviewed_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()
                ->route('admin.loans.show', $loanId)
                ->with('success', 'Demande de prêt rejetée.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Erreur lors du rejet: ' . $e->getMessage());
        }
    }

    /**
     * Décaisser un prêt approuvé
     */
    public function disburse(Request $request, $loanId)
    {
        $request->validate([
            'disbursement_method' => 'required|in:cash,bank_transfer,mobile_money',
            'disbursement_reference' => 'nullable|string|max:255',
            'disbursement_notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $loan = Loan::with('client')->findOrFail($loanId);

            if ($loan->status !== 'approved') {
                throw new \Exception('Seuls les prêts approuvés peuvent être décaissés.');
            }

            $disbursementDate = now();
            $firstPaymentDate = (clone $disbursementDate)->addMonth();
            $maturityDate = (clone $disbursementDate)->addMonths($loan->duration_months);

            // Mettre à jour le prêt
            $loan->update([
                'status' => 'disbursed',
                'disbursement_method' => $request->disbursement_method,
                'disbursement_reference' => $request->disbursement_reference,
                'disbursement_notes' => $request->disbursement_notes,
                'disbursed_by' => auth()->id(),
                'disbursed_at' => $disbursementDate,
                'first_payment_date' => $firstPaymentDate,
                'maturity_date' => $maturityDate,
            ]);

            // Générer l'échéancier de paiement
            $this->generatePaymentSchedule($loan);

            // Créer une transaction de décaissement
            Transaction::create([
                'transaction_reference' => $this->generateTransactionReference(),
                'account_id' => $loan->client->accounts->where('account_type', 'savings')->first()->id ?? null,
                'transaction_type' => 'payout',
                'amount' => $loan->approved_amount,
                'payment_method' => $request->disbursement_method,
                'payment_reference' => $request->disbursement_reference,
                'description' => "Décaissement prêt {$loan->loan_number}",
                'status' => 'completed',
                'processed_by' => auth()->id(),
                'processed_at' => now(),
                'transaction_date' => $disbursementDate,
            ]);

            DB::commit();

            return redirect()
                ->route('admin.loans.show', $loanId)
                ->with('success', 'Prêt décaissé avec succès. Premier paiement dû le ' . $firstPaymentDate->format('d/m/Y'));

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Erreur lors du décaissement: ' . $e->getMessage());
        }
    }

    /**
     * Enregistrer un paiement
     */
    public function recordPayment(Request $request, $loanId)
    {
        $request->validate([
            'payment_id' => 'required|exists:loan_payments,id',
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,bank_transfer,mobile_money',
            'payment_reference' => 'nullable|string|max:255',
            'payment_notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $loan = Loan::findOrFail($loanId);
            $payment = LoanPayment::where('id', $request->payment_id)
                ->where('loan_id', $loanId)
                ->firstOrFail();

            if ($payment->status === 'paid') {
                throw new \Exception('Ce paiement a déjà été effectué.');
            }

            $paidAmount = $request->paid_amount;
            $paidDate = now();

            // Calculer les pénalités si en retard
            $penaltyAmount = 0;
            if ($paidDate->gt($payment->due_date)) {
                $daysLate = $paidDate->diffInDays($payment->due_date);
                $penaltyAmount = ($payment->expected_amount * 0.01) * $daysLate; // 1% par jour
            }

            $user = auth()->id();
            // Mettre à jour le paiement
            $payment->update([
                'paid_amount' => $paidAmount,
                'penalty_amount' => $penaltyAmount,
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference,
                'payment_notes' => $request->payment_notes,
                'paid_date' => $paidDate,
                'status' => 'paid',
                'processed_by' => $user,
                'processed_at' => now(),
            ]);

            // Mettre à jour le prêt
            $loan->increment('total_paid', $paidAmount);
            $loan->decrement('outstanding_principal', $payment->principal_amount);
            $loan->decrement('outstanding_interest', $payment->interest_amount);
            $loan->increment('penalty_amount', $penaltyAmount);

            // Vérifier si le prêt est soldé
            if ($loan->fresh()->outstanding_principal <= 0 && $loan->fresh()->outstanding_interest <= 0) {
                $loan->update(['status' => 'completed']);
            }

            // Créer une transaction
            Transaction::create([
                'transaction_reference' => $this->generateTransactionReference(),
                'account_id' => $loan->client->accounts->where('account_type', 'savings')->first()->id ?? null,
                'transaction_type' => 'deposit',
                'amount' => $paidAmount,
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference,
                'description' => "Remboursement prêt {$loan->loan_number} - Échéance #{$payment->payment_number}",
                'status' => 'completed',
                'processed_by' => auth()->id(),
                'processed_at' => now(),
                'transaction_date' => $paidDate,
            ]);

            DB::commit();

            return redirect()
                ->route('admin.loans.show', $loanId)
                ->with('success', 'Paiement enregistré avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de l\'enregistrement: ' . $e->getMessage());
        }
    }

    /**
     * Voir l'échéancier de paiement
     */
    public function schedule($loanId)
    {
        $loan = Loan::with(['client', 'payments'])->findOrFail($loanId);

        return view('admin.loans.schedule', compact('loan'));
    }

    /**
     * Rapport des prêts
     */
    public function report(Request $request)
    {
        $user = auth()->user();

        // Déterminer la période
        $period = $request->get('period', '30days');
        $startDate = $this->getStartDate($period, $request->get('start_date'));
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : now();

        // Query de base avec filtrage par rôle
        $baseQuery = Loan::query();

        if ($user->role !== 'administrateur_systeme') {
            $baseQuery->whereHas('client', function($q) use ($user) {
                $q->where('registered_by', $user->id)
                ->orWhere('agency_id', $user->agency_id);
            });
        }

        // Statistiques principales
        $stats = [
            'total_loans' => (clone $baseQuery)
                ->whereBetween('application_date', [$startDate, $endDate])
                ->count(),

            'approved_loans' => (clone $baseQuery)
                ->where('status', 'approved')
                ->whereBetween('approved_at', [$startDate, $endDate])
                ->count(),

            'disbursed_loans' => (clone $baseQuery)
                ->whereIn('status', ['disbursed', 'active'])
                ->whereBetween('disbursed_at', [$startDate, $endDate])
                ->count(),

            'total_disbursed' => (clone $baseQuery)
                ->whereIn('status', ['deposit', 'active', 'completed'])
                ->whereBetween('disbursed_at', [$startDate, $endDate])
                ->sum('approved_amount'),

            'total_collected' => LoanPayment::whereHas('loan', function($q) use ($baseQuery) {
                    $q->whereIn('id', $baseQuery->pluck('id'));
                })
                ->where('status', 'paid')
                ->whereBetween('paid_date', [$startDate, $endDate])
                ->sum('paid_amount'),

            'active_portfolio' => (clone $baseQuery)
                ->whereIn('status', ['disbursed', 'active'])
                ->sum('outstanding_principal'),

            'overdue_amount' => LoanPayment::whereHas('loan', function($q) use ($baseQuery) {
                    $q->whereIn('id', $baseQuery->pluck('id'));
                })
                ->where('status', 'overdue')
                ->sum('expected_amount'),

            'pending_loans' => (clone $baseQuery)
                ->where('status', 'pending')
                ->whereBetween('application_date', [$startDate, $endDate])
                ->count(),

            'rejected_loans' => (clone $baseQuery)
                ->where('status', 'rejected')
                ->whereBetween('application_date', [$startDate, $endDate])
                ->count(),
        ];

        // Prêts par statut
        $loansByStatus = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as count, COALESCE(SUM(approved_amount), 0) as total')
            ->whereBetween('application_date', [$startDate, $endDate])
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        // Prêts par niveau de risque
        $loansByRisk = (clone $baseQuery)
            ->selectRaw('risk_level, COUNT(*) as count, COALESCE(SUM(approved_amount), 0) as total')
            ->whereBetween('application_date', [$startDate, $endDate])
            ->whereNotNull('risk_level')
            ->groupBy('risk_level')
            ->get()
            ->keyBy('risk_level');

        // Évolution des décaissements (par jour)
        $disbursementTimeline = (clone $baseQuery)
            ->selectRaw('DATE(disbursed_at) as date, COUNT(*) as count, SUM(approved_amount) as total')
            ->whereIn('status', ['disbursed', 'active', 'completed'])
            ->whereBetween('disbursed_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Statistiques de remboursement
        $repaymentStats = [
            'on_time_payments' => LoanPayment::whereHas('loan', function($q) use ($baseQuery) {
                    $q->whereIn('id', $baseQuery->pluck('id'));
                })
                ->where('status', 'paid')
                ->whereColumn('paid_date', '<=', 'due_date')
                ->whereBetween('paid_date', [$startDate, $endDate])
                ->count(),

            'late_payments' => LoanPayment::whereHas('loan', function($q) use ($baseQuery) {
                    $q->whereIn('id', $baseQuery->pluck('id'));
                })
                ->where('status', 'paid')
                ->whereColumn('paid_date', '>', 'due_date')
                ->whereBetween('paid_date', [$startDate, $endDate])
                ->count(),

            'total_penalties' => LoanPayment::whereHas('loan', function($q) use ($baseQuery) {
                    $q->whereIn('id', $baseQuery->pluck('id'));
                })
                ->where('status', 'paid')
                ->whereBetween('paid_date', [$startDate, $endDate])
                ->sum('penalty_amount'),
        ];

        // Taux de remboursement
        $expectedPayments = LoanPayment::whereHas('loan', function($q) use ($baseQuery) {
                $q->whereIn('id', $baseQuery->pluck('id'));
            })
            ->whereBetween('due_date', [$startDate, $endDate])
            ->sum('expected_amount');

        $actualPayments = LoanPayment::whereHas('loan', function($q) use ($baseQuery) {
                $q->whereIn('id', $baseQuery->pluck('id'));
            })
            ->where('status', 'paid')
            ->whereBetween('paid_date', [$startDate, $endDate])
            ->sum('paid_amount');

        $stats['repayment_rate'] = $expectedPayments > 0
            ? round(($actualPayments / $expectedPayments) * 100, 2)
            : 0;

        return view('admin.loans.report', compact(
            'stats',
            'loansByStatus',
            'loansByRisk',
            'disbursementTimeline',
            'repaymentStats',
            'period',
            'startDate',
            'endDate'
        ));
    }
    // =============== MÉTHODES PRIVÉES ===============

    private function getLoanStats($user)
    {
        $query = Loan::query();

        if ($user->role !== 'administrateur_systeme') {
            $query->whereHas('client', function($q) use ($user) {
                $q->where('registered_by', $user->id)
                  ->orWhere('agency_id', $user->agency_id);
            });
        }

        return [
            'total_loans' => (clone $query)->count(),
            'pending_loans' => (clone $query)->where('status', 'pending')->count(),
            'approved_loans' => (clone $query)->where('status', 'approved')->count(),
            'active_loans' => (clone $query)->whereIn('status', ['disbursed', 'active'])->count(),
            'total_disbursed' => (clone $query)->whereIn('status', ['disbursed', 'active', 'completed'])->sum('approved_amount'),
            'active_portfolio' => (clone $query)->whereIn('status', ['disbursed', 'active'])->sum('outstanding_principal'),
            'total_collected' => LoanPayment::where('status', 'paid')->sum('paid_amount'),
            'overdue_count' => LoanPayment::where('status', 'overdue')->count(),
        ];
    }

    private function calculateEligibilityScore($client, $requestedAmount)
    {
        $score = 0;

        // 1. Ancienneté du client (20 points)
        $monthsSinceRegistration = $client->created_at->diffInMonths(now());
        $score += min($monthsSinceRegistration * 2, 20);

        // 2. Historique d'épargne (25 points)
        $savingsAccount = $client->accounts->where('account_type', 'savings')->first();
        if ($savingsAccount) {
            $savingsBalance = $savingsAccount->balance;
            $savingsRatio = ($savingsBalance / $requestedAmount) * 100;
            $score += min($savingsRatio * 0.25, 25);
        }

        // 3. Historique de prêts (20 points)
        $completedLoans = $client->loans->where('status', 'completed')->count();
        $score += min($completedLoans * 10, 20);

        // 4. KYC et documents (15 points)
        if ($client->kyc_status === 'approved') {
            $score += 15;
        }

        // 5. Activité du compte (20 points)
        $transactionCount = Transaction::whereHas('account', function($q) use ($client) {
            $q->where('client_id', $client->id);
        })->where('created_at', '>=', now()->subMonths(6))->count();
        $score += min($transactionCount * 0.5, 20);

        return min($score, 100);
    }

    private function determineRiskLevel($score)
    {
        if ($score >= 80) return 'low';
        if ($score >= 60) return 'medium';
        if ($score >= 40) return 'high';
        return 'very_high';
    }

    private function getInterestRateByRisk($riskLevel)
    {
        return match($riskLevel) {
            'low' => 8.0,
            'medium' => 12.0,
            'high' => 18.0,
            'very_high' => 24.0,
            default => 15.0,
        };
    }

    private function calculateMonthlyPayment($principal, $annualRate, $months)
    {
        $monthlyRate = ($annualRate / 100) / 12;
        if ($monthlyRate == 0) {
            return $principal / $months;
        }
        return $principal * ($monthlyRate * pow(1 + $monthlyRate, $months)) / (pow(1 + $monthlyRate, $months) - 1);
    }

    private function generatePaymentSchedule($loan)
    {
        $loan->first_payment_date = $loan->first_payment_date ?? now()->addMonth();

        $currentDate = $loan->first_payment_date;
        $monthlyPrincipal = $loan->approved_amount / $loan->duration_months;
        $monthlyInterest = ($loan->approved_amount * ($loan->interest_rate / 100)) / $loan->duration_months;
        $expectedAmount = $monthlyPrincipal + $monthlyInterest;

        for ($i = 1; $i <= $loan->duration_months; $i++) {
            LoanPayment::create([
                'loan_id' => $loan->id,
                'payment_number' => $i,
                'due_date' => $currentDate->format('Y-m-d H:i:s'), // ✅ important
                'expected_amount' => $expectedAmount,
                'principal_amount' => $monthlyPrincipal,
                'interest_amount' => $monthlyInterest,
                'status' => 'pending',
            ]);

            $currentDate = (clone $currentDate)->addMonth();
        }
    }

    private function performLoanAnalysis($loan)
    {
        $client = $loan->client;

        return [
            'client_profile' => [
                'registration_date' => $client->created_at,
                'months_as_client' => $client->created_at->diffInMonths(now()),
                'kyc_status' => $client->kyc_status,
            ],
            'savings_analysis' => [
                'total_balance' => $client->accounts->sum('balance'),
                'savings_balance' => $client->accounts->where('account_type', 'savings')->sum('balance'),
                'coverage_ratio' => $client->accounts->sum('balance') > 0
                    ? round(($client->accounts->sum('balance') / $loan->requested_amount) * 100, 2)
                    : 0,
            ],
            'loan_history' => [
                'total_loans' => $client->loans->count(),
                'completed_loans' => $client->loans->where('status', 'completed')->count(),
                'active_loans' => $client->loans->whereIn('status', ['disbursed', 'active'])->count(),
                'defaulted_loans' => $client->loans->where('status', 'defaulted')->count(),
            ],
            'transaction_activity' => [
                'last_6_months' => Transaction::whereHas('account', function($q) use ($client) {
                    $q->where('client_id', $client->id);
                })->where('created_at', '>=', now()->subMonths(6))->count(),
            ],
            'recommendation' => $this->getRecommendation($loan->eligibility_score),
        ];
    }

    private function getRecommendation($score)
    {
        if ($score >= 80) {
            return ['status' => 'approve', 'message' => 'Client très fiable, approbation recommandée'];
        } elseif ($score >= 60) {
            return ['status' => 'review', 'message' => 'Profil correct, vérification additionnelle recommandée'];
        } elseif ($score >= 40) {
            return ['status' => 'caution', 'message' => 'Risque élevé, garanties supplémentaires nécessaires'];
        }
        return ['status' => 'reject', 'message' => 'Profil à risque très élevé, rejet recommandé'];
    }

    private function generateLoanNumber()
    {
        do {
            $number = 'LOAN-' . date('ym') . '-' . strtoupper(Str::random(6));
        } while (Loan::where('loan_number', $number)->exists());

        return $number;
    }

    private function generateTransactionReference()
    {
        do {
            $number = 'TXN-' . date('YmdHis') . '-' . rand(1000, 9999);
        } while (Transaction::where('transaction_reference', $number)->exists());

        return $number;
    }

    private function getStartDate($period, $customStart)
    {
        return match($period) {
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            'year' => now()->subYear(),
            'custom' => $customStart ? Carbon::parse($customStart) : now()->subDays(30),
            default => now()->subDays(30),
        };
    }
}
