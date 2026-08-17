<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BankingFeesSeeder extends Seeder
{
    public function run()
    {
        $fees = [
            [
                'parameter_key' => 'savings_account_activation_fee',
                'parameter_value' => '7000',
                'parameter_type' => 'number',
                'description' => 'Frais d\'activation compte épargne',
                'category' => 'fees',
                'is_editable' => true,
            ],
            [
                'parameter_key' => 'tontine_carnet_fee',
                'parameter_value' => '1000',
                'parameter_type' => 'number',
                'description' => 'Frais de carnet de tontine',
                'category' => 'fees',
                'is_editable' => true,
            ],
            [
                'parameter_key' => 'savings_withdrawal_fee_percentage',
                'parameter_value' => '2.0',
                'parameter_type' => 'number',
                'description' => 'Pourcentage retrait Épargne',
                'category' => 'fees',
                'is_editable' => true,
            ],
            [
                'parameter_key' => 'savings_withdrawal_fee_fixed',
                'parameter_value' => '0',
                'parameter_type' => 'number',
                'description' => 'Frais fixe retrait Épargne',
                'category' => 'fees',
                'is_editable' => true,
            ],
            [
                'parameter_key' => 'tontine_withdrawal_fee_percentage',
                'parameter_value' => '3.0',
                'parameter_type' => 'number',
                'description' => 'Pourcentage retrait Tontine',
                'category' => 'fees',
                'is_editable' => true,
            ],
            [
                'parameter_key' => 'tontine_withdrawal_fee_fixed',
                'parameter_value' => '0',
                'parameter_type' => 'number',
                'description' => 'Frais fixe retrait Tontine',
                'category' => 'fees',
                'is_editable' => true,
            ],
        ];

        foreach ($fees as $fee) {
            DB::table('system_parameters')->updateOrInsert(
                ['parameter_key' => $fee['parameter_key']],
                array_merge($fee, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
