<?php

namespace App\Http\Controllers\Web\Agent;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Agency;
use App\Models\User;
use App\Models\CashierSession;
use App\Models\Client;
use App\Models\SavingsAccount;
use App\Models\Transaction;
use App\Models\TontineAccount;
use App\Models\TontineCycle;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\AuditLog;
use App\Models\SystemParameter;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AgentCashierController extends Controller
{

    /**
     * Tableau de bord principal du caissier
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        // Session de caisse active
        $activeSession = $this->getActiveSession($user);

        if (!$activeSession) {
            return view('agent.cashier.session_required');
        }

        $today = Carbon::today();

        // Stats du jour (session active)
        $todayStats = Transaction::where('cashier_session_id', $activeSession->id)
            ->where('status', 'completed')
            ->select(
                DB::raw('SUM(CASE WHEN transaction_type IN ("deposit","savings_deposit","tontine_deposit","loan_repayment","transfer_in") THEN amount ELSE 0 END) as total_in'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("withdrawal","payout","loan_disbursement","transfer_out") THEN amount ELSE 0 END) as total_out'),
                DB::raw('COUNT(*) as tx_count')
            )->first();

        $currentCash = (float) $activeSession->opening_balance + (float) ($todayStats->total_in ?? 0) - (float) ($todayStats->total_out ?? 0);

        // Trésorerie de l'agence
        $agency = Agency::find($activeSession->agency_id ?? ($user->agency_id ?? 1));
        $vaultBalance = $agency ? (float) $agency->vault_balance : 0.0;

        // Stats collectes agents terrain du jour
        $fieldAgentsCount = User::where(function ($q) {
            $q->where('role', 'agent_terrain')->orWhere('role', 'LIKE', 'agent%');
        })->where('role', '!=', 'caissier')
        ->where('agency_id', $agency->id ?? 1)
        ->count();

        $fieldAgentsTodayCollected = Transaction::whereHas('processedBy', function ($q) use ($agency) {
            $q->where('role', '!=', 'caissier')->where('agency_id', $agency->id ?? 1);
        })->where('status', 'completed')
          ->whereDate('transaction_date', $today)
          ->whereIn('transaction_type', ['deposit', 'savings_deposit', 'tontine_deposit', 'loan_repayment'])
          ->sum('amount');

        // Stats clients de l'agence
        $agencyClientCount = Client::where('registration_status', 'approved')
            ->when($agency, fn($q) => $q->where('agency_id', $agency->id))
            ->count();

        // Clients distincts servis au guichet durant cette session
        $todayClientsServed = Transaction::where('transactions.cashier_session_id', $activeSession->id)
            ->where('transactions.status', 'completed')
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->distinct('accounts.client_id')
            ->count('accounts.client_id');

        // Prêts actifs en attente de remboursement
        $activeLoanCount = Loan::whereIn('status', ['active', 'disbursed'])->count();
        $approvedLoanCount = Loan::where('status', 'approved')->count();

        // Dernières transactions avec possibilité d'extourne
        $recentTransactions = Transaction::with(['account.client', 'loan.client', 'processedBy'])
            ->where('cashier_session_id', $activeSession->id)
            ->latest('transaction_date')
            ->limit(10)
            ->get();

        return view('agent.cashier.dashboard', [
            'activeSession'             => $activeSession,
            'todayStats'                => $todayStats,
            'currentCash'               => $currentCash,
            'vaultBalance'              => $vaultBalance,
            'agency'                    => $agency,
            'agencyClientCount'         => $agencyClientCount,
            'todayClientsServed'        => $todayClientsServed,
            'activeLoanCount'           => $activeLoanCount,
            'approvedLoanCount'         => $approvedLoanCount,
            'fieldAgentsCount'          => $fieldAgentsCount,
            'fieldAgentsTodayCollected' => $fieldAgentsTodayCollected,
            'recentTransactions'        => $recentTransactions,
        ]);
    }

    // ================================================================
    //  TERMINAL DE CAISSE (Sessions)
    // ================================================================

    /**
     * Terminal de caisse - Journal des opérations
     */
    public function terminal(Request $request)
    {
        try {
            Log::info('terminal method called', ['user_id' => Auth::id()]);
            $user = Auth::user();
            $activeSession = $this->getActiveSession($user);

            if (!$activeSession) {
                return view('agent.cashier.session_required');
            }

        $isAuditMode = $request->filled('date_start') || $request->filled('date_end');
        $statusFilter = $request->get('status', 'all');

        $baseQuery = Transaction::query();

        if ($isAuditMode) {
            if ($request->filled('date_start')) {
                $baseQuery->whereDate('transaction_date', '>=', $request->date_start);
            }
            if ($request->filled('date_end')) {
                $baseQuery->whereDate('transaction_date', '<=', $request->date_end);
            }
            $openingBalanceForPeriod = 0;
        } else {
            $baseQuery->where('cashier_session_id', $activeSession->id);
            $openingBalanceForPeriod = $activeSession->opening_balance;
        }

        // Totaux financiers basés uniquement sur les écritures complétées
        $statsQuery = (clone $baseQuery)->where('status', 'completed');
        $totals = $statsQuery->select(
            DB::raw('SUM(CASE WHEN transaction_type IN ("deposit","savings_deposit","tontine_deposit","loan_repayment","transfer_in") THEN amount ELSE 0 END) as total_in'),
            DB::raw('SUM(CASE WHEN transaction_type IN ("withdrawal","payout","loan_disbursement","transfer_out") THEN amount ELSE 0 END) as total_out')
        )->first();

        $displayStats = (object) [
            'total_in' => $totals->total_in ?? 0,
            'total_out' => $totals->total_out ?? 0,
            'opening_balance' => $openingBalanceForPeriod,
            'is_audit' => $isAuditMode,
        ];
        $displayStats->current_balance = $openingBalanceForPeriod + $displayStats->total_in - $displayStats->total_out;

        // Compteurs pour filtres du journal
        $countAll = (clone $baseQuery)->count();
        $countCompleted = (clone $baseQuery)->where('status', 'completed')->count();
        $countCancelled = (clone $baseQuery)->where('status', 'cancelled')->count();

        // Requête de liste avec eager-loading et filtre de statut optionnel
        $listQuery = (clone $baseQuery)->with(['account.client', 'loan.client']);
        if ($statusFilter === 'completed') {
            $listQuery->where('status', 'completed');
        } elseif ($statusFilter === 'cancelled') {
            $listQuery->where('status', 'cancelled');
        }

        $perPage = (int) $request->get('per_page', 15);
        $recentTransactions = $listQuery->latest('transaction_date')->latest('id')->paginate($perPage)->withQueryString();

            return view('agent.cashier.terminal', [
                'activeSession'      => $activeSession,
                'recentTransactions' => $recentTransactions,
                'stats'              => $displayStats,
                'statusFilter'       => $statusFilter,
                'countAll'           => $countAll,
                'countCompleted'     => $countCompleted,
                'countCancelled'     => $countCancelled,
            ]);
        } catch (\Throwable $e) {
            Log::error('Terminal error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Afficher le formulaire de clôture de session (Résumé)
     */
    public function closeSessionForm()
    {
        $session = $this->getActiveSession();

        $stats = DB::table('transactions')
            ->where('cashier_session_id', $session->id)
            ->where('status', 'completed')
            ->select([
                DB::raw("SUM(CASE WHEN transaction_type IN ('deposit', 'savings_deposit', 'tontine_deposit', 'loan_repayment') THEN amount ELSE 0 END) as total_in"),
                DB::raw("SUM(CASE WHEN transaction_type IN ('withdrawal', 'savings_withdrawal') THEN amount ELSE 0 END) as total_out")
            ])
            ->first();

        return view('agent.cashier.close', [
            'activeSession' => $session,
            'todayStats' => $stats
        ]);
    }

    /**
     * Ouvrir la session de caisse par le caissier lui-même
     */
    public function openSession(Request $request)
    {
        $request->validate([
            'opening_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();

        // Contrôle d'habilitation : le caissier ne peut ouvrir sa caisse que si l'administrateur lui en a donné l'autorisation
        $isAdmin = in_array($user->role, ['administrateur_systeme', 'administrateur_reglementaire']);
        if (!$isAdmin && !$user->can_open_cashier_session) {
            return redirect()->back()
                ->with('error', "⛔ Vous n'êtes pas autorisé à ouvrir votre caisse de manière autonome. Cette action doit être effectuée par un Administrateur.");
        }

        // Vérifier si une session est déjà ouverte
        $existing = CashierSession::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return redirect()->route('caissier.dashboard')
                ->with('info', 'Votre session de caisse est déjà active.');
        }

        try {
            DB::beginTransaction();

            $agency = Agency::lockForUpdate()->find($user->agency_id ?? 1);
            $openingBalance = (float) $request->opening_balance;

            // Si le caissier demande un approvisionnement initial prélevé sur le coffre
            if ($openingBalance > 0 && $agency) {
                if ($agency->vault_balance < $openingBalance) {
                    throw new \Exception(
                        'Fonds insuffisants dans le coffre d\'agence. ' .
                        'Disponible : ' . number_format($agency->vault_balance, 0, ',', ' ') . ' FCFA. ' .
                        'Montant demandé : ' . number_format($openingBalance, 0, ',', ' ') . ' FCFA.'
                    );
                }
                $agency->decrement('vault_balance', $openingBalance);
            }

            $session = CashierSession::create([
                'user_id' => $user->id,
                'agency_id' => $user->agency_id ?? ($agency->id ?? 1),
                'opened_at' => now(),
                'opening_balance' => $openingBalance,
                'status' => 'open',
                'notes' => $request->notes ?? 'Ouverture de session au guichet'
            ]);

            // Enregistrer l'opération d'approvisionnement initial si > 0
            if ($openingBalance > 0) {
                Transaction::create([
                    'transaction_reference' => 'PROV-' . date('YmdHis') . '-' . rand(1000, 9999),
                    'transaction_type' => 'transfer_in',
                    'amount' => $openingBalance,
                    'balance_before' => 0,
                    'balance_after' => $openingBalance,
                    'status' => 'completed',
                    'processed_by' => $user->id,
                    'agency_id' => $session->agency_id,
                    'cashier_session_id' => $session->id,
                    'processed_at' => now(),
                    'transaction_date' => now(),
                    'description' => 'Dotation initiale de fond de caisse'
                ]);
            }

            DB::commit();

            return redirect()->route('caissier.dashboard')
                ->with('success', 'Votre session de caisse a été ouverte avec succès ! Fond initial : ' . number_format($openingBalance, 0, ',', ' ') . ' FCFA');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Clôturer manuellement une session
     */
    public function closeSession(Request $request, CashierSession $session)
    {
        $user = Auth::user();

        if ($session->status === 'closed') {
            return redirect()->back()->with('error', 'Cette session est déjà fermée.');
        }

        $totals = Transaction::where('cashier_session_id', $session->id)
            ->where('status', 'completed')
            ->select(
                DB::raw('SUM(CASE WHEN transaction_type IN ("deposit","savings_deposit","tontine_deposit","loan_repayment") THEN amount ELSE 0 END) as total_deposits'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("withdrawal","payout") THEN amount ELSE 0 END) as total_withdrawals')
            )->first();

        $expectedClosingBalance = $session->opening_balance + ($totals->total_deposits ?? 0) - ($totals->total_withdrawals ?? 0);

        $session->update([
            'closed_at' => now(),
            'closing_balance' => $request->closing_balance ?? $expectedClosingBalance,
            'expected_closing_balance' => $expectedClosingBalance,
            'total_deposits' => $totals->total_deposits ?? 0,
            'total_withdrawals' => $totals->total_withdrawals ?? 0,
            'status' => 'closed',
            'notes' => $request->notes ?? 'Clôture manuelle par le caissier',
        ]);

        // Générer une notification pour l'administrateur
        Notification::systemNotification(
            "🔴 Clôture de Caisse : {$user->full_name}",
            "Le caissier {$user->full_name} ({$user->agency->name}) a clôturé sa session. " .
            "Volume Entrées: " . number_format($totals->total_deposits, 0, ',', ' ') . " CFA | " .
            "Volume Sorties: " . number_format($totals->total_withdrawals, 0, ',', ' ') . " CFA | " .
            "Solde Final: " . number_format($session->closing_balance, 0, ',', ' ') . " CFA.",
            Notification::TYPE_WARNING,
            'cashier_session',
            $session->id
        );

        return redirect()->route('caissier.terminal')
            ->with('success', 'Session fermée avec succès. Solde final validé : ' . number_format($expectedClosingBalance, 0, ',', ' ') . ' FCFA');
    }

    // ================================================================
    //  ENCAISSEMENT (Dépôts - Épargne, Tontine, Remboursement Prêt)
    // ================================================================

    /**
     * Page de dépôt rapide
     */
    public function depotForm()
    {
        $user = Auth::user();
        $activeSession = $this->getActiveSession($user);
        if (!$activeSession) return view('agent.cashier.session_required');
        return view('agent.cashier.depot', compact('activeSession'));
    }

    /**
     * Recherche AJAX de comptes pour dépôt (Optimisée)
     */
    public function depotSearch(Request $request)
    {
        $request->validate(['query' => 'required|string|min:1']);
        $query = trim($request->get('query'));

        // ⚡ Mode rapide : recherche exacte par préfixe de compte ou prêt (ex: ACC-, SAV-, TNT-, LN-, LOAN-)
        $isExactSearch = (bool) preg_match('/^(TON|TNT|SAV|EPR|LN|LOAN|PRE|CLI|ACC)-/i', $query);

        if ($isExactSearch) {
            // Recherche rapide par préfixe de numéro
            $accounts = Account::select('accounts.id', 'accounts.account_number', 'accounts.account_type', 'accounts.balance', 'accounts.client_id')
                ->with(['client:id,first_name,last_name,client_number,phone', 'tontineAccount:id,account_id,tontine_amount'])
                ->where('status', 'active')
                ->where('account_number', 'like', "{$query}%")
                ->limit(10)
                ->get();

            $loans = Loan::select('id', 'loan_number', 'client_id', 'status', 'total_amount_due', 'total_paid')
                ->with('client:id,first_name,last_name,phone')
                ->whereIn('status', ['approved', 'disbursed', 'active'])
                ->where('loan_number', 'like', "{$query}%")
                ->limit(5)
                ->get();
        } else {
            // Recherche par nom, téléphone, numéro de client ou numéro partiel de compte/prêt
            $cleanPhone = preg_replace('/[^0-9]/', '', $query);

            $accounts = Account::select('accounts.id', 'accounts.account_number', 'accounts.account_type', 'accounts.balance', 'accounts.client_id')
                ->with(['tontineAccount:id,account_id,tontine_amount'])
                ->join('clients', 'accounts.client_id', '=', 'clients.id')
                ->where('accounts.status', 'active')
                ->where(function ($q) use ($query, $cleanPhone) {
                    $q->where('accounts.account_number', 'like', "%{$query}%")
                        ->orWhere('clients.first_name', 'like', "%{$query}%")
                        ->orWhere('clients.last_name', 'like', "%{$query}%")
                        ->orWhere('clients.client_number', 'like', "%{$query}%")
                        ->orWhere('clients.phone', 'like', "%{$query}%")
                        ->orWhereRaw("CONCAT(clients.first_name, ' ', clients.last_name) LIKE ?", ["%{$query}%"]);

                    if (!empty($cleanPhone) && strlen($cleanPhone) >= 4) {
                        $q->orWhere('clients.phone', 'like', "%{$cleanPhone}%");
                    }
                })
                ->limit(15)
                ->get()
                ->load('client:id,first_name,last_name,client_number,phone');

            $loans = Loan::select('loans.id', 'loans.loan_number', 'loans.client_id', 'loans.status', 'loans.total_amount_due', 'loans.total_paid')
                ->join('clients', 'loans.client_id', '=', 'clients.id')
                ->whereIn('loans.status', ['approved', 'disbursed', 'active'])
                ->where(function ($q) use ($query, $cleanPhone) {
                    $q->where('loans.loan_number', 'like', "%{$query}%")
                        ->orWhere('clients.first_name', 'like', "%{$query}%")
                        ->orWhere('clients.last_name', 'like', "%{$query}%")
                        ->orWhere('clients.phone', 'like', "%{$query}%")
                        ->orWhereRaw("CONCAT(clients.first_name, ' ', clients.last_name) LIKE ?", ["%{$query}%"]);

                    if (!empty($cleanPhone) && strlen($cleanPhone) >= 4) {
                        $q->orWhere('clients.phone', 'like', "%{$cleanPhone}%");
                    }
                })
                ->limit(5)
                ->get()
                ->load('client:id,first_name,last_name,phone');
        }

        $accountResults = $accounts->map(function ($account) {
            $info = [
                'id' => $account->id,
                'account_number' => $account->account_number,
                'account_type' => $account->account_type,
                'balance' => $account->balance,
                'client_name' => $account->client->full_name ?? 'N/A',
                'client_number' => $account->client->client_number ?? '',
                'client_phone' => $account->client->phone ?? '',
                'deposit_url' => route('caissier.depot.process', $account->id),
            ];

            if ($account->account_type === 'tontine' && $account->tontineAccount) {
                $info['tontine_amount'] = $account->tontineAccount->tontine_amount;
                $info['suggested_amount'] = $account->tontineAccount->tontine_amount;
            }

            return $info;
        });

        $loanResults = $loans->map(function ($loan) {
            return [
                'id' => $loan->id,
                'account_number' => $loan->loan_number,
                'account_type' => 'loan',
                'balance' => $loan->remaining_amount,
                'client_name' => $loan->client->full_name,
                'client_phone' => $loan->client->phone,
                'deposit_url' => route('caissier.depot.process', $loan->id) . '?type=loan',
                'schedule_url' => route('caissier.loans.schedule', $loan->id)
            ];
        });

        $results = $accountResults->concat($loanResults);

        return response()->json([
            'success' => true,
            'data' => $results,
            'fast_mode' => $isExactSearch,
        ]);
    }

    public function processDeposit(Request $request, $accountId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
            'payment_method' => 'required|in:cash,mobile_money,bank_transfer',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $activeSession = $this->getActiveSession($user);
            if (!$activeSession) throw new \Exception('Aucune session de caisse ouverte.');
            $amount = $request->amount;

            if ($request->get('type') === 'loan') {
                // Traitement d'un remboursement de prêt
                $loan = Loan::with(['client'])->lockForUpdate()->findOrFail($accountId);
                
                // S'assurer que l'échéancier existe
                $this->ensureLoanSchedule($loan);

                $loan->load(['payments' => function($q) {
                    $q->whereIn('status', ['pending', 'overdue', 'partial'])->orderBy('payment_number');
                }]);
                
                $transaction = Transaction::create([
                    'transaction_reference' => $this->generateTransactionReference(),
                    'loan_id' => $loan->id,
                    'transaction_type' => 'loan_repayment',
                    'amount' => $amount,
                    'fee_amount' => 0,
                    'balance_before' => $loan->total_paid,
                    'balance_after' => $loan->total_paid + $amount,
                    'payment_method' => $request->payment_method,
                    'payment_reference' => $this->generatePaymentReference($request->payment_method),
                    'description' => $request->description ?? $request->notes ?? 'Remboursement de prêt au guichet',
                    'status' => 'completed',
                    'processed_by' => $user->id,
                    'agency_id' => $user->agency_id,
                    'cashier_session_id' => $activeSession->id,
                    'processed_at' => now(),
                    'transaction_date' => now(),
                ]);

                // 🔥 RÉPARTITION INTELLIGENTE SUR LES ÉCHÉANCES
                $remainingToDistribute = $amount;

                foreach ($loan->payments as $payment) {
                    if ($remainingToDistribute <= 0) break;

                    // Calcul de pénalités si en retard et non calculées
                    $penaltyAmount = $payment->penalty_amount;
                    if (now()->gt($payment->due_date) && !in_array($payment->status, ['paid'])) {
                        $daysLate = now()->diffInDays($payment->due_date);
                        $penaltyRate = SystemParameter::where('parameter_key', 'loan_late_penalty_rate')->value('parameter_value') ?? 0.01;
                        $penaltyAmount = ($payment->expected_amount * $penaltyRate) * $daysLate;
                    }

                    $amountNeededForThisPayment = ($payment->expected_amount + $penaltyAmount) - $payment->paid_amount;
                    
                    if ($amountNeededForThisPayment <= 0) continue;

                    // On prend ce qu'on peut payer pour cette échéance
                    $payForThis = min($remainingToDistribute, $amountNeededForThisPayment);
                    
                    $remainingToDistribute -= $payForThis;
                    $newPaidAmount = $payment->paid_amount + $payForThis;
                    
                    $status = ($newPaidAmount >= ($payment->expected_amount + $penaltyAmount)) ? 'paid' : 'partial';

                    $payment->update([
                        'paid_amount' => $newPaidAmount,
                        'penalty_amount' => $penaltyAmount,
                        'payment_method' => $request->payment_method,
                        'payment_reference' => $transaction->payment_reference,
                        'paid_date' => now(),
                        'status' => $status,
                        'processed_by' => $user->id,
                        'processed_at' => now(),
                    ]);
                }

                // S'il y a un surplus (paiement par anticipation au-delà du tableau)
                if ($remainingToDistribute > 0) {
                    LoanPayment::create([
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
                        'processed_by' => $user->id,
                        'status' => 'paid',
                        'processed_at' => now(),
                    ]);
                }

                // Mettre à jour les totaux du prêt
                $loan->increment('total_paid', $amount);
                
                // Mettre à jour le reste du capital (réduction approximative prioritaire sur le principal)
                $reductionPrincipal = min($loan->outstanding_principal, $amount);
                $reductionInterest = min($loan->outstanding_interest, $amount - $reductionPrincipal);
                
                $loan->decrement('outstanding_principal', $reductionPrincipal);
                $loan->decrement('outstanding_interest', $reductionInterest);

                // Si le prêt est totalement payé, changer le statut
                if ($loan->total_paid >= $loan->total_amount_due) {
                    $loan->update(['status' => 'completed']);
                }

                DB::commit();

                $this->logActivity('loan_repayment', "Remboursement de prêt de " . number_format($amount, 0, ',', ' ') . " FCFA pour le prêt {$loan->loan_number}.", $loan);

                $redirectRoute = ($request->input('source') === 'depot')
                    ? redirect()->route('caissier.depot')
                    : redirect()->route('caissier.loans.show', $loan->id);

                return $redirectRoute
                    ->with('success', '✅ Remboursement de ' . number_format($amount, 0, ',', ' ') . ' FCFA effectué • ' . $loan->client->full_name . ' (Prêt ' . $loan->loan_number . ')')
                    ->with('print_receipt', route('caissier.receipt.print', $transaction->id));

            } else {
                // Traitement d'un dépôt sur compte (Epargne/Tontine)
                $account = Account::with(['client', 'tontineAccount.activeCycle', 'tontineAccount.cycles'])
                    ->lockForUpdate()
                    ->findOrFail($accountId);

                if ($account->status !== 'active') {
                    throw new \Exception('Le compte n\'est pas actif.');
                }

                $balanceBefore = $account->balance;
                $balanceAfter = $balanceBefore + $amount;
                $paymentReference = $this->generatePaymentReference($request->payment_method);
                $description = $request->description;

                if ($account->account_type === 'tontine') {
                    $tontine = $account->tontineAccount;

                    // 🔒 VÉRIFICATION : Ne pas dépasser le total attendu de la tontine
                    $totalRemaining = $tontine->total_expected - $tontine->total_paid;

                    if ($totalRemaining <= 0) {
                        throw new \Exception(
                            'Cette tontine est complète ! Total atteint : ' .
                            number_format($tontine->total_expected, 0, ',', ' ') . ' FCFA'
                        );
                    }

                    // Si le montant dépasse ce qui reste à payer, ajuster automatiquement
                    if ($amount > $totalRemaining) {
                        $amount = $totalRemaining;
                        $balanceAfter = $balanceBefore + $amount;
                    }

                    if (!$description) {
                        $description = 'Cotisation tontine';
                    }

                    // Récupérer ou créer le cycle actif
                    $activeCycle = $tontine->activeCycle;
                    if (!$activeCycle) {
                        $activeCycle = $this->createTontineCycle($tontine);
                    }

                    // Calcul des Pénalités
                    $penaltyAmount = 0;
                    if ($activeCycle && now()->gt($activeCycle->end_date)) {
                        $daysLate = now()->diffInDays($activeCycle->end_date);
                        $penaltyRate = SystemParameter::where('parameter_key', 'tontine_late_penalty_rate')->value('parameter_value') ?? 0.01;
                        $penaltyAmount = $tontine->tontine_amount * $penaltyRate * $daysLate;
                        
                        if ($penaltyAmount > 0) {
                            $tontine->increment('total_penalties', $penaltyAmount);
                        }
                    }

                    // 🔥 GESTION MULTI-CYCLES
                    $remainingAmount = $amount;
                    $cyclesAffected = [];
                    $currentCycle = $activeCycle;
                    
                    // Fonds de Solidarité
                    $solidarityRate = SystemParameter::where('parameter_key', 'solidarity_fund_rate')->value('parameter_value') ?? 0.005;
                    $solidarityAmount = $amount * $solidarityRate;
                    $tontine->increment('solidarity_fund_total', $solidarityAmount);

                    while ($remainingAmount > 0) {
                        $cycleRemaining = $currentCycle->target_amount - $currentCycle->collected_amount;

                        if ($cycleRemaining <= 0) {
                            $currentCycle = $this->getOrCreateNextCycle($tontine, $currentCycle);
                            continue;
                        }

                        $amountForThisCycle = min($remainingAmount, $cycleRemaining);
                        $newCollectedAmount = $currentCycle->collected_amount + $amountForThisCycle;
                        
                        $currentCycle->update([
                            'collected_amount' => $newCollectedAmount,
                        ]);

                        $cyclesAffected[] = [
                            'cycle_number' => $currentCycle->cycle_number,
                            'amount' => $amountForThisCycle,
                            'completed' => $newCollectedAmount >= $currentCycle->target_amount
                        ];

                        $remainingAmount -= $amountForThisCycle;

                        if ($newCollectedAmount >= $currentCycle->target_amount) {
                            $currentCycle->update([
                                'status' => 'completed',
                                'payout_date' => now(),
                            ]);

                            if ($remainingAmount > 0) {
                                $currentCycle = $this->getOrCreateNextCycle($tontine, $currentCycle);
                            }
                        }
                    }

                    // Créer la transaction
                    $transaction = Transaction::create([
                        'transaction_reference' => $this->generateTransactionReference(),
                        'account_id' => $account->id,
                        'transaction_type' => 'deposit', // ENUM standard
                        'amount' => $amount,
                        'fee_amount' => 0,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                        'payment_method' => $request->payment_method,
                        'payment_reference' => $paymentReference,
                        'description' => $description . ' (Cycles: ' . implode(', ', array_column($cyclesAffected, 'cycle_number')) . ')',
                        'status' => 'completed',
                        'processed_by' => $user->id,
                        'cashier_session_id' => $activeSession->id,
                        'agency_id' => $user->agency_id,
                        'processed_at' => now(),
                        'transaction_date' => now(),
                    ]);

                    $tontine->update([
                        'total_paid' => $tontine->total_paid + $amount,
                    ]);

                    $message = $this->generateMultiCycleMessage($amount, $cyclesAffected, $tontine);

                } else {
                    // Épargne
                    if (!$description) {
                        $description = 'Dépôt sur compte d\'épargne';
                    }

                    $transaction = Transaction::create([
                        'transaction_reference' => $this->generateTransactionReference(),
                        'account_id' => $account->id,
                        'transaction_type' => 'deposit',
                        'amount' => $amount,
                        'fee_amount' => 0,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                        'payment_method' => $request->payment_method,
                        'payment_reference' => $paymentReference,
                        'description' => $description,
                        'status' => 'completed',
                        'processed_by' => $user->id,
                        'cashier_session_id' => $activeSession->id,
                        'agency_id' => $user->agency_id,
                        'processed_at' => now(),
                        'transaction_date' => now(),
                    ]);

                    $message = '✅ Dépôt effectué. Nouveau solde: ' . number_format($balanceAfter, 0, ',', ' ') . ' FCFA';
                }

                $account->update([
                    'balance' => $balanceAfter,
                    'last_transaction_at' => now(),
                ]);

                DB::commit();

                $this->logActivity('deposit', "Dépôt de " . number_format($amount, 0, ',', ' ') . " FCFA sur le compte {$account->account_number}.", $account);

                return redirect()->route('caissier.depot')
                    ->with('success', $message)
                    ->with('print_receipt', route('caissier.receipt.print', $transaction->id));
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()
                ->with('error', 'Erreur lors du dépôt: ' . $e->getMessage());
        }
    }

    // ================================================================
    //  DÉCAISSEMENT (Retraits)
    // ================================================================

    /**
     * Page de retrait
     */
    public function retraitForm()
    {
        $user = Auth::user();
        $activeSession = $this->getActiveSession($user);
        if (!$activeSession) return view('agent.cashier.session_required');
        return view('agent.cashier.retrait', compact('activeSession'));
    }

    /**
     * Détail du prêt (Échéancier)
     */
    public function loanSchedule($id)
    {
        $loan = Loan::with(['client', 'payments'])->findOrFail($id);
        $user = Auth::user();
        $activeSession = $this->getActiveSession($user);
        if (!$activeSession) return view('agent.cashier.session_required');
        return view('agent.cashier.loans.schedule', compact('loan', 'activeSession'));
    }

    /**
     * Recherche AJAX de comptes pour retrait (Optimisée)
     */
    public function retraitSearch(Request $request)
    {
        $request->validate(['query' => 'required|string|min:1']);
        $query = trim($request->get('query'));

        // ⚡ Mode rapide : recherche exacte par préfixe de compte (ex: ACC-, SAV-, TNT-)
        $isExactSearch = (bool) preg_match('/^(TON|TNT|SAV|EPR|ACC)-/i', $query);

        if ($isExactSearch) {
            $accounts = Account::select('accounts.id', 'accounts.account_number', 'accounts.account_type', 'accounts.balance', 'accounts.client_id')
                ->with(['client:id,first_name,last_name,client_number,phone', 'tontineAccount'])
                ->where('status', 'active')
                ->where('account_number', 'like', "{$query}%")
                ->limit(15)
                ->get();
        } else {
            $cleanPhone = preg_replace('/[^0-9]/', '', $query);

            $accounts = Account::select('accounts.id', 'accounts.account_number', 'accounts.account_type', 'accounts.balance', 'accounts.client_id')
                ->join('clients', 'accounts.client_id', '=', 'clients.id')
                ->where('accounts.status', 'active')
                ->where(function ($q) use ($query, $cleanPhone) {
                    $q->where('accounts.account_number', 'like', "%{$query}%")
                        ->orWhere('clients.first_name', 'like', "%{$query}%")
                        ->orWhere('clients.last_name', 'like', "%{$query}%")
                        ->orWhere('clients.client_number', 'like', "%{$query}%")
                        ->orWhere('clients.phone', 'like', "%{$query}%")
                        ->orWhereRaw("CONCAT(clients.first_name, ' ', clients.last_name) LIKE ?", ["%{$query}%"]);

                    if (!empty($cleanPhone) && strlen($cleanPhone) >= 4) {
                        $q->orWhere('clients.phone', 'like', "%{$cleanPhone}%");
                    }
                })
                ->limit(15)
                ->get()
                ->load(['client:id,first_name,last_name,client_number,phone', 'tontineAccount']);
        }

        return response()->json([
            'success' => true,
            'data' => $accounts->map(function ($account) {
                return [
                    'id' => $account->id,
                    'account_number' => $account->account_number,
                    'account_type' => $account->account_type,
                    'balance' => $account->balance,
                    'client_name' => $account->client->full_name ?? 'N/A',
                    'client_number' => $account->client->client_number ?? '',
                    'client_phone' => $account->client->phone ?? '',
                    'tontine_amount' => $account->tontineAccount->tontine_amount ?? 0,
                    'payment_frequency' => $account->tontineAccount->payment_frequency ?? '',
                    'withdrawal_url' => route('caissier.retrait.process', $account->id),
                ];
            }),
            'fast_mode' => $isExactSearch,
        ]);
    }

    /**
     * Traitement du retrait
     */
    public function processWithdrawal(Request $request, $accountId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
            'payment_method' => 'required|in:cash,mobile_money,bank_transfer',
            'withdrawal_fee' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $account = Account::with(['client', 'savingsAccount', 'tontineAccount'])
                ->lockForUpdate()
                ->findOrFail($accountId);

            if ($account->status !== 'active') {
                throw new \Exception('Le compte n\'est pas actif.');
            }

            $amount = $request->amount; // Montant que le client reçoit
            
            // Calculer les frais de retrait via les paramètres système si non fournis
            if ($request->filled('withdrawal_fee')) {
                $withdrawalFee = (float) $request->withdrawal_fee;
            } else {
                if ($account->account_type === 'tontine') {
                    $tontine = $account->tontineAccount;
                    $mise = (float) $tontine->tontine_amount;
                    
                    if ($tontine->payment_frequency === 'daily') {
                        // Règle 1/31 : Une mise de commission par tranche de 31 jours
                        $nbDaysTotal = $amount / ($mise ?: 1);
                        $nbCommissions = ceil($nbDaysTotal / 31);
                        $withdrawalFee = $nbCommissions * $mise;
                    } elseif ($tontine->payment_frequency === 'weekly') {
                        // Une semaine de commission par cycle annuel (52 semaines)
                        $nbWeeksTotal = $amount / ($mise ?: 1);
                        $nbCommissions = ceil($nbWeeksTotal / 52);
                        $withdrawalFee = $nbCommissions * $mise;
                    } else {
                        // Mensuel ou autre : une mise par défaut
                        $withdrawalFee = $mise;
                    }
                } else {
                    $feePercentage = (float) (SystemParameter::where('parameter_key', 'savings_withdrawal_fee_percentage')->value('parameter_value') ?? 2.0);
                    $feeFixed = (float) (SystemParameter::where('parameter_key', 'savings_withdrawal_fee_fixed')->value('parameter_value') ?? 0);
                    $withdrawalFee = round(($amount * ($feePercentage / 100)) + $feeFixed);
                }
            }

            $totalDebit = $amount + $withdrawalFee;

            if ($totalDebit > $account->balance) {
                throw new \Exception('Solde insuffisant pour couvrir le retrait et les frais. Requis: ' . number_format($totalDebit, 0, ',', ' ') . ' FCFA');
            }
            $activeSession = $this->getActiveSession($user);
            if (!$activeSession) throw new \Exception('Aucune session de caisse ouverte.');
            $balanceBefore = $account->balance;
            $balanceAfter = $balanceBefore - $totalDebit;

            $transaction = Transaction::create([
                'transaction_reference' => $this->generateTransactionReference(),
                'account_id' => $account->id,
                'transaction_type' => 'withdrawal',
                'amount' => $amount,
                'fee_amount' => $withdrawalFee,
                'withdrawal_fee' => $withdrawalFee,
                'net_amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'payment_method' => $request->payment_method,
                'payment_reference' => $this->generatePaymentReference($request->payment_method),
                'description' => $request->description ?? 'Retrait au guichet',
                'status' => 'completed',
                'processed_by' => $user->id,
                'agency_id' => $user->agency_id,
                'cashier_session_id' => $activeSession->id,
                'processed_at' => now(),
                'transaction_date' => now(),
            ]);

            $account->update([
                'balance' => $balanceAfter,
                'last_transaction_at' => now(),
            ]);

            // RÈGLE TONTINE : Suspendre si le solde est à zéro
            if ($account->account_type === 'tontine' && $balanceAfter == 0) {
                $account->update([
                    'status' => 'suspended',
                    'suspension_reason' => 'Compte vidé suite à un retrait total',
                    'suspended_at' => now(),
                    'suspended_by' => $user->id,
                ]);

                if ($account->tontineAccount && $account->tontineAccount->activeCycle) {
                    $account->tontineAccount->activeCycle->update([
                        'status' => 'completed',
                        'completion_date' => now(),
                    ]);
                }
            }

            DB::commit();

            $this->logActivity('withdrawal', "Retrait de " . number_format($amount, 0, ',', ' ') . " FCFA (frais: " . number_format($withdrawalFee, 0, ',', ' ') . ") sur le compte " . $account->account_number . ".", $account);

            return redirect()->route('caissier.retrait')
                ->with('success', '✅ Retrait effectué. Remis: ' . number_format($amount, 0, ',', ' ') . ' FCFA. Frais: ' . number_format($withdrawalFee, 0, ',', ' ') . ' FCFA.')
                ->with('print_receipt', route('caissier.receipt.print', $transaction->id));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()
                ->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    // ================================================================
    //  UTILITAIRES PRIVÉES
    // ================================================================

    /**
     * Récupérer la session active du caissier (doit être ouverte par un administrateur)
     */
    private function getActiveSession($user = null): ?CashierSession
    {
        $user = $user ?? Auth::user();
        
        $session = CashierSession::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if ($session) {
            return $session;
        }

        // Si administrateur ou superviseur, rattacher à la session ouverte de son agence ou la dernière active
        if ($user && in_array($user->role, ['administrateur_systeme', 'administrateur_reglementaire', 'gestionnaire_superviseur'])) {
            return CashierSession::where('status', 'open')
                ->when($user->agency_id, fn($q) => $q->where('agency_id', $user->agency_id))
                ->latest('opened_at')
                ->first()
                ?? CashierSession::where('status', 'open')->latest('opened_at')->first();
        }

        return null;
    }

    private function generateTransactionReference(): string
    {
        do {
            $reference = 'TXN-' . date('YmdHis') . '-' . rand(1000, 9999);
        } while (Transaction::where('transaction_reference', $reference)->exists());
        return $reference;
    }

    // ================================================================
    //  DÉCAISSEMENT DE PRÊTS
    // ================================================================

    /**
     * Liste des prêts approuvés en attente de décaissement
     */
    public function loanDisbursementList()
    {
        $user = Auth::user();
        $activeSession = $this->getActiveSession($user);

        $loans = Loan::with(['client', 'guarantors'])
            ->where('status', 'approved')
            ->whereNull('disbursed_at')
            ->latest()
            ->get();

        $totalPendingCount = $loans->count();
        $totalPendingAmount = (float) $loans->sum('approved_amount');

        // Prêts décaissés aujourd'hui
        $todayDisbursedLoans = Loan::with(['client', 'guarantors'])
            ->where('status', 'disbursed')
            ->whereDate('disbursed_at', now()->toDateString())
            ->latest('disbursed_at')
            ->get();
        $todayDisbursedCount = $todayDisbursedLoans->count();
        $todayDisbursedAmount = (float) $todayDisbursedLoans->sum('approved_amount');

        // Solde liquide actuellement disponible en caisse
        $currentCash = 0;
        if ($activeSession) {
            $todayStats = Transaction::where('cashier_session_id', $activeSession->id)
                ->where('status', 'completed')
                ->select(
                    DB::raw('SUM(CASE WHEN transaction_type IN ("deposit","savings_deposit","tontine_deposit","loan_repayment","transfer_in") THEN amount ELSE 0 END) as total_in'),
                    DB::raw('SUM(CASE WHEN transaction_type IN ("withdrawal","payout","loan_disbursement","transfer_out") THEN amount ELSE 0 END) as total_out')
                )->first();
            $currentCash = (float) $activeSession->opening_balance + (float) ($todayStats->total_in ?? 0) - (float) ($todayStats->total_out ?? 0);
        }

        return view('agent.cashier.loans.disbursements', compact(
            'loans',
            'totalPendingCount',
            'totalPendingAmount',
            'todayDisbursedLoans',
            'todayDisbursedCount',
            'todayDisbursedAmount',
            'currentCash',
            'activeSession'
        ));
    }

    /**
     * Traiter le décaissement d'un prêt
     */
    public function processLoanDisbursement(Request $request, Loan $loan)
    {
        if ($loan->status !== 'approved' || $loan->disbursed_at) {
            return redirect()->back()->with('error', 'Ce prêt ne peut pas être décaissé.');
        }

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $activeSession = $this->getActiveSession($user);
            if (!$activeSession) throw new \Exception('Aucune session de caisse ouverte.');

            // 1. Mettre à jour le prêt
            $firstDate = now()->addMonth();
            $loan->update([
                'status' => 'disbursed',
                'disbursed_at' => now(),
                'disbursed_by' => $user->id,
                'first_payment_date' => $firstDate,
            ]);

            // Générer automatiquement l'échéancier si inexistant
            $this->ensureLoanSchedule($loan);

            // 2. Créer la transaction (Inflow/Outflow: Ici c'est un Outflow pour la caisse)
            $transaction = Transaction::create([
                'transaction_reference' => $this->generateTransactionReference(),
                'transaction_type' => 'loan_disbursement',
                'loan_id' => $loan->id,
                'amount' => $loan->approved_amount,
                'balance_before' => 0,
                'balance_after' => 0,
                'payment_method' => 'cash',
                'payment_reference' => $loan->loan_number,
                'description' => 'Décaissement du prêt ' . $loan->loan_number,
                'status' => 'completed',
                'processed_by' => $user->id,
                'agency_id' => $user->agency_id,
                'cashier_session_id' => $activeSession->id,
                'processed_at' => now(),
                'transaction_date' => now(),
            ]);

            // 3. Logger l'action caissier
            $this->logActivity('loan_disbursement', "Décaissement du prêt {$loan->loan_number} pour un montant de " . number_format($loan->approved_amount, 0, ',', ' ') . " FCFA.", $loan);

            DB::commit();

            return redirect()->route('caissier.loans.disbursement')
                ->with('success', '✅ Décaissement effectué avec succès pour le prêt ' . $loan->loan_number)
                ->with('print_receipt', route('caissier.receipt.print', $transaction->id));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erreur lors du décaissement : ' . $e->getMessage());
        }
    }

    // ================================================================
    //  LOGS & IMPRESSION
    // ================================================================

    /**
     * Journal d'activité spécifique du caissier
     */
    public function operationLogs()
    {
        $user = Auth::user();
        $logs = AuditLog::where('user_id', $user->id)
            ->latest('id')
            ->paginate(50);

        return view('agent.cashier.logs', compact('logs'));
    }

    /**
     * Impression d'un reçu de transaction
     */
    public function printReceipt(Transaction $transaction)
    {
        $transaction->load(['account.client', 'loan.client', 'processedBy', 'agency']);
        
        $clientName = $transaction->client?->full_name;
        
        // Fallback intelligent si le prêt n'était pas lié directement
        if (!$clientName) {
            $ref = $transaction->payment_reference ?? '';
            $desc = $transaction->description ?? '';
            
            preg_match('/LOAN-[0-9]{4}-[0-9]{4}/', $ref . ' ' . $desc, $matches);
            if (!empty($matches[0])) {
                $loan = Loan::where('loan_number', $matches[0])->with('client')->first();
                if ($loan) {
                    $clientName = $loan->client?->full_name;
                    $transaction->update(['loan_id' => $loan->id]);
                }
            }
        }
        
        $clientName = $clientName ?? 'N/A';
        
        return view('agent.cashier.receipt', compact('transaction', 'clientName'));
    }

    // ================================================================
    //  UTILITAIRES PRIVÉES
    // ================================================================

    /**
     * Log d'activité caissier
     */
    private function logActivity($action, $message, $entity = null)
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'table_name' => $entity ? $entity->getTable() : 'System',
            'record_id' => $entity ? $entity->id : 0,
            'additional_data' => ['message' => $message],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now()
        ]);
    }

    private function generatePaymentReference($method): string
    {
        $prefix = match ($method) {
            'cash' => 'CASH',
            'bank_transfer' => 'BANK',
            'mobile_money' => 'MOMO',
            default => 'PAY',
        };
        do {
            $reference = $prefix . '-' . date('YmdHis') . '-' . strtoupper(Str::random(4));
        } while (Transaction::where('payment_reference', $reference)->exists());
        return $reference;
    }

    /**
     * Helper : Créer un cycle de tontine
     */
    private function createTontineCycle(TontineAccount $tontineAccount): TontineCycle
    {
        $cycleNumber = $tontineAccount->cycles()->count() + 1;
        
        if ($cycleNumber == 1) {
            $startDate = now();
        } else {
            $lastCycle = $tontineAccount->cycles()->latest('cycle_number')->first();
            $startDate = $lastCycle ? $lastCycle->end_date->copy()->addDay() : now();
        }

        $daysInCycle = 31;
        
        switch ($tontineAccount->payment_frequency) {
            case 'daily':
                $endDate = $startDate->copy()->addDays($daysInCycle);
                $targetAmount = $tontineAccount->tontine_amount * $daysInCycle;
                break;
            case 'weekly':
                $endDate = $startDate->copy()->addMonths(1);
                $targetAmount = $tontineAccount->tontine_amount * (52 / 12);
                break;
            case 'monthly':
                $endDate = $startDate->copy()->addMonths(1);
                $targetAmount = $tontineAccount->tontine_amount;
                break;
            default:
                $endDate = $startDate->copy()->addMonths(1);
                $targetAmount = $tontineAccount->tontine_amount;
        }

        return TontineCycle::create([
            'tontine_account_id' => $tontineAccount->id,
            'cycle_number' => $cycleNumber,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'target_amount' => $targetAmount,
            'collected_amount' => 0,
            'payout_amount' => 0,
            'status' => 'active',
        ]);
    }

    /**
     * Helper : Obtenir ou créer le cycle suivant
     */
    private function getOrCreateNextCycle(TontineAccount $tontineAccount, TontineCycle $currentCycle): TontineCycle
    {
        $nextCycle = TontineCycle::where('tontine_account_id', $tontineAccount->id)
            ->where('cycle_number', $currentCycle->cycle_number + 1)
            ->first();

        if (!$nextCycle) {
            $nextCycle = $this->createTontineCycle($tontineAccount);
        }

        return $nextCycle;
    }

    /**
     * Helper : Message succès multi-cycles
     */
    private function generateMultiCycleMessage(float $totalAmount, array $cyclesAffected, TontineAccount $tontine): string
    {
        $nbCycles = count($cyclesAffected);
        $nbCompleted = count(array_filter($cyclesAffected, fn($c) => $c['completed']));

        $message = '✅ Cotisation enregistrée : ' . number_format($totalAmount, 0, ',', ' ') . ' FCFA';

        if ($nbCycles === 1) {
            $cycle = $cyclesAffected[0];
            if ($cycle['completed']) {
                $message .= '<br>🎉 Cycle ' . $cycle['cycle_number'] . ' complété !';
            } else {
                $remaining = $tontine->tontine_amount - $cycle['amount']; // Simplifié pour le message
                $message .= '<br>📊 Cycle ' . $cycle['cycle_number'] . ' mis à jour.';
            }
        } else {
            $message .= '<br>📈 Réparti sur ' . $nbCycles . ' cycle(s).';
            if ($nbCompleted > 0) {
                $message .= '<br>🎉 ' . $nbCompleted . ' cycle(s) complété(s) !';
            }
        }

        $progress = $tontine->total_expected > 0 ? ($tontine->total_paid / $tontine->total_expected) * 100 : 0;
        $message .= '<br>📊 Progression totale : ' . number_format($progress, 1) . '%';

        return $message;
    }
    /**
     * Formulaire combiné d'inscription client + ouverture compte tontine
     */
    public function registerWithTontineForm()
    {
        $user = Auth::user();
        $activeSession = $this->getActiveSession($user);

        if (!$activeSession) {
            return view('agent.cashier.session_required');
        }

        return view('agent.cashier.clients.register_with_tontine');
    }

    /**
     * Traiter l'inscription et la création du compte tontine
     */
    public function storeWithTontine(Request $request)
    {
        $request->validate([
            // Client
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:clients,phone',
            'address' => 'required|string|max:255',
            'gender' => 'nullable|in:M,F',
            // Compte Tontine
            'target_amount' => 'required|numeric|min:100',
            'cycle_duration_months' => 'required|integer|min:1|max:24',
            'payment_frequency' => 'required|in:daily,weekly,monthly',
            'initial_deposit' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $activeSession = $this->getActiveSession($user);

            // 1. Rechercher ou créer le client avec numéro normalisé
            $normalizedPhone = Client::normalizePhone($request->phone);
            $client = Client::where('phone', $normalizedPhone)->first();

            if ($client) {
                if (!$client->canOpenTontineAccount()) {
                    throw new \Exception("Ce client (" . $client->full_name . ") possède déjà un carnet Tontine actif (limite autorisée : " . ($client->max_tontine_accounts ?? 1) . "). Seul l'administrateur peut autoriser un carnet supplémentaire.");
                }
                if ($client->hasActiveUnfinishedTontineCycle()) {
                    throw new \Exception("Ce client a déjà un cycle de tontine de 31 jours en cours de cotisation non achevé. Il doit obligatoirement terminer son mois (31 jours) avant de passer à un autre carnet.");
                }
            } else {
                $client = Client::create([
                    'client_number' => $this->generateClientNumber(),
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'phone' => $normalizedPhone,
                    'address' => $request->address,
                    'gender' => $request->gender,
                    'registered_by' => $user->id,
                    'agency_id' => $user->agency_id,
                    'registration_channel' => 'agent_assisted',
                    'registration_status' => 'approved',
                    'kyc_status' => 'pending',
                    'password' => \Illuminate\Support\Facades\Hash::make('12@4'),
                    'is_active' => true,
                    'max_tontine_accounts' => 1,
                ]);
            }

            // 2. Créer le compte de base
            $account = Account::create([
                'client_id' => $client->id,
                'account_number' => $this->generateAccountNumber('tontine'),
                'account_type' => 'tontine',
                'status' => 'active', // Activé direct car fait en agence
                'balance' => 0,
                'created_by' => $user->id,
                'activated_by' => $user->id,
                'activated_at' => now(),
                'agency_id' => $user->agency_id,
            ]);

            // 3. Créer les détails tontine
            $targetAmount = (float) $request->target_amount;
            $duration = (int) $request->cycle_duration_months;
            $frequency = $request->payment_frequency;

            // Calcul du total attendu approximatif
            $startDate = now();
            $endDate = (clone $startDate)->addMonths($duration);
            
            // 🔹 Calcul du nombre de périodes (Règles institutionnelles : 31j par mois / 52s par an)
            $totalPeriods = match ($frequency) {
                'daily' => $duration * 31,
                'weekly' => (int) round(($duration * 52) / 12),
                'monthly' => $duration,
                default => 0,
            };
            
            $totalExpected = $targetAmount * $totalPeriods;

            $tontineAccount = TontineAccount::create([
                'account_id' => $account->id,
                'tontine_amount' => $targetAmount,
                'cycle_duration_months' => $duration,
                'payment_frequency' => $frequency,
                'expected_monthly_payment' => $targetAmount,
                'total_expected' => $totalExpected,
                'total_paid' => 0,
                'cycle_start_date' => $startDate,
                'cycle_end_date' => $endDate,
            ]);

            // 4. Créer le premier cycle
            $this->createTontineCycle($tontineAccount);

            // 5. Gérer le dépôt initial (facultatif)
            $transactionId = null;
            if ($request->initial_deposit > 0) {
                $amount = $request->initial_deposit;
                $balanceAfter = $amount;

                $transaction = Transaction::create([
                    'transaction_reference' => $this->generateTransactionReference(),
                    'account_id' => $account->id,
                    'transaction_type' => 'deposit',
                    'amount' => $amount,
                    'balance_before' => 0,
                    'balance_after' => $balanceAfter,
                    'payment_method' => 'cash',
                    'payment_reference' => 'INITIAL-DEP-' . time(),
                    'description' => 'Dépôt initial à l\'inscription',
                    'status' => 'completed',
                    'processed_by' => $user->id,
                    'agency_id' => $user->agency_id,
                    'cashier_session_id' => $activeSession->id,
                    'processed_at' => now(),
                    'transaction_date' => now(),
                ]);

                $account->update(['balance' => $amount]);
                $tontineAccount->increment('total_paid', $amount);
                $activeCycle = $tontineAccount->activeCycle;
                if ($activeCycle) {
                    $activeCycle->increment('collected_amount', $amount);
                    if ($activeCycle->collected_amount >= $activeCycle->target_amount) {
                        $activeCycle->update(['status' => 'completed', 'payout_date' => now()]);
                    }
                }
                $transactionId = $transaction->id;
            }

            DB::commit();

            $resp = redirect()->route('caissier.dashboard')
                ->with('success', "Client {$client->full_name} enregistré avec succès et compte tontine ouvert.");
            
            if ($transactionId) {
                $resp->with('print_receipt', route('caissier.receipt.print', $transactionId));
            }

            return $resp;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur Inscription Cashier: " . $e->getMessage());
            return redirect()->back()->withInput()->with('error', "Erreur: " . $e->getMessage());
        }
    }

    // ================================================================
    //  VIREMENT TRÉSORERIE / COFFRE-FORT (APPROVISIONNEMENT & DÉCHARGE)
    // ================================================================

    /**
     * Approvisionnement ou décharge direct(e) Trésorerie <-> Caisse au guichet
     */
    public function vaultTransfer(Request $request)
    {
        $request->validate([
            'type'   => 'required|in:supply,discharge,in,out', // supply=coffre->caisse, discharge=caisse->coffre
            'amount' => 'required|numeric|min:100',
            'notes'  => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $session = $this->getActiveSession($user);

        if (!$session) {
            return back()->with('error', 'Opération refusée : Aucune session de caisse ouverte.');
        }

        $agency = Agency::lockForUpdate()->findOrFail($session->agency_id ?? ($user->agency_id ?? 1));
        $amount = (float) $request->amount;
        $type = in_array($request->type, ['supply', 'in']) ? 'supply' : 'discharge';

        // Calculer solde physique actuel de la session
        $totals = Transaction::where('cashier_session_id', $session->id)
            ->where('status', 'completed')
            ->select(
                DB::raw('SUM(CASE WHEN transaction_type IN ("deposit", "savings_deposit", "tontine_deposit", "loan_repayment", "transfer_in") THEN amount ELSE 0 END) as total_in'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("withdrawal", "payout", "loan_disbursement", "transfer_out") THEN amount ELSE 0 END) as total_out')
            )->first();
        $currentCash = (float) $session->opening_balance + (float) ($totals->total_in ?? 0) - (float) ($totals->total_out ?? 0);

        try {
            DB::beginTransaction();

            if ($type === 'supply') {
                // Approvisionnement : Coffre-fort -> Caisse
                if ($agency->vault_balance < $amount) {
                    throw new \Exception('Fonds insuffisants dans la Trésorerie de l\'agence. Solde disponible : ' . number_format($agency->vault_balance, 0, ',', ' ') . ' FCFA.');
                }
                $agency->decrement('vault_balance', $amount);
                $txType = 'transfer_in';
                $desc = $request->notes ?? 'Approvisionnement caisse depuis le coffre-fort';
                $successMsg = '✅ Approvisionnement de ' . number_format($amount, 0, ',', ' ') . ' FCFA reçu du coffre avec succès.';
            } else {
                // Décharge : Caisse -> Coffre-fort
                if ($currentCash < $amount) {
                    throw new \Exception('Solde de caisse insuffisant pour effectuer cette décharge. Solde disponible : ' . number_format($currentCash, 0, ',', ' ') . ' FCFA.');
                }
                $agency->increment('vault_balance', $amount);
                $txType = 'transfer_out';
                $desc = $request->notes ?? 'Décharge de caisse vers le coffre-fort';
                $successMsg = '✅ Décharge de ' . number_format($amount, 0, ',', ' ') . ' FCFA versée au coffre-fort avec succès.';
            }

            $tx = Transaction::create([
                'transaction_reference' => $this->generateTransactionReference(),
                'transaction_type'      => $txType,
                'amount'                => $amount,
                'balance_before'        => $currentCash,
                'balance_after'         => ($type === 'supply') ? ($currentCash + $amount) : ($currentCash - $amount),
                'payment_method'        => 'cash',
                'description'           => $desc,
                'status'                => 'completed',
                'processed_by'          => $user->id,
                'agency_id'             => $agency->id,
                'cashier_session_id'    => $session->id,
                'processed_at'          => now(),
                'transaction_date'      => now(),
            ]);

            $this->logActivity('vault_transfer', $desc . ' (' . number_format($amount, 0, ',', ' ') . ' FCFA)');

            DB::commit();

            return back()->with('success', $successMsg);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    // ================================================================
    //  EXTOURNE / ANNULATION DE TRANSACTION AVEC PIN SUPERVISEUR
    // ================================================================

    /**
     * Annuler une transaction au guichet sous contrôle d'un PIN superviseur
     */
    public function reverseTransaction(Request $request, $transactionId = null)
    {
        $refOrId = $transactionId ?: $request->input('transaction_reference') ?: $request->input('transaction_id');

        $request->validate([
            'supervisor_pin' => 'required|string',
            'reason'         => 'required|string|min:4|max:500',
        ]);

        if (empty($refOrId)) {
            return back()->with('error', 'Veuillez préciser le numéro de référence de la transaction à extourner.');
        }

        $user = Auth::user();
        $validPins = ['1234', '0000', '9999', '2026', '3600'];

        if (!in_array($request->supervisor_pin, $validPins) && !in_array($user->role, ['gestionnaire_superviseur', 'administrateur_systeme'])) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Code PIN Superviseur incorrect.'], 403);
            }
            return back()->with('error', 'Code PIN Superviseur incorrect.');
        }

        try {
            DB::beginTransaction();

            $transaction = Transaction::with(['account.client'])
                ->where('transaction_reference', trim($refOrId))
                ->orWhere('payment_reference', trim($refOrId))
                ->when(is_numeric($refOrId), function($q) use ($refOrId) {
                    $q->orWhere('id', $refOrId);
                })
                ->first();

            if (!$transaction) {
                throw new \Exception("Aucune transaction trouvée avec la référence « {$refOrId} ».");
            }

            if ($transaction->status !== 'completed') {
                throw new \Exception('Seules les transactions complétées peuvent être extournées.');
            }

            $amount = (float) $transaction->amount;
            $type = strtolower($transaction->transaction_type);
            $account = $transaction->account;

            // Inversion comptable des soldes
            if (str_contains($type, 'deposit') || str_contains($type, 'tontine_deposit') || str_contains($type, 'savings_deposit')) {
                if ($account) {
                    if ($account->balance < $amount) {
                        throw new \Exception('Impossible d\'extourner : le solde actuel du compte (' . number_format($account->balance, 0, ',', ' ') . ' FCFA) est inférieur au montant déposé.');
                    }
                    $account->decrement('balance', $amount);

                    // Rectification du carnet tontine en cas d'extourne d'une cotisation
                    if ($account->account_type === 'tontine' && $account->tontineAccount) {
                        $tontine = $account->tontineAccount;
                        $tontine->decrement('total_paid', min((float)$tontine->total_paid, $amount));
                        $activeCycle = $tontine->activeCycle ?? $tontine->cycles()->latest('cycle_number')->first();
                        if ($activeCycle) {
                            $activeCycle->decrement('collected_amount', min((float)$activeCycle->collected_amount, $amount));
                        }
                    }
                }
            } elseif (str_contains($type, 'withdrawal') || str_contains($type, 'payout')) {
                if ($account) {
                    $account->increment('balance', $amount);
                }
            } elseif (str_contains($type, 'transfer')) {
                if ($account && $transaction->related_account_id) {
                    $destAccount = Account::find($transaction->related_account_id);
                    if ($destAccount && $destAccount->balance < $amount) {
                        throw new \Exception('Impossible d\'extourner le virement : le compte destinataire n\'a pas assez de fonds.');
                    }
                    if ($destAccount) $destAccount->decrement('balance', $amount);
                    $account->increment('balance', $amount);
                }
            }

            // Marquer la transaction annulée
            $transaction->update([
                'status' => 'cancelled',
                'description' => $transaction->description . ' [EXTOURNE: ' . $request->reason . ' - PIN validé]',
            ]);

            // Tracer dans l'audit log
            $this->logActivity('reversal', "Extourne de la transaction {$transaction->transaction_reference} pour {$amount} FCFA. Motif: {$request->reason}", $transaction);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => "Transaction {$transaction->transaction_reference} annulée avec succès."]);
            }

            return back()->with('success', "✅ Transaction {$transaction->transaction_reference} annulée avec succès. Les soldes ont été rectifiés.");

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', 'Erreur lors de l\'annulation : ' . $e->getMessage());
        }
    }

    // ================================================================
    //  VIREMENT DE COMPTE À COMPTE CLIENT
    // ================================================================

    /**
     * Formulaire de virement entre comptes
     */
    public function transferForm(Request $request)
    {
        $user = Auth::user();
        $activeSession = $this->getActiveSession($user);

        if (!$activeSession) {
            return view('agent.cashier.session_required');
        }

        $accounts = Account::with('client')
            ->where('status', 'active')
            ->when($user->agency_id && $user->role === 'caissier', function ($q) use ($user) {
                $q->whereHas('client', fn($cq) => $cq->where('agency_id', $user->agency_id));
            })
            ->orderBy('account_number')
            ->get();

        return view('agent.cashier.transfer', compact('accounts', 'activeSession'));
    }

    /**
     * Traiter le virement entre deux comptes clients
     */
    public function processTransfer(Request $request)
    {
        $request->validate([
            'source_account_id'      => 'required|exists:accounts,id|different:destination_account_id',
            'destination_account_id' => 'required|exists:accounts,id',
            'amount'                 => 'required|numeric|min:100',
            'description'            => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $activeSession = $this->getActiveSession($user);

        if (!$activeSession) {
            return back()->with('error', 'Aucune session de caisse ouverte.');
        }

        try {
            DB::beginTransaction();

            $source = Account::with('client')->lockForUpdate()->findOrFail($request->source_account_id);
            $destination = Account::with('client')->lockForUpdate()->findOrFail($request->destination_account_id);
            $amount = (float) $request->amount;

            if ($source->balance < $amount) {
                throw new \Exception('Solde insuffisant sur le compte source (' . number_format($source->balance, 0, ',', ' ') . ' FCFA).');
            }

            // Débit source
            $sourceBefore = $source->balance;
            $source->decrement('balance', $amount);

            // Crédit destination
            $destBefore = $destination->balance;
            $destination->increment('balance', $amount);

            $ref = $this->generateTransactionReference();
            $desc = $request->description ?? ("Virement de " . $source->client->full_name . " vers " . $destination->client->full_name);

            // Transaction débit
            $txOut = Transaction::create([
                'transaction_reference' => $ref . '-OUT',
                'account_id'            => $source->id,
                'related_account_id'    => $destination->id,
                'transaction_type'      => 'transfer_out',
                'amount'                => $amount,
                'balance_before'        => $sourceBefore,
                'balance_after'         => $sourceBefore - $amount,
                'payment_method'        => 'internal_transfer',
                'description'           => $desc,
                'status'                => 'completed',
                'processed_by'          => $user->id,
                'agency_id'             => $user->agency_id ?? 1,
                'cashier_session_id'    => $activeSession->id,
                'processed_at'          => now(),
                'transaction_date'      => now(),
            ]);

            // Transaction crédit
            Transaction::create([
                'transaction_reference' => $ref . '-IN',
                'account_id'            => $destination->id,
                'related_account_id'    => $source->id,
                'transaction_type'      => 'transfer_in',
                'amount'                => $amount,
                'balance_before'        => $destBefore,
                'balance_after'         => $destBefore + $amount,
                'payment_method'        => 'internal_transfer',
                'description'           => $desc,
                'status'                => 'completed',
                'processed_by'          => $user->id,
                'agency_id'             => $user->agency_id ?? 1,
                'cashier_session_id'    => $activeSession->id,
                'processed_at'          => now(),
                'transaction_date'      => now(),
            ]);

            $this->logActivity('transfer', $desc, $source);

            DB::commit();

            return redirect()->route('caissier.transfer')
                ->with('success', '✅ Virement de ' . number_format($amount, 0, ',', ' ') . ' FCFA exécuté avec succès.')
                ->with('print_receipt', route('caissier.receipt.print', $txOut->id));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    // ================================================================
    //  SUPERVISION DES AGENTS DE TERRAIN
    // ================================================================

    /**
     * Liste et suivi en temps réel des agents de terrain de l'agence
     */
    public function fieldAgents(Request $request)
    {
        $user = Auth::user();
        $agencyId = $user->agency_id ?? 1;

        $agentsQuery = User::where(function ($q) {
            $q->where('role', 'agent_terrain')
              ->orWhere('role', 'LIKE', 'agent%');
        })->where('role', '!=', 'caissier');

        if ($user->role !== 'administrateur_systeme') {
            $agentsQuery->where('agency_id', $agencyId);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $agentsQuery->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        $agents = $agentsQuery->orderBy('first_name')->get();

        // Calcul des métriques journalières pour chaque agent
        $today = Carbon::today();
        $summary = [
            'total_agents'     => $agents->count(),
            'active_today'     => 0,
            'total_collected'  => 0,
            'total_operations' => 0,
        ];

        $agentsData = $agents->map(function ($agent) use ($today, &$summary) {
            $txQuery = Transaction::where('processed_by', $agent->id)
                ->where('status', 'completed')
                ->whereDate('transaction_date', $today);

            $inflows = (clone $txQuery)->whereIn('transaction_type', ['deposit', 'savings_deposit', 'tontine_deposit', 'loan_repayment'])
                ->sum('amount');
            $count = (clone $txQuery)->count();
            $clientsCount = Client::where('registered_by', $agent->id)->count();

            if ($count > 0) {
                $summary['active_today']++;
            }
            $summary['total_collected'] += $inflows;
            $summary['total_operations'] += $count;

            return (object) [
                'agent'            => $agent,
                'today_collected'  => $inflows,
                'today_count'      => $count,
                'today_ops_count'  => $count,
                'clients_count'    => $clientsCount,
                'cash_limit'       => 500000,
                'is_active_today'  => $count > 0,
            ];
        });

        return view('agent.cashier.field_agents.index', [
            'agents'  => $agentsData,
            'summary' => (object) $summary,
            'date'    => $today,
        ]);
    }

    /**
     * Détails et journal des collectes d'un agent de terrain
     */
    public function fieldAgentDetails(Request $request, $agentId)
    {
        $agent = User::findOrFail($agentId);
        $clients = Client::where('registered_by', $agent->id)->with('accounts')->latest()->paginate(15);
        $transactions = Transaction::where('processed_by', $agent->id)
            ->with(['account.client'])
            ->latest('transaction_date')
            ->paginate(25);

        $todayCollected = Transaction::where('processed_by', $agent->id)
            ->whereDate('transaction_date', Carbon::today())
            ->where('status', 'completed')
            ->whereIn('transaction_type', ['deposit', 'savings_deposit', 'tontine_deposit', 'loan_repayment'])
            ->sum('amount');

        return view('agent.cashier.field_agents.show', compact('agent', 'clients', 'transactions', 'todayCollected'));
    }

    // ================================================================
    //  MODULE CRÉDITS & PRÊTS AU GUICHET
    // ================================================================

    /**
     * Portefeuille des prêts de l'agence
     */
    public function loansIndex(Request $request)
    {
        $user = Auth::user();
        $status = $request->get('status', 'all');

        $query = Loan::with(['client', 'approvedBy', 'disbursedBy']);

        if ($user->agency_id && $user->role !== 'administrateur_systeme') {
            $query->whereHas('client', fn($q) => $q->where('agency_id', $user->agency_id));
        }

        if ($status !== 'all' && !empty($status)) {
            $query->where('status', $status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('loan_number', 'like', "%{$s}%")
                  ->orWhereHas('client', fn($cq) => $cq->where('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%"));
            });
        }

        $loans = $query->latest()->paginate(20);

        $metrics = (object) [
            'total'     => Loan::count(),
            'approved'  => Loan::where('status', 'approved')->count(),
            'disbursed' => Loan::whereIn('status', ['disbursed', 'active'])->count(),
            'completed' => Loan::where('status', 'completed')->count(),
        ];

        return view('agent.cashier.loans.index', compact('loans', 'metrics', 'status'));
    }

    /**
     * Formulaire de montage de dossier de prêt au guichet
     */
    public function loanApplyForm(Request $request)
    {
        $user = Auth::user();
        $preselectedClient = $request->client_id ? Client::find($request->client_id) : null;
        $clients = Client::when($user->agency_id && $user->role === 'caissier', fn($q) => $q->where('agency_id', $user->agency_id))
            ->orderBy('first_name')
            ->get();

        return view('agent.cashier.loans.apply', compact('clients', 'preselectedClient'));
    }

    /**
     * Test d'éligibilité AJAX au guichet avec scoring de risque avancé et conseiller IA
     */
    public function checkEligibility(Request $request, $clientId)
    {
        $client = Client::with(['accounts.tontineAccount', 'loans'])->findOrFail($clientId);

        $requestedAmount = (float) $request->input('requested_amount', 200000);
        $durationMonths = (int) $request->input('duration_months', 6);
        $interestRate = (float) $request->input('interest_rate', 17.0);

        $scoringService = new \App\Services\CreditScoringService();
        $analysis = $scoringService->analyze($client, $requestedAmount, $durationMonths, $interestRate);

        return response()->json($analysis);
    }

    /**
     * Simulateur de crédit AJAX
     */
    public function simulateLoan(Request $request)
    {
        $request->validate([
            'amount'          => 'required|numeric|min:10000',
            'duration_months' => 'required|integer|min:1|max:36',
            'interest_rate'   => 'nullable|numeric|min:1|max:50',
        ]);

        $amount = (float) $request->amount;
        $months = (int) $request->duration_months;
        $annualRate = (float) ($request->interest_rate ?? 17.0);

        $totalInterest = $amount * ($annualRate / 100) * ($months / 12);
        $totalDue = $amount + $totalInterest;
        $monthlyPayment = $totalDue / $months;

        return response()->json([
            'success'         => true,
            'amount'          => $amount,
            'interest_rate'   => $annualRate,
            'total_interest'  => round($totalInterest),
            'total_due'       => round($totalDue),
            'monthly_payment' => round($monthlyPayment),
        ]);
    }

    /**
     * Enregistrer une nouvelle demande de prêt au guichet
     */
    public function processLoanApply(Request $request)
    {
        $request->validate([
            'client_id'              => 'required|exists:clients,id',
            'requested_amount'       => 'required|numeric|min:10000',
            'duration_months'        => 'required|integer|min:1|max:36',
            'purpose'                => 'required|string|max:255',
            'collateral_description' => 'nullable|string|max:500',
            'interest_rate'          => 'nullable|numeric|min:1|max:50',
            'guarantors'             => 'nullable|array',
            'verifications'          => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            $annualRate = (float) ($request->interest_rate ?? 17.0);
            $amount = (float) $request->requested_amount;
            $months = (int) $request->duration_months;
            $totalInterest = $amount * ($annualRate / 100) * ($months / 12);

            $loanNumber = 'LN-' . date('Ym') . '-' . rand(1000, 9999);

            // Traitement des vérifications cochées
            $verifications = [];
            $submittedVerifs = $request->input('verifications', []);
            foreach (Loan::VERIFICATION_ITEMS as $key => $item) {
                $verifications[$key] = !empty($submittedVerifs[$key]);
            }

            $loan = Loan::create([
                'loan_number'            => $loanNumber,
                'client_id'              => $request->client_id,
                'requested_amount'       => $amount,
                'approved_amount'        => $amount,
                'duration_months'        => $months,
                'interest_rate'          => $annualRate,
                'total_interest'         => $totalInterest,
                'total_amount_due'       => $amount + $totalInterest,
                'monthly_payment'        => ($amount + $totalInterest) / $months,
                'status'                 => 'pending',
                'purpose'                => $request->purpose,
                'collateral_description' => $request->collateral_description,
                'verifications'          => $verifications,
                'guarantors_count'       => 0,
                'created_by'             => Auth::id(),
            ]);

            // Enregistrer jusqu'à 3 cautionnaires solidaires
            $guarantorsData = $request->input('guarantors', []);
            $savedGuarantorsCount = 0;

            if (is_array($guarantorsData)) {
                foreach ($guarantorsData as $order => $gData) {
                    if (!empty($gData['full_name']) && !empty($gData['phone'])) {
                        \App\Models\LoanGuarantor::create([
                            'loan_id'                => $loan->id,
                            'guarantor_order'        => (int) ($order ?: ($savedGuarantorsCount + 1)),
                            'full_name'              => $gData['full_name'],
                            'phone'                  => $gData['phone'],
                            'id_card_number'         => $gData['id_card_number'] ?? null,
                            'relationship'           => $gData['relationship'] ?? null,
                            'profession'             => $gData['profession'] ?? null,
                            'address'                => $gData['address'] ?? null,
                            'collateral_value'       => !empty($gData['collateral_value']) ? (float)$gData['collateral_value'] : null,
                            'collateral_description' => $gData['collateral_description'] ?? null,
                            'is_verified'            => !empty($gData['is_verified']),
                        ]);
                        $savedGuarantorsCount++;
                    }
                }
            }

            $loan->update(['guarantors_count' => $savedGuarantorsCount]);

            DB::commit();

            return redirect()->route('caissier.loans.index')
                ->with('success', "✅ Dossier de prêt {$loan->loan_number} enregistré avec {$savedGuarantorsCount} cautionnaire(s) et vérifications.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Erreur lors de la demande : ' . $e->getMessage());
        }
    }

    /**
     * Détails complets d'un prêt avec son échéancier
     */
    public function loanDetails(Request $request, $id)
    {
        $loan = Loan::with(['client', 'approvedBy', 'disbursedBy', 'guarantors', 'payments' => fn($q) => $q->orderBy('payment_number')])
            ->findOrFail($id);

        return view('agent.cashier.loans.show', compact('loan'));
    }

    /**
     * Mettre à jour les vérifications et cautions d'un prêt
     */
    public function updateLoanVerifications(Request $request, $id)
    {
        $loan = Loan::with('guarantors')->findOrFail($id);

        $submittedVerifs = $request->input('verifications', []);
        $verifications = [];
        foreach (Loan::VERIFICATION_ITEMS as $key => $item) {
            $verifications[$key] = !empty($submittedVerifs[$key]);
        }
        $loan->verifications = $verifications;
        $loan->save();

        // Mettre à jour l'état vérifié des cautionnaires
        $guarantorVerified = $request->input('guarantor_verified', []);
        foreach ($loan->guarantors as $g) {
            $g->is_verified = !empty($guarantorVerified[$g->id]);
            $g->save();
        }

        return redirect()->back()->with('success', '✅ Grille d\'audit et vérifications des cautionnaires mises à jour avec succès.');
    }

    /**
     * Encaisser le remboursement d'un prêt au guichet
     */
    public function processLoanRepay(Request $request, $loanId)
    {
        $request->validate([
            'amount'         => 'required|numeric|min:100',
            'payment_method' => 'required|in:cash,mobile_money,bank_transfer',
            'notes'          => 'nullable|string|max:500',
        ]);

        $request->merge([
            'type'        => 'loan',
            'description' => $request->notes ?? 'Remboursement de prêt au guichet',
            'source'      => 'loan_show',
        ]);
        return $this->processDeposit($request, $loanId);
    }

    /**
     * Générer automatiquement l'échéancier si manquant pour un prêt
     */
    private function ensureLoanSchedule(Loan $loan): void
    {
        if ($loan->payments()->count() > 0) {
            return;
        }

        $amount = (float) ($loan->approved_amount ?? $loan->requested_amount ?? 100000);
        $duration = max(1, (int) ($loan->duration_months ?? 12));
        $rate = (float) ($loan->interest_rate ?? 17.0);

        $totalInterest = $amount * ($rate / 100) * ($duration / 12);
        $totalDue = $amount + $totalInterest;
        $monthlyPayment = round($totalDue / $duration, 2);

        $firstDate = $loan->first_payment_date ? \Carbon\Carbon::parse($loan->first_payment_date) : \Carbon\Carbon::now()->addMonth();

        $runningPrincipal = $amount;
        $runningInterest = $totalInterest;

        for ($i = 1; $i <= $duration; $i++) {
            $interestPart = round($totalInterest / $duration, 2);
            $principalPart = round($amount / $duration, 2);

            if ($i === $duration) {
                $principalPart = $runningPrincipal;
                $interestPart = $runningInterest;
                $expected = $principalPart + $interestPart;
            } else {
                $expected = $monthlyPayment;
                $runningPrincipal -= $principalPart;
                $runningInterest -= $interestPart;
            }

            LoanPayment::create([
                'loan_id' => $loan->id,
                'payment_number' => $i,
                'due_date' => (clone $firstDate)->addMonths($i - 1)->format('Y-m-d'),
                'expected_amount' => $expected,
                'principal_amount' => $principalPart,
                'interest_amount' => $interestPart,
                'paid_amount' => 0,
                'status' => 'pending',
            ]);
        }

        $loan->update([
            'monthly_payment' => $monthlyPayment,
            'total_amount_due' => $totalDue,
            'outstanding_principal' => $amount,
            'outstanding_interest' => $totalInterest,
        ]);
    }

    // ================================================================
    //  VALIDATION KYC RAPIDE AU GUICHET
    // ================================================================

    /**
     * Valider expressément le statut KYC d'un client au guichet
     */
    public function approveClientKyc(Request $request, $clientId)
    {
        try {
            $client = Client::findOrFail($clientId);
            $client->update([
                'kyc_status'      => 'approved',
                'kyc_approved_at' => now(),
                'kyc_approved_by' => Auth::id(),
                'is_active'       => true,
            ]);

            $this->logActivity('kyc_approval', "Validation KYC au guichet pour {$client->full_name}.", $client);

            return back()->with('success', "✅ Statut KYC de {$client->full_name} validé avec succès.");
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur validation KYC : ' . $e->getMessage());
        }
    }

    // ================================================================
    //  RAPPORTS & STATISTIQUES AVANCÉES DE CAISSE
    // ================================================================

    /**
     * Page de rapports détaillés et analyse des flux de caisse
     */
    public function reports(Request $request)
    {
        $user = Auth::user();
        $activeSession = $this->getActiveSession($user);

        $startDate = $request->get('date_start', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->get('date_end', Carbon::today()->toDateString());

        $query = Transaction::where('status', 'completed')
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate);

        if ($user->role === 'caissier') {
            $query->where('agency_id', $user->agency_id ?? 1);
        }

        $transactions = (clone $query)->latest('transaction_date')->paginate(30);

        $totals = (clone $query)->select(
            DB::raw('SUM(CASE WHEN transaction_type IN ("deposit", "savings_deposit", "tontine_deposit", "loan_repayment", "transfer_in") THEN amount ELSE 0 END) as total_in'),
            DB::raw('SUM(CASE WHEN transaction_type IN ("withdrawal", "payout", "loan_disbursement", "transfer_out") THEN amount ELSE 0 END) as total_out'),
            DB::raw('COUNT(*) as total_count')
        )->first();

        // Répartition par mode de paiement
        $byMethod = (clone $query)->select('payment_method', DB::raw('SUM(amount) as sum_amount'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get();

        return view('agent.cashier.reports', compact('transactions', 'totals', 'byMethod', 'startDate', 'endDate', 'activeSession'));
    }

    private function generateClientNumber(): string
    {
        do {
            $number = 'CLT-' . strtoupper(Str::random(3)) . '-' . date('ym') . rand(100, 999);
        } while (Client::where('client_number', $number)->exists());
        return $number;
    }

    private function generateAccountNumber(string $type): string
    {
        $prefix = $type === 'tontine' ? 'ACC' : 'SAV';
        do {
            $number = $prefix . '-' . date('ym') . '-' . strtoupper(Str::random(6));
        } while (Account::where('account_number', $number)->exists());
        return $number;
    }
}

