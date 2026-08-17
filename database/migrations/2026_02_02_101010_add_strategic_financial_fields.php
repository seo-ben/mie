<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add new strategic parameters
        $parameters = [
            [
                'parameter_key' => 'kyc_enrollment_fee',
                'parameter_value' => '5000',
                'parameter_type' => 'number',
                'description' => 'Frais de constitution de dossier KYC',
                'category' => 'fees',
                'created_at' => now(),
            ],
            [
                'parameter_key' => 'monthly_management_fee',
                'parameter_value' => '500',
                'parameter_type' => 'number',
                'description' => 'Agios de gestion mensuelle (Tenue de compte)',
                'category' => 'fees',
                'created_at' => now(),
            ],
            [
                'parameter_key' => 'solidarity_fund_rate',
                'parameter_value' => '0.005',
                'parameter_type' => 'number',
                'description' => 'Taux de prélèvement pour le Fonds de Solidarité (Provision pour Aléas)',
                'category' => 'tontine',
                'created_at' => now(),
            ],
            [
                'parameter_key' => 'tontine_late_penalty_rate',
                'parameter_value' => '0.01',
                'parameter_type' => 'number',
                'description' => 'Pénalité de retard par jour pour les tontines (1% par défaut)',
                'category' => 'penalties',
                'created_at' => now(),
            ]
        ];

        foreach ($parameters as $param) {
            DB::table('system_parameters')->updateOrInsert(
                ['parameter_key' => $param['parameter_key']],
                $param
            );
        }

        // Add kyc_expiry_date to clients
        Schema::table('clients', function (Blueprint $table) {
            $table->date('kyc_expiry_date')->nullable()->after('kyc_approved_at');
            $table->boolean('enrollment_fee_paid')->default(false)->after('kyc_status');
        });

        // Add solidarity_fund fields
        Schema::table('tontine_accounts', function (Blueprint $table) {
            $table->decimal('solidarity_fund_total', 15, 2)->default(0.00)->after('total_paid');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['kyc_expiry_date', 'enrollment_fee_paid']);
        });

        Schema::table('tontine_accounts', function (Blueprint $table) {
            $table->dropColumn('solidarity_fund_total');
        });
    }
};
