<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

class RenumberAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounts:renumber {--dry-run : Afficher les changements sans les enregistrer}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Harmonise et réordonne les numéros de comptes : Tontine (ACC-00001...) et Épargne (SAV-237878...)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info("=== Harmonisation des Numéros de Comptes FSD YAYRA ===");
        if ($isDryRun) {
            $this->warn("⚠️ Mode SIMULATION (Dry Run) : Aucune modification ne sera enregistrée.");
        }

        DB::beginTransaction();
        try {
            // 1. Comptes Tontine : ordonnés par date de création (ACC-00001, ACC-00002, ...)
            $tontineAccounts = Account::where('account_type', 'tontine')
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $this->info("\n--- Comptes Tontine (" . $tontineAccounts->count() . " trouvés) ---");
            $tontineSeq = 1;
            foreach ($tontineAccounts as $account) {
                $newNumber = 'ACC-' . str_pad($tontineSeq, 5, '0', STR_PAD_LEFT);
                $this->line("ID: {$account->id} | {$account->account_number} ➔ {$newNumber}");

                if (!$isDryRun) {
                    $account->update(['account_number' => $newNumber]);
                }
                $tontineSeq++;
            }

            // 2. Comptes Épargne : format à 6 chiffres mélangés (SAV-XXXXXX)
            $savingsAccounts = Account::where('account_type', 'savings')
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $this->info("\n--- Comptes Épargne (" . $savingsAccounts->count() . " trouvés) ---");
            $usedSavNumbers = [];

            foreach ($savingsAccounts as $account) {
                do {
                    $randNum = rand(100000, 999999);
                    $newNumber = 'SAV-' . $randNum;
                } while (in_array($newNumber, $usedSavNumbers));

                $usedSavNumbers[] = $newNumber;
                $this->line("ID: {$account->id} | {$account->account_number} ➔ {$newNumber}");

                if (!$isDryRun) {
                    $account->update(['account_number' => $newNumber]);
                }
            }

            if ($isDryRun) {
                DB::rollBack();
                $this->info("\nSimulation terminée avec succès. Aucun compte modifié.");
            } else {
                DB::commit();
                $this->info("\n✅ Tous les comptes ont été mis à jour avec succès dans la base de données !");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Erreur lors de la mise à jour : " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
