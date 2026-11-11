<?php

namespace App\Http\Controllers\Web\Agent;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Client;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgentTransactionController extends Controller
{
    /**
     * Liste des transactions de l'agent (liées à ses clients)
     */
    public function index(Request $request)
    {
        $user    = Auth::user();
        $agentId = $user->id;

        // Récupérer les clients enregistrés par l'agent
        $clientIds = Client::where('registered_by', $agentId)->pluck('id');

        // Récupérer les IDs de comptes de ces clients
        $accountIds = Account::whereIn('client_id', $clientIds)->pluck('id');

        // Requête de base pour les transactions
        $query = Transaction::with(['account.client', 'processedBy'])
            ->whereIn('account_id', $accountIds)
            ->orderBy('created_at', 'desc');

        // Filtres
        if ($request->filled('date_start')) {
            $query->whereDate('created_at', '>=', $request->date_start);
        }

        if ($request->filled('date_end')) {
            $query->whereDate('created_at', '<=', $request->date_end);
        }

        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                // CORRECTION : utilisation de transaction_reference (cohérent avec le modèle)
                $q->where('transaction_reference', 'like', "%$search%")
                  ->orWhereHas('account.client', function ($cq) use ($search) {
                      $cq->where('first_name', 'like', "%$search%")
                         ->orWhere('last_name', 'like', "%$search%")
                         ->orWhere('client_number', 'like', "%$search%");
                  })
                  ->orWhereHas('account', function ($aq) use ($search) {
                      $aq->where('account_number', 'like', "%$search%");
                  });
            });
        }

        $transactions = $query->paginate(20);

        if (request()->routeIs('caissier.*')) {
            return view('agent.cashier.transactions.index', compact('transactions'));
        }

        return view('agent.transactions.index', compact('transactions'));
    }

    /**
     * Voir les détails d'une transaction
     */
    public function show(Transaction $transaction)
    {
        $user = Auth::user();

        // Charger la relation pour éviter les erreurs de null
        $transaction->load('account.client');

        // Sécurité : vérifier que la transaction appartient à un client de l'agent
        if (
            ! $transaction->account ||
            ! $transaction->account->client ||
            $transaction->account->client->registered_by !== $user->id
        ) {
            abort(403, "Vous n'êtes pas autorisé à voir cette transaction.");
        }

        if (request()->routeIs('caissier.*')) {
            return view('agent.cashier.transactions.show', compact('transaction'));
        }

        return view('agent.transactions.show', compact('transaction'));
    }

    /**
     * Voir le reçu d'une transaction
     */
    public function receipt(Transaction $transaction)
    {
        $user = Auth::user();

        // Charger la relation pour éviter les erreurs de null
        $transaction->load('account.client');

        // Sécurité
        if (
            ! $transaction->account ||
            ! $transaction->account->client ||
            $transaction->account->client->registered_by !== $user->id
        ) {
            abort(403, "Accès refusé.");
        }

        if (request()->routeIs('caissier.*')) {
            return view('agent.cashier.transactions.receipt', compact('transaction'));
        }

        return view('agent.transactions.receipt', compact('transaction'));
    }
}