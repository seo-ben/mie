<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Initialisation propre de la plateforme YAYRA à partir de zéro
     */
    public function run(): void
    {
        $this->command->info('🚀 Initialisation des données fondamentales de YAYRA...');

        // 1. Création des Agences fondamentales
        $agenceAgoe = Agency::updateOrCreate(
            ['code' => 'AGO'],
            [
                'name' => 'Agence Agoè (Siège)',
                'code' => 'AGO',
                'address' => 'Agoè, Carrefour 2 Lions',
                'city' => 'Lomé',
                'region' => 'Maritime',
                'phone' => '+228 90 00 00 01',
                'cash_limit' => 50000000.00,
                'vault_balance' => 0.00,
                'is_active' => true,
            ]
        );

        $agenceDjagble = Agency::updateOrCreate(
            ['code' => 'DJG'],
            [
                'name' => 'Agence Djagblé',
                'code' => 'DJG',
                'address' => 'Djagblé Centre',
                'city' => 'Djagblé',
                'region' => 'Maritime',
                'phone' => '+228 90 00 00 02',
                'cash_limit' => 20000000.00,
                'vault_balance' => 0.00,
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Agences créées : Agoè (Siège) & Djagblé');

        // 2. Création de l'Administrateur Système Unique
        $adminUser = User::updateOrCreate(
            ['email' => 'Mieadmin360@gmail.com'],
            [
                'username' => 'mieadmin360',
                'first_name' => 'Super',
                'last_name' => 'Administrateur',
                'email' => 'Mieadmin360@gmail.com',
                'phone' => '+22890000000',
                'password' => Hash::make('Mie@2026360@'),
                'role' => 'administrateur_systeme',
                'agency_id' => $agenceAgoe->id,
                'is_active' => true,
                'mfa_enabled' => false,
            ]
        );

        $this->command->info('✅ Administrateur créé : Mieadmin360@gmail.com');

        // 3. Paramètres financiers et frais de base
        $this->call([
            BankingFeesSeeder::class,
            LoanInterestRatesSeeder::class,
        ]);

        $this->command->info('🎉 Plateforme initialisée avec succès ! Prête pour la création des agents de terrain.');
    }
}
