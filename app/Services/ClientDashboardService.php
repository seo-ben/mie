<?php
namespace App\Services;

use App\Models\Client;
use App\Models\Notification;
use Carbon\Carbon;

class ClientDashboardService
{
    public function getDashboardData($clientId)
    {
        $client = Client::with([
            'accounts.savingsAccount',
            'accounts.tontineAccount',
            'loans' => function($query) {
                $query->whereIn('status', ['active', 'disbursed']);
            }
        ])->findOrFail($clientId);

        // Calculs des soldes
        $totalSavings = $client->savingsAccounts->sum('balance');
        $totalTontine = $client->tontineAccounts->sum('balance');
        $totalBalance = $totalSavings + $totalTontine;

        // Informations sur les prêts
        $activeLoans = $client->loans;
        $totalOutstanding = $activeLoans->sum('outstanding_principal');
        
        // Prochaine échéance
        $nextPayment = $this->getNextPaymentDue($clientId);

        // Transactions récentes
        $recentTransactions = $this->getRecentTransactions($clientId, 10);

        // Progression des tontines
        $tontineProgress = $this->getTontineProgress($clientId);

        // Notifications non lues
        $unreadNotifications = Notification::where('recipient_type', 'client')
            ->where('recipient_id', $clientId)
            ->where('status', '!=', 'read')
            ->count();

        return [
            'client' => $client,
            'total_savings' => $totalSavings,
            'total_tontine' => $totalTontine,
            'total_balance' => $totalBalance,
            'savings_accounts_count' => $client->savingsAccounts->count(),
            'tontine_accounts_count' => $client->tontineAccounts->count(),
            'active_loans_count' => $activeLoans->count(),
            'total_outstanding' => $totalOutstanding,
            'next_payment_date' => $nextPayment['date'] ?? null,
            'next_payment_amount' => $nextPayment['amount'] ?? null,
            'recent_transactions' => $recentTransactions,
            'tontine_progress' => $tontineProgress,
            'unread_notifications_count' => $unreadNotifications
        ];
    }

    private function getNextPaymentDue($clientId)
    {
        $nextPayment = \DB::table('loan_payments')
            ->join('loans', 'loan_payments.loan_id', '=', 'loans.id')
            ->where('loans.client_id', $clientId)
            ->where('loan_payments.status', 'pending')
            ->where('loan_payments.due_date', '>=', Carbon::now())
            ->orderBy('loan_payments.due_date')
            ->select('loan_payments.due_date', 'loan_payments.expected_amount', 'loan_payments.penalty_amount')
            ->first();

        if ($nextPayment) {
            return [
                'date' => $nextPayment->due_date,
                'amount' => $nextPayment->expected_amount + $nextPayment->penalty_amount
            ];
        }

        return null;
    }

    private function getRecentTransactions($clientId, $limit = 10)
    {
        return \DB::table('v_client_transaction_history')
            ->where('client_id', $clientId)
            ->orderBy('transaction_date', 'desc')
            ->limit($limit)
            ->get();
    }

    private function getTontineProgress($clientId)
    {
        $client = Client::findOrFail($clientId);
        $tontineAccounts = $client->tontineAccounts()->with('tontineAccount')->get();

        return $tontineAccounts->map(function($account) {
            $tontine = $account->tontineAccount;
            return [
                'account_id' => $account->id,
                'tontine_amount' => $tontine->tontine_amount,
                'cycle_progress' => ($tontine->payments_made / ($tontine->total_expected / $tontine->expected_monthly_payment)) * 100,
                'payments_made' => $tontine->payments_made,
                'total_payments_expected' => $tontine->total_expected / $tontine->expected_monthly_payment,
                'cycle_end_date' => $tontine->cycle_end_date,
                'next_payment_due' => Carbon::parse($tontine->cycle_start_date)->addMonths($tontine->payments_made + 1)
            ];
        });
    }
}