<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Loan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NormalizeAndMergeClients extends Command
{
    protected $signature = 'clients:normalize-and-merge {--dry-run : Exécuter une simulation sans modifier la base}';
    protected $description = 'Normalise les numéros de téléphone des clients et fusionne les dossiers en double';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info($isDryRun ? "=== SIMULATION DE NORMALISATION ET FUSION DES CLIENTS ===" : "=== NORMALISATION ET FUSION DES CLIENTS ===");

        DB::beginTransaction();

        try {
            $clients = Client::orderBy('id', 'asc')->get();
            $normalizedMap = [];

            foreach ($clients as $client) {
                $rawPhone = $client->phone;
                $cleanPhone = Client::normalizePhone($rawPhone);

                // Clé de regroupement : téléphone normalisé s'il existe, sinon nom complet en minuscules
                $groupKey = !empty($cleanPhone) ? 'phone:' . $cleanPhone : 'name:' . mb_strtolower(trim($client->first_name . ' ' . $client->last_name));

                if (!isset($normalizedMap[$groupKey])) {
                    $normalizedMap[$groupKey] = [];
                }
                $normalizedMap[$groupKey][] = $client;
            }

            $mergedCount = 0;
            $updatedPhonesCount = 0;

            foreach ($normalizedMap as $groupKey => $groupClients) {
                // Le client principal est le premier créé
                $primaryClient = $groupClients[0];
                $cleanPhone = Client::normalizePhone($primaryClient->phone);

                // Mettre à jour le téléphone du client principal s'il a changé
                if ($primaryClient->phone !== $cleanPhone && !empty($cleanPhone)) {
                    $this->line("Normalisation téléphone Client {$primaryClient->id} ({$primaryClient->full_name}) : '{$primaryClient->phone}' ➔ '{$cleanPhone}'");
                    if (!$isDryRun) {
                        $primaryClient->phone = $cleanPhone;
                        $primaryClient->saveQuietly();
                    }
                    $updatedPhonesCount++;
                }

                // S'il y a des doublons dans le groupe
                if (count($groupClients) > 1) {
                    $duplicates = array_slice($groupClients, 1);

                    foreach ($duplicates as $duplicate) {
                        $this->warn("Fusion du doublon Client {$duplicate->id} ({$duplicate->full_name}, {$duplicate->phone}) vers Client Principal {$primaryClient->id} ({$primaryClient->full_name})");

                        // Transférer les comptes
                        $accounts = Account::where('client_id', $duplicate->id)->get();
                        foreach ($accounts as $acc) {
                            // Vérifier si le compte du doublon est vide et fait doublon avec un compte du client principal
                            $hasTx = Transaction::where('account_id', $acc->id)->exists();
                            $hasBalance = (float) $acc->balance > 0;
                            $tontinePaid = $acc->tontineAccount ? (float) $acc->tontineAccount->total_paid : 0;

                            $primaryExistingAcc = Account::where('client_id', $primaryClient->id)
                                ->where('account_type', $acc->account_type)
                                ->where('status', 'active')
                                ->first();

                            if (!$hasTx && !$hasBalance && $tontinePaid <= 0 && $primaryExistingAcc) {
                                // Compte vide inutile, on supprime
                                $this->line("  - Suppression du compte vide {$acc->account_number} ({$acc->id})");
                                if (!$isDryRun) {
                                    if ($acc->tontineAccount) {
                                        $acc->tontineAccount->cycles()->delete();
                                        $acc->tontineAccount->delete();
                                    }
                                    $acc->delete();
                                }
                            } else {
                                // Compte actif ou avec transactions, on le rattache au client principal
                                $this->line("  - Rattachement du compte {$acc->account_number} ({$acc->id}) au Client Principal {$primaryClient->id}");
                                if (!$isDryRun) {
                                    $acc->client_id = $primaryClient->id;
                                    $acc->saveQuietly();
                                }
                            }
                        }

                        // Transférer les prêts
                        if (!$isDryRun) {
                            Loan::where('client_id', $duplicate->id)->update(['client_id' => $primaryClient->id]);
                        }

                        // Supprimer le client en double
                        if (!$isDryRun) {
                            $duplicate->delete();
                        }
                        $mergedCount++;
                    }
                }
            }

            if (!$isDryRun) {
                DB::commit();
                $this->info("Opération terminée avec succès !");
            } else {
                DB::rollBack();
                $this->info("Simulation terminée (aucune modification enregistrée).");
            }

            $this->table(
                ['Métrique', 'Nombre'],
                [
                    ['Téléphones normalisés', $updatedPhonesCount],
                    ['Clients en doublon fusionnés', $mergedCount],
                    ['Clients actifs restants', Client::count()]
                ]
            );

            return 0;

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Erreur lors de la normalisation : " . $e->getMessage());
            Log::error("MergeClients Error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            return 1;
        }
    }
}

