<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SystemTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Démarrage du seed système complet...');
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Nettoyage complet
        $tables = [
            'security_alerts',
            'audit_logs',
            'notifications',
            'loan_payments',
            'transaction_receipts',
            'transactions',
            'loans',
            'tontine_cycles',
            'tontine_accounts',
            'savings_accounts',
            'accounts',
            'client_documents',
            'clients',
            'users',
            'agencies',
            'system_parameters'
        ];

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('🔧 1/12 Initialisation des paramètres système...');
        $this->createSystemParameters();

        $this->command->info('🏢 2/12 Création des agences institutionnelles...');
        $agencyIds = $this->createAgencies();

        $this->command->info('👥 3/12 Création du personnel (Staff)...');
        $userIds = $this->createUsers($agencyIds);

        $this->command->info('👤 4/12 Création des adhérents (Clients)...');
        $clientIds = $this->createClients($agencyIds, $userIds);

        $this->command->info('💰 5/12 Ouverture des comptes et tontines...');
        $accountData = $this->createAccounts($clientIds, $userIds, $agencyIds);

        $this->command->info('🏦 6/12 Injection du portefeuille de prêts...');
        $this->createLoans($clientIds, $userIds, $agencyIds);

        $this->command->info('💸 7/12 Génération de l\'historique des transactions...');
        $this->createTransactions($accountData, $userIds, $agencyIds);

        $this->command->info('📄 8/12 Archivage des documents KYC...');
        $this->createDocuments($clientIds);

        $this->command->info('🔔 9/12 Dispatching des notifications...');
        $this->createNotifications($userIds);

        $this->command->info('📜 10/12 Initialisation des journaux d\'audit...');
        $this->createAuditLogs($userIds);

        $this->command->info('🛡️ 11/12 Simulation d\'alertes de sécurité...');
        $this->createSecurityAlerts($userIds);

        $this->command->info('✅ 12/12 Base de données prête pour les tests intégraux!');
    }

    private function createSystemParameters()
    {
        $parameters = [
            ['parameter_key' => 'savings_account_activation_fee', 'parameter_value' => '7000', 'parameter_type' => 'number', 'description' => 'Frais d\'activation compte épargne', 'category' => 'fees'],
            ['parameter_key' => 'tontine_300_activation_fee', 'parameter_value' => '300', 'parameter_type' => 'number', 'description' => 'Frais d\'activation tontine 300', 'category' => 'fees'],
            ['parameter_key' => 'loan_interest_rate_min', 'parameter_value' => '0.08', 'parameter_type' => 'number', 'description' => 'Taux d\'intérêt prêt minimum', 'category' => 'rates'],
            ['parameter_key' => 'loan_interest_rate_max', 'parameter_value' => '0.15', 'parameter_type' => 'number', 'description' => 'Taux d\'intérêt prêt maximum', 'category' => 'rates'],
            ['parameter_key' => 'penalty_rate', 'parameter_value' => '0.05', 'parameter_type' => 'number', 'description' => 'Taux de pénalité retards', 'category' => 'rates'],
            ['parameter_key' => 'max_cash_per_agent', 'parameter_value' => '1000000', 'parameter_type' => 'number', 'description' => 'Limite encaisse par agent', 'category' => 'limits'],
            
            // Frais Retrait ÉPARGNE
            ['parameter_key' => 'savings_withdrawal_fee_percentage', 'parameter_value' => '2.0', 'parameter_type' => 'number', 'description' => 'Pourcentage retrait Épargne', 'category' => 'fees'],
            ['parameter_key' => 'savings_withdrawal_fee_fixed', 'parameter_value' => '0', 'parameter_type' => 'number', 'description' => 'Frais fixe retrait Épargne', 'category' => 'fees'],
            
            // Frais Retrait TONTINE
            ['parameter_key' => 'tontine_withdrawal_fee_percentage', 'parameter_value' => '3.0', 'parameter_type' => 'number', 'description' => 'Pourcentage retrait Tontine', 'category' => 'fees'],
            ['parameter_key' => 'tontine_withdrawal_fee_fixed', 'parameter_value' => '0', 'parameter_type' => 'number', 'description' => 'Frais fixe retrait Tontine', 'category' => 'fees'],
        ];

        foreach ($parameters as $param) {
            DB::table('system_parameters')->insert(array_merge($param, [
                'created_at' => now(), 'updated_at' => now()
            ]));
        }
    }

    private function createAgencies()
    {
        $agencies = [
            ['name' => 'Agence Centrale (Siège)', 'code' => 'HQ001', 'city' => 'Lomé', 'region' => 'Maritime', 'phone' => '+228 22 00 00 01'],
            ['name' => 'Agence Atakpamé', 'code' => 'AT005', 'city' => 'Atakpamé', 'region' => 'Plateaux', 'phone' => '+228 22 00 00 05'],
        ];

        $ids = [];
        foreach ($agencies as $agency) {
            $ids[] = DB::table('agencies')->insertGetId(array_merge($agency, [
                'is_active' => true, 'cash_limit' => 10000000, 'created_at' => now(), 'updated_at' => now()
            ]));
        }
        return $ids;
    }

    private function createUsers($agencyIds)
    {
        $roles = [
            'administrateur_systeme' => ['Super', 'ADMIN', 'admin@mie.tg'],
            'administrateur_reglementaire' => ['Control', 'REGL', 'reg@mie.tg'],
            'gestionnaire_credit' => ['Jean', 'CREDIT', 'credit@mie.tg'],
            'agent_terrain' => ['Koffi', 'TERRAIN', 'agent1@mie.tg'],
            'agent_agence' => ['Awa', 'CAISSE', 'caisse1@mie.tg']
        ];

        $ids = [];
        foreach ($roles as $role => $data) {
            $ids[$role] = DB::table('users')->insertGetId([
                'username' => strtolower($data[1]),
                'email' => $data[2],
                'role' => $role,
                'first_name' => $data[0],
                'last_name' => $data[1],
                'agency_id' => $agencyIds[0],
                'password' => Hash::make('password'),
                'is_active' => true,
                'phone' => '+228 9' . rand(0, 9) . rand(100000, 999999),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        return $ids;
    }

    private function createClients($agencyIds, $userIds)
    {
        $clients = [
            ['first_name' => 'Koffi', 'last_name' => 'MENSAH', 'is_leader_or_elected' => true, 'profession' => 'Dirigeant Politique', 'kyc' => 'approved'],
            ['first_name' => 'Afiwa', 'last_name' => 'AZOTI', 'is_leader_or_elected' => false, 'profession' => 'Commerçante', 'kyc' => 'approved'],
            ['first_name' => 'Yao', 'last_name' => 'DOGBE', 'is_leader_or_elected' => true, 'profession' => 'Haut Fonctionnaire', 'kyc' => 'approved'],
            ['first_name' => 'Akouwa', 'last_name' => 'GADE', 'is_leader_or_elected' => false, 'profession' => 'Couturière', 'kyc' => 'pending'],
            ['first_name' => 'Ablam', 'last_name' => 'SOSSAH', 'is_leader_or_elected' => false, 'profession' => 'Agriculteur', 'kyc' => 'rejected'],
        ];

        $ids = [];
        foreach ($clients as $i => $c) {
            $ids[] = DB::table('clients')->insertGetId([
                'client_number' => 'CLT-' . strtoupper(Str::random(6)),
                'first_name' => $c['first_name'],
                'last_name' => $c['last_name'],
                'is_leader_or_elected' => $c['is_leader_or_elected'],
                'profession' => $c['profession'],
                'phone' => '+228 91' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'password' => Hash::make('password'),
                'email' => strtolower($c['first_name']) . '@test.com',
                'gender' => ($i % 2 == 0) ? 'M' : 'F',
                'city' => 'Lomé',
                'kyc_status' => $c['kyc'],
                'is_active' => ($c['kyc'] == 'approved'),
                'registered_by' => $userIds['agent_terrain'],
                'agency_id' => $agencyIds[0],
                'created_at' => now()->subMonths(6),
                'updated_at' => now()
            ]);
        }
        return $ids;
    }

    private function createAccounts($clientIds, $userIds, $agencyIds)
    {
        $data = [];
        foreach ($clientIds as $i => $clientId) {
            // Compte Epargne (Systématique pour approuvés)
            if ($i < 3) {
                $accId = DB::table('accounts')->insertGetId([
                    'client_id' => $clientId,
                    'account_type' => 'savings',
                    'account_number' => 'SAV-' . rand(100000, 999999),
                    'status' => 'active',
                    'balance' => 0, // Sera mis à jour par transactions
                    'created_at' => now()->subMonths(5),
                    'updated_at' => now()
                ]);

                DB::table('savings_accounts')->insert([
                    'account_id' => $accId,
                    'interest_rate' => 0.035,
                    'total_deposits' => 0,
                    'created_at' => now()->subMonths(5),
                    'updated_at' => now()
                ]);
                
                $data[] = ['account_id' => $accId, 'type' => 'savings', 'client_id' => $clientId];

                // Un compte Tontine pour certains
                if ($i % 2 == 0) {
                    $tontId = DB::table('accounts')->insertGetId([
                        'client_id' => $clientId,
                        'account_type' => 'tontine',
                        'account_number' => 'TNT-' . rand(100000, 999999),
                        'status' => 'active',
                        'balance' => 0,
                        'created_at' => now()->subMonths(4),
                        'updated_at' => now()
                    ]);

                    $cycleStart = now()->subMonths(4)->startOfMonth();
                    $cycleEnd = $cycleStart->copy()->addMonths(12)->endOfMonth();

                    DB::table('tontine_accounts')->insert([
                        'account_id' => $tontId,
                        'tontine_amount' => 500,
                        'cycle_duration_months' => 12,
                        'payment_frequency' => 'daily',
                        'expected_monthly_payment' => 500 * 30,
                        'total_expected' => 500 * 365,
                        'cycle_start_date' => $cycleStart,
                        'cycle_end_date' => $cycleEnd,
                        'current_cycle' => 1,
                        'created_at' => now()->subMonths(4),
                        'updated_at' => now()
                    ]);
                    
                    $data[] = ['account_id' => $tontId, 'type' => 'tontine', 'client_id' => $clientId];
                }
            }
        }
        return $data;
    }

    private function createLoans($clientIds, $userIds, $agencyIds)
    {
        $loanTypes = ['CREDIT AGRICOLE', 'CREDIT COMMERCE', 'FNFI AGRISEF'];
        
        // 1. Prêt sain
        $this->insertLoan($clientIds[0], $userIds['gestionnaire_credit'], $loanTypes[1], 1000000, 0, 'active');
        // 2. Prêt en retard léger
        $this->insertLoan($clientIds[1], $userIds['gestionnaire_credit'], $loanTypes[1], 500000, 15, 'active');
        // 3. Prêt en contentieux (leader)
        $this->insertLoan($clientIds[2], $userIds['gestionnaire_credit'], $loanTypes[0], 2500000, 120, 'active');
        // 4. Prêt en perte
        $this->insertLoan($clientIds[0], $userIds['gestionnaire_credit'], $loanTypes[2], 300000, 400, 'defaulted');
    }

    private function insertLoan($clientId, $userId, $type, $amount, $daysOverdue, $status)
    {
        $loanId = DB::table('loans')->insertGetId([
            'client_id' => $clientId,
            'loan_number' => 'LN-' . strtoupper(Str::random(5)),
            'loan_type' => $type,
            'requested_amount' => $amount,
            'approved_amount' => $amount,
            'interest_rate' => 0.12,
            'duration_months' => 12,
            'monthly_payment' => $amount / 12,
            'status' => $status,
            'outstanding_principal' => $amount * 0.7,
            'days_overdue' => $daysOverdue,
            'application_date' => now()->subMonths(10),
            'approved_at' => now()->subMonths(9),
            'disbursed_at' => now()->subMonths(9),
            'approved_by' => $userId,
            'created_at' => now()->subMonths(10),
            'updated_at' => now()
        ]);

        // Simuler quelques remboursements
        for ($i = 1; $i <= 3; $i++) {
            DB::table('loan_payments')->insert([
                'loan_id' => $loanId,
                'payment_number' => $i,
                'due_date' => now()->subMonths(9 - $i)->startOfMonth(),
                'paid_date' => now()->subMonths(9 - $i),
                'expected_amount' => $amount / 12,
                'principal_amount' => ($amount / 12) * 0.8,
                'interest_amount' => ($amount / 12) * 0.2,
                'paid_amount' => $amount / 12,
                'status' => 'paid',
                'payment_reference' => 'PAY-' . Str::random(5),
                'created_at' => now()->subMonths(9 - $i),
                'updated_at' => now()->subMonths(9 - $i)
            ]);
        }
    }

    private function createTransactions($accountData, $userIds, $agencyIds)
    {
        foreach ($accountData as $acc) {
            $balance = 0;
            // 10 transactions par compte
            for ($j = 0; $j < 10; $j++) {
                $type = (rand(0, 10) > 3) ? 'deposit' : 'withdrawal';
                $amount = rand(1000, 50000);
                
                if ($type == 'withdrawal' && $balance < $amount) {
                    $type = 'deposit';
                }

                $balanceBefore = $balance;
                $balance = ($type == 'deposit') ? $balance + $amount : $balance - $amount;

                $tId = DB::table('transactions')->insertGetId([
                    'transaction_reference' => 'TRX-' . strtoupper(Str::random(12)),
                    'account_id' => $acc['account_id'],
                    'agency_id' => $agencyIds[0],
                    'transaction_type' => $type,
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balance,
                    'status' => 'completed',
                    'processed_by' => $userIds['agent_agence'],
                    'payment_method' => 'cash',
                    'transaction_date' => now()->subDays(30 - $j),
                    'created_at' => now()->subDays(30 - $j),
                    'updated_at' => now()
                ]);

                // Receipt
                DB::table('transaction_receipts')->insert([
                    'transaction_id' => $tId,
                    'receipt_number' => 'REC-' . strtoupper(Str::random(12)),
                    'receipt_type' => 'digital',
                    'created_at' => now()->subDays(30 - $j)
                ]);
            }

            // Update account balance
            DB::table('accounts')->where('id', $acc['account_id'])->update(['balance' => $balance]);
        }
    }

    private function createDocuments($clientIds)
    {
        foreach ($clientIds as $clientId) {
            DB::table('client_documents')->insert([
                'client_id' => $clientId,
                'document_type' => 'id_front',
                'file_url' => 'kyc/id_front_' . $clientId . '.png',
                'file_name' => 'ID_FRONT.png',
                'status' => 'approved',
                'created_at' => now()->subMonths(5),
                'updated_at' => now()->subMonths(5)
            ]);
        }
    }

    private function createNotifications($userIds)
    {
        foreach ($userIds as $role => $id) {
            DB::table('notifications')->insert([
                'recipient_type' => 'App\Models\User',
                'recipient_id' => $id,
                'title' => 'Bienvenue sur MIE',
                'message' => 'Votre compte a été initialisé avec succès.',
                'type' => 'info',
                'status' => 'pending',
                'channel' => 'in_app',
                'created_at' => now()->subHours(5)
            ]);
        }
    }

    private function createAuditLogs($userIds)
    {
        DB::table('audit_logs')->insert([
            'user_id' => $userIds['administrateur_systeme'],
            'action' => 'SYSTEM_SEED',
            'table_name' => 'system_parameters',
            'record_id' => 1,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Seeder',
            'created_at' => now()
        ]);
    }

    private function createSecurityAlerts($userIds)
    {
        DB::table('security_alerts')->insert([
            'ip' => '192.168.1.100',
            'message' => 'Tentatives de connexion multiples échouées pour un utilisateur',
            'created_at' => now()->subMinutes(30),
            'updated_at' => now()->subMinutes(30)
        ]);
    }
}
