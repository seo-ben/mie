<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\TontineAccount;
use App\Models\TontineCycle;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
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
    public function show($tontineId, Request $request)
    {
        $tontine = TontineAccount::with([
            'account.client',
            'account.transactions' => function($q) {
                $q->where('transaction_type', 'deposit')
                  ->latest()
                  ->limit(50);
            },
            'cycles' => function($q) {
                $q->orderBy('cycle_number', 'asc');
            }
        ])->findOrFail($tontineId);

        // Tous les cycles pour la navigation
        $cycles = $tontine->cycles;

        // Cycle actif (pour les opérations courantes)
        $activeCycle = $cycles->where('status', 'active')->first();

        // Cycle sélectionné pour l'affichage (calendrier)
        $selectedCycleNumber = $request->get('cycle_number');
        $selectedCycle = null;
        if ($selectedCycleNumber) {
            $selectedCycle = $cycles->where('cycle_number', $selectedCycleNumber)->first();
        }
        
        // Par défaut, montrer le cycle actif ou le dernier cycle
        if (!$selectedCycle) {
            $selectedCycle = $activeCycle ?? $cycles->last();
        }

        // Statistiques pour l'affichage (Globales + Cycle sélectionné)
        $stats = [
            'total_cycles' => $cycles->count(),
            'completed_cycles' => $cycles->where('status', 'completed')->count(),
            'total_contributions' => $tontine->account->transactions()
                ->where('transaction_type', 'deposit')
                ->where('status', 'completed')
                ->sum('amount'),
            'completion_rate' => $selectedCycle && $selectedCycle->target_amount > 0 ?
                round(($selectedCycle->collected_amount / $selectedCycle->target_amount) * 100, 2) : 0,
        ];

        // Cycles en attente de déblocage (complétés mais non payés)
        $hasPendingPayout = $cycles->where('status', 'completed')->whereNull('payout_date')->count() > 0;
        $pendingPayoutCycle = $cycles->where('status', 'completed')->whereNull('payout_date')->sortByDesc('cycle_number')->first();

        // Commission mensuelle (1/31 de l'objectif mensuel)
        $dailyCommission = $tontine->tontine_amount; 

        // Configuration de la grille visuelle (Le Calendrier de Tontine)
        if ($tontine->payment_frequency === 'weekly') {
            // Vue Annuelle pour l'hebdomadaire : 52 semaines
            // Note : L'utilisateur veut naviguer entre cycles, donc on traite hebdomadaire comme les autres
            $gridTotalSlots = 52;
            $totalCollectedOverall = (float) $tontine->account->transactions()
                ->where('transaction_type', 'deposit')
                ->where('status', 'completed')
                ->sum('amount');
            $gridFilledSlots = $tontine->tontine_amount > 0 ? (int) floor($totalCollectedOverall / $tontine->tontine_amount) : 0;
            $gridFilledSlots = min($gridFilledSlots, $gridTotalSlots);
        } else {
            // Vue Cycle (31 jours pour daily, 1 pour monthly)
            $gridTotalSlots = match($tontine->payment_frequency) {
                'daily' => 31,
                'monthly' => 1,
                default => 31,
            };
            $gridFilledSlots = 0;
            if ($selectedCycle && $tontine->tontine_amount > 0) {
                // Utilisation du cycle sélectionné pour le calendrier
                $gridFilledSlots = (int) floor($selectedCycle->collected_amount / $tontine->tontine_amount);
                $gridFilledSlots = min($gridFilledSlots, $gridTotalSlots);
            }
        }

        // Calcul des jours restants (pour le cycle actif uniquement)
        $daysRemaining = 0;
        if ($activeCycle) {
            $daysRemaining = max(0, (int) now()->diffInDays($activeCycle->end_date, false));
        }

        return view('admin.tontines.show', compact(
            'tontine', 
            'activeCycle', 
            'selectedCycle',
            'cycles',
            'stats', 
            'hasPendingPayout', 
            'pendingPayoutCycle',
            'dailyCommission',
            'gridTotalSlots',
            'gridFilledSlots',
            'daysRemaining'
        ));
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
        try {
            DB::beginTransaction();


            $tontine = TontineAccount::with('account')->findOrFail($tontineId);
            $activeCycle = $tontine->cycles()->where('status', 'active')->firstOrFail();

            // Vérifier que le compte est actif
            if ($tontine->account->status !== 'active') {
                $statusMessage = match($tontine->account->status) {
                    'suspended' => 'Ce compte a été suspendu par la direction.',
                    'pending_activation' => 'Ce compte est en attente d\'activation.',
                    'closed' => 'Ce compte a été fermé.',
                    default => 'Ce compte n\'est pas actif.',
                };
                
                if ($tontine->account->suspension_reason) {
                    $statusMessage .= ' Raison : ' . $tontine->account->suspension_reason;
                }
                
                $statusMessage .= ' Veuillez contacter un administrateur pour réactiver ce compte.';
                
                throw new \Exception($statusMessage);
            }


            // Paiements anticipés : permettre de payer pour plusieurs cycles
            $amount = (float) $request->amount;
            $remainingAmount = (float) $activeCycle->target_amount - (float) $activeCycle->collected_amount;
            
            $currentBalance = (float) ($tontine->account->balance ?? 0);
            $newBalance = $currentBalance + $amount;

            // Créer la transaction principale
            $transaction = Transaction::create([
                'transaction_reference' => 'TON-' . strtoupper(uniqid()),
            'account_id' => $tontine->account_id,
            'transaction_type' => 'deposit',
            'amount' => $amount,
            'payment_method' => $request->payment_method,
            'payment_reference' => $request->payment_reference,
            'mobile_money_operator' => $request->mobile_money_operator,
            'description' => $request->description ?? "Cotisation tontine (paiement anticipé possible)",
            'status' => 'completed',
            'balance_before' => $currentBalance,
            'balance_after'  => $newBalance,
            'processed_by' => Auth::id(),
            'agency_id' => Auth::user()->agency_id,
            'processed_at' => now(),
            'transaction_date' => now(),
            ]);

            // Mettre à jour le solde du compte
            $tontine->account->increment('balance', $amount);
            $tontine->account->update(['last_transaction_at' => now()]);

            // Répartir le montant sur les cycles
            $amountToDistribute = $amount;
            $currentCycle = $activeCycle;
            $cyclesUpdated = [];
            
            while ($amountToDistribute > 0) {
                $cycleRemaining = (float) $currentCycle->target_amount - (float) $currentCycle->collected_amount;
                $amountForThisCycle = min($amountToDistribute, $cycleRemaining);
                
                // Mettre à jour le cycle
                $currentCycle->update([
                    'collected_amount' => $currentCycle->collected_amount + $amountForThisCycle,
                ]);
                
                $cyclesUpdated[] = [
                    'cycle_number' => $currentCycle->cycle_number,
                    'amount' => $amountForThisCycle,
                ];
                
                $amountToDistribute -= $amountForThisCycle;
                
                // Vérifier si le cycle est complété
                $currentCycle->refresh();
                if ($currentCycle->collected_amount >= $currentCycle->target_amount) {
                    // Calcul de la commission microfinance (1/31 de chaque mois/cycle)
                    $commission = ($currentCycle->target_amount / 31);
                    $payoutAmount = $currentCycle->collected_amount - $commission;

                    $currentCycle->update([
                        'status' => 'completed',
                        'payout_amount' => $payoutAmount,
                    ]);
                    
                    // S'il reste de l'argent à distribuer, créer le prochain cycle
                    if ($amountToDistribute > 0) {
                        $nextCycle = $this->createNextCycle($tontine);
                        if ($nextCycle) {
                            $currentCycle = $nextCycle;
                        } else {
                            // Impossible de créer un nouveau cycle, arrêter la distribution
                            break;
                        }
                    }
                }
                
                // Sécurité : éviter une boucle infinie
                if (count($cyclesUpdated) > 100) {
                    throw new \Exception('Erreur : trop de cycles générés. Veuillez contacter un administrateur.');
                }
            }

            // Mettre à jour le compte tontine
            $tontine->update([
                'total_paid' => $tontine->total_paid + $amount,
            ]);

            DB::commit();

            // Message de succès avec détails de la répartition
            $message = 'Cotisation de ' . number_format($amount, 0, ',', ' ') . ' XOF enregistrée avec succès.';
            if (count($cyclesUpdated) > 1) {
                $message .= ' Le montant a été réparti sur ' . count($cyclesUpdated) . ' cycle(s).';
            }

            return redirect()
                ->route('admin.tontines.show', $tontineId)
                ->with('success', $message);


        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage());
        }
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

            // Créer une transaction de retrait (Montant net après commission microfinance)
            $transaction = Transaction::create([
                'transaction_reference' => 'PAYOUT-' . strtoupper(uniqid()),
                'account_id' => $tontine->account_id,
                'transaction_type' => 'withdrawal',
                'amount' => $completedCycle->payout_amount,
                'fee_amount' => $completedCycle->collected_amount - $completedCycle->payout_amount,
                'payment_method' => 'cash', // À adapter si besoin
                'status' => 'completed',
                'description' => "Déblocage capital Tontine - Cycle #" . $completedCycle->cycle_number,
                'balance_before' => $tontine->account->balance,
                'balance_after' => $tontine->account->balance - $completedCycle->collected_amount,
                'processed_by' => Auth::id(),
                'agency_id' => Auth::user()->agency_id,
                'processed_at' => now(),
                'transaction_date' => now(),
            ]);

            // Mettre à jour le solde (On retire le montant total collecté du solde client, la différence est la commission)
            $tontine->account->update([
                'balance' => DB::raw('balance - ' . $completedCycle->collected_amount),
                'last_transaction_at' => now(),
            ]);

            // Marquer le cycle comme payé
            $completedCycle->update([
                'payout_date' => now(),
            ]);

            // Mettre à jour le compte tontine
            $tontine->update([
                'payout_amount' => DB::raw('payout_amount + ' . $completedCycle->payout_amount),
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

            // Calcul de la commission obligatoire (1/31 de l'objectif mensuel)
            $commission = $cycle->target_amount / 31;
            $payoutAmount = max(0, $cycle->collected_amount - $commission);

            $cycle->update([
                'status' => 'completed',
                'payout_amount' => $payoutAmount,
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

    /**
     * Vue d'audit visuel interactive
     */
    public function visualAudit()
    {
        return view('admin.tontines.visual_audit');
    }

    /**
     * Données pour l'audit visuel (AJAX)
     */
    public function visualAuditData(Request $request)
    {
        $query = $request->get('account_number');
        $cycleNumber = $request->get('cycle_number');

        if (!$query) {
            return response()->json(['error' => 'Numéro de compte requis'], 400);
        }

        $tontine = TontineAccount::whereHas('account', function($q) use ($query) {
            $q->where('account_number', $query);
        })->with(['account.client', 'cycles' => function($q) {
            $q->orderBy('cycle_number', 'asc');
        }])->first();

        if (!$tontine) {
            return response()->json(['error' => 'Compte tontine introuvable'], 404);
        }

        $cycles = $tontine->cycles;
        $activeCycle = $cycles->where('status', 'active')->first();
        
        $selectedCycle = $cycleNumber 
            ? $cycles->where('cycle_number', $cycleNumber)->first() 
            : ($activeCycle ?? $cycles->last());

        if (!$selectedCycle) {
            return response()->json(['error' => 'Cycle introuvable'], 404);
        }

        // Calcul de la grille
        $gridTotalSlots = match($tontine->payment_frequency) {
            'daily' => 31,
            'weekly' => 52, // Note: On garde 52 pour l'affichage annuel si hebdomadaire
            'monthly' => 1,
            default => 31,
        };

        if ($tontine->payment_frequency === 'weekly' && $selectedCycle) {
            // Pour l'hebdo, si on est par cycle, c'est ~4.33 slots, mais on va garder la logique du tontine_amount
            $gridFilledSlots = $tontine->tontine_amount > 0 ? (int) floor($selectedCycle->collected_amount / $tontine->tontine_amount) : 0;
        } else {
            $gridFilledSlots = $tontine->tontine_amount > 0 ? (int) floor($selectedCycle->collected_amount / $tontine->tontine_amount) : 0;
        }

        return response()->json([
            'tontine' => [
                'id' => $tontine->id,
                'client_id' => $tontine->account->client->id,
                'client_name' => $tontine->account->client->full_name,
                'account_number' => $tontine->account->account_number,
                'frequency' => $tontine->payment_frequency,
                'amount' => $tontine->tontine_amount,
                'balance' => $tontine->account->balance,
            ],
            'selected_cycle' => [
                'id' => $selectedCycle->id,
                'number' => $selectedCycle->cycle_number,
                'target' => $selectedCycle->target_amount,
                'collected' => $selectedCycle->collected_amount,
                'status' => $selectedCycle->status,
                'start_date' => $selectedCycle->start_date->format('d/m/Y'),
                'end_date' => $selectedCycle->end_date->format('d/m/Y'),
            ],
            'cycles' => $cycles->map(function($c) {
                return [
                    'number' => $c->cycle_number,
                    'status' => $c->status,
                ];
            }),
            'grid' => [
                'total' => $gridTotalSlots,
                'filled' => min($gridFilledSlots, $gridTotalSlots),
            ]
        ]);
    }

    // =============== MÉTHODES PRIVÉES ===============

    /**
     * Créer le prochain cycle
     */
    private function createNextCycle(TontineAccount $tontine): TontineCycle
    {
        $lastCycle = $tontine->cycles()->latest('cycle_number')->first();
        $cycleNumber = $lastCycle ? $lastCycle->cycle_number + 1 : 1;

        $startDate = $lastCycle ? $lastCycle->end_date->copy()->addDay() : now();
        
        // Un cycle représente un "mois" de tontine, soit 31 jours selon la règle institutionnelle
        $daysInCycle = 31;
        $targetAmount = $tontine->tontine_amount;

        switch ($tontine->payment_frequency) {
            case 'daily':
                $endDate = $startDate->copy()->addDays($daysInCycle);
                $targetAmount = $tontine->tontine_amount * $daysInCycle;
                break;
            case 'weekly':
                $endDate = $startDate->copy()->addMonths(1);
                // 52 semaines / 12 mois = ~4.33 semaines par cycle
                $targetAmount = $tontine->tontine_amount * (52 / 12);
                break;
            case 'monthly':
                $endDate = $startDate->copy()->addMonths(1);
                $targetAmount = $tontine->tontine_amount;
                break;
            default:
                $endDate = $startDate->copy()->addMonths(1);
        }

        return TontineCycle::create([
            'tontine_account_id' => $tontine->id,
            'cycle_number' => $cycleNumber,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'target_amount' => $targetAmount,
            'collected_amount' => 0,
            'status' => 'active',
        ]);
    }
}
