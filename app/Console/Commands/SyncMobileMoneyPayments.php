<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MobileMoneyService;
use App\Models\Transaction;

class SyncMobileMoneyPayments extends Command
{
    protected $signature = 'mobilemoney:sync';
    protected $description = 'Synchronise les paiements Mobile Money en attente';

    public function handle(MobileMoneyService $mobileMoneyService)
    {
        $this->info('Début de la synchronisation Mobile Money...');

        $pendingTransactions = Transaction::where('payment_method', 'mobile_money')
                                        ->where('status', 'pending')
                                        ->get();

        $updated = 0;
        foreach ($pendingTransactions as $transaction) {
            if ($mobileMoneyService->checkPaymentStatus($transaction)) {
                $updated++;
            }
        }

        $this->info("Synchronisation terminée. {$updated} transactions mises à jour.");
    }
}