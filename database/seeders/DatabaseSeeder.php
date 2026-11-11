<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\User;
use App\Models\SystemParameter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeder principal pour initialiser la plateforme YAYRA à partir de zéro
     */
    public function run(): void
    {
        $this->command->info('🚀 [YAYRA SEEDER] Démarrage de l\'initialisation fondamentale...');

        // ---------------------------------------------------------------------
        // 1. CRÉATION DES AGENCES FONDAMENTALES (avec IDs explicites)
        // ---------------------------------------------------------------------
        DB::table('agencies')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'Agence Agoè (Siège)',
                'code' => 'AGO',
                'address' => 'Agoè, Carrefour 2 Lions',
                'city' => 'Lomé',
                'region' => 'Maritime',
                'phone' => '+228 90 00 00 01',
                'cash_limit' => 50000000.00,
                'vault_balance' => 50000000.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('agencies')->updateOrInsert(
            ['id' => 2],
            [
                'name' => 'Agence Djagblé',
                'code' => 'DJG',
                'address' => 'Djagblé Centre',
                'city' => 'Djagblé',
                'region' => 'Maritime',
                'phone' => '+228 90 00 00 02',
                'cash_limit' => 20000000.00,
                'vault_balance' => 20000000.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command->info('✅ Agences configurées : Agoè (Siège - ID 1) & Djagblé (ID 2)');

        // ---------------------------------------------------------------------
        // 2. CRÉATION DU SUPER ADMINISTRATEUR SYSTÈME (avec ID explicite)
        // ---------------------------------------------------------------------
        DB::table('users')->updateOrInsert(
            ['email' => 'Mieadmin360@gmail.com'],
            [
                'id' => 1,
                'username' => 'mieadmin360',
                'first_name' => 'Super',
                'last_name' => 'Administrateur',
                'email' => 'Mieadmin360@gmail.com',
                'phone' => '+22890000000',
                'password' => Hash::make('Mie@2026360@'),
                'role' => 'administrateur_systeme',
                'agency_id' => 1,
                'is_active' => true,
                'mfa_enabled' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command->info('✅ Super Admin configuré : Mieadmin360@gmail.com (Mie@2026360@)');

        // ---------------------------------------------------------------------
        // 3. PARAMÈTRES SYSTÈME COMPLETS (Frais, Taux, Tontine, Limites)
        // ---------------------------------------------------------------------
        $parameters = [
            // Frais Bancaires et Services
            [
                'parameter_key' => 'savings_account_activation_fee',
                'parameter_value' => '7000',
                'parameter_type' => 'number',
                'description' => 'Frais d\'activation compte épargne (FCFA)',
                'category' => 'fees',
                'is_editable' => true,
            ],
            [
                'parameter_key' => 'tontine_carnet_fee',
                'parameter_value' => '1000',
                'parameter_type' => 'number',
                'description' => 'Frais d\'adhésion / carnet de tontine (FCFA)',
                'category' => 'fees',
                'is_editable' => true,
            ],
            [
                'parameter_key' => 'savings_withdrawal_fee_percentage',
                'parameter_value' => '2.0',
                'parameter_type' => 'number',
                'description' => 'Commission sur retrait Épargne (%)',
                'category' => 'fees',
                'is_editable' => true,
            ],
            [
                'parameter_key' => 'savings_withdrawal_fee_fixed',
                'parameter_value' => '0',
                'parameter_type' => 'number',
                'description' => 'Frais fixe sur retrait Épargne (FCFA)',
                'category' => 'fees',
                'is_editable' => true,
            ],
            [
                'parameter_key' => 'tontine_withdrawal_fee_percentage',
                'parameter_value' => '3.0',
                'parameter_type' => 'number',
                'description' => 'Commission sur retrait Tontine (%)',
                'category' => 'fees',
                'is_editable' => true,
            ],
            [
                'parameter_key' => 'tontine_withdrawal_fee_fixed',
                'parameter_value' => '0',
                'parameter_type' => 'number',
                'description' => 'Frais fixe sur retrait Tontine (FCFA)',
                'category' => 'fees',
                'is_editable' => true,
            ],
            [
                'parameter_key' => 'loan_file_study_fee',
                'parameter_value' => '5000',
                'parameter_type' => 'number',
                'description' => 'Frais d\'étude de dossier de crédit (FCFA)',
                'category' => 'fees',
                'is_editable' => true,
            ],
            [
                'parameter_key' => 'sms_notification_fee',
                'parameter_value' => '25',
                'parameter_type' => 'number',
                'description' => 'Frais par notification SMS (FCFA)',
                'category' => 'fees',
                'is_editable' => true,
            ],

            // Taux d'intérêt des crédits
            [
                'parameter_key' => 'loan_interest_rate_low',
                'parameter_value' => '12.0',
                'parameter_type' => 'number',
                'description' => 'Taux d\'intérêt annuel - Risque faible (%)',
                'category' => 'rates',
                'is_editable' => true,
            ],
            [
                'parameter_key' => 'loan_interest_rate_medium',
                'parameter_value' => '17.0',
                'parameter_type' => 'number',
                'description' => 'Taux d\'intérêt annuel - Risque moyen (%)',
                'category' => 'rates',
                'is_editable' => true,
            ],
            [
                'parameter_key' => 'loan_interest_rate_high',
                'parameter_value' => '20.0',
                'parameter_type' => 'number',
                'description' => 'Taux d\'intérêt annuel - Risque élevé (%)',
                'category' => 'rates',
                'is_editable' => true,
            ],
            [
                'parameter_key' => 'loan_interest_rate_default',
                'parameter_value' => '17.0',
                'parameter_type' => 'number',
                'description' => 'Taux d\'intérêt annuel standard (%)',
                'category' => 'rates',
                'is_editable' => true,
            ],
            [
                'parameter_key' => 'loan_penalty_rate_daily',
                'parameter_value' => '0.1',
                'parameter_type' => 'number',
                'description' => 'Pénalité journalière de retard (%)',
                'category' => 'rates',
                'is_editable' => true,
            ],

            // Règles Tontine et Collecte Terrain
            [
                'parameter_key' => 'tontine_cycle_duration_days',
                'parameter_value' => '31',
                'parameter_type' => 'number',
                'description' => 'Durée standard d\'un cycle de tontine (Jours)',
                'category' => 'tontine',
                'is_editable' => true,
            ],
            [
                'parameter_key' => 'tontine_commission_days',
                'parameter_value' => '1',
                'parameter_type' => 'number',
                'description' => 'Nombre de mises prélevées comme commission par cycle',
                'category' => 'tontine',
                'is_editable' => true,
            ],
            [
                'parameter_key' => 'agent_max_daily_cash_limit',
                'parameter_value' => '500000',
                'parameter_type' => 'number',
                'description' => 'Plafond maximum d\'espèces par agent terrain (FCFA)',
                'category' => 'limits',
                'is_editable' => true,
            ],
            [
                'parameter_key' => 'min_deposit_amount',
                'parameter_value' => '100',
                'parameter_type' => 'number',
                'description' => 'Montant minimum d\'un dépôt (FCFA)',
                'category' => 'limits',
                'is_editable' => true,
            ],

            // Données Générales
            [
                'parameter_key' => 'app_currency',
                'parameter_value' => 'XOF',
                'parameter_type' => 'string',
                'description' => 'Devise officielle de l\'institution',
                'category' => 'general',
                'is_editable' => false,
            ],
            [
                'parameter_key' => 'company_name',
                'parameter_value' => 'YAYRA Microfinance',
                'parameter_type' => 'string',
                'description' => 'Nom légal de l\'institution',
                'category' => 'general',
                'is_editable' => true,
            ],
            [
                'parameter_key' => 'company_country',
                'parameter_value' => 'Togo',
                'parameter_type' => 'string',
                'description' => 'Pays du siège social',
                'category' => 'general',
                'is_editable' => true,
            ],
        ];

        foreach ($parameters as $param) {
            DB::table('system_parameters')->updateOrInsert(
                ['parameter_key' => $param['parameter_key']],
                array_merge($param, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('✅ Paramètres système initialisés avec succès.');

        // ---------------------------------------------------------------------
        // 4. SEEDERS ASSOCIÉS (Frais, Taux, Utilisateur Caissier)
        // ---------------------------------------------------------------------
        $this->call([
            BankingFeesSeeder::class,
            LoanInterestRatesSeeder::class,
            CashierUserSeeder::class,
        ]);

        // ---------------------------------------------------------------------
        // 5. CRÉATION D'ADHÉRENTS (CLIENTS) ET COMPTES TESTS SI ABSENTS
        // ---------------------------------------------------------------------
        if (DB::table('clients')->count() === 0) {
            $this->command->info('👤 Initialisation des clients de test avec comptes actifs...');

            $adminUser = DB::table('users')->where('email', 'Mieadmin360@gmail.com')->first();
            $adminId = $adminUser ? $adminUser->id : 1;

            $sampleClients = [
                [
                    'client_number' => 'CLT-AGO-001',
                    'first_name' => 'Koffi',
                    'last_name' => 'MENSAH',
                    'phone' => '+228 90 11 22 33',
                    'email' => 'koffi.mensah@gmail.com',
                    'profession' => 'Commerçant',
                    'gender' => 'M',
                    'city' => 'Lomé',
                    'savings_balance' => 25000,
                    'tontine_amount' => 1000,
                ],
                [
                    'client_number' => 'CLT-AGO-002',
                    'first_name' => 'Afi',
                    'last_name' => 'KOUDOSSOU',
                    'phone' => '+228 91 22 33 44',
                    'email' => 'afi.koudossou@gmail.com',
                    'profession' => 'Couturière',
                    'gender' => 'F',
                    'city' => 'Lomé',
                    'savings_balance' => 15000,
                    'tontine_amount' => 500,
                ],
                [
                    'client_number' => 'CLT-AGO-003',
                    'first_name' => 'Yao',
                    'last_name' => 'ADOTEVI',
                    'phone' => '+228 92 33 44 55',
                    'email' => 'yao.adotevi@gmail.com',
                    'profession' => 'Chauffeur',
                    'gender' => 'M',
                    'city' => 'Lomé',
                    'savings_balance' => 50000,
                    'tontine_amount' => 2000,
                ],
            ];

            foreach ($sampleClients as $c) {
                $clientId = DB::table('clients')->insertGetId([
                    'client_number' => $c['client_number'],
                    'first_name' => $c['first_name'],
                    'last_name' => $c['last_name'],
                    'phone' => $c['phone'],
                    'email' => $c['email'],
                    'profession' => $c['profession'],
                    'gender' => $c['gender'],
                    'city' => $c['city'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'kyc_status' => 'approved',
                    'kyc_approved_at' => now(),
                    'kyc_approved_by' => $adminId,
                    'registration_channel' => 'agent_assisted',
                    'registration_type' => 'agency',
                    'registration_status' => 'approved',
                    'agency_id' => 1,
                    'registered_by' => $adminId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // 1. Compte Épargne
                $savingsAccountId = DB::table('accounts')->insertGetId([
                    'client_id' => $clientId,
                    'account_number' => 'SAV-' . rand(100000, 999999),
                    'account_type' => 'savings',
                    'status' => 'active',
                    'balance' => $c['savings_balance'],
                    'activation_fee' => 7000.00,
                    'activation_fee_paid' => true,
                    'activated_at' => now(),
                    'activated_by' => $adminId,
                    'created_by' => $adminId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('savings_accounts')->insert([
                    'account_id' => $savingsAccountId,
                    'interest_rate' => 2.0,
                    'minimum_balance' => 1000.00,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // 2. Compte Tontine
                $tontineAccountId = DB::table('accounts')->insertGetId([
                    'client_id' => $clientId,
                    'account_number' => 'TNT-' . rand(100000, 999999),
                    'account_type' => 'tontine',
                    'status' => 'active',
                    'balance' => 0.00,
                    'activation_fee' => 1000.00,
                    'activation_fee_paid' => true,
                    'activated_at' => now(),
                    'activated_by' => $adminId,
                    'created_by' => $adminId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $totalExpected = $c['tontine_amount'] * 31;
                $tontineSubId = DB::table('tontine_accounts')->insertGetId([
                    'account_id' => $tontineAccountId,
                    'tontine_amount' => $c['tontine_amount'],
                    'cycle_duration_months' => 1,
                    'payment_frequency' => 'daily',
                    'expected_monthly_payment' => $totalExpected,
                    'total_expected' => $totalExpected,
                    'total_paid' => 0.00,
                    'payments_made' => 0,
                    'current_cycle' => 1,
                    'cycle_start_date' => now()->toDateString(),
                    'cycle_end_date' => now()->addDays(31)->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('tontine_cycles')->insert([
                    'tontine_account_id' => $tontineSubId,
                    'cycle_number' => 1,
                    'start_date' => now()->toDateString(),
                    'end_date' => now()->addDays(31)->toDateString(),
                    'target_amount' => $totalExpected,
                    'collected_amount' => 0.00,
                    'payout_amount' => 0.00,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->command->info('✅ 3 Clients de test avec comptes Épargne et Tontine configurés avec succès.');
        }

        $this->command->info('🎉 [YAYRA SEEDER TERMINÉ] Plateforme prête !');
    }
}
