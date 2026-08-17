<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LoanInterestRatesSeeder extends Seeder
{
    public function run()
    {
        $params = [
            [
                'parameter_key' => 'loan_interest_rate_low',
                'parameter_value' => '12.0',
                'parameter_type' => 'number',
                'description' => 'Taux d\'intérêt annuel (Risque faible)',
                'category' => 'rates',
                'is_editable' => true,
            ],
            [
                'parameter_key' => 'loan_interest_rate_medium',
                'parameter_value' => '17.0',
                'parameter_type' => 'number',
                'description' => 'Taux d\'intérêt annuel (Risque moyen)',
                'category' => 'rates',
                'is_editable' => true,
            ],
            [
                'parameter_key' => 'loan_interest_rate_high',
                'parameter_value' => '20.0',
                'parameter_type' => 'number',
                'description' => 'Taux d\'intérêt annuel (Risque élevé)',
                'category' => 'rates',
                'is_editable' => true,
            ],
            [
                'parameter_key' => 'loan_interest_rate_very_high',
                'parameter_value' => '25.0',
                'parameter_type' => 'number',
                'description' => 'Taux d\'intérêt annuel (Risque très élevé)',
                'category' => 'rates',
                'is_editable' => true,
            ],
            [
                'parameter_key' => 'loan_interest_rate_default',
                'parameter_value' => '17.0',
                'parameter_type' => 'number',
                'description' => 'Taux d\'intérêt annuel (Défaut)',
                'category' => 'rates',
                'is_editable' => true,
            ]
        ];

        foreach ($params as $param) {
            DB::table('system_parameters')->updateOrInsert(
                ['parameter_key' => $param['parameter_key']],
                array_merge($param, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
