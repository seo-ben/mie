<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AgentTransactionController extends Controller
{
    /**
     * Liste des transactions gérées par l'agent ou pour ses clients.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Récupérer les IDs des clients enregistrés par l'agent
        $clientIds = Client::where('registered_by', $user->id)->pluck('id');

        // Récupérer les IDs des comptes de ces clients
        $accountIds = Account::whereIn('client_id', $clientIds)->pluck('id');

        $query = Transaction::query()
            ->with(['account.client', 'processedBy'])
            ->where(function ($q) use ($user, $accountIds) {
                $q->where('processed_by', $user->id)
                  ->orWhereIn('account_id', $accountIds);
            });

        // Filtres
        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_start')) {
            $query->whereDate('created_at', '>=', $request->date_start);
        }

        if ($request->filled('date_end')) {
            $query->whereDate('created_at', '<=', $request->date_end);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                // CORRECTION : harmonisé avec le Web — utilisation de transaction_reference
                $q->where('transaction_reference', 'like', "%{$search}%")
                  ->orWhereHas('account.client', function ($cq) use ($search) {
                      $cq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('client_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('account', function ($aq) use ($search) {
                      $aq->where('account_number', 'like', "%{$search}%");
                  });
            });
        }

        $transactions = $query->latest()->paginate(20);

        $transformed = collect($transactions->items())->map(function ($t) {
            return [
                'id'                  => $t->id,
                // CORRECTION : champ réel en base — transaction_reference
                'transaction_reference' => $t->transaction_reference,
                'transaction_type'    => $t->transaction_type,
                'amount'              => $t->amount,
                'fee_amount'          => $t->fee_amount,
                'status'              => $t->status,
                'payment_method'      => $t->payment_method,
                'payment_reference'   => $t->payment_reference,
                'description'         => $t->description,
                'created_at'          => $t->created_at,
                'transaction_date'    => $t->transaction_date,
                'client_name'         => $t->account->client->full_name ?? 'N/A',
                'client_number'       => $t->account->client->client_number ?? 'N/A',
                'account_number'      => $t->account->account_number ?? 'N/A',
                'processed_by'        => $t->processedBy->full_name ?? 'Système',
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $transformed,
            'meta'    => [
                'current_page' => $transactions->currentPage(),
                'last_page'    => $transactions->lastPage(),
                'per_page'     => $transactions->perPage(),
                'total'        => $transactions->total(),
            ],
        ]);
    }

    /**
     * Détails d'une transaction.
     * CORRECTION : vérification de sécurité (registered_by) ajoutée, absente dans l'ancienne version.
     */
    public function show(int $id): JsonResponse
    {
        $user = auth()->user();

        $transaction = Transaction::with(['account.client', 'processedBy'])
            ->findOrFail($id);

        // Sécurité : vérifier que la transaction appartient à un client de l'agent
        if (
            ! $transaction->account ||
            ! $transaction->account->client ||
            $transaction->account->client->registered_by !== $user->id
        ) {
            return response()->json([
                'success' => false,
                'message' => "Vous n'êtes pas autorisé à voir cette transaction.",
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => $transaction,
        ]);
    }

    /**
     * Reçu d'une transaction (équivalent API de la méthode receipt du Web).
     */
    public function receipt(int $id): JsonResponse
    {
        $user = auth()->user();

        $transaction = Transaction::with(['account.client', 'processedBy', 'agency'])
            ->findOrFail($id);

        // Sécurité
        if (
            ! $transaction->account ||
            ! $transaction->account->client ||
            $transaction->account->client->registered_by !== $user->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'transaction'      => $transaction,
                'client_name'      => $transaction->account->client->full_name,
                'account_number'   => $transaction->account->account_number,
                'processed_by'     => $transaction->processedBy->full_name ?? 'Système',
                'agency'           => $transaction->agency->name ?? 'N/A',
            ],
        ]);
    }
}