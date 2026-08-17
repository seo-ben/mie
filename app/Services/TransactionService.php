<?php
namespace App\Services;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\Client;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionService
{
    /**
     * Créer un dépôt
     */
    public function createDeposit($data)
    {
        return DB::transaction(function() use ($data) {
            $account = Account::lockForUpdate()->findOrFail($data['account_id']);
            $balanceBefore = $account->balance;
            
            $transaction = Transaction::create([
                'account_id' => $account->id,
                'transaction_type' => 'deposit',
                'amount' => $data['amount'],
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $data['amount'],
                'transaction_reference' => 'DEP-' . strtoupper(Str::random(10)),
                'payment_method' => $data['payment_method'],
                'payment_reference' => $data['payment_reference'],
                'mobile_money_operator' => $data['mobile_money_operator'] ?? null,
                'description' => $data['description'],
                'status' => 'completed',
                'transaction_date' => now()
            ]);

            $account->update([
                'balance' => $balanceBefore + $data['amount'],
                'last_transaction_at' => now()
            ]);

            return $transaction;
        });
    }

    /**
     * Créer un retrait
     */
    public function createWithdrawal($data)
    {
        return DB::transaction(function() use ($data) {
            $account = Account::lockForUpdate()->findOrFail($data['account_id']);
            
            if ($account->balance < $data['amount']) {
                throw new \Exception("Solde insuffisant.");
            }

            $balanceBefore = $account->balance;
            
            $transaction = Transaction::create([
                'account_id' => $account->id,
                'transaction_type' => 'withdrawal',
                'amount' => $data['amount'],
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore - $data['amount'],
                'transaction_reference' => 'WTH-' . strtoupper(Str::random(10)),
                'payment_method' => $data['withdrawal_method'] ?? 'cash',
                'description' => $data['description'],
                'status' => 'pending', // Les retraits nécessitent souvent une validation
                'validation_required' => $data['amount'] > 50000,
                'transaction_date' => now()
            ]);

            // On ne met à jour le solde qu'à la validation si nécessaire, 
            // mais ici on simplifie pour le test
            $account->update([
                'balance' => $balanceBefore - $data['amount'],
                'last_transaction_at' => now()
            ]);

            return $transaction;
        });
    }

    /**
     * Générer un reçu
     */
    public function generateReceipt($transactionId)
    {
        $transaction = Transaction::with(['account.client'])->findOrFail($transactionId);
        
        return [
            'reference' => $transaction->transaction_reference,
            'date' => $transaction->transaction_date->format('d/m/Y H:i'),
            'client' => $transaction->account->client->full_name,
            'amount' => $transaction->amount,
            'type' => $transaction->transaction_type,
            'status' => $transaction->status
        ];
    }

    public function generateReceiptPDF($transactionId)
    {
        // Simulation de chemin PDF
        return "storage/receipts/receipt_{$transactionId}.pdf";
    }

    /**
     * Exporter les transactions
     */
    public function exportTransactions($clientId, $format, $params)
    {
        return "storage/exports/transactions_{$clientId}.{$format}";
    }

    /**
     * Statistiques de transactions client
     */
    public function getClientTransactionStats($clientId, $startDate, $endDate)
    {
        $transactions = Transaction::join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->where('accounts.client_id', $clientId)
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->get();

        return [
            'total_count' => $transactions->count(),
            'total_deposited' => $transactions->where('transaction_type', 'deposit')->sum('amount'),
            'total_withdrawn' => $transactions->where('transaction_type', 'withdrawal')->sum('amount'),
        ];
    }

    /**
     * Rechercher des transactions
     */
    public function searchTransactions($query, $user, $limit)
    {
        return Transaction::join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->where('accounts.client_id', $user->id)
            ->where('transactions.transaction_reference', 'LIKE', "%{$query}%")
            ->limit($limit)
            ->get();
    }
}