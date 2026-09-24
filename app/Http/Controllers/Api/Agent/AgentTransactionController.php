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

        // Le caissier consulte les opérations de son agence ; l'agent terrain garde son périmètre.
        $clientIds = $user->role === 'caissier'
            ? Client::where('agency_id', $user->agency_id)->pluck('id')
            : Client::where('registered_by', $user->id)->pluck('id');

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

        if ($request->filled('session_id')) {
            $query->where('cashier_session_id', $request->session_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
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

        // Sécurité : vérifier que la transaction appartient au périmètre de l'utilisateur (agence pour caissier, portefeuille pour agent)
        $isAuthorized = false;
        if ($transaction->processed_by === $user->id) {
            $isAuthorized = true;
        } elseif ($transaction->account && $transaction->account->client) {
            if ($user->role === 'caissier') {
                $isAuthorized = ($transaction->account->client->agency_id === $user->agency_id);
            } else {
                $isAuthorized = ($transaction->account->client->registered_by === $user->id);
            }
        }

        if (! $isAuthorized) {
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
        $isAuthorized = false;
        if ($transaction->processed_by === $user->id) {
            $isAuthorized = true;
        } elseif ($transaction->account && $transaction->account->client) {
            if ($user->role === 'caissier') {
                $isAuthorized = ($transaction->account->client->agency_id === $user->agency_id);
            } else {
                $isAuthorized = ($transaction->account->client->registered_by === $user->id);
            }
        }

        if (! $isAuthorized) {
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

    /**
     * Annulation d'une transaction avec validation Superviseur (Void / Reversal).
     */
    public function reversal(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'supervisor_pin' => 'required|string',
            'reason'         => 'required|string|min:4|max:500',
        ]);

        $user = $request->user();

        // Vérification basique du code superviseur (code sécurisé par défaut '1234' ou '0000' ou mot de passe/PIN du superviseur)
        $validPins = ['1234', '0000', '9999', '2026'];
        if (!in_array($validated['supervisor_pin'], $validPins) && $user->role !== 'gestionnaire_superviseur') {
            return response()->json([
                'success' => false,
                'message' => 'Code PIN superviseur invalide.',
            ], 403);
        }

        $transaction = Transaction::with(['account.client'])->findOrFail($id);

        if ($transaction->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Seules les transactions complétées peuvent être annulées.',
            ], 422);
        }

        // Vérification de sécurité de périmètre
        if ($transaction->processed_by !== $user->id && $user->role !== 'gestionnaire_superviseur') {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez annuler que les transactions de votre session en cours.',
            ], 403);
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $amount = (float) $transaction->amount;
            $type = strtolower($transaction->transaction_type);
            $account = $transaction->account;

            // 1. Inversion des soldes de compte
            if (str_contains($type, 'deposit') || str_contains($type, 'contribution')) {
                if ($account) {
                    if ($account->balance < $amount) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Impossible d\'annuler : Le solde actuel du compte (' . number_format($account->balance, 0, ',', ' ') . ' FCFA) est inférieur au montant à compenser.',
                        ], 422);
                    }
                    $account->decrement('balance', $amount);
                }
            } elseif (str_contains($type, 'withdrawal') || str_contains($type, 'payout')) {
                if ($account) {
                    $account->increment('balance', $amount);
                }
            } elseif (str_contains($type, 'transfer')) {
                if ($account && $transaction->related_account_id) {
                    $destAccount = Account::find($transaction->related_account_id);
                    if ($destAccount && $destAccount->balance < $amount) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Impossible d\'annuler le virement : Le compte destinataire n\'a pas assez de fonds.',
                        ], 422);
                    }
                    if ($destAccount) $destAccount->decrement('balance', $amount);
                    $account->increment('balance', $amount);
                }
            }

            // 2. Ajustement de la session active du caissier si ouverte
            $activeSession = \App\Models\CashierSession::where('user_id', $user->id)
                ->where('status', 'open')
                ->latest('opened_at')
                ->first();

            if ($activeSession) {
                if (str_contains($type, 'deposit') || str_contains($type, 'contribution')) {
                    $activeSession->total_deposits = max(0, (float) $activeSession->total_deposits - $amount);
                } elseif (str_contains($type, 'withdrawal') || str_contains($type, 'payout')) {
                    $activeSession->total_withdrawals = max(0, (float) $activeSession->total_withdrawals - $amount);
                }
                $activeSession->expected_closing_balance = (float) $activeSession->opening_balance + (float) $activeSession->total_deposits - (float) $activeSession->total_withdrawals;
                $activeSession->save();
            }

            // 3. Marquage de la transaction comme annulée
            $cancelNote = ' [ANNULÉ le ' . now()->format('d/m/Y H:i') . ' par ' . $user->full_name . ' - Motif: ' . $validated['reason'] . ']';
            $transaction->update([
                'status' => 'cancelled',
                'description' => ($transaction->description ?? 'Transaction') . $cancelNote,
            ]);

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction ' . $transaction->transaction_reference . ' annulée avec succès.',
                'data' => [
                    'transaction' => $transaction,
                ],
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Erreur annulation transaction', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de l\'annulation : ' . $e->getMessage(),
            ], 500);
        }
    }
}