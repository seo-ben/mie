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
use Illuminate\Support\Facades\Mail;
use App\Mail\LoanStatusChanged;

class AdminLoanController extends Controller
{
    /**
     * Liste tous les prêts
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Loan::with(['client', 'approvedBy', 'disbursedBy']);

        // Filtrage par Agence (Isolation Multi-Agence)
        if ($user->role !== 'administrateur_systeme' && $user->role !== 'administrateur_reglementaire') {
            $query->whereHas('client', function($q) use ($user) {
                $q->where('agency_id', $user->agency_id);
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
            'loan_type' => 'required|string|max:100',
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
                'loan_type' => $request->loan_type,
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

        // Analyse intelligente automatique
        $analysis = $this->performLoanAnalysis($loan);

        return view('admin.loans.show', compact('loan', 'stats', 'analysis'));
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

            // 📧 Notification Email (Approbation)
            if ($loan->client->email) {
                try {
                    $emailMessage = "Nous avons le plaisir de vous informer que votre demande de prêt #{$loan->loan_number} a été approuvée.\n\n" .
                                   "Montant accordé : " . number_format($request->approved_amount, 0, ',', ' ') . " XOF\n" .
                                   "Taux d'intérêt : {$request->interest_rate}%\n" .
                                   "Durée : {$request->duration_months} mois\n\n" .
                                   "Veuillez passer en agence pour la signature des contrats.";

                    Mail::to($loan->client->email)->send(new LoanStatusChanged($loan, 'approved', $emailMessage));
                } catch (\Exception $e) {
                    \Log::error("Erreur d'envoi d'email pour le prêt {$loan->id}: " . $e->getMessage());
                    // On ne casse pas le flux si l'email échoue
                }
            }

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

            // 📧 Notification Email (Rejet)
            if ($loan->client->email) {
                try {
                    $emailMessage = "Nous regrettons de vous informer que votre demande de prêt #{$loan->loan_number} n'a pas pu être retenue pour le motif suivant :\n\n" .
                                   "Motif : {$request->rejection_reason}\n\n" .
                                   "Nous vous invitons à contacter votre conseiller pour plus d'informations.";

                    Mail::to($loan->client->email)->send(new LoanStatusChanged($loan, 'rejected', $emailMessage));
                } catch (\Exception $e) {
                    \Log::error("Erreur d'envoi d'email de rejet pour le prêt {$loan->id}: " . $e->getMessage());
                }
            }

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

            // Tenter de trouver un compte pour la transaction (priorité Épargne)
            $targetAccount = $loan->client->accounts->where('account_type', 'savings')->first() 
                            ?? $loan->client->accounts->first();

            if (!$targetAccount) {
                throw new \Exception("L'adhérent ne possède aucun compte actif pour recevoir le décaissement.");
            }

            // Créer une transaction de décaissement
            Transaction::create([
                'transaction_reference' => $this->generateTransactionReference(),
                'account_id' => $targetAccount->id,
                'transaction_type' => 'payout',
                'amount' => $loan->approved_amount,
                'payment_method' => $request->disbursement_method,
                'payment_reference' => $request->disbursement_reference,
                'description' => "Décaissement prêt {$loan->loan_number}",
                'status' => 'completed',
                'processed_by' => auth()->id(),
                'agency_id' => auth()->user()->agency_id ?? 1, // Fallback agence
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

            $loan = Loan::with(['client', 'payments' => function($q) {
                $q->whereIn('status', ['pending', 'overdue', 'partial'])->orderBy('payment_number');
            }])->lockForUpdate()->findOrFail($loanId);

            $paidAmount = $request->paid_amount;
            $remainingToDistribute = $paidAmount;
            $user = auth()->id();
            
            // Transaction Globale
            $transaction = Transaction::create([
                'transaction_reference' => $this->generateTransactionReference(),
                'loan_id' => $loan->id,
                'transaction_type' => 'loan_repayment',
                'amount' => $paidAmount,
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference,
                'description' => "Remboursement prêt {$loan->loan_number} " . ($request->payment_notes ? " - ".$request->payment_notes : ""),
                'status' => 'completed',
                'processed_by' => $user,
                'agency_id' => auth()->user()->agency_id ?? 1,
                'processed_at' => now(),
                'transaction_date' => now(),
            ]);

            // 🔥 RÉPARTITION INTELLIGENTE SUR LES ÉCHÉANCES
            foreach ($loan->payments as $payment) {
                if ($remainingToDistribute <= 0) break;

                // Calcul de pénalités si en retard et non calculées
                $penaltyAmount = $payment->penalty_amount;
                if (now()->gt($payment->due_date) && !in_array($payment->status, ['paid'])) {
                    $daysLate = now()->diffInDays($payment->due_date);
                    $penaltyRate = \App\Models\SystemParameter::where('parameter_key', 'loan_late_penalty_rate')->value('parameter_value') ?? 0.01;
                    $penaltyAmount = ($payment->expected_amount * $penaltyRate) * $daysLate;
                }

                $amountNeededForThisPayment = ($payment->expected_amount + $penaltyAmount) - $payment->paid_amount;
                
                if ($amountNeededForThisPayment <= 0) continue;

                $payForThis = min($remainingToDistribute, $amountNeededForThisPayment);
                
                $remainingToDistribute -= $payForThis;
                $newPaidAmount = $payment->paid_amount + $payForThis;
                
                $status = ($newPaidAmount >= ($payment->expected_amount + $penaltyAmount)) ? 'paid' : 'partial';

                $payment->update([
                    'paid_amount' => $newPaidAmount,
                    'penalty_amount' => $penaltyAmount,
                    'payment_method' => $request->payment_method,
                    'payment_reference' => $transaction->payment_reference,
                    'payment_notes' => $request->payment_notes,
                    'paid_date' => now(),
                    'status' => $status,
                    'processed_by' => $user,
                    'processed_at' => now(),
                ]);
            }

            // Surplus (Paiement par anticipation)
            if ($remainingToDistribute > 0) {
                \App\Models\LoanPayment::create([
                    'loan_id' => $loan->id,
                    'payment_number' => \App\Models\LoanPayment::where('loan_id', $loan->id)->max('payment_number') + 1,
                    'due_date' => now(),
                    'expected_amount' => 0,
                    'principal_amount' => 0,
                    'interest_amount' => 0,
                    'paid_date' => now(),
                    'paid_amount' => $remainingToDistribute,
                    'payment_method' => $request->payment_method,
                    'payment_reference' => $transaction->payment_reference,
                    'payment_notes' => $request->payment_notes,
                    'processed_by' => $user,
                    'status' => 'paid',
                    'processed_at' => now(),
                ]);
            }

            // Mettre à jour les totaux du prêt
            $loan->increment('total_paid', $paidAmount);
            
            $reductionPrincipal = min($loan->outstanding_principal, $paidAmount);
            $reductionInterest = min($loan->outstanding_interest, $paidAmount - $reductionPrincipal);
            
            $loan->decrement('outstanding_principal', $reductionPrincipal);
            $loan->decrement('outstanding_interest', $reductionInterest);

            // Si le prêt est totalement payé
            if ($loan->total_paid >= $loan->total_amount_due) {
                $loan->update(['status' => 'completed']);
            }

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

        // Matrice PAR (Portfolio at Risk) selon les standards institutionnels
        $par1 = $this->calculatePAR(1, $user);
        $par30 = $this->calculatePAR(30, $user);
        $par60 = $this->calculatePAR(60, $user);
        $par90 = $this->calculatePAR(90, $user);

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
                ->whereIn('status', ['disbursed', 'active', 'completed']) // Correction status decaisse
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

            'par_1' => $par1,
            'par_30' => $par30,
            'par_60' => $par60,
            'par_90' => $par90,
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
            'par_30' => $this->calculatePAR(30, $user),
            'par_90' => $this->calculatePAR(90, $user),
        ];
    }

    /**
     * Calcul du PAR (Portfolio at Risk) pour les rapports
     */
    private function calculatePAR(int $days, $user): float
    {
        $cutoffDate = Carbon::now()->subDays($days);

        $query = Loan::whereIn('status', ['active', 'disbursed']);

        if ($user->role !== 'administrateur_systeme') {
            $query->whereHas('client', function($q) use ($user) {
                $q->where('registered_by', $user->id)
                  ->orWhere('agency_id', $user->agency_id);
            });
        }

        $overduePrincipal = (clone $query)->whereHas('payments', function ($q) use ($cutoffDate) {
                $q->where('status', 'overdue')
                  ->where('due_date', '<=', $cutoffDate);
            })
            ->sum('outstanding_principal');

        $totalOutstanding = (clone $query)->sum('outstanding_principal');

        return $totalOutstanding > 0 ? round(($overduePrincipal / $totalOutstanding) * 100, 2) : 0;
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
        // Tenter de récupérer depuis la configuration système
        $paramKey = "loan_interest_rate_{$riskLevel}";
        $configuredRate = \App\Models\SystemParameter::where('parameter_key', $paramKey)->value('parameter_value');

        if ($configuredRate !== null) {
            return (float) $configuredRate;
        }

        // Valeurs par défaut si non configuré
        return match($riskLevel) {
            'low' => 12.0,
            'medium' => 17.0,
            'high' => 20.0,
            'very_high' => 25.0,
            default => 17.0,
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
        $totalBalance = $client->accounts->sum('balance');
        $coverageRatio = $loan->requested_amount > 0 ? round(($totalBalance / $loan->requested_amount) * 100, 2) : 0;
        
        $insights = [];
        
        // Signaux Positifs
        if ($client->kyc_status === 'approved') $insights['positive'][] = "Dossier KYC validé et conforme.";
        if ($client->created_at->diffInMonths(now()) >= 6) $insights['positive'][] = "Adhérent fidèle (> 6 mois).";
        if ($coverageRatio >= 50) $insights['positive'][] = "Excellente couverture par l'épargne ($coverageRatio%).";
        if ($client->loans->where('status', 'completed')->count() > 0) $insights['positive'][] = "Historique de remboursement exemplaire.";
        
        // Points de Vigilance
        if ($coverageRatio < 20) $insights['warning'][] = "Faible garantie financière par l'épargne.";
        if ($client->kyc_status !== 'approved') $insights['warning'][] = "Risque réglementaire : KYC non finalisé.";
        if ($client->loans->where('status', 'defaulted')->count() > 0) $insights['warning'][] = "ALERTE : Antécédents de défaut de paiement.";
        if ($totalBalance < ($loan->requested_amount * 0.1)) $insights['warning'][] = "Capacité d'autofinancement critique.";

        return [
            'client_profile' => [
                'registration_date' => $client->created_at,
                'months_as_client' => $client->created_at->diffInMonths(now()),
                'kyc_status' => $client->kyc_status,
            ],
            'savings_analysis' => [
                'total_balance' => $totalBalance,
                'savings_balance' => $client->accounts->where('account_type', 'savings')->sum('balance'),
                'coverage_ratio' => $coverageRatio,
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
            'insights' => $insights,
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
