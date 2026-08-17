<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashierSession;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class CashierSessionController extends Controller
{

    /**
     * Liste des sessions de caisse avec Audit par intervalle
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $isAuditMode = $request->filled('date_start') || $request->filled('date_end');
        
        $statsQuery = Transaction::where('status', 'completed');
        $listQuery = Transaction::with(['account.client', 'processedBy'])->where('status', 'completed');

        // Isolation des données (Sécurité Agence)
        if ($user->role !== 'administrateur_systeme' && $user->role !== 'administrateur_reglementaire') {
            $statsQuery->where('agency_id', $user->agency_id);
            $listQuery->where('agency_id', $user->agency_id);
        }

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
            // Par défaut on regarde les sessions ouvertes aujourd'hui
            $statsQuery->whereDate('processed_at', Carbon::today());
            $listQuery->whereDate('processed_at', Carbon::today());
            $openingBalanceForPeriod = 0;
        }

        // CALCUL DES STATS CONSOLIDÉES
        $totals = $statsQuery->select(
            DB::raw('SUM(CASE WHEN transaction_type IN ("deposit", "savings_deposit", "tontine_deposit", "loan_repayment", "transfer_in") THEN amount ELSE 0 END) as total_in'),
            DB::raw('SUM(CASE WHEN transaction_type IN ("withdrawal", "payout", "loan_disbursement", "transfer_out") THEN amount ELSE 0 END) as total_out')
        )->first();

        $displayStats = (object)[
            'total_in' => $totals->total_in ?? 0,
            'total_out' => $totals->total_out ?? 0,
            'opening_balance' => $openingBalanceForPeriod,
            'is_audit' => $isAuditMode
        ];
        $displayStats->current_balance = $openingBalanceForPeriod + $displayStats->total_in - $displayStats->total_out;

        // Récupération des transactions
        $recentTransactions = $listQuery->latest()->paginate(20);

        // Récupérer les sessions actives pour l'affichage des caisses ouvertes
        $activeSessions = CashierSession::with('user')
            ->where('status', 'open')
            ->when($user->role !== 'administrateur_systeme', fn($q) => $q->where('agency_id', $user->agency_id))
            ->get();

        return view('admin.cashier.sessions.index', [
            'activeSessions' => $activeSessions,
            'recentTransactions' => $recentTransactions,
            'stats' => $displayStats
        ]);
    }

    /**
     * Formulaire d'ouverture manuelle d'une session
     */
    public function create()
    {
        $user = Auth::user();
        
        // Liste des utilisateurs ayant le rôle caissier dans l'agence (ou tous si superadmin)
        $cashiers = \App\Models\User::where('role', 'caissier')
            ->when($user->role !== 'administrateur_systeme', fn($q) => $q->where('agency_id', $user->agency_id))
            ->whereDoesntHave('cashierSessions', fn($q) => $q->where('status', 'open'))
            ->get();

        $agencies = \App\Models\Agency::where('is_active', true)->get();

        return view('admin.cashier.sessions.create', compact('cashiers', 'agencies'));
    }

    /**
     * Ouvrir manuellement une session de caisse (Provisioning)
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'opening_balance' => 'required|numeric|min:0',
            'agency_id' => 'required|exists:agencies,id',
            'notes' => 'nullable|string|max:255'
        ]);

        try {
            DB::beginTransaction();

            // Vérifier s'il y a déjà une session ouverte pour cet utilisateur
            $existing = CashierSession::where('user_id', $request->user_id)
                ->where('status', 'open')
                ->first();

            if ($existing) {
                throw new \Exception('Ce caissier a déjà une session ouverte.');
            }

            $agency = \App\Models\Agency::lockForUpdate()->findOrFail($request->agency_id);

            // ✅ VÉRIFICATION OBLIGATOIRE : La Grande Caisse doit avoir assez de fonds
            if ($agency->vault_balance < $request->opening_balance) {
                throw new \Exception(
                    'Fonds insuffisants dans la Trésorerie de l\'agence "' . $agency->name . '". ' .
                    'Solde disponible : ' . number_format($agency->vault_balance, 0, ',', ' ') . ' XOF — ' .
                    'Montant demandé : ' . number_format($request->opening_balance, 0, ',', ' ') . ' XOF. ' .
                    'Veuillez approvisionner la Grande Caisse avant d\'ouvrir cette session.'
                );
            }

            $session = CashierSession::create([
                'user_id' => $request->user_id,
                'agency_id' => $request->agency_id,
                'opened_at' => now(),
                'opening_balance' => $request->opening_balance,
                'status' => 'open',
                'notes' => $request->notes ?? 'Ouverture manuelle par l\'administrateur'
            ]);

            // Mettre à jour le solde de la grande trésorerie
            $agency->decrement('vault_balance', $request->opening_balance);

            // Créer une transaction de type 'transfer_in' pour la session (Approvisionnement initial)
            if ($request->opening_balance > 0) {
                Transaction::create([
                    'transaction_reference' => 'PROV-' . date('YmdHis') . '-' . rand(1000, 9999),
                    'transaction_type' => 'transfer_in',
                    'amount' => $request->opening_balance,
                    'balance_before' => 0,
                    'balance_after' => $request->opening_balance,
                    'status' => 'completed',
                    'processed_by' => Auth::id(),
                    'agency_id' => $request->agency_id,
                    'cashier_session_id' => $session->id,
                    'processed_at' => now(),
                    'transaction_date' => now(),
                    'description' => 'Approvisionnement initial de session'
                ]);
            }

            DB::commit();

            return redirect()->route('admin.cashier.sessions.index')
                ->with('success', 'Session de caisse ouverte avec succès pour ' . $session->user->full_name);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Effectuer un virement Trésorerie <-> Caisse
     */
    public function transfer(Request $request)
    {
        $request->validate([
            'cashier_session_id' => 'required|exists:cashier_sessions,id',
            'type' => 'required|in:in,out', // in: Main -> Caisse, out: Caisse -> Main
            'amount' => 'required|numeric|min:100',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $session = CashierSession::with('user')->findOrFail($request->cashier_session_id);
            $agency = \App\Models\Agency::findOrFail($session->agency_id);
            $amount = $request->amount;

            if ($request->type === 'in') {
                // Grande Trésorerie -> Caisse (Approvisionnement)
                if ($agency->vault_balance < $amount) {
                    throw new \Exception('Solde Trésorerie insuffisant.');
                }
                $agency->decrement('vault_balance', $amount);
                $transactionType = 'transfer_in';
                $desc = 'Approvisionnement en cours de journée';
            } else {
                // Caisse -> Grande Trésorerie (Versement / Décharge)
                $agency->increment('vault_balance', $amount);
                $transactionType = 'transfer_out';
                $desc = 'Versement vers Trésorerie (Décharge)';
            }

            Transaction::create([
                'transaction_reference' => 'TRSF-' . date('YmdHis') . '-' . rand(1000, 9999),
                'transaction_type' => $transactionType,
                'amount' => $amount,
                'status' => 'completed',
                'processed_by' => Auth::id(),
                'agency_id' => $agency->id,
                'cashier_session_id' => $session->id,
                'processed_at' => now(),
                'transaction_date' => now(),
                'description' => $request->notes ?? $desc
            ]);

            DB::commit();

            return back()->with('success', 'Virement effectué avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Fermer la session
     */
    public function close(Request $request, $id)
    {
        $session = CashierSession::findOrFail($id);
        
        if ($session->status === 'closed') {
            return redirect()->back()->with('error', 'Cette session est déjà fermée.');
        }

        // Calculer les totaux de la période
        $totals = Transaction::where('cashier_session_id', $session->id)
            ->where('status', 'completed')
            ->select(
                DB::raw('SUM(CASE WHEN transaction_type IN ("deposit", "savings_deposit", "tontine_deposit", "loan_repayment", "transfer_in") THEN amount ELSE 0 END) as total_in'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("withdrawal", "payout", "loan_disbursement", "transfer_out") THEN amount ELSE 0 END) as total_out')
            )->first();

        $expectedClosingBalance = $session->opening_balance + $totals->total_in - $totals->total_out;

        $session->update([
            'closed_at' => now(),
            'closing_balance' => $request->closing_balance ?? $expectedClosingBalance,
            'expected_closing_balance' => $expectedClosingBalance,
            'total_deposits' => $totals->total_in ?? 0,
            'total_withdrawals' => $totals->total_out ?? 0,
            'status' => 'closed',
            'notes' => $request->notes,
        ]);

        return redirect()->route('admin.cashier.sessions.index')->with('success', 'Session fermée. Solde final validé pour report.');
    }

    /**
     * Afficher les détails d'une session
     */
    public function show($id)
    {
        $session = CashierSession::with(['user', 'agency', 'transactions.account.client'])->findOrFail($id);
        return view('admin.cashier.sessions.show', compact('session'));
    }

    /**
     * Imprimer le rapport de session (Format Banque)
     */
    public function print($id)
    {
        $session = CashierSession::with(['user', 'agency', 'transactions.account.client'])->findOrFail($id);
        
        // Calcul des statistiques pour le rapport
        $stats = Transaction::where('cashier_session_id', $session->id)
            ->where('status', 'completed')
            ->select(
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("deposit", "savings_deposit", "tontine_deposit", "loan_repayment", "transfer_in") THEN amount ELSE 0 END) as total_in'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("withdrawal", "payout", "loan_disbursement", "transfer_out") THEN amount ELSE 0 END) as total_out'),
                DB::raw('COUNT(CASE WHEN transaction_type IN ("deposit", "savings_deposit") THEN 1 END) as count_deposits'),
                DB::raw('COUNT(CASE WHEN transaction_type = "tontine_deposit" THEN 1 END) as count_tontines'),
                DB::raw('COUNT(CASE WHEN transaction_type = "loan_repayment" THEN 1 END) as count_loans'),
                DB::raw('COUNT(CASE WHEN transaction_type IN ("withdrawal", "savings_withdrawal") THEN 1 END) as count_withdrawals')
            )->first();

        return view('admin.cashier.sessions.print', compact('session', 'stats'));
    }
}
