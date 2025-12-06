<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;

class AdminLayoutComposer
{
    public function compose(View $view)
    {
        $view->with([
            'overview' => [
                'clients' => ['total' => 0],
                'accounts' => ['total' => 0],
            ],
            'operational' => [
                'pending_tasks' => [
                    'loan_applications' => 0,
                    'kyc_pending' => 0,
                ],
                'system_alerts' => [],
            ],
            'financial' => [
                'tontine_performance' => [
                    'active_cycles' => 0
                ],
            ],
            'period' => 7, // valeur par défaut pour le filtre
        ]);
    }
}
