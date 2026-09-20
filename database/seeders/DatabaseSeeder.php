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
                'vault_balance' => 0.00,
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
                'vault_balance' => 0.00,
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
        $this->command->info('🎉 [YAYRA SEEDER TERMINÉ] Plateforme prête !');
    }
}
