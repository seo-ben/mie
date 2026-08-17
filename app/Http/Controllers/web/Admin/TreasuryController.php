<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\TreasuryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TreasuryController extends Controller
{
    /**
     * Afficher la page principale de gestion de la trésorerie
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Récupérer les agences accessibles
        $agenciesQuery = Agency::where('is_active', true);
        if ($user->role !== 'administrateur_systeme') {
            $agenciesQuery->where('id', $user->agency_id);
        }
        $agencies = $agenciesQuery->get();

        // Filtrer les mouvements
        $movementsQuery = TreasuryMovement::with(['agency', 'processedBy'])
            ->when($request->agency_id, fn($q, $id) => $q->where('agency_id', $id))
            ->when($request->type, fn($q, $t) => $q->where('type', $t))
            ->when($request->date_start, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->date_end, fn($q, $d) => $q->whereDate('created_at', '<=', $d));

        // Isolation sécurité agence
        if ($user->role !== 'administrateur_systeme' && $user->role !== 'administrateur_reglementaire') {
            $movementsQuery->where('agency_id', $user->agency_id);
        }

        $movements = $movementsQuery->latest()->paginate(20);

        // Stats globales des coffres
        $vaultStats = $agenciesQuery->newQuery()
            ->when($user->role !== 'administrateur_systeme', fn($q) => $q->where('id', $user->agency_id))
            ->select('id', 'name', 'vault_balance', 'cash_limit')
            ->get();

        $totalVaultBalance = $vaultStats->sum('vault_balance');

        return view('admin.treasury.index', compact('agencies', 'movements', 'vaultStats', 'totalVaultBalance'));
    }

    /**
     * Enregistrer un mouvement de trésorerie (Crédit ou Débit manuel)
     */
    public function store(Request $request)
    {
        $request->validate([
            'agency_id' => 'required|exists:agencies,id',
            'type'      => 'required|in:credit,debit',
            'amount'    => 'required|numeric|min:1',
            'motive'    => 'required|string|max:255',
            'notes'     => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $agency = Agency::lockForUpdate()->findOrFail($request->agency_id);
            $amount = (float) $request->amount;
            $balanceBefore = (float) $agency->vault_balance;

            if ($request->type === 'debit' && $balanceBefore < $amount) {
                throw new \Exception('Fonds insuffisants dans la Trésorerie. Solde actuel : ' . number_format($balanceBefore, 0, ',', ' ') . ' XOF');
            }

            // Mettre à jour le coffre-fort de l'agence
            if ($request->type === 'credit') {
                $agency->increment('vault_balance', $amount);
            } else {
                $agency->decrement('vault_balance', $amount);
            }
            $agency->refresh();

            // Enregistrer le mouvement
            TreasuryMovement::create([
                'agency_id'      => $agency->id,
                'processed_by'   => Auth::id(),
                'type'           => $request->type,
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => (float) $agency->vault_balance,
                'reference'      => 'TRES-' . date('YmdHis') . '-' . rand(100, 999),
                'motive'         => $request->motive,
                'notes'          => $request->notes,
            ]);

            DB::commit();

            $typeLabel = $request->type === 'credit' ? 'Crédit' : 'Débit';
            return back()->with('success', "{$typeLabel} de " . number_format($amount, 0, ',', ' ') . " XOF enregistré avec succès sur la trésorerie de {$agency->name}.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Initialiser le solde de départ d'une agence (nouveau déploiement)
     */
    public function initialize(Request $request)
    {
        $request->validate([
            'agency_id'       => 'required|exists:agencies,id',
            'initial_balance' => 'required|numeric|min:0',
            'notes'           => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $agency = Agency::lockForUpdate()->findOrFail($request->agency_id);
            $balanceBefore = (float) $agency->vault_balance;
            $newBalance = (float) $request->initial_balance;

            $agency->update(['vault_balance' => $newBalance]);

            TreasuryMovement::create([
                'agency_id'      => $agency->id,
                'processed_by'   => Auth::id(),
                'type'           => 'credit',
                'amount'         => $newBalance,
                'balance_before' => $balanceBefore,
                'balance_after'  => $newBalance,
                'reference'      => 'INIT-' . date('YmdHis') . '-' . rand(100, 999),
                'motive'         => 'initialisation_systeme',
                'notes'          => $request->notes ?? 'Initialisation du solde de trésorerie au démarrage du système',
            ]);

            DB::commit();

            return back()->with('success', "Trésorerie de {$agency->name} initialisée à " . number_format($newBalance, 0, ',', ' ') . " XOF.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
