<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SavingsAccount;

class AccrueDailyInterest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:accrue-daily-interest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Accrue interest on savings accounts on a daily basis (prorata)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting daily interest accrual...');
        
        $savingsAccounts = SavingsAccount::whereHas('account', function($query) {
            $query->where('status', 'active')->where('balance', '>', 0);
        })->get();

        $bar = $this->output->createProgressBar(count($savingsAccounts));

        foreach ($savingsAccounts as $savingsAccount) {
            $savingsAccount->accrueDailyInterest();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Daily interest accrual completed.');
    }
}
