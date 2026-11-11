<?php

namespace App\Http\Controllers\Web\Manager;

use App\Http\Controllers\Controller;
use App\Services\ManagerDashboardService;
use App\Models\Client;
use App\Models\Loan;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ManagerDashboardController extends Controller
{
    public function __construct(
        private ManagerDashboardService $dashboardService
    ) {}

    /**
     * Dashboard principal du gestionnaire
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $agencyId = $user->agency_id;
        $period = $request->get('period', '30d');

        // Métriques principales
        $mainMetrics = [
            'total_clients' => Client::where('agency_id', $agencyId)->count(),
            'new_clients_this_month' => Client::where('agency_id', $agencyId)
                ->whereMonth('created_at', now()->month)
                ->count(),
            'kyc_pending' => Client::where('agency_id', $agencyId)
                ->where('kyc_status', 'pending')
                ->count(),
            'total_deposits' => \App\Models\Account::whereHas('client', function($query) use ($agencyId) {
                $query->where('agency_id', $agencyId);
            })->sum('balance')
        ];

        // Métriques prêts
        $loanMetrics = [
            'pending_approvals' => Loan::whereHas('client', function($query) use ($agencyId) {
                $query->where('agency_id', $agencyId);
            })->whereIn('status', ['pending', 'under_review'])->count(),

            'active_loans' => Loan::whereHas('client', function($query) use ($agencyId) {
                $query->where('agency_id', $agencyId);
            })->whereIn('status', ['active', 'disbursed'])->count(),

            'total_outstanding' => Loan::whereHas('client', function($query) use ($agencyId) {
                $query->where('agency_id', $agencyId);
            })->whereIn('status', ['active', 'disbursed'])->sum('outstanding_principal'),

            'overdue_loans' => Loan::whereHas('client', function($query) use ($agencyId) {
                $query->where('agency_id', $agencyId);
            })->where('days_overdue', '>', 0)->count()
        ];

        // Métriques transactions
        $transactionMetrics = [
            'pending_validations' => Transaction::whereHas('account.client', function($query) use ($agencyId) {
                $query->where('agency_id', $agencyId);
            })->where('validation_required', true)
              ->where('status', 'pending')->count(),

            'today_volume' => Transaction::whereHas('account.client', function($query) use ($agencyId) {
                $query->where('agency_id', $agencyId);
            })->whereDate('transaction_date', today())
              ->where('status', 'completed')
              ->sum('amount'),

            'month_volume' => Transaction::whereHas('account.client', function($query) use ($agencyId) {
                $query->where('agency_id', $agencyId);
            })->whereMonth('transaction_date', now()->month)
              ->where('status', 'completed')
              ->sum('amount')
        ];

        // Performance de l'équipe
        $teamPerformance = User::where('agency_id', $agencyId)
            ->where('role', 'LIKE', 'agent%')
            ->withCount([
                'clients as clients_registered' => function($query) {
                    $query->whereMonth('created_at', now()->month);
                }
            ])
            ->get()
            ->map(function($agent) {
                $collections = Transaction::whereHas('account.client', function($query) use ($agent) {
                    $query->where('registered_by', $agent->id);
                })->whereMonth('transaction_date', now()->month)
                  ->where('status', 'completed')
                  ->sum('amount');

                return [
                    'id' => $agent->id,
                    'name' => $agent->full_name,
                    'role' => $agent->role,
                    'clients_registered' => $agent->clients_registered,
                    'collections' => $collections,
                    'last_activity' => $agent->last_login
                ];
            });

        // Évolution sur la période
        $evolution = $this->getEvolutionData($agencyId, $period);

        return response()->json([
            'success' => true,
            'data' => [
                'main_metrics' => $mainMetrics,
                'loan_metrics' => $loanMetrics,
                'transaction_metrics' => $transactionMetrics,
                'team_performance' => $teamPerformance,
                'evolution' => $evolution,
                'alerts' => $this->getAlerts($agencyId)
            ],
            'user_scope' => [
                'agency_id' => $agencyId,
                'agency_name' => $user->agency->name ?? 'N/A',
                'role' => $user->role
            ]
        ]);
    }

    /**
     * KPIs en temps réel
     */
    public function kpis(Request $request)
    {
        $user = auth()->user();
        $agencyId = $user->agency_id;

        $kpis = [
            'collection_rate' => $this->calculateCollectionRate($agencyId),
            'loan_approval_rate' => $this->calculateApprovalRate($agencyId),
            'client_growth_rate' => $this->calculateClientGrowthRate($agencyId),
            'portfolio_at_risk' => $this->calculatePortfolioAtRisk($agencyId),
            'average_loan_size' => $this->calculateAverageLoanSize($agencyId),
            'agent_productivity' => $this->calculateAgentProductivity($agencyId)
        ];

        return response()->json([
            'success' => true,
            'data' => $kpis,
            'last_updated' => now()->toISOString()
        ]);
    }

    /**
     * Tendances et évolutions
     */
    public function trends(Request $request)
    {
        $user = auth()->user();
        $agencyId = $user->agency_id;
        $period = $request->get('period', '6m');

        $months = $period === '3m' ? 3 : ($period === '1y' ? 12 : 6);
        $trends = [];

        for ($i = $months; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthKey = $date->format('Y-m');

            $trends[] = [
                'period' => $monthKey,
                'period_label' => $date->format('M Y'),
                'new_clients' => Client::where('agency_id', $agencyId)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
                'loans_disbursed' => Loan::whereHas('client', function($query) use ($agencyId) {
                        $query->where('agency_id', $agencyId);
                    })
                    ->where('status', 'disbursed')
                    ->whereYear('disbursed_at', $date->year)
                    ->whereMonth('disbursed_at', $date->month)
                    ->sum('approved_amount'),
                'transaction_volume' => Transaction::whereHas('account.client', function($query) use ($agencyId) {
                        $query->where('agency_id', $agencyId);
                    })
                    ->where('status', 'completed')
                    ->whereYear('transaction_date', $date->year)
                    ->whereMonth('transaction_date', $date->month)
                    ->sum('amount')
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $trends
        ]);
    }

    /**
     * Alertes et notifications pour le gestionnaire
     */
    public function alerts()
    {
        $user = auth()->user();
        $agencyId = $user->agency_id;

        $alerts = [
            'kyc_pending' => [
                'count' => Client::where('agency_id', $agencyId)
                    ->where('kyc_status', 'pending')
                    ->where('created_at', '<', now()->subDays(2))
                    ->count(),
                'message' => 'Dossiers KYC en attente depuis plus de 2 jours',
                'priority' => 'medium',
                'action_url' => '/manager/kyc/pending'
            ],
            'loans_pending' => [
                'count' => Loan::whereHas('client', function($query) use ($agencyId) {
                        $query->where('agency_id', $agencyId);
                    })
                    ->whereIn('status', ['pending', 'under_review'])
                    ->where('application_date', '<', now()->subDays(3))
                    ->count(),
                'message' => 'Demandes de prêt en attente depuis plus de 3 jours',
                'priority' => 'high',
                'action_url' => '/manager/loans/pending'
            ],
            'overdue_loans' => [
                'count' => Loan::whereHas('client', function($query) use ($agencyId) {
                        $query->where('agency_id', $agencyId);
                    })
                    ->where('days_overdue', '>', 30)
                    ->count(),
                'message' => 'Prêts en retard de plus de 30 jours',
                'priority' => 'high',
                'action_url' => '/manager/loans/overdue'
            ],
            'cash_threshold' => [
                'count' => 1,
                'message' => 'Niveau de liquidité de l\'agence à surveiller',
                'priority' => 'low',
                'action_url' => '/manager/cash-management'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => array_filter($alerts, fn($alert) => $alert['count'] > 0)
        ]);
    }

    /**
     * Résumé de l'équipe
     */
    public function teamSummary()
    {
        $user = auth()->user();
        $agencyId = $user->agency_id;

        $agents = User::where('agency_id', $agencyId)
            ->where('role', 'LIKE', 'agent%')
            ->where('is_active', true)
            ->get();

        $summary = $agents->map(function($agent) {
            $clientsCount = Client::where('registered_by', $agent->id)->count();
            $activeClientsCount = Client::where('registered_by', $agent->id)
                ->where('is_active', true)->count();

            $monthlyCollections = Transaction::whereHas('account.client', function($query) use ($agent) {
                $query->where('registered_by', $agent->id);
            })->whereMonth('transaction_date', now()->month)
              ->where('status', 'completed')
              ->sum('amount');

            return [
                'id' => $agent->id,
                'name' => $agent->full_name,
                'role' => $agent->role,
                'status' => $agent->is_active ? 'active' : 'inactive',
                'last_activity' => $agent->last_login,
                'stats' => [
                    'total_clients' => $clientsCount,
                    'active_clients' => $activeClientsCount,
                    'monthly_collections' => $monthlyCollections
                ],
                'performance_rating' => $this->calculateAgentRating($agent->id)
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'total_agents' => $agents->count(),
                'active_agents' => $agents->where('is_active', true)->count(),
                'agents_detail' => $summary,
                'team_metrics' => [
                    'avg_clients_per_agent' => $summary->avg('stats.total_clients'),
                    'total_portfolio' => $summary->sum('stats.total_clients'),
                    'total_monthly_collections' => $summary->sum('stats.monthly_collections')
                ]
            ]
        ]);
    }

    private function getEvolutionData($agencyId, $period)
    {
        $days = match($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 30
        };

        $evolution = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');

            $evolution[] = [
                'date' => $date,
                'new_clients' => Client::where('agency_id', $agencyId)
                    ->whereDate('created_at', $date)
                    ->count(),
                'transactions_volume' => Transaction::whereHas('account.client', function($query) use ($agencyId) {
                        $query->where('agency_id', $agencyId);
                    })
                    ->whereDate('transaction_date', $date)
                    ->where('status', 'completed')
                    ->sum('amount')
            ];
        }

        return $evolution;
    }

    private function getAlerts($agencyId)
    {
        return [
            'urgent' => Loan::whereHas('client', function($query) use ($agencyId) {
                $query->where('agency_id', $agencyId);
            })->where('days_overdue', '>', 60)->count(),

            'medium' => Client::where('agency_id', $agencyId)
                ->where('kyc_status', 'pending')
                ->where('created_at', '<', now()->subDays(7))
                ->count(),

            'low' => Transaction::whereHas('account.client', function($query) use ($agencyId) {
                $query->where('agency_id', $agencyId);
            })->where('validation_required', true)
              ->where('status', 'pending')
              ->count()
        ];
    }

    private function calculateCollectionRate($agencyId)
    {
        $expected = \App\Models\LoanPayment::whereHas('loan.client', function($query) use ($agencyId) {
            $query->where('agency_id', $agencyId);
        })->where('due_date', '<=', now())
          ->sum('expected_amount');

        $collected = \App\Models\LoanPayment::whereHas('loan.client', function($query) use ($agencyId) {
            $query->where('agency_id', $agencyId);
        })->where('paid_date', '<=', now())
          ->sum('paid_amount');

        return $expected > 0 ? round(($collected / $expected) * 100, 2) : 0;
    }

    private function calculateApprovalRate($agencyId)
    {
        $thisMonth = now()->month;
        $total = Loan::whereHas('client', function($query) use ($agencyId) {
            $query->where('agency_id', $agencyId);
        })->whereMonth('application_date', $thisMonth)->count();

        $approved = Loan::whereHas('client', function($query) use ($agencyId) {
            $query->where('agency_id', $agencyId);
        })->whereMonth('application_date', $thisMonth)
          ->whereIn('status', ['approved', 'disbursed', 'active'])
          ->count();

        return $total > 0 ? round(($approved / $total) * 100, 2) : 0;
    }

    private function calculateClientGrowthRate($agencyId)
    {
        $thisMonth = Client::where('agency_id', $agencyId)
            ->whereMonth('created_at', now()->month)
            ->count();

        $lastMonth = Client::where('agency_id', $agencyId)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->count();

        return $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2) : 0;
    }

    private function calculatePortfolioAtRisk($agencyId)
    {
        $totalOutstanding = Loan::whereHas('client', function($query) use ($agencyId) {
            $query->where('agency_id', $agencyId);
        })->whereIn('status', ['active', 'disbursed'])
          ->sum('outstanding_principal');

        $atRiskOutstanding = Loan::whereHas('client', function($query) use ($agencyId) {
            $query->where('agency_id', $agencyId);
        })->where('days_overdue', '>', 0)
          ->sum('outstanding_principal');

        return $totalOutstanding > 0 ? round(($atRiskOutstanding / $totalOutstanding) * 100, 2) : 0;
    }

    private function calculateAverageLoanSize($agencyId)
    {
        return Loan::whereHas('client', function($query) use ($agencyId) {
            $query->where('agency_id', $agencyId);
        })->whereIn('status', ['disbursed', 'active'])
          ->avg('approved_amount') ?? 0;
    }

    private function calculateAgentProductivity($agencyId)
    {
        $agents = User::where('agency_id', $agencyId)
            ->where('role', 'LIKE', 'agent%')
            ->count();

        $totalClients = Client::where('agency_id', $agencyId)->count();

        return $agents > 0 ? round($totalClients / $agents, 2) : 0;
    }

    private function calculateAgentRating($agentId)
    {
        $clientsCount = Client::where('registered_by', $agentId)->count();
        $collectionsThisMonth = Transaction::whereHas('account.client', function($query) use ($agentId) {
            $query->where('registered_by', $agentId);
        })->whereMonth('transaction_date', now()->month)
          ->where('status', 'completed')
          ->sum('amount');

        // Calcul simple du rating basé sur la performance
        $rating = 0;
        if ($clientsCount >= 50) $rating += 2;
        elseif ($clientsCount >= 20) $rating += 1;

        if ($collectionsThisMonth >= 1000000) $rating += 3;
        elseif ($collectionsThisMonth >= 500000) $rating += 2;
        elseif ($collectionsThisMonth >= 100000) $rating += 1;

        return min($rating, 5); // Maximum 5 étoiles
    }
}
