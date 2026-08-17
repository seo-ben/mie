<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\SystemParameter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApplyMonthlyManagementFees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'banking:apply-monthly-fees';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prélève automatiquement les agios de gestion mensuelle sur les comptes d\'épargne.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $feeAmount = DB::table('system_parameters')->where('parameter_key', 'monthly_management_fee')->value('parameter_value') ?? 500;
        
        $accounts = Account::where('account_type', 'savings')
            ->where('status', 'active')
            ->where('balance', '>=', $feeAmount)
            ->get();

        $this->info("Traitement de " . $accounts->count() . " comptes pour les agios mensuels...");

        $bar = $this->output->createProgressBar(count($accounts));
        $bar->start();

        foreach ($accounts as $account) {
            DB::transaction(function () use ($account, $feeAmount) {
                $balanceBefore = $account->balance;
                $balanceAfter = $balanceBefore - $feeAmount;

                // Création de la transaction de frais
                Transaction::create([
                    'transaction_reference' => 'FEE-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                    'account_id' => $account->id,
                    'transaction_type' => 'fee',
                    'amount' => $feeAmount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'payment_method' => 'system',
                    'description' => 'Agios de gestion mensuelle (Maintenance infrastructurelle)',
                    'status' => 'completed',
                    'processed_at' => now(),
                    'transaction_date' => now(),
                ]);

                // Mise à jour du solde
                $account->update([
                    'balance' => $balanceAfter,
                    'last_transaction_at' => now()
                ]);
            });

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Prélèvement des agios terminé avec succès.');
    }
}
