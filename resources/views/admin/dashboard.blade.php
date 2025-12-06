@extends('layouts.app_admin')

@section('title', 'Tableau de Bord Admin')
@section('content')
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Clients -->
                <div class="metric-card bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                        <span class="{{ $overview['clients']['growth'] >= 0 ? 'stat-up' : 'stat-down' }} text-sm font-semibold">
                            <i class="fas fa-arrow-{{ $overview['clients']['growth'] >= 0 ? 'up' : 'down' }}"></i> {{ number_format(abs($overview['clients']['growth']), 1) }}%
                        </span>
                    </div>
                    <h3 class="text-gray-500 text-sm font-medium mb-1">Total Clients</h3>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($overview['clients']['total']) }}</p>
                    <p class="text-sm text-gray-500 mt-2">
                        <span class="text-green-600 font-semibold">+{{ number_format($overview['clients']['new_period']) }}</span> sur {{ $period }}j
                        <span class="text-xs text-gray-400 ml-2">• {{ number_format($overview['clients']['kyc_approval_rate'], 1) }}% KYC</span>
                    </p>
                </div>

                <!-- Comptes Actifs -->
                <div class="metric-card bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-wallet text-green-600 text-xl"></i>
                        </div>
                        <span class="stat-up text-sm font-semibold">
                            <i class="fas fa-arrow-up"></i> {{ number_format($overview['accounts']['activation_rate'], 1) }}%
                        </span>
                    </div>
                    <h3 class="text-gray-500 text-sm font-medium mb-1">Comptes Actifs</h3>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($overview['accounts']['active']) }}</p>
                    <p class="text-sm text-gray-500 mt-2">
                        Sur {{ number_format($overview['accounts']['total']) }} comptes
                        <span class="text-xs text-gray-400 ml-2">• {{ number_format($overview['loans']['active']) }} prêts</span>
                    </p>
                </div>

                <!-- Dépôts Totaux -->
                <div class="metric-card bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-piggy-bank text-purple-600 text-xl"></i>
                        </div>
                        <span class="stat-up text-sm font-semibold">
                            <i class="fas fa-chart-line"></i> Liquidité
                        </span>
                    </div>
                    <h3 class="text-gray-500 text-sm font-medium mb-1">Dépôts Totaux</h3>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($overview['financial']['total_deposits'] / 1000000, 1) }}M</p>
                    <p class="text-sm text-gray-500 mt-2">
                        FCFA
                        <span class="text-xs text-gray-400 ml-2">• Épargne: {{ number_format($financial['savings_performance']['total_balance'] / 1000000, 1) }}M</span>
                    </p>
                </div>

                <!-- Portefeuille Prêts -->
                <div class="metric-card bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-hand-holding-usd text-orange-600 text-xl"></i>
                        </div>
                        <span class="text-sm font-semibold" style="color: {{ $overview['financial']['loan_to_deposit_ratio'] > 80 ? '#EF4444' : '#10B981' }}">
                            <i class="fas fa-percentage"></i> {{ number_format($overview['financial']['loan_to_deposit_ratio'], 1) }}%
                        </span>
                    </div>
                    <h3 class="text-gray-500 text-sm font-medium mb-1">Portefeuille Prêts</h3>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($overview['loans']['portfolio_value'] / 1000000, 1) }}M</p>
                    <p class="text-sm text-gray-500 mt-2">
                        FCFA
                        <span class="text-xs text-gray-400 ml-2">• +{{ number_format($overview['loans']['new_period']) }} nouveaux</span>
                    </p>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Croissance -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Croissance</h3>
                            <p class="text-xs text-gray-500">Évolution sur {{ $period }} jours</p>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="growthChart"></canvas>
                    </div>
                </div>

                <!-- Répartition Géographique -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Répartition Géographique</h3>
                            <p class="text-xs text-gray-500">Clients par région</p>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="geographicChart"></canvas>
                    </div>
                </div>
            </div>
            <!-- Tontines & Épargne Performance -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Performance Tontines -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Performance Tontines</h3>
                            <p class="text-xs text-gray-500">{{ $period }} derniers jours</p>
                        </div>
                        <span class="text-xs bg-purple-100 text-purple-600 px-3 py-1 rounded-full font-semibold">
                            {{ $financial['tontine_performance']['collection_rate'] }}% collecte
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="text-center p-4 bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Cycles Actifs</p>
                            <p class="text-3xl font-bold text-purple-600">{{ $financial['tontine_performance']['active_cycles'] }}</p>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Complétés</p>
                            <p class="text-3xl font-bold text-green-600">{{ $financial['tontine_performance']['completed_cycles'] }}</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-600">Total Collecté</span>
                            <span class="font-bold text-gray-800">{{ number_format($financial['tontine_performance']['total_collected'] / 1000000, 2) }}M FCFA</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-600">Payouts Distribués</span>
                            <span class="font-bold text-gray-800">{{ number_format($financial['tontine_performance']['total_payouts'] / 1000000, 2) }}M FCFA</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-600">Valeur Moyenne</span>
                            <span class="font-bold text-gray-800">{{ number_format($financial['tontine_performance']['average_cycle_value']) }} FCFA</span>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm text-gray-600">Progression Collecte</span>
                            <span class="text-sm font-bold text-purple-600">{{ $financial['tontine_performance']['collection_rate'] }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-gradient-to-r from-purple-500 to-purple-600 h-3 rounded-full transition-all duration-500" style="width: {{ min($financial['tontine_performance']['collection_rate'], 100) }}%"></div>
                        </div>
                    </div>
                </div>

                <!-- Performance Épargne -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Performance Épargne</h3>
                            <p class="text-xs text-gray-500">{{ $period }} derniers jours</p>
                        </div>
                        <span class="text-xs bg-green-100 text-green-600 px-3 py-1 rounded-full font-semibold">
                            {{ $financial['savings_performance']['total_accounts'] }} comptes
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="text-center p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Balance Totale</p>
                            <p class="text-2xl font-bold text-green-600">{{ number_format($financial['savings_performance']['total_balance'] / 1000000, 1) }}M</p>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Balance Moy.</p>
                            <p class="text-2xl font-bold text-blue-600">{{ number_format($financial['savings_performance']['average_balance']) }}</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-arrow-down text-green-600"></i>
                                <span class="text-sm text-gray-700">Nouveaux Dépôts</span>
                            </div>
                            <span class="font-bold text-green-600">+{{ number_format($financial['savings_performance']['new_deposits'] / 1000000, 2) }}M</span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-arrow-up text-orange-600"></i>
                                <span class="text-sm text-gray-700">Retraits</span>
                            </div>
                            <span class="font-bold text-orange-600">-{{ number_format($financial['savings_performance']['withdrawals'] / 1000000, 2) }}M</span>
                        </div>

                        <div class="flex items-center justify-between p-3 {{ $financial['savings_performance']['net_flow'] >= 0 ? 'bg-blue-50' : 'bg-red-50' }} rounded-lg">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-exchange-alt {{ $financial['savings_performance']['net_flow'] >= 0 ? 'text-blue-600' : 'text-red-600' }}"></i>
                                <span class="text-sm text-gray-700">Flux Net</span>
                            </div>
                            <span class="font-bold {{ $financial['savings_performance']['net_flow'] >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                                {{ $financial['savings_performance']['net_flow'] >= 0 ? '+' : '' }}{{ number_format($financial['savings_performance']['net_flow'] / 1000000, 2) }}M
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Performers & Geographic Details -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Top Agents Performance -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Top Agents ({{ $period }}j)</h3>
                    <div class="space-y-3">
                        @foreach($operational['agents_performance']->take(5) as $index => $agent)
                        <div class="flex items-center gap-4 p-3 hover:bg-gray-50 rounded-lg transition">
                            <div class="w-10 h-10 rounded-full {{ $index === 0 ? 'bg-gradient-to-br from-yellow-400 to-orange-500' : ($index === 1 ? 'bg-gradient-to-br from-gray-300 to-gray-400' : 'bg-gradient-to-br from-orange-400 to-red-500') }} flex items-center justify-center text-white font-bold">
                                {{ $index + 1 }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-800 truncate">{{ $agent['name'] }}</p>
                                <p class="text-sm text-gray-500 truncate">{{ $agent['agency'] }} • {{ ucfirst(str_replace('_', ' ', $agent['role'])) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-gray-800">{{ $agent['clients_registered'] }}</p>
                                <p class="text-xs text-gray-500">{{ $agent['approval_rate'] }}% KYC</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Geographic Distribution Details -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Distribution Régionale</h3>
                    <div class="space-y-4">
                        @foreach($geographic as $region)
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-map-marker-alt text-blue-500 text-sm"></i>
                                    <span class="font-medium text-gray-800">{{ $region['region'] }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-bold text-gray-800">{{ number_format($region['clients']) }}</span>
                                    <span class="text-xs text-gray-500 ml-1">({{ $region['percentage'] }}%)</span>
                                </div>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 mb-1">
                                <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full transition-all" style="width: {{ $region['percentage'] }}%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500">
                                <span>{{ $region['active_accounts'] }} comptes actifs</span>
                                <span>{{ number_format($region['total_deposits'] / 1000000, 1) }}M FCFA</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Agencies Performance -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Performance des Agences</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agence</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Manager</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Clients</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Agents</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Comptes Actifs</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Localisation</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($operational['agencies_performance'] as $agency)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white text-xs font-bold">
                                            {{ strtoupper(substr($agency['name'], 0, 2)) }}
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">{{ $agency['name'] }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="text-sm text-gray-900">{{ $agency['manager'] }}</p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="text-sm font-semibold text-gray-900">{{ number_format($agency['total_clients']) }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="text-sm text-gray-900">{{ $agency['total_agents'] }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        {{ number_format($agency['active_accounts']) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <p class="text-sm text-gray-500">{{ $agency['city'] }}</p>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Additional Performance Indicators -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- ROA -->
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-sm p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <i class="fas fa-chart-line text-3xl opacity-80"></i>
                        <span class="text-xs bg-white bg-opacity-20 px-2 py-1 rounded-full">KPI</span>
                    </div>
                    <h3 class="text-sm opacity-90 mb-1">ROA (Return on Assets)</h3>
                    <p class="text-3xl font-bold">{{ number_format($performance['roa'], 2) }}%</p>
                </div>

                <!-- Taux d'Utilisation -->
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-sm p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <i class="fas fa-percentage text-3xl opacity-80"></i>
                        <span class="text-xs bg-white bg-opacity-20 px-2 py-1 rounded-full">Liquidité</span>
                    </div>
                    <h3 class="text-sm opacity-90 mb-1">Taux d'Utilisation</h3>
                    <p class="text-3xl font-bold">{{ number_format($performance['utilization_rate'], 1) }}%</p>
                </div>

                <!-- Rétention Client -->
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-sm p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <i class="fas fa-user-check text-3xl opacity-80"></i>
                        <span class="text-xs bg-white bg-opacity-20 px-2 py-1 rounded-full">Fidélité</span>
                    </div>
                    <h3 class="text-sm opacity-90 mb-1">Taux de Rétention</h3>
                    <p class="text-3xl font-bold">{{ number_format($performance['client_retention_rate'], 1) }}%</p>
                </div>

                <!-- Prêt Moyen -->
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-sm p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <i class="fas fa-coins text-3xl opacity-80"></i>
                        <span class="text-xs bg-white bg-opacity-20 px-2 py-1 rounded-full">Moyenne</span>
                    </div>
                    <h3 class="text-sm opacity-90 mb-1">Prêt Moyen</h3>
                    <p class="text-3xl font-bold">{{ number_format($performance['average_loan_size'] / 1000) }}K</p>
                </div>
            </div>

        <script>
            // Growth Chart with real data from controller
            const growthCtx = document.getElementById('growthChart').getContext('2d');
            const growthChart = new Chart(growthCtx, {
                type: 'line',
                data: {
                    labels: @json($growthChartData['labels']),
                    datasets: [
                        {
                            label: 'Nouveaux Clients',
                            data: @json($growthChartData['datasets'][0]['data']),
                            borderColor: '#3B82F6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 0,
                            pointHoverRadius: 6,
                            borderWidth: 3
                        },
                        {
                            label: 'Nouveaux Comptes',
                            data: @json($growthChartData['datasets'][1]['data']),
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 0,
                            pointHoverRadius: 6,
                            borderWidth: 3
                        },
                        {
                            label: 'Demandes de Prêt',
                            data: @json($growthChartData['datasets'][2]['data']),
                            borderColor: '#F59E0B',
                            backgroundColor: 'rgba(245, 158, 11, 0.1)',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 0,
                            pointHoverRadius: 6,
                            borderWidth: 3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: {
                                    size: 12,
                                    family: 'Inter'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14,
                                family: 'Inter'
                            },
                            bodyFont: {
                                size: 13,
                                family: 'Inter'
                            },
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: {
                                    size: 11,
                                    family: 'Inter'
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                maxRotation: 0,
                                font: {
                                    size: 11,
                                    family: 'Inter'
                                }
                            }
                        }
                    }
                }
            });

            // Geographic Chart with real data from controller
            const geographicCtx = document.getElementById('geographicChart').getContext('2d');
            const geographicChart = new Chart(geographicCtx, {
                type: 'doughnut',
                data: {
                    labels: @json($geographicChartData['labels']),
                    datasets: [{
                        data: @json($geographicChartData['clients']),
                        backgroundColor: [
                            '#3B82F6',
                            '#10B981',
                            '#F59E0B',
                            '#EF4444',
                            '#8B5CF6',
                            '#EC4899'
                        ],
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'right',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: {
                                    size: 12,
                                    family: 'Inter'
                                },
                                generateLabels: function(chart) {
                                    const data = chart.data;
                                    if (data.labels.length && data.datasets.length) {
                                        return data.labels.map((label, i) => {
                                            const value = data.datasets[0].data[i];
                                            const percentages = @json($geographicChartData['percentages']);
                                            return {
                                                text: `${label} (${percentages[i]}%)`,
                                                fillStyle: data.datasets[0].backgroundColor[i],
                                                hidden: false,
                                                index: i
                                            };
                                        });
                                    }
                                    return [];
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14,
                                family: 'Inter'
                            },
                            bodyFont: {
                                size: 13,
                                family: 'Inter'
                            },
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    const percentages = @json($geographicChartData['percentages']);
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += context.parsed.toLocaleString('fr-FR') + ' clients (' + percentages[context.dataIndex] + '%)';
                                    return label;
                                }
                            }
                        }
                    },
                    cutout: '65%'
                }
            });

            // Sidebar active link management
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    if (link.getAttribute('href') === '#') {
                        e.preventDefault();
                    }
                });
            });

            // Responsive chart resize
            window.addEventListener('resize', () => {
                growthChart.resize();
                geographicChart.resize();
            });
        </script>
@endsection
