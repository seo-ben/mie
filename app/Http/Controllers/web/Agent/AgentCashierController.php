<?php

namespace App\Http\Controllers\Web\Agent;

use App\Http\Controllers\Controller;
use App\Models\Account;
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
                DB::raw('SUM(CASE WHEN transaction_type IN ("deposit","savings_deposit","tontine_deposit","loan_repayment") THEN amount ELSE 0 END) as total_in'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("withdrawal","payout") THEN amount ELSE 0 END) as total_out'),
                DB::raw('COUNT(*) as tx_count')
            )->first();

        // Stats clients de l'agence
        $agencyClientCount = Client::where('registration_status', 'approved')->count();

        // Prêts actifs en attente de remboursement
        $activeLoanCount = Loan::whereIn('status', ['active', 'disbursed'])->count();

        // Dernières transactions
        $recentTransactions = Transaction::with(['account.client'])
            ->where('cashier_session_id', $activeSession->id)
            ->where('status', 'completed')
            ->latest('transaction_date')
            ->limit(10)
            ->get();

        return view('agent.cashier.dashboard', [
            'activeSession' => $activeSession,
            'todayStats' => $todayStats,
            'agencyClientCount' => $agencyClientCount,
            'activeLoanCount' => $activeLoanCount,
            'recentTransactions' => $recentTransactions,
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

        $statsQuery = Transaction::where('status', 'completed')
            ->where('cashier_session_id', $activeSession->id);
        $listQuery = Transaction::with(['account.client'])
            ->where('status', 'completed')
            ->where('cashier_session_id', $activeSession->id);

        if ($isAuditMode) {
            if ($request->filled('date_start')) {
                $statsQuery->whereDate('transaction_date', '>=', $request->date_start);
                $listQuery->whereDate('transaction_date', '>=', $request->date_start);
            }
            if ($request->filled('date_end')) {
                $statsQuery->whereDate('transaction_date', '<=', $request->date_end);
                $listQuery->whereDate('transaction_date', '<=', $request->date_end);
            }
            $openingBalanceForPeriod = 0;
        } else {
            $statsQuery->where('cashier_session_id', $activeSession->id);
            $listQuery->where('cashier_session_id', $activeSession->id);
            $openingBalanceForPeriod = $activeSession->opening_balance;
        }

        $totals = $statsQuery->select(
            DB::raw('SUM(CASE WHEN transaction_type IN ("deposit","savings_deposit","tontine_deposit","loan_repayment") THEN amount ELSE 0 END) as total_in'),
            DB::raw('SUM(CASE WHEN transaction_type IN ("withdrawal","payout") THEN amount ELSE 0 END) as total_out')
        )->first();

        $displayStats = (object) [
            'total_in' => $totals->total_in ?? 0,
            'total_out' => $totals->total_out ?? 0,
            'opening_balance' => $openingBalanceForPeriod,
            'is_audit' => $isAuditMode,
        ];
        $displayStats->current_balance = $openingBalanceForPeriod + $displayStats->total_in - $displayStats->total_out;

        $recentTransactions = $listQuery->latest('transaction_date')->paginate(25);

            return view('agent.cashier.terminal', [
                'activeSession' => $activeSession,
                'recentTransactions' => $recentTransactions,
                'stats' => $displayStats,
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

        // ⚡ Mode rapide : recherche exacte par numéro de compte (préfixe)
        $isExactSearch = preg_match('/^(TON|SAV|EPR|LN|PRE|CLI|ACC)/i', $query) || is_numeric($query);

        if ($isExactSearch) {
            // Recherche rapide par numéro — utilise un index et pas de LIKE %...
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
            // Recherche par nom/téléphone — optimisée avec join
            $accounts = Account::select('accounts.id', 'accounts.account_number', 'accounts.account_type', 'accounts.balance', 'accounts.client_id')
                ->with(['tontineAccount:id,account_id,tontine_amount'])
                ->join('clients', 'accounts.client_id', '=', 'clients.id')
                ->where('accounts.status', 'active')
                ->where(function ($q) use ($query) {
                    $q->where('accounts.account_number', 'like', "%{$query}%")
                        ->orWhere('clients.first_name', 'like', "%{$query}%")
                        ->orWhere('clients.last_name', 'like', "%{$query}%")
                        ->orWhere('clients.client_number', 'like', "%{$query}%")
                        ->orWhere('clients.phone', 'like', "%{$query}%")
                        ->orWhereRaw("CONCAT(clients.first_name, ' ', clients.last_name) LIKE ?", ["%{$query}%"]);
                })
                ->limit(10)
                ->get()
                ->load('client:id,first_name,last_name,client_number,phone');

            $loans = Loan::select('loans.id', 'loans.loan_number', 'loans.client_id', 'loans.status', 'loans.total_amount_due', 'loans.total_paid')
                ->join('clients', 'loans.client_id', '=', 'clients.id')
                ->whereIn('loans.status', ['approved', 'disbursed', 'active'])
                ->where(function ($q) use ($query) {
                    $q->where('loans.loan_number', 'like', "%{$query}%")
                        ->orWhere('clients.first_name', 'like', "%{$query}%")
                        ->orWhere('clients.last_name', 'like', "%{$query}%")
                        ->orWhere('clients.phone', 'like', "%{$query}%")
                        ->orWhereRaw("CONCAT(clients.first_name, ' ', clients.last_name) LIKE ?", ["%{$query}%"]);
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
                $loan = Loan::with(['client', 'payments' => function($q) {
                    $q->whereIn('status', ['pending', 'overdue', 'partial'])->orderBy('payment_number');
                }])->lockForUpdate()->findOrFail($accountId);
                
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
                    'description' => $request->description ?? 'Remboursement de prêt au guichet',
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

                return redirect()->route('caissier.depot')
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

        // ⚡ Mode rapide : recherche exacte par numéro de compte
        $isExactSearch = preg_match('/^(TON|SAV|EPR|ACC)/i', $query) || is_numeric($query);

        if ($isExactSearch) {
            $accounts = Account::select('accounts.id', 'accounts.account_number', 'accounts.account_type', 'accounts.balance', 'accounts.client_id')
                ->with(['client:id,first_name,last_name,client_number,phone', 'tontineAccount'])
                ->where('status', 'active')
                ->where('balance', '>', 0)
                ->where('account_number', 'like', "{$query}%")
                ->limit(15)
                ->get();
        } else {
            $accounts = Account::select('accounts.id', 'accounts.account_number', 'accounts.account_type', 'accounts.balance', 'accounts.client_id')
                ->join('clients', 'accounts.client_id', '=', 'clients.id')
                ->where('accounts.status', 'active')
                ->where('accounts.balance', '>', 0)
                ->where(function ($q) use ($query) {
                    $q->where('accounts.account_number', 'like', "%{$query}%")
                        ->orWhere('clients.first_name', 'like', "%{$query}%")
                        ->orWhere('clients.last_name', 'like', "%{$query}%")
                        ->orWhere('clients.client_number', 'like', "%{$query}%")
                        ->orWhere('clients.phone', 'like', "%{$query}%")
                        ->orWhereRaw("CONCAT(clients.first_name, ' ', clients.last_name) LIKE ?", ["%{$query}%"]);
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
        Log::info('Checking for active session', ['user_id' => $user->id]);
        
        return CashierSession::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();
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
        $loans = Loan::with('client')
            ->where('status', 'approved')
            ->whereNull('disbursed_at')
            ->latest()
            ->get();

        return view('agent.cashier.loans.disbursements', compact('loans'));
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
            $loan->update([
                'status' => 'disbursed',
                'disbursed_at' => now(),
                'disbursed_by' => $user->id,
                'first_payment_date' => now()->addMonth(), // Premier remboursement dans 1 mois par défaut
            ]);

            // 2. Créer la transaction (Inflow/Outflow: Ici c'est un Outflow pour la caisse)
            // On peut lier cela à un compte d'épargne du client si nécessaire, ou juste enregistrer la sortie cash
            $transaction = Transaction::create([
                'transaction_reference' => $this->generateTransactionReference(),
                'transaction_type' => 'loan_disbursement', // Type explicite pour le reçu @break
                'amount' => $loan->approved_amount,
                'balance_before' => 0,
                'balance_after' => 0,
                'payment_method' => 'cash',
                'payment_reference' => 'LOAN-' . $loan->loan_number,
                'description' => 'Décaissement du prêt #' . $loan->loan_number,
                'status' => 'completed',
                'processed_by' => $user->id,
                'agency_id' => $user->agency_id,
                'cashier_session_id' => $activeSession->id,
                'processed_at' => now(),
                'transaction_date' => now(),
            ]);

            // 3. Logger l'action caissier
            $this->logActivity('loan_disbursement', "Décaissement du prêt #{$loan->loan_number} pour un montant de {$loan->approved_amount} FCFA.", $loan);

            DB::commit();

            return redirect()->route('caissier.loans.disbursement')
                ->with('success', 'Remboursement effectué avec succès pour le prêt #' . $loan->loan_number)
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
            ->whereIn('action', ['deposit', 'withdrawal', 'loan_disbursement', 'session_open', 'session_close'])
            ->latest()
            ->paginate(50);

        return view('agent.cashier.logs', compact('logs'));
    }

    /**
     * Impression d'un reçu de transaction
     */
    public function printReceipt(Transaction $transaction)
    {
        $transaction->load(['account.client', 'processedBy', 'agency']);
        
        $clientName = 'N/A';
        
        if ($transaction->account) {
            $clientName = $transaction->account->client->full_name;
        } elseif (str_starts_with($transaction->payment_reference, 'LOAN-')) {
            // Rechercher le client via le prêt
            $loanNumber = str_replace('LOAN-', '', $transaction->payment_reference);
            $loan = Loan::where('loan_number', $loanNumber)->with('client')->first();
            if ($loan) {
                $clientName = $loan->client->full_name;
            }
        }
        
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
                $message .= '<br>🎉 Cycle #' . $cycle['cycle_number'] . ' complété !';
            } else {
                $remaining = $tontine->tontine_amount - $cycle['amount']; // Simplifié pour le message
                $message .= '<br>📊 Cycle #' . $cycle['cycle_number'] . ' mis à jour.';
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

            // 1. Créer le client
            $client = Client::create([
                'client_number' => $this->generateClientNumber(),
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'address' => $request->address,
                'gender' => $request->gender,
                'registered_by' => $user->id,
                'agency_id' => $user->agency_id,
                'registration_channel' => 'agent_assisted',
                'registration_status' => 'approved', // Approuvé d'office si fait par un caissier ?
                'kyc_status' => 'pending',
                'password' => \Illuminate\Support\Facades\Hash::make('12@4'),
                'is_active' => true,
            ]);

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

