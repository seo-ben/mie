<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Nettoyer les tables
        DB::table('loan_payments')->truncate();
        DB::table('loans')->truncate();
        DB::table('transactions')->truncate();
        DB::table('tontine_cycles')->truncate();
        DB::table('tontine_accounts')->truncate();
        DB::table('savings_accounts')->truncate();
        DB::table('accounts')->truncate();
        DB::table('client_documents')->truncate();
        DB::table('clients')->truncate();
        DB::table('users')->truncate();
        DB::table('agencies')->truncate();
        DB::table('system_parameters')->truncate();
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('🔧 Création des paramètres système...');
        $this->createSystemParameters();

        $this->command->info('🏢 Création des agences...');
        $agencyIds = $this->createAgencies();

        $this->command->info('👥 Création des utilisateurs...');
        $userIds = $this->createUsers($agencyIds);

        $this->command->info('👤 Création des clients...');
        $clientIds = $this->createClients($agencyIds, $userIds);

        $this->command->info('💰 Création des comptes...');
        $accountIds = $this->createAccounts($clientIds, $userIds);

        $this->command->info('💸 Création des transactions...');
        $this->createTransactions($accountIds, $userIds);

        $this->command->info('🏦 Création des prêts...');
        $this->createLoans($clientIds, $userIds);

        $this->command->info('✅ Données de test créées avec succès!');
        $this->displayCredentials();
    }

    private function createSystemParameters()
    {
        $parameters = [
            // Frais
            ['parameter_key' => 'savings_account_activation_fee', 'parameter_value' => '7000', 'parameter_type' => 'number', 'description' => 'Frais d\'activation compte épargne', 'category' => 'fees'],
            ['parameter_key' => 'tontine_300_activation_fee', 'parameter_value' => '300', 'parameter_type' => 'number', 'description' => 'Frais d\'activation tontine 300 FCFA', 'category' => 'fees'],
            ['parameter_key' => 'tontine_500_activation_fee', 'parameter_value' => '500', 'parameter_type' => 'number', 'description' => 'Frais d\'activation tontine 500 FCFA', 'category' => 'fees'],
            ['parameter_key' => 'tontine_700_activation_fee', 'parameter_value' => '700', 'parameter_type' => 'number', 'description' => 'Frais d\'activation tontine 700 FCFA', 'category' => 'fees'],
            
            // Taux
            ['parameter_key' => 'savings_interest_rate', 'parameter_value' => '0.02', 'parameter_type' => 'number', 'description' => 'Taux d\'intérêt épargne annuel', 'category' => 'rates'],
            ['parameter_key' => 'loan_interest_rate_min', 'parameter_value' => '0.08', 'parameter_type' => 'number', 'description' => 'Taux d\'intérêt prêt minimum', 'category' => 'rates'],
            ['parameter_key' => 'loan_interest_rate_max', 'parameter_value' => '0.15', 'parameter_type' => 'number', 'description' => 'Taux d\'intérêt prêt maximum', 'category' => 'rates'],
            ['parameter_key' => 'penalty_rate', 'parameter_value' => '0.05', 'parameter_type' => 'number', 'description' => 'Taux de pénalité retards', 'category' => 'rates'],
            
            // Limites
            ['parameter_key' => 'max_loan_amount', 'parameter_value' => '5000000', 'parameter_type' => 'number', 'description' => 'Montant maximum prêt', 'category' => 'limits'],
            ['parameter_key' => 'min_savings_for_loan', 'parameter_value' => '50000', 'parameter_type' => 'number', 'description' => 'Épargne minimum pour prêt', 'category' => 'limits'],
            
            // Intégrations
            ['parameter_key' => 'mobile_money_operators', 'parameter_value' => '["MTN", "Orange", "Moov"]', 'parameter_type' => 'json', 'description' => 'Opérateurs Mobile Money', 'category' => 'integrations'],
        ];

        foreach ($parameters as $param) {
            DB::table('system_parameters')->insert(array_merge($param, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }
    }

    private function createAgencies()
    {
        $agencies = [
            ['name' => 'Agence Centrale Lomé', 'code' => 'AG001', 'address' => '123 Avenue de la République', 'city' => 'Lomé', 'region' => 'Maritime', 'phone' => '+228 22 12 34 56'],
            ['name' => 'Agence Kara', 'code' => 'AG002', 'address' => 'Boulevard de la Paix', 'city' => 'Kara', 'region' => 'Kara', 'phone' => '+228 22 34 56 78'],
            ['name' => 'Agence Sokodé', 'code' => 'AG003', 'address' => 'Rue du Commerce', 'city' => 'Sokodé', 'region' => 'Centrale', 'phone' => '+228 22 45 67 89'],
        ];

        $agencyIds = [];
        foreach ($agencies as $agency) {
            $agencyIds[] = DB::table('agencies')->insertGetId(array_merge($agency, [
                'is_active' => true,
                'cash_limit' => 5000000,
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }

        return $agencyIds;
    }

    private function createUsers($agencyIds)
    {
        $users = [
            // Administrateurs
            [
                'username' => 'admin',
                'email' => 'admin@mie-microfinance.tg',
                'phone' => '+228 90 00 00 01',
                'password' => Hash::make('password'),
                'role' => 'administrateur_systeme',
                'first_name' => 'Admin',
                'last_name' => 'SYSTEME',
                'agency_id' => $agencyIds[0],
                'is_active' => true
            ],
            [
                'username' => 'admin.reg',
                'email' => 'admin.reg@mie-microfinance.tg',
                'phone' => '+228 90 00 00 02',
                'password' => Hash::make('password'),
                'role' => 'administrateur_reglementaire',
                'first_name' => 'Admin',
                'last_name' => 'REGLEMENTAIRE',
                'agency_id' => $agencyIds[0],
                'is_active' => true
            ],
            
            // Gestionnaires
            [
                'username' => 'manager.lome',
                'email' => 'manager.lome@mie-microfinance.tg',
                'phone' => '+228 90 11 11 01',
                'password' => Hash::make('password'),
                'role' => 'gestionnaire_superviseur',
                'first_name' => 'Koffi',
                'last_name' => 'MENSAH',
                'agency_id' => $agencyIds[0],
                'is_active' => true
            ],
            [
                'username' => 'credit.lome',
                'email' => 'credit.lome@mie-microfinance.tg',
                'phone' => '+228 90 11 11 02',
                'password' => Hash::make('password'),
                'role' => 'gestionnaire_credit',
                'first_name' => 'Ama',
                'last_name' => 'KOUASSI',
                'agency_id' => $agencyIds[0],
                'is_active' => true
            ],
            
            // Agents Terrain
            [
                'username' => 'agent.terrain1',
                'email' => 'agent.terrain1@mie-microfinance.tg',
                'phone' => '+228 90 22 22 01',
                'password' => Hash::make('password'),
                'role' => 'agent_terrain',
                'first_name' => 'Yao',
                'last_name' => 'ADZODO',
                'agency_id' => $agencyIds[0],
                'is_active' => true
            ],
            [
                'username' => 'agent.terrain2',
                'email' => 'agent.terrain2@mie-microfinance.tg',
                'phone' => '+228 90 22 22 02',
                'password' => Hash::make('password'),
                'role' => 'agent_terrain',
                'first_name' => 'Akossiwa',
                'last_name' => 'AGBEKO',
                'agency_id' => $agencyIds[0],
                'is_active' => true
            ],
            
            // Agent Agence
            [
                'username' => 'agent.agence',
                'email' => 'agent.agence@mie-microfinance.tg',
                'phone' => '+228 90 33 33 01',
                'password' => Hash::make('password'),
                'role' => 'agent_agence',
                'first_name' => 'Edem',
                'last_name' => 'DOGBE',
                'agency_id' => $agencyIds[0],
                'is_active' => true
            ],
        ];

        $userIds = [];
        foreach ($users as $user) {
            $userIds[] = DB::table('users')->insertGetId(array_merge($user, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }

        return $userIds;
    }

    private function createClients($agencyIds, $userIds)
    {
        $clients = [
            [
                'first_name' => 'Jean',
                'last_name' => 'DUPONT',
                'date_of_birth' => '1985-03-15',
                'gender' => 'M',
                'client_number' => 'C987654321',
                'phone' => '+228 90 12 34 56',
                'password' => Hash::make('clientpass01'),
                'email' => 'jean.dupont@example.com',
                'address' => '123 Rue de la Paix, Bé',
                'city' => 'Lomé',
                'region' => 'Maritime',
                'profession' => 'Commerçant',
                'monthly_income' => 150000,
                'id_type' => 'cni',
                'id_number' => 'CNI123456789',
                'kyc_status' => 'approved',
                'kyc_approved_at' => now()->subDays(5),
                'kyc_approved_by' => $userIds[2],
                'registered_by' => $userIds[4],
                'agency_id' => $agencyIds[0],
                'credit_score' => 75.5
            ],
            [
                'first_name' => 'Marie',
                'last_name' => 'AKOFA',
                'date_of_birth' => '1990-07-22',
                'gender' => 'F',
                'client_number' => 'C123456789',
                'phone' => '+228 90 23 45 67',
                'password' => Hash::make('clientpass02'),
                'email' => 'marie.akofa@example.com',
                'address' => '45 Avenue du Commerce',
                'city' => 'Lomé',
                'region' => 'Maritime',
                'profession' => 'Couturière',
                'monthly_income' => 80000,
                'id_type' => 'cni',
                'id_number' => 'CNI987654321',
                'kyc_status' => 'approved',
                'kyc_approved_at' => now()->subDays(3),
                'kyc_approved_by' => $userIds[2],
                'registered_by' => $userIds[5],
                'agency_id' => $agencyIds[0],
                'credit_score' => 68.0
            ],
            [
                'first_name' => 'Kokou',
                'last_name' => 'AGBETO',
                'date_of_birth' => '1982-11-10',
                'gender' => 'M',
                'client_number' => 'C456789123',
                'phone' => '+228 90 34 56 78',
                'password' => Hash::make('clientpass03'),
                'email' => 'kokou.agbeto@example.com',
                'address' => '78 Boulevard Circulaire',
                'city' => 'Lomé',
                'region' => 'Maritime',
                'profession' => 'Chauffeur',
                'monthly_income' => 120000,
                'id_type' => 'cni',
                'id_number' => 'CNI456789123',
                'kyc_status' => 'pending',
                'registered_by' => $userIds[4],
                'agency_id' => $agencyIds[0],
                'credit_score' => 0
            ],
            [
                'first_name' => 'Afi',
                'last_name' => 'KPEGLO',
                'date_of_birth' => '1995-04-18',
                'gender' => 'F',
                'client_number' => 'C789123456',
                'phone' => '+228 90 45 67 89',
                'password' => Hash::make('clientpass04'),
                'email' => 'afi.kpeglo@example.com',
                'address' => '12 Rue des Artisans',
                'city' => 'Lomé',
                'region' => 'Maritime',
                'profession' => 'Coiffeuse',
                'monthly_income' => 95000,
                'id_type' => 'cni',
                'id_number' => 'CNI789123456',
                'kyc_status' => 'approved',
                'kyc_approved_at' => now()->subDays(7),
                'kyc_approved_by' => $userIds[2],
                'registered_by' => $userIds[5],
                'agency_id' => $agencyIds[0],
                'credit_score' => 82.0
            ],
            [
                'first_name' => 'Kossi',
                'last_name' => 'GBENOU',
                'date_of_birth' => '1988-09-25',
                'gender' => 'M',
                'client_number' => 'C321654987',
                
                'phone' => '+228 90 56 78 90',
                'password' => Hash::make('clientpass05'),
                'email' => 'kossi.gbenou@example.com',
                'address' => '56 Quartier Nyekonakpoe',
                'city' => 'Lomé',
                'region' => 'Maritime',
                'profession' => 'Mécanicien',
                'monthly_income' => 110000,
                'id_type' => 'cni',
                'id_number' => 'CNI321654987',
                'kyc_status' => 'approved',
                'kyc_approved_at' => now()->subDays(10),
                'kyc_approved_by' => $userIds[2],
                'registered_by' => $userIds[4],
                'agency_id' => $agencyIds[0],
                'credit_score' => 71.5
            ],
        ];

        $clientIds = [];
        foreach ($clients as $client) {
            $clientIds[] = DB::table('clients')->insertGetId(array_merge($client, [
                'registration_channel' => 'agent_assisted',
                'is_active' => true,
                'created_at' => now()->subDays(rand(10, 30)),
                'updated_at' => now()
            ]));
        }

        return $clientIds;
    }

    private function createAccounts($clientIds, $userIds)
    {
        $accountIds = [];
        
        foreach ($clientIds as $index => $clientId) {
            // Compte épargne pour chaque client
            $savingsAccountId = DB::table('accounts')->insertGetId([
                'client_id' => $clientId,
                'account_type' => 'savings',
                'activation_fee' => 7000,
                'activation_fee_paid' => true,
                'account_number' => 'SA' . rand(10000000, 99999999),
                'activation_payment_method' => 'mobile_money',
                'activation_reference' => 'MM' . rand(100000000, 999999999),
                'status' => 'active',
                'activated_at' => now()->subDays(rand(5, 25)),
                'activated_by' => $userIds[2],
                'balance' => rand(50000, 200000),
                'created_by' => $userIds[4],
                'created_at' => now()->subDays(rand(10, 30)),
                'updated_at' => now()
            ]);

            $accountIds[] = $savingsAccountId;

            // Créer le savings_account
            DB::table('savings_accounts')->insert([
                'account_id' => $savingsAccountId,
                'interest_rate' => 0.02,
                'minimum_balance' => 1000,
                'total_deposits' => rand(100000, 300000),
                'total_withdrawals' => rand(20000, 50000),
                'created_at' => now()->subDays(rand(10, 30)),
                'updated_at' => now()
            ]);

            // Compte tontine pour certains clients
            if ($index < 3) {
                $tontineAmount = [300, 500, 700][rand(0, 2)];
                $tontineAccountId = DB::table('accounts')->insertGetId([
                    'client_id' => $clientId,
                    'account_type' => 'tontine',
                    'activation_fee' => $tontineAmount,
                    'account_number' => 'TA' . rand(10000000, 99999999),
                    'activation_fee_paid' => true,
                    'activation_payment_method' => 'cash',
                    'status' => 'active',
                    'activated_at' => now()->subDays(rand(5, 20)),
                    'activated_by' => $userIds[6],
                    'balance' => $tontineAmount * rand(3, 8),
                    'created_by' => $userIds[4],
                    'created_at' => now()->subDays(rand(10, 30)),
                    'updated_at' => now()
                ]);

                $accountIds[] = $tontineAccountId;

                // Créer le tontine_account
                $cycleStart = now()->subMonths(rand(1, 3))->startOfMonth();
                $cycleEnd = $cycleStart->copy()->addMonths(12)->endOfMonth();
                
                DB::table('tontine_accounts')->insert([
                    'account_id' => $tontineAccountId,
                    'tontine_amount' => $tontineAmount,
                    'cycle_duration_months' => 12,
                    'cycle_start_date' => $cycleStart,
                    'cycle_end_date' => $cycleEnd,
                    'payment_frequency' => 'monthly',
                    'expected_monthly_payment' => $tontineAmount,
                    'total_expected' => $tontineAmount * 12,
                    'total_paid' => $tontineAmount * rand(3, 8),
                    'payments_made' => rand(3, 8),
                    'current_cycle' => 1,
                    'cycle_start_date' => $cycleStart,
                    'cycle_end_date' => $cycleEnd,
                    'created_at' => now()->subDays(rand(10, 30)),
                    'updated_at' => now()
                ]);
            }
        }

        return $accountIds;
    }

    private function createTransactions($accountIds, $userIds)
    {
        foreach ($accountIds as $accountId) {
            $account = DB::table('accounts')->find($accountId);
            
            // Créer 5-10 transactions par compte
            $numTransactions = rand(5, 10);
            $currentBalance = 0;

            for ($i = 0; $i < $numTransactions; $i++) {
                $transactionDate = now()->subDays(rand(1, 20));
                $isDeposit = rand(0, 100) > 30; // 70% dépôts, 30% retraits
                
                if ($isDeposit) {
                    $amount = rand(5000, 50000);
                    $transactionType = 'deposit';
                    $balanceAfter = $currentBalance + $amount;
                } else {
                    $amount = rand(2000, min(20000, $currentBalance));
                    $transactionType = 'withdrawal';
                    $balanceAfter = $currentBalance - $amount;
                }

                $paymentMethods = ['cash', 'mobile_money', 'bank_transfer'];
                $paymentMethod = $paymentMethods[rand(0, 2)];

                DB::table('transactions')->insert([
                    'account_id' => $accountId,
                    'transaction_type' => $transactionType,
                    'amount' => $amount,
                    'balance_before' => $currentBalance,
                    'transaction_reference' => strtoupper(substr($transactionType, 0, 3)) . rand(10000000, 99999999),
                    'balance_after' => $balanceAfter,
                    'payment_method' => $paymentMethod,
                    'payment_reference' => $paymentMethod === 'mobile_money' ? 'MM' . rand(100000000, 999999999) : null,
                    'mobile_money_operator' => $paymentMethod === 'mobile_money' ? ['MTN', 'Orange', 'Moov'][rand(0, 2)] : null,
                    'description' => $isDeposit ? 'Dépôt régulier' : 'Retrait',
                    'status' => 'completed',
                    'processed_by' => $userIds[rand(4, 6)],
                    'transaction_date' => $transactionDate,
                    'created_at' => $transactionDate,
                    'updated_at' => $transactionDate
                ]);

                $currentBalance = $balanceAfter;
            }

            // Mettre à jour le solde final du compte
            DB::table('accounts')->where('id', $accountId)->update([
                'balance' => $currentBalance,
                'last_transaction_at' => now()
            ]);
        }
    }

    private function createLoans($clientIds, $userIds)
    {
        // Prêt approuvé et actif
        DB::table('loans')->insert([
            'client_id' => $clientIds[0],
            'requested_amount' => 150000,
            'approved_amount' => 150000,
            'interest_rate' => 0.10,
            'loan_number' => 'LN' . rand(10000000, 99999999),
            'duration_months' => 12,
            'monthly_payment' => 13200,
            'total_amount_due' => 158400,
            'purpose' => 'Achat de marchandises pour commerce',
            'status' => 'active',
            'eligibility_score' => 75.5,
            'risk_level' => 'low',
            'application_date' => now()->subDays(45),
            'approved_by' => $userIds[3],
            'approved_at' => now()->subDays(40),
            'disbursed_by' => $userIds[3],
            'disbursed_at' => now()->subDays(38),
            'disbursement_method' => 'mobile_money',
            'disbursement_reference' => 'MM987654321',
            'first_payment_date' => now()->subDays(8)->startOfMonth(),
            'maturity_date' => now()->addMonths(11),
            'outstanding_principal' => 140000,
            'outstanding_interest' => 8400,
            'total_paid' => 13200,
            'created_at' => now()->subDays(45),
            'updated_at' => now()
        ]);

        // Prêt en attente d'approbation
        DB::table('loans')->insert([
            'client_id' => $clientIds[1],
            'requested_amount' => 100000,
            'interest_rate' => 0.10,
            'duration_months' => 6,
            'loan_number' => 'LN' . rand(10000000, 99999999),
            'purpose' => 'Extension de l\'atelier de couture',
            'status' => 'pending',
            'eligibility_score' => 68.0,
            'risk_level' => 'medium',
            'application_date' => now()->subDays(3),
            'created_at' => now()->subDays(3),
            'updated_at' => now()
        ]);

        // Prêt rejeté
        DB::table('loans')->insert([
            'client_id' => $clientIds[2],
            'requested_amount' => 200000,
            'interest_rate' => 0.10,
            'duration_months' => 12,
            'purpose' => 'Achat de véhicule',
            'status' => 'rejected',
            'loan_number' => 'LN' . rand(10000000, 99999999),
            'eligibility_score' => 45.0,
            'risk_level' => 'high',
            'application_date' => now()->subDays(15),
            'reviewed_by' => $userIds[3],
            'reviewed_at' => now()->subDays(14),
            'rejection_reason' => 'Épargne insuffisante et historique trop court',
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(14)
        ]);

        // Prêt complété
        DB::table('loans')->insert([
            'client_id' => $clientIds[3],
            'requested_amount' => 80000,
            'approved_amount' => 80000,
            'interest_rate' => 0.10,
            'duration_months' => 6,
            'monthly_payment' => 14000,
            'total_amount_due' => 84000,
            'loan_number' => 'LN' . rand(10000000, 99999999),
            'purpose' => 'Équipement salon de coiffure',
            'status' => 'completed',
            'eligibility_score' => 82.0,
            'risk_level' => 'low',
            'application_date' => now()->subMonths(8),
            'approved_by' => $userIds[3],
            'approved_at' => now()->subMonths(8)->addDays(2),
            'disbursed_by' => $userIds[3],
            'disbursed_at' => now()->subMonths(8)->addDays(3),
            'disbursement_method' => 'cash',
            'first_payment_date' => now()->subMonths(7)->startOfMonth(),
            'maturity_date' => now()->subMonth(),
            'outstanding_principal' => 0,
            'outstanding_interest' => 0,
            'total_paid' => 84000,
            'created_at' => now()->subMonths(8),
            'updated_at' => now()
        ]);
    }

    private function displayCredentials()
    {
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('🔑 IDENTIFIANTS DE CONNEXION');
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('');
        $this->command->info('👑 ADMINISTRATEURS:');
        $this->command->info('   • Admin Système: admin@mie-microfinance.tg | password');
        $this->command->info('   • Admin Réglementaire: admin.reg@mie-microfinance.tg | password');
        $this->command->info('');
        $this->command->info('👨‍💼 GESTIONNAIRES:');
        $this->command->info('   • Superviseur: manager.lome@mie-microfinance.tg | password');
        $this->command->info('   • Crédit: credit.lome@mie-microfinance.tg | password');
        $this->command->info('');
        $this->command->info('🧑‍💼 AGENTS:');
        $this->command->info('   • Agent Terrain 1: agent.terrain1@mie-microfinance.tg | password');
        $this->command->info('   • Agent Terrain 2: agent.terrain2@mie-microfinance.tg | password');
        $this->command->info('   • Agent Agence: agent.agence@mie-microfinance.tg | password');
        $this->command->info('');
        $this->command->info('👤 CLIENTS:');
        $this->command->info('   • jean.dupont@example.com | password');
        $this->command->info('   • marie.akofa@example.com | password');
        $this->command->info('   • afi.kpeglo@example.com | password');
        $this->command->info('');
        $this->command->info('📊 DONNÉES CRÉÉES:');
        $this->command->info('   • 3 Agences');
        $this->command->info('   • 7 Utilisateurs (Staff)');
        $this->command->info('   • 5 Clients');
        $this->command->info('   • ~10 Comptes (épargne + tontines)');
        $this->command->info('   • ~50 Transactions');
        $this->command->info('   • 4 Prêts (actif, en attente, rejeté, complété)');
        $this->command->info('');
        $this->command->info('🚀 Vous pouvez maintenant tester l\'API!');
        $this->command->info('   URL: http://localhost:8000/api/v1');
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════');
    }
}