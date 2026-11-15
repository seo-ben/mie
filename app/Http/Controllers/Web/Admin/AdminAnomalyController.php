<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Client;
use App\Models\TontineAccount;
use App\Models\TontineCycle;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminAnomalyController extends Controller
{
    /**
     * Tableau de bord d'audit et détection des anomalies
     */
    public function index(Request $request)
    {
        // 1. Détection des grappes de doublons de dépôts (même compte, même montant, même jour >= 2 fois)
        $rawClusters = DB::table('transactions')
            ->select(
                'account_id',
                'amount',
                DB::raw('DATE(transaction_date) as tx_date'),
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->where('transaction_type', 'deposit')
            ->where('status', 'completed')
            ->groupBy('account_id', 'amount', DB::raw('DATE(transaction_date)'))
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->orderBy(DB::raw('DATE(transaction_date)'), 'desc')
            ->limit(30)
            ->get();

        $anomalies = [];
        foreach ($rawClusters as $cluster) {
            $account = Account::with(['client', 'tontineAccount'])->find($cluster->account_id);
            if (!$account) continue;

            $transactions = Transaction::where('account_id', $cluster->account_id)
                ->where('amount', $cluster->amount)
                ->where('transaction_type', 'deposit')
                ->whereDate('transaction_date', $cluster->tx_date)
                ->orderBy('id', 'asc')
                ->get();

            $anomalies[] = [
                'account'       => $account,
                'amount'        => (float) $cluster->amount,
                'date'          => $cluster->tx_date,
                'total_count'   => (int) $cluster->total_count,
                'excess_count'  => (int) $cluster->total_count - 1,
                'excess_amount' => ((int) $cluster->total_count - 1) * (float) $cluster->amount,
                'transactions'  => $transactions,
            ];
        }

        // 2. Recherche spécifique d'un compte pour audit & régularisation manuelle
        $searchQuery = trim($request->get('q', ''));
        $searchedAccount = null;

        if (!empty($searchQuery)) {
            $searchedAccount = Account::with([
                'client',
                'tontineAccount.cycles' => fn($q) => $q->orderBy('cycle_number', 'asc'),
                'transactions' => fn($q) => $q->latest()->limit(15)
            ])
            ->where('account_number', $searchQuery)
            ->orWhere('id', is_numeric($searchQuery) ? (int)$searchQuery : 0)
            ->orWhereHas('client', function ($q) use ($searchQuery) {
                $q->where('first_name', 'like', "%{$searchQuery}%")
                  ->orWhere('last_name', 'like', "%{$searchQuery}%")
                  ->orWhere('phone', 'like', "%{$searchQuery}%")
                  ->orWhere('client_number', $searchQuery);
            })
            ->first();
        }

        return view('admin.anomalies.index', compact('anomalies', 'searchedAccount', 'searchQuery'));
    }

    /**
     * Résoudre automatiquement les doublons d'une grappe détectée
     */
    public function resolveDuplicates(Request $request)
    {
        $validated = $request->validate([
            'account_id'      => 'required|exists:accounts,id',
            'amount'          => 'required|numeric|min:1',
            'date'            => 'nullable|string',
            'transaction_ids' => 'nullable|string',
        ]);

        $accountId = (int) $validated['account_id'];
        $amount    = (float) $validated['amount'];
        $date      = $validated['date'] ?? null;

        DB::beginTransaction();
        try {
            $account = Account::with('tontineAccount')->findOrFail($accountId);

            // 1. Récupérer les transactions par leurs IDs exacts ou par date/montant
            if (!empty($request->transaction_ids)) {
                $ids = array_filter(array_map('intval', explode(',', $request->transaction_ids)));
                $transactions = Transaction::whereIn('id', $ids)->orderBy('id', 'asc')->get();
            } else {
                $transactions = Transaction::where('account_id', $accountId)
                    ->where('amount', $amount)
                    ->where('transaction_type', 'deposit')
                    ->where(function($q) use ($date) {
                        if ($date) {
                            $q->whereDate('transaction_date', $date)
                              ->orWhereDate('created_at', $date);
                        }
                    })
                    ->orderBy('id', 'asc')
                    ->get();
            }

            // Fallback si la date ne correspond pas exactement
            if ($transactions->count() <= 1) {
                $transactions = Transaction::where('account_id', $accountId)
                    ->where('amount', $amount)
                    ->where('transaction_type', 'deposit')
                    ->orderBy('id', 'asc')
                    ->get();
            }

            if ($transactions->count() <= 1) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Aucun doublon à supprimer pour ce compte.');
            }

            // Conserver la 1ère transaction originale, supprimer les suivantes
            $original = $transactions->first();
            $duplicates = $transactions->slice(1);
            $deletedCount = $duplicates->count();
            $deletedAmount = $deletedCount * $amount;

            // Suppression des doublons
            Transaction::whereIn('id', $duplicates->pluck('id'))->delete();

            // Réajustement du solde du compte
            $newBalance = max(0, $account->balance - $deletedAmount);
            $account->update([
                'balance'    => $newBalance,
                'updated_at' => now(),
            ]);

            // Mettre à jour la transaction originale pour cohérence
            $original->update([
                'balance_after' => $original->balance_before + $amount,
                'updated_at'    => now(),
            ]);

            // Réalignement complet des cycles de tontine si c'est un compte tontine
            if ($account->account_type === 'tontine' && $account->tontineAccount) {
                $tontine = $account->tontineAccount;
                $newTotalPaid = max(0, $tontine->total_paid - $deletedAmount);
                $this->realignTontineCycles($tontine, $newTotalPaid);
            }

            DB::commit();

            Log::info("Audit Doublons: {$deletedCount} doublons de " . number_format($amount, 0) . " XOF supprimés sur compte #{$accountId} par admin #" . auth()->id());

            return redirect()->back()->with('success', "Audit terminé avec succès : {$deletedCount} doublon(s) supprimé(s). Déduction de " . number_format($deletedAmount, 0, ',', ' ') . " XOF appliquée.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur Audit Doublons: " . $e->getMessage());
            return redirect()->back()->with('error', "Erreur lors de la résolution des doublons : " . $e->getMessage());
        }
    }

    /**
     * Régulariser manuellement le montant exact cotisé sur un compte tontine
     */
    public function regularizeBalance(Request $request)
    {
        $validated = $request->validate([
            'account_id'    => 'required|exists:accounts,id',
            'target_amount' => 'required|numeric|min:0',
            'reason'        => 'nullable|string|max:255',
        ]);

        $accountId    = (int) $validated['account_id'];
        $targetAmount = (float) $validated['target_amount'];
        $reason       = $validated['reason'] ?? 'Régularisation administrative des flux';

        DB::beginTransaction();
        try {
            $account = Account::with('tontineAccount')->findOrFail($accountId);

            // 1. Réajuster les transactions de dépôt
            $deposits = Transaction::where('account_id', $accountId)
                ->where('transaction_type', 'deposit')
                ->orderBy('id', 'asc')
                ->get();

            if ($deposits->isNotEmpty()) {
                $first = $deposits->first();
                $first->update([
                    'amount'         => $targetAmount,
                    'balance_before' => 0.00,
                    'balance_after'  => $targetAmount,
                    'description'    => $first->description . ' [Régularisé]',
                    'updated_at'     => now(),
                ]);

                // Supprimer les autres dépôts en trop
                if ($deposits->count() > 1) {
                    Transaction::whereIn('id', $deposits->slice(1)->pluck('id'))->delete();
                }
            } else {
                // Créer une transaction unique régularisée
                Transaction::create([
                    'transaction_reference' => 'REGUL-' . strtoupper(\Illuminate\Support\Str::random(6)) . '-' . now()->format('Ymd'),
                    'account_id'            => $accountId,
                    'amount'                => $targetAmount,
                    'transaction_type'      => 'deposit',
                    'payment_method'        => 'cash',
                    'description'           => $reason,
                    'status'                => 'completed',
                    'processed_by'          => auth()->id(),
                    'transaction_date'      => now(),
                    'balance_before'        => 0.00,
                    'balance_after'         => $targetAmount,
                ]);
            }

            // 2. Mettre à jour le solde du compte
            $account->update([
                'balance'    => $targetAmount,
                'updated_at' => now(),
            ]);

            // 3. Réalignement des cycles de tontine
            if ($account->account_type === 'tontine' && $account->tontineAccount) {
                $this->realignTontineCycles($account->tontineAccount, $targetAmount);
            }

            DB::commit();

            Log::info("Régularisation Compte #{$accountId} : Solde réaligné à " . number_format($targetAmount, 0) . " XOF par admin #" . auth()->id());

            return redirect()->back()->with('success', "Compte #{$account->account_number} régularisé à " . number_format($targetAmount, 0, ',', ' ') . " XOF avec succès. Les cycles ont été réalignés.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur Régularisation: " . $e->getMessage());
            return redirect()->back()->with('error', "Erreur lors de la régularisation: " . $e->getMessage());
        }
    }

    /**
     * Méthode interne : Réaligne mathématiquement les cycles de tontine (règle des 31 jours)
     */
    private function realignTontineCycles(TontineAccount $tontine, float $targetTotal): void
    {
        $mise = max(50, (float) $tontine->tontine_amount);
        $cycleTarget = $mise * 31;

        $fullCycles = (int) floor($targetTotal / $cycleTarget);
        $remainder  = (float) fmod($targetTotal, $cycleTarget);
        $activeCycleNumber = ($remainder > 0 || $fullCycles == 0) ? ($fullCycles + 1) : $fullCycles;

        // Mettre à jour le total_paid et current_cycle
        $tontine->update([
            'total_paid'    => $targetTotal,
            'payments_made' => ($targetTotal > 0) ? 1 : 0,
            'current_cycle' => max(1, $activeCycleNumber),
            'updated_at'    => now(),
        ]);

        // S'assurer que les cycles 1 à $activeCycleNumber existent
        for ($i = 1; $i <= max(1, $activeCycleNumber); $i++) {
            $cycle = TontineCycle::firstOrCreate(
                [
                    'tontine_account_id' => $tontine->id,
                    'cycle_number'       => $i,
                ],
                [
                    'start_date'       => now()->copy()->addMonths($i - 1)->startOfMonth(),
                    'end_date'         => now()->copy()->addMonths($i - 1)->startOfMonth()->addDays(31),
                    'target_amount'    => $cycleTarget,
                    'collected_amount' => 0,
                    'status'           => 'active',
                ]
            );

            if ($i <= $fullCycles) {
                // Cycles complètement achevés
                $cycle->update([
                    'target_amount'    => $cycleTarget,
                    'collected_amount' => $cycleTarget,
                    'status'           => 'completed',
                    'updated_at'       => now(),
                ]);
            } elseif ($i == $activeCycleNumber) {
                // Cycle actif en cours
                $cycle->update([
                    'target_amount'    => $cycleTarget,
                    'collected_amount' => $remainder,
                    'status'           => 'active',
                    'payout_date'      => null,
                    'updated_at'       => now(),
                ]);
            }
        }

        // Supprimer tous les cycles au-delà du cycle actif
        TontineCycle::where('tontine_account_id', $tontine->id)
            ->where('cycle_number', '>', max(1, $activeCycleNumber))
            ->delete();
    }
}
