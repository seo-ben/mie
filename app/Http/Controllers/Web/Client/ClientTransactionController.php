<?php

namespace App\Http\Controllers\Web\Client;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\User;
use App\Models\Loan;
use App\Services\TransactionService;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\WithdrawalRequest;
use App\Http\Resources\TransactionResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ClientTransactionController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Historique des transactions du client
     */
    public function index(Request $request)
    {
        $client = auth()->user();

        $query = Transaction::join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->where('accounts.client_id', $client->id)
            ->with(['account'])
            ->select('transactions.*');

        // Filtres
        if ($request->get('account_id')) {
            $query->where('transactions.account_id', $request->get('account_id'));
        }

        if ($request->get('transaction_type')) {
            $query->where('transactions.transaction_type', $request->get('transaction_type'));
        }

        if ($request->get('status')) {
            $query->where('transactions.status', $request->get('status'));
        }

        if ($request->get('date_from')) {
            $query->whereDate('transactions.transaction_date', '>=', $request->get('date_from'));
        }

        if ($request->get('date_to')) {
            $query->whereDate('transactions.transaction_date', '<=', $request->get('date_to'));
        }

        if ($request->get('min_amount')) {
            $query->where('transactions.amount', '>=', $request->get('min_amount'));
        }

        if ($request->get('max_amount')) {
            $query->where('transactions.amount', '<=', $request->get('max_amount'));
        }

        if ($request->get('payment_method')) {
            $query->where('transactions.payment_method', $request->get('payment_method'));
        }

        // Recherche par référence
        if ($request->get('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('transactions.transaction_reference', 'LIKE', "%{$search}%")
                  ->orWhere('transactions.payment_reference', 'LIKE', "%{$search}%")
                  ->orWhere('transactions.description', 'LIKE', "%{$search}%");
            });
        }

        $transactions = $query->orderBy('transactions.transaction_date', 'desc')
            ->paginate($request->get('per_page', 20));

        // Calculer les totaux de la période
        $totals = [
            'deposits' => Transaction::join('accounts', 'transactions.account_id', '=', 'accounts.id')
                ->where('accounts.client_id', $client->id)
                ->where('transactions.transaction_type', 'deposit')
                ->where('transactions.status', 'completed')
                ->when($request->get('date_from'), fn($q) => $q->whereDate('transactions.transaction_date', '>=', $request->get('date_from')))
                ->when($request->get('date_to'), fn($q) => $q->whereDate('transactions.transaction_date', '<=', $request->get('date_to')))
                ->sum('transactions.amount'),

            'withdrawals' => Transaction::join('accounts', 'transactions.account_id', '=', 'accounts.id')
                ->where('accounts.client_id', $client->id)
                ->where('transactions.transaction_type', 'withdrawal')
                ->where('transactions.status', 'completed')
                ->when($request->get('date_from'), fn($q) => $q->whereDate('transactions.transaction_date', '>=', $request->get('date_from')))
                ->when($request->get('date_to'), fn($q) => $q->whereDate('transactions.transaction_date', '<=', $request->get('date_to')))
                ->sum('transactions.amount')
        ];

        return response()->json([
            'success' => true,
            'data' => TransactionResource::collection($transactions),
            'meta' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage()
            ],
            'summary' => [
                'period_deposits' => $totals['deposits'],
                'period_withdrawals' => $totals['withdrawals'],
                'net_movement' => $totals['deposits'] - $totals['withdrawals']
            ]
        ]);
    }

    /**
     * Détails d'une transaction spécifique
     */
    public function show($transactionId)
    {
        $client = auth()->user();

        $transaction = Transaction::join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->where('accounts.client_id', $client->id)
            ->where('transactions.id', $transactionId)
            ->with(['account', 'processedBy', 'validatedBy'])
            ->select('transactions.*')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'transaction' => new TransactionResource($transaction),
                'receipt' => [
                    'available' => true,
                    'download_url' => route('api.client.transactions.receipt', $transactionId)
                ],
                'can_dispute' => $this->canDisputeTransaction($transaction),
                'timeline' => $this->getTransactionTimeline($transaction)
            ]
        ]);
    }

    /**
     * Effectuer un dépôt
     */
    public function deposit(DepositRequest $request)
    {
        try {
            $client = auth()->user();

            // Vérifier que le compte appartient au client
            $account = Account::where('client_id', $client->id)
                ->where('status', 'active')
                ->findOrFail($request->get('account_id'));

            $transaction = $this->transactionService->createDeposit([
                'client_id' => $client->id,
                'account_id' => $account->id,
                'amount' => $request->get('amount'),
                'payment_method' => $request->get('payment_method'),
                'payment_reference' => $request->get('payment_reference'),
                'mobile_money_operator' => $request->get('mobile_money_operator'),
                'description' => $request->get('description', 'Dépôt client')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Dépôt initié avec succès',
                'data' => [
                    'transaction' => new TransactionResource($transaction),
                    'status_message' => $this->getStatusMessage($transaction->payment_method),
                    'estimated_processing_time' => $this->getProcessingTime($transaction->payment_method)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du dépôt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Effectuer un retrait
     */
    public function withdrawal(WithdrawalRequest $request)
    {
        try {
            $client = auth()->user();

            // Vérifier que le compte appartient au client et a un solde suffisant
            $account = Account::where('client_id', $client->id)
                ->where('status', 'active')
                ->findOrFail($request->get('account_id'));

            if ($account->balance < $request->get('amount')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solde insuffisant'
                ], 422);
            }

            $transaction = $this->transactionService->createWithdrawal([
                'client_id' => $client->id,
                'account_id' => $account->id,
                'amount' => $request->get('amount'),
                'withdrawal_method' => $request->get('withdrawal_method'),
                'description' => $request->get('description', 'Retrait client')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Demande de retrait soumise avec succès',
                'data' => [
                    'transaction' => new TransactionResource($transaction),
                    'requires_validation' => $transaction->validation_required,
                    'estimated_processing_time' => '24-48 heures'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la demande de retrait',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer le reçu d'une transaction
     */
    public function receipt($transactionId)
    {
        try {
            $client = auth()->user();

            $transaction = Transaction::join('accounts', 'transactions.account_id', '=', 'accounts.id')
                ->where('accounts.client_id', $client->id)
                ->where('transactions.id', $transactionId)
                ->select('transactions.*')
                ->firstOrFail();

            $receipt = $this->transactionService->generateReceipt($transactionId);

            return response()->json([
                'success' => true,
                'data' => $receipt
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du reçu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Télécharger le reçu en PDF
     */
    public function downloadReceipt($transactionId)
    {
        try {
            $client = auth()->user();

            $transaction = Transaction::join('accounts', 'transactions.account_id', '=', 'accounts.id')
                ->where('accounts.client_id', $client->id)
                ->where('transactions.id', $transactionId)
                ->select('transactions.*')
                ->firstOrFail();

            $pdf = $this->transactionService->generateReceiptPDF($transactionId);

            return response()->download($pdf)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exporter l'historique des transactions
     */
    public function export(Request $request)
    {
        $request->validate([
            'format' => 'required|in:pdf,excel,csv',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from'
        ]);

        try {
            $client = auth()->user();

            $file = $this->transactionService->exportTransactions(
                $client->id,
                $request->get('format'),
                $request->only(['date_from', 'date_to', 'account_id', 'transaction_type'])
            );

            return response()->download($file)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir la chronologie d'une transaction
     */
    private function getTransactionTimeline(Transaction $transaction): array
    {
        $timeline = [];

        $timeline[] = [
            'date' => $transaction->created_at,
            'action' => 'Transaction initiée',
            'status' => 'completed'
        ];

        if ($transaction->processed_at) {
            $timeline[] = [
                'date' => $transaction->processed_at,
                'action' => 'Transaction traitée',
                'status' => 'completed',
                'actor' => $transaction->processedBy?->full_name
            ];
        }

        if ($transaction->validated_at) {
            $timeline[] = [
                'date' => $transaction->validated_at,
                'action' => 'Transaction validée',
                'status' => 'completed',
                'actor' => $transaction->validatedBy?->full_name
            ];
        }

        return $timeline;
    }

    /**
     * Vérifier si une transaction peut être contestée
     */
    private function canDisputeTransaction(Transaction $transaction): bool
    {
        if (!in_array($transaction->status, ['completed', 'failed'])) {
            return false;
        }

        // On peut contester une transaction jusqu'à 30 jours après
        return $transaction->created_at->diffInDays(now()) <= 30;
    }

    /**
     * Obtenir le message de statut selon la méthode de paiement
     */
    private function getStatusMessage(string $paymentMethod): string
    {
        return match($paymentMethod) {
            'mobile_money' => 'Veuillez confirmer le paiement sur votre téléphone',
            'bank_transfer' => 'Le transfert bancaire sera vérifié sous 24-48h',
            'cash' => 'Le dépôt sera validé par l\'agent',
            default => 'Transaction en cours de traitement'
        };
    }

    /**
     * Obtenir le temps de traitement estimé
     */
    private function getProcessingTime(string $paymentMethod): string
    {
        return match($paymentMethod) {
            'mobile_money' => '5-15 minutes',
            'bank_transfer' => '24-48 heures',
            'cash' => '1-2 heures',
            default => '24 heures'
        };
    }

    /**
     * Statistiques des transactions
     */
    public function statistics(Request $request): JsonResponse
    {
        $client = auth()->user();
        $period = $request->get('period', '30d');

        $days = match($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
            default => 30
        };

        $startDate = now()->subDays($days);

        $stats = $this->transactionService->getClientTransactionStats(
            $client->id,
            $startDate,
            now()
        );

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Recherche globale
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:3',
            'type' => 'required|in:transactions,accounts,loans',
            'limit' => 'integer|min:1|max:50'
        ]);

        $query = $request->get('query');
        $type = $request->get('type');
        $limit = $request->get('limit', 10);
        $user = auth()->user();
        $results = collect();

        switch($type) {
            case 'transactions':
                $results = $this->transactionService->searchTransactions($query, $user, $limit);
                break;
            case 'accounts':
                $results = $this->searchAccounts($query, $user, $limit);
                break;
            case 'loans':
                $results = $this->searchLoans($query, $user, $limit);
                break;
        }

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }
}
