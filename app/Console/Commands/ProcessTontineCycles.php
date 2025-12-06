<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TontineAccount;
use App\Models\TontineCycle;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessTontineCycles extends Command
{
    protected $signature = 'tontine:process-cycles';
    protected $description = 'Traite les cycles de tontine terminés';

    public function handle()
    {
        $this->info('Début du traitement des cycles de tontine...');

        $completedCycles = TontineCycle::where('status', 'active')
            ->where('end_date', '<=', Carbon::now())
            ->get();

        $processed = 0;
        foreach ($completedCycles as $cycle) {
            if ($this->processCycle($cycle)) {
                $processed++;
            }
        }

        $this->info("Traitement terminé. {$processed} cycles traités.");
    }

    private function processCycle($cycle)
    {
        DB::beginTransaction();
        try {
            // Calculer le montant de redistribution
            $payoutAmount = $cycle->collected_amount * 1.1; // 10% de bénéfice

            // Marquer le cycle comme terminé
            $cycle->update([
                'status' => 'completed',
                'payout_amount' => $payoutAmount,
                'payout_date' => Carbon::now()
            ]);

            // Créditer le compte
            $tontineAccount = $cycle->tontineAccount;
            $account = $tontineAccount->account;

            Transaction::create([
                'account_id' => $account->id,
                'transaction_type' => 'payout',
                'amount' => $payoutAmount,
                'balance_before' => $account->balance,
                'balance_after' => $account->balance + $payoutAmount,
                'payment_method' => 'system',
                'description' => "Redistribution cycle tontine #{$cycle->cycle_number}",
                'status' => 'completed',
                'transaction_date' => Carbon::now()
            ]);

            // Mettre à jour le solde du compte
            $account->update([
                'balance' => $account->balance + $payoutAmount,
                'last_transaction_at' => Carbon::now()
            ]);

            // Créer le nouveau cycle si nécessaire
            $this->createNextCycle($tontineAccount);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors du traitement du cycle tontine', [
                'cycle_id' => $cycle->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function createNextCycle($tontineAccount)
    {
        $nextCycleNumber = $tontineAccount->current_cycle + 1;
        
        TontineCycle::create([
            'tontine_account_id' => $tontineAccount->id,
            'cycle_number' => $nextCycleNumber,
            'start_date' => Carbon::now()->startOfMonth(),
            'end_date' => Carbon::now()->addMonths($tontineAccount->cycle_duration_months)->endOfMonth(),
            'target_amount' => $tontineAccount->total_expected,
            'status' => 'active'
        ]);

        $tontineAccount->update([
            'current_cycle' => $nextCycleNumber,
            'cycle_start_date' => Carbon::now()->startOfMonth(),
            'cycle_end_date' => Carbon::now()->addMonths($tontineAccount->cycle_duration_months)->endOfMonth(),
            'payments_made' => 0 // Reset pour le nouveau cycle
        ]);
    }
}