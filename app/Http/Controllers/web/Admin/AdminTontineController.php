<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\TontineAccount;
use App\Models\TontineCycle;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminTontineController extends Controller
{
    /**
     * Liste de tous les comptes tontine
     */
    public function index(Request $request)
    {
        $query = TontineAccount::with([
            'account.client',
            'cycles' => function($q) {
                $q->latest()->limit(1);
            }
        ]);

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('account', function($q) use ($search) {
                $q->where('account_number', 'like', "%{$search}%")
                  ->orWhereHas('client', function($q2) use ($search) {
                      $q2->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('client_number', 'like', "%{$search}%");
                  });
            });
        }

        // Filtre par statut du cycle
        if ($request->filled('cycle_status')) {
            $query->whereHas('cycles', function($q) use ($request) {
                $q->where('status', $request->cycle_status)
                  ->latest()
                  ->limit(1);
            });
        }

        // Filtre par fréquence de paiement
        if ($request->filled('payment_frequency')) {
            $query->where('payment_frequency', $request->payment_frequency);
        }

        $tontines = $query->latest()->paginate(15);

        // Statistiques
        $stats = [
            'total_tontines' => TontineAccount::count(),
            'active_cycles' => TontineCycle::where('status', 'active')->count(),
            'total_collected' => TontineCycle::sum('collected_amount'),
            'total_paid_out' => TontineCycle::whereNotNull('payout_date')->sum('payout_amount'),
            'pending_collections' => TontineAccount::whereHas('cycles', function($q) {
                $q->where('status', 'active')
                  ->whereRaw('collected_amount < target_amount');
            })->count(),
        ];

        return view('admin.tontines.index', compact('tontines', 'stats'));
    }

    /**
     * Détails d'un compte tontine
     */
    public function show($tontineId)
    {
        $tontine = TontineAccount::with([
            'account.client',
            'account.transactions' => function($q) {
                $q->where('transaction_type', 'deposit')
                  ->latest()
                  ->limit(50);
            },
            'cycles' => function($q) {
                $q->latest();
            }
        ])->findOrFail($tontineId);

        // Cycle actif
        $activeCycle = $tontine->cycles()->where('status', 'active')->first();

        // Statistiques du compte
        $stats = [
            'total_cycles' => $tontine->cycles()->count(),
            'completed_cycles' => $tontine->cycles()->where('status', 'completed')->count(),
            'total_contributions' => $tontine->account->transactions()
                ->where('transaction_type', 'deposit')
                ->where('status', 'completed')
                ->sum('amount'),
            'completion_rate' => $activeCycle ?
                round(($activeCycle->collected_amount / $activeCycle->target_amount) * 100, 2) : 0,
        ];

        // Calcul des jours restants
        $daysRemaining = $activeCycle ?
            Carbon::parse($activeCycle->end_date)->diffInDays(now(), false) : null;

        return view('admin.tontines.show', compact('tontine', 'activeCycle', 'stats', 'daysRemaining'));
    }

    /**
     * Afficher le formulaire de cotisation
     */
    public function contributeForm($tontineId)
    {
        $tontine = TontineAccount::with([
            'account.client',
            'cycles' => function($q) {
                $q->where('status', 'active')->latest();
            }
        ])->findOrFail($tontineId);

        $activeCycle = $tontine->cycles()->where('status', 'active')->first();

        if (!$activeCycle) {
            return redirect()
                ->route('admin.tontines.show', $tontineId)
                ->with('error', 'Aucun cycle actif pour ce compte tontine.');
        }

        // dd(auth()->id());


        // Calcul du montant suggéré
        $remainingAmount = $activeCycle->target_amount - $activeCycle->collected_amount;
        $suggestedAmount = min($tontine->tontine_amount, $remainingAmount);

        return view('admin.tontines.contribute', compact('tontine', 'activeCycle', 'suggestedAmount'));
    }

    /**
     * Enregistrer une cotisation
     */
    public function contribute(Request $request, $tontineId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
            'payment_method' => 'required|in:cash,mobile_money,bank_transfer',
            'payment_reference' => 'nullable|string|max:255',
            'mobile_money_operator' => 'required_if:payment_method,mobile_money|in:tmoney,flooz',
            'description' => 'nullable|string|max:500',
        ]);
        // dd(auth()->id());

        // try {
            DB::beginTransaction();

            $tontine = TontineAccount::with('account')->findOrFail($tontineId);
            $activeCycle = $tontine->cycles()->where('status', 'active')->firstOrFail();

            // Vérifier que le compte est actif
            if ($tontine->account->status !== 'active') {
                throw new \Exception('Le compte tontine doit être actif pour effectuer une cotisation.');
            }

            // Vérifier que le montant ne dépasse pas le reste à collecter
            $remainingAmount = (float) $activeCycle->target_amount - (float) $activeCycle->collected_amount;
            $amount = (float) $request->amount;

            if ($amount > $remainingAmount) {
                throw new \Exception("Le montant dépasse le reste à collecter ({$remainingAmount} FCFA).");
            }

            $currentBalance = (float) ($tontine->account->balance ?? 0);
            $newBalance = $currentBalance + $amount;

            // Créer la transaction
            $transaction = Transaction::create([
                'transaction_reference' => 'TON-' . strtoupper(uniqid()),
                'account_id' => $tontine->account_id,
                'transaction_type' => 'deposit',
                'amount' => $amount,
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference,
                'mobile_money_operator' => $request->mobile_money_operator,
                'description' => $request->description ?? "Cotisation tontine - Cycle #{$activeCycle->cycle_number}",
                'status' => 'completed',
                'balance_before' => $currentBalance,
                'balance_after'  => $newBalance,
                'processed_by' => auth()->id(),
                'processed_at' => now(),
                'transaction_date' => now(),
            ]);

            // Mettre à jour le solde du compte (évite Brick\Math en utilisant increment)
            $tontine->account->increment('balance', $amount);
            $tontine->account->update(['last_transaction_at' => now()]);

            // Mettre à jour le cycle (en évitant les DB::raw)
            $activeCycle->update([
                'collected_amount' => $activeCycle->collected_amount + $amount,
            ]);

            // Mettre à jour le compte tontine
            $tontine->update([
                'total_paid' => $tontine->total_paid + $amount,
            ]);

            // Vérifier si le cycle est complété
            $activeCycle->refresh();
            if ($activeCycle->collected_amount >= $activeCycle->target_amount) {
                $activeCycle->update([
                    'status' => 'completed',
                    'payout_amount' => $activeCycle->collected_amount,
                    'payout_date' => now(),
                ]);

                // Créer un nouveau cycle si nécessaire
                $this->createNextCycle($tontine);
            }

            DB::commit();

            return redirect()
                ->route('admin.tontines.show', $tontineId)
                ->with('success', 'Cotisation enregistrée avec succès.');

        // } catch (\Exception $e) {
        //     DB::rollBack();

        //     return redirect()
        //         ->back()
        //         ->withInput()
        //         ->with('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage());
        // }
    }

    /**
     * Historique des cotisations
     */
    public function contributions($tontineId, Request $request)
    {
        $tontine = TontineAccount::with('account.client')->findOrFail($tontineId);

        $query = Transaction::where('account_id', $tontine->account_id)
            ->where('transaction_type', 'tontine_contribution')
            ->with(['processedBy']);

        // Filtre par cycle
        if ($request->filled('cycle_id')) {
            $cycle = TontineCycle::findOrFail($request->cycle_id);
            $query->whereBetween('transaction_date', [$cycle->start_date, $cycle->end_date]);
        }

        // Filtre par période
        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        $contributions = $query->latest('transaction_date')->paginate(50);

        // Statistiques
        $stats = [
            'total_amount' => $contributions->sum('amount'),
            'total_count' => $contributions->total(),
            'average_amount' => $contributions->avg('amount'),
        ];

        // Liste des cycles pour le filtre
        $cycles = $tontine->cycles()->orderBy('cycle_number', 'desc')->get();

        return view('admin.tontines.contributions', compact('tontine', 'contributions', 'stats', 'cycles'));
    }

    /**
     * Débloquer le paiement (payout)
     */
    public function payout($tontineId)
    {
        try {
            DB::beginTransaction();

            $tontine = TontineAccount::with('account')->findOrFail($tontineId);
            $completedCycle = $tontine->cycles()
                ->where('status', 'completed')
                ->whereNull('payout_date')
                ->latest()
                ->firstOrFail();

            // Créer une transaction de retrait
            $transaction = Transaction::create([
                'transaction_reference' => 'PAYOUT-' . strtoupper(uniqid()),
                'account_id' => $tontine->account_id,
                'transaction_type' => 'tontine_payout',
                'amount' => $completedCycle->collected_amount,
                'payment_method' => 'bank_transfer',
                'description' => "Déblocage tontine - Cycle #{$completedCycle->cycle_number}",
                'status' => 'completed',
                'balance_before' => $tontine->account->balance,
                'balance_after' => $tontine->account->balance - $completedCycle->collected_amount,
                'processed_by' => auth()->id(),
                'processed_at' => now(),
                'transaction_date' => now(),
            ]);

            // Mettre à jour le solde
            $tontine->account->update([
                'balance' => DB::raw('balance - ' . $completedCycle->collected_amount),
                'last_transaction_at' => now(),
            ]);

            // Marquer le cycle comme payé
            $completedCycle->update([
                'payout_date' => now(),
                'payout_amount' => $completedCycle->collected_amount,
            ]);

            // Mettre à jour le compte tontine
            $tontine->update([
                'payout_amount' => DB::raw('payout_amount + ' . $completedCycle->collected_amount),
                'payout_date' => now(),
            ]);

            DB::commit();

            return redirect()
                ->route('admin.tontines.show', $tontineId)
                ->with('success', 'Déblocage effectué avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Erreur lors du déblocage: ' . $e->getMessage());
        }
    }

    /**
     * Clôturer un cycle manuellement
     */
    public function closeCycle($cycleId)
    {
        try {
            DB::beginTransaction();

            $cycle = TontineCycle::with('tontineAccount')->findOrFail($cycleId);

            if ($cycle->status !== 'active') {
                throw new \Exception('Seul un cycle actif peut être clôturé.');
            }

            $cycle->update([
                'status' => 'completed',
                'end_date' => now(),
            ]);

            // Créer le prochain cycle
            $this->createNextCycle($cycle->tontineAccount);

            DB::commit();

            return redirect()
                ->route('admin.tontines.show', $cycle->tontine_account_id)
                ->with('success', 'Cycle clôturé avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la clôture: ' . $e->getMessage());
        }
    }

    /**
     * Rapport des tontines
     */
    public function report(Request $request)
    {
        $query = TontineAccount::with(['account.client', 'cycles']);

        if ($request->filled('date_from')) {
            $query->whereHas('cycles', function($q) use ($request) {
                $q->whereDate('start_date', '>=', $request->date_from);
            });
        }

        if ($request->filled('date_to')) {
            $query->whereHas('cycles', function($q) use ($request) {
                $q->whereDate('end_date', '<=', $request->date_to);
            });
        }

        $tontines = $query->get();

        $reportData = [
            'total_tontines' => $tontines->count(),
            'total_collected' => $tontines->sum('total_paid'),
            'total_target' => $tontines->sum('total_expected'),
            'average_completion' => $tontines->avg(function($t) {
                return $t->total_expected > 0 ? ($t->total_paid / $t->total_expected) * 100 : 0;
            }),
            'by_frequency' => $tontines->groupBy('payment_frequency')->map->count(),
        ];

        return view('admin.tontines.report', compact('tontines', 'reportData'));
    }

    // =============== MÉTHODES PRIVÉES ===============

    /**
     * Créer le prochain cycle
     */
    private function createNextCycle(TontineAccount $tontine): TontineCycle
    {
        $lastCycle = $tontine->cycles()->latest('cycle_number')->first();
        $cycleNumber = $lastCycle ? $lastCycle->cycle_number + 1 : 1;

        $startDate = now();
        $endDate = (clone $startDate)->addMonths((int) $tontine->cycle_duration_months);

        return TontineCycle::create([
            'tontine_account_id' => $tontine->id,
            'cycle_number' => $cycleNumber,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'target_amount' => $tontine->tontine_amount,
            'collected_amount' => 0,
            'status' => 'active',
        ]);
    }
}
