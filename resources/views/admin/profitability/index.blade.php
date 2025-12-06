@extends('layouts.app_admin')

@section('title', 'Dashboard de Rentabilité')

@section('content')
<div class="py-4 container-fluid">

    <!-- En-tête avec filtres de période -->
    <div class="mb-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="mb-1 text-3xl font-bold text-gray-900">💰 Rentabilité & Performance</h2>
                <p class="text-sm text-gray-600">
                    Période: <span class="font-semibold">{{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }}</span>
                </p>
            </div>

            <div class="flex gap-2">
                <form method="GET" action="{{ route('admin.profitability.index') }}" class="flex gap-2">
                    <select name="period"
                            class="px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            onchange="this.form.submit()">
                        <option value="7days" {{ $period == '7days' ? 'selected' : '' }}>7 derniers jours</option>
                        <option value="30days" {{ $period == '30days' ? 'selected' : '' }}>30 derniers jours</option>
                        <option value="90days" {{ $period == '90days' ? 'selected' : '' }}>90 derniers jours</option>
                        <option value="6months" {{ $period == '6months' ? 'selected' : '' }}>6 mois</option>
                        <option value="year" {{ $period == 'year' ? 'selected' : '' }}>1 an</option>
                    </select>
                </form>

                {{-- <a href="{{ route('admin.profitability.investor-report') }}"
                   class="flex items-center gap-2 px-4 py-2 text-sm text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-file-pdf"></i>
                    <span>Rapport Investisseurs</span>
                </a> --}}
            </div>
        </div>
    </div>

    <!-- KPIs Principaux en Grid -->
    <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-2 lg:grid-cols-4">
        <!-- Revenus Totaux -->
        <div class="relative p-6 overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
            <div class="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8 rounded-full opacity-50 bg-green-50"></div>
            <div class="relative">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <p class="mb-1 text-xs font-medium text-gray-500 uppercase">Revenus Totaux</p>
                        <h3 class="text-3xl font-bold text-green-600">
                            {{ number_format($profitability['total_revenue'], 0, ',', ' ') }}
                        </h3>
                        <p class="text-xs text-gray-500">FCFA</p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-xl">
                        <i class="text-xl text-green-600 fas fa-arrow-trend-up"></i>
                    </div>
                </div>
                @if(isset($previousPeriodComparison['revenue_change']))
                <div class="flex items-center gap-1 mt-2">
                    <i class="text-xs {{ $previousPeriodComparison['revenue_change'] >= 0 ? 'text-green-600 fas fa-arrow-up' : 'text-red-600 fas fa-arrow-down' }}"></i>
                    <span class="text-xs font-semibold {{ $previousPeriodComparison['revenue_change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ abs($previousPeriodComparison['revenue_change']) }}%
                    </span>
                    <span class="text-xs text-gray-500">vs période précédente</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Coûts Totaux -->
        <div class="relative p-6 overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
            <div class="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8 rounded-full opacity-50 bg-red-50"></div>
            <div class="relative">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <p class="mb-1 text-xs font-medium text-gray-500 uppercase">Coûts Totaux</p>
                        <h3 class="text-3xl font-bold text-red-600">
                            {{ number_format($profitability['total_costs'], 0, ',', ' ') }}
                        </h3>
                        <p class="text-xs text-gray-500">FCFA</p>
                    </div>
                    <div class="p-3 bg-red-100 rounded-xl">
                        <i class="text-xl text-red-600 fas fa-arrow-trend-down"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="w-full h-1 bg-gray-200 rounded-full">
                        @php
                            $costRatio = $profitability['total_revenue'] > 0
                                ? ($profitability['total_costs'] / $profitability['total_revenue']) * 100
                                : 0;
                        @endphp
                        <div class="h-1 transition-all duration-300 bg-red-600 rounded-full" style="width: {{ min($costRatio, 100) }}%"></div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">{{ round($costRatio, 1) }}% des revenus</p>
                </div>
            </div>
        </div>

        <!-- Bénéfice Net -->
        <div class="relative p-6 overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
            <div class="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8 rounded-full opacity-50 bg-blue-50"></div>
            <div class="relative">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <p class="mb-1 text-xs font-medium text-gray-500 uppercase">Bénéfice Net</p>
                        <h3 class="text-3xl font-bold {{ $profitability['net_profit'] >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                            {{ number_format($profitability['net_profit'], 0, ',', ' ') }}
                        </h3>
                        <p class="text-xs text-gray-500">FCFA</p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-xl">
                        <i class="text-xl text-blue-600 fas fa-chart-line"></i>
                    </div>
                </div>
                @if(isset($previousPeriodComparison['profit_change']))
                <div class="flex items-center gap-1 mt-2">
                    <i class="text-xs {{ $previousPeriodComparison['profit_change'] >= 0 ? 'text-green-600 fas fa-arrow-up' : 'text-red-600 fas fa-arrow-down' }}"></i>
                    <span class="text-xs font-semibold {{ $previousPeriodComparison['profit_change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ abs($previousPeriodComparison['profit_change']) }}%
                    </span>
                    <span class="text-xs text-gray-500">vs période précédente</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Marge Bénéficiaire -->
        <div class="relative p-6 overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
            <div class="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8 rounded-full opacity-50 bg-cyan-50"></div>
            <div class="relative">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <p class="mb-1 text-xs font-medium text-gray-500 uppercase">Marge Bénéficiaire</p>
                        <h3 class="text-3xl font-bold text-cyan-600">
                            {{ $profitability['profit_margin'] }}%
                        </h3>
                        <p class="text-xs text-gray-500">Sur revenus</p>
                    </div>
                    <div class="p-3 rounded-xl bg-cyan-100">
                        <i class="text-xl fas fa-percentage text-cyan-600"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-600">Objectif: 25%</span>
                        <span class="font-semibold {{ $profitability['profit_margin'] >= 25 ? 'text-green-600' : 'text-orange-600' }}">
                            {{ $profitability['profit_margin'] >= 25 ? '✓ Atteint' : '△ En cours' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Répartition des Revenus -->
    <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-3">
        <!-- Graphique Doughnut -->
        <div class="bg-white border border-gray-200 shadow-sm rounded-xl lg:col-span-2">
            <div class="px-6 py-4 border-b border-gray-200">
                <h5 class="text-lg font-bold text-gray-900">📊 Répartition des Revenus par Source</h5>
                <p class="text-sm text-gray-500">Distribution des revenus sur la période</p>
            </div>
            <div class="p-6">
                <div class="relative" style="height: 320px;">
                    <canvas id="revenueSourceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Liste des Sources -->
        <div class="bg-white border border-gray-200 shadow-sm rounded-xl">
            <div class="px-6 py-4 border-b border-gray-200">
                <h5 class="text-lg font-bold text-gray-900">💡 Sources de Revenus</h5>
                <p class="text-sm text-gray-500">Détails par catégorie</p>
            </div>
            <div class="p-6">
                @php
                    $totalRevenue = array_sum(array_column($revenueBySource, 'amount'));
                    $colors = [
                        'loan_interest' => 'bg-blue-600',
                        'loan_penalties' => 'bg-green-600',
                        'fees' => 'bg-yellow-500',
                        'tontine' => 'bg-purple-600'
                    ];
                    $icons = [
                        'loan_interest' => 'fa-hand-holding-usd',
                        'loan_penalties' => 'fa-exclamation-triangle',
                        'fees' => 'fa-money-bill-wave',
                        'tontine' => 'fa-users'
                    ];
                @endphp

                <div class="space-y-4">
                    @foreach($revenueBySource as $key => $source)
                        @php
                            $percentage = $totalRevenue > 0 ? round(($source['amount'] / $totalRevenue) * 100, 1) : 0;
                            $color = $colors[$key] ?? 'bg-gray-600';
                            $icon = $icons[$key] ?? 'fa-circle';
                        @endphp
                        <div class="p-4 transition-all duration-200 border border-gray-100 rounded-lg hover:border-gray-300 hover:shadow-sm">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="p-2 {{ str_replace('bg-', 'bg-opacity-10 bg-', $color) }} rounded-lg">
                                    <i class="text-sm fas {{ $icon }} {{ str_replace('bg-', 'text-', $color) }}"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold text-gray-700">{{ $source['label'] }}</span>
                                        <span class="text-sm font-bold text-gray-900">{{ $percentage }}%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="w-full h-2 overflow-hidden bg-gray-100 rounded-full">
                                <div class="h-2 transition-all duration-500 {{ $color }} rounded-full"
                                     style="width: {{ $percentage }}%"></div>
                            </div>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs font-bold text-gray-900">
                                    {{ number_format($source['amount'], 0, ',', ' ') }} FCFA
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ number_format($source['amount'] / max($startDate->diffInDays($endDate), 1), 0, ',', ' ') }} FCFA/jour
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- KPIs Investisseurs -->
    <div class="mb-6">
        <div class="bg-white border border-gray-200 shadow-sm rounded-xl">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h5 class="text-lg font-bold text-gray-900">📈 Indicateurs Clés pour Investisseurs</h5>
                        <p class="text-sm text-gray-500">Métriques de performance et de croissance</p>
                    </div>
                    <a href="{{ route('admin.profitability.investor-report') }}" class="text-sm text-blue-600 hover:text-blue-700">
                        Voir le rapport complet →
                    </a>
                </div>
            </div>
            <div class="p-6">
                <!-- Métriques Opérationnelles -->
                <div class="mb-6">
                    <h6 class="mb-4 text-sm font-semibold text-gray-700 uppercase">Métriques Opérationnelles</h6>
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                        <div class="p-4 text-center transition-all duration-200 border-2 border-gray-200 rounded-xl hover:border-blue-300 hover:shadow-md">
                            <div class="mb-2">
                                <i class="text-2xl text-blue-600 fas fa-users"></i>
                            </div>
                            <p class="mb-1 text-xs font-medium text-gray-500">Clients Actifs</p>
                            <h4 class="text-2xl font-bold text-gray-900">{{ number_format($kpis['total_clients']) }}</h4>
                        </div>
                        <div class="p-4 text-center transition-all duration-200 border-2 border-gray-200 rounded-xl hover:border-green-300 hover:shadow-md">
                            <div class="mb-2">
                                <i class="text-2xl text-green-600 fas fa-wallet"></i>
                            </div>
                            <p class="mb-1 text-xs font-medium text-gray-500">Comptes Actifs</p>
                            <h4 class="text-2xl font-bold text-gray-900">{{ number_format($kpis['active_accounts']) }}</h4>
                        </div>
                        <div class="p-4 text-center transition-all duration-200 border-2 border-gray-200 rounded-xl hover:border-yellow-300 hover:shadow-md">
                            <div class="mb-2">
                                <i class="text-2xl text-yellow-600 fas fa-piggy-bank"></i>
                            </div>
                            <p class="mb-1 text-xs font-medium text-gray-500">Dépôts Totaux</p>
                            <h4 class="text-lg font-bold text-gray-900">{{ number_format($kpis['total_deposits'] / 1000000, 1) }}M</h4>
                            <p class="text-xs text-gray-500">FCFA</p>
                        </div>
                        <div class="p-4 text-center transition-all duration-200 border-2 border-gray-200 rounded-xl hover:border-purple-300 hover:shadow-md">
                            <div class="mb-2">
                                <i class="text-2xl text-purple-600 fas fa-hand-holding-usd"></i>
                            </div>
                            <p class="mb-1 text-xs font-medium text-gray-500">Portfolio Prêts</p>
                            <h4 class="text-lg font-bold text-gray-900">{{ number_format($kpis['loan_portfolio'] / 1000000, 1) }}M</h4>
                            <p class="text-xs text-gray-500">FCFA</p>
                        </div>
                    </div>
                </div>

                <hr class="my-6 border-gray-200">

                <!-- Métriques Financières -->
                <div>
                    <h6 class="mb-4 text-sm font-semibold text-gray-700 uppercase">Métriques Financières</h6>
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                        <div class="p-5 text-center transition-all duration-200 border-2 border-green-200 rounded-xl bg-green-50 hover:shadow-lg">
                            <p class="mb-2 text-xs font-semibold text-green-700 uppercase">ROI</p>
                            <h4 class="mb-1 text-3xl font-bold text-green-600">{{ $kpis['roi'] }}%</h4>
                            <p class="text-xs text-green-600">Return on Investment</p>
                        </div>
                        <div class="p-5 text-center transition-all duration-200 border-2 border-blue-200 rounded-xl bg-blue-50 hover:shadow-lg">
                            <p class="mb-2 text-xs font-semibold text-blue-700 uppercase">Marge Nette</p>
                            <h4 class="mb-1 text-3xl font-bold text-blue-600">{{ $kpis['profit_margin'] }}%</h4>
                            <p class="text-xs text-blue-600">Net Profit Margin</p>
                        </div>
                        <div class="p-5 text-center transition-all duration-200 border-2 border-yellow-200 rounded-xl bg-yellow-50 hover:shadow-lg">
                            <p class="mb-2 text-xs font-semibold text-yellow-700 uppercase">Taux de Défaut</p>
                            <h4 class="mb-1 text-3xl font-bold text-yellow-600">{{ $kpis['default_rate'] }}%</h4>
                            <p class="text-xs text-yellow-600">Default Rate</p>
                        </div>
                        <div class="p-5 text-center transition-all duration-200 border-2 rounded-xl border-cyan-200 bg-cyan-50 hover:shadow-lg">
                            <p class="mb-2 text-xs font-semibold uppercase text-cyan-700">Prêts Actifs</p>
                            <h4 class="mb-1 text-3xl font-bold text-cyan-600">{{ number_format($kpis['active_loans']) }}</h4>
                            <p class="text-xs text-cyan-600">Active Loans</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Évolution Temporelle -->
    <div class="mb-6">
        <div class="bg-white border border-gray-200 shadow-sm rounded-xl">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h5 class="text-lg font-bold text-gray-900">📅 Évolution des Revenus</h5>
                        <p class="text-sm text-gray-500">Tendance quotidienne sur la période</p>
                    </div>
                    <div class="flex gap-2">
                        <button class="px-3 py-1 text-xs font-medium text-blue-600 bg-blue-100 rounded-lg hover:bg-blue-200">
                            Revenus
                        </button>
                        <button class="px-3 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">
                            Frais
                        </button>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="relative" style="height: 280px;">
                    <canvas id="revenueTimelineChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Projections Futures -->
    <div class="mb-6">
        <div class="bg-white border border-gray-200 shadow-sm rounded-xl">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center gap-2">
                    <h5 class="text-lg font-bold text-gray-900">🔮 Projections</h5>
                    <span class="px-2 py-1 text-xs font-semibold text-purple-700 bg-purple-100 rounded-full">Prévisionnel</span>
                </div>
                <p class="text-sm text-gray-500">Estimations basées sur les tendances actuelles</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div class="p-5 text-center transition-all duration-200 border border-gray-200 rounded-xl hover:border-blue-300 hover:shadow-md bg-gradient-to-br from-white to-blue-50">
                        <div class="mb-2">
                            <i class="text-2xl text-blue-600 fas fa-calendar-day"></i>
                        </div>
                        <p class="mb-2 text-xs font-medium text-gray-600">Mois Prochain</p>
                        <h5 class="text-xl font-bold text-gray-900">{{ number_format($projections['next_month_revenue'] / 1000000, 2) }}M</h5>
                        <p class="text-xs text-gray-500">FCFA</p>
                    </div>
                    <div class="p-5 text-center transition-all duration-200 border border-gray-200 rounded-xl hover:border-green-300 hover:shadow-md bg-gradient-to-br from-white to-green-50">
                        <div class="mb-2">
                            <i class="text-2xl text-green-600 fas fa-calendar-week"></i>
                        </div>
                        <p class="mb-2 text-xs font-medium text-gray-600">Prochain Trimestre</p>
                        <h5 class="text-xl font-bold text-gray-900">{{ number_format($projections['next_quarter_revenue'] / 1000000, 2) }}M</h5>
                        <p class="text-xs text-gray-500">FCFA</p>
                    </div>
                    <div class="p-5 text-center transition-all duration-200 border border-gray-200 rounded-xl hover:border-yellow-300 hover:shadow-md bg-gradient-to-br from-white to-yellow-50">
                        <div class="mb-2">
                            <i class="text-2xl text-yellow-600 fas fa-calendar-alt"></i>
                        </div>
                        <p class="mb-2 text-xs font-medium text-gray-600">Revenu Annuel</p>
                        <h5 class="text-xl font-bold text-gray-900">{{ number_format($projections['annual_revenue'] / 1000000, 2) }}M</h5>
                        <p class="text-xs text-gray-500">FCFA</p>
                    </div>
                    <div class="p-5 text-center transition-all duration-200 border border-gray-200 rounded-xl hover:border-purple-300 hover:shadow-md bg-gradient-to-br from-white to-purple-50">
                        <div class="mb-2">
                            <i class="text-2xl text-purple-600 fas fa-chart-line"></i>
                        </div>
                        <p class="mb-2 text-xs font-medium text-gray-600">Potentiel de Croissance</p>
                        <h5 class="text-xl font-bold text-purple-600">{{ $projections['growth_potential']['growth_potential'] }}%</h5>
                        <p class="text-xs text-gray-500">Market Potential</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Breakdown Détaillé des Revenus -->
    <div class="mb-6">
        <div class="bg-white border border-gray-200 shadow-sm rounded-xl">
            <div class="px-6 py-4 border-b border-gray-200">
                <h5 class="text-lg font-bold text-gray-900">💎 Analyse Détaillée des Revenus</h5>
                <p class="text-sm text-gray-500">Décomposition complète par type de revenu</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Intérêts sur Prêts -->
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-gray-700">Intérêts Prêts</span>
                            <span class="text-xs font-medium text-blue-600">{{ $revenuePercentages['loan_interest'] }}%</span>
                        </div>
                        <h4 class="mb-1 text-xl font-bold text-gray-900">
                            {{ number_format($revenueBreakdown['loan_interest'], 0, ',', ' ') }}
                        </h4>
                        <p class="text-xs text-gray-500">FCFA</p>
                    </div>

                    <!-- Pénalités -->
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-gray-700">Pénalités</span>
                            <span class="text-xs font-medium text-orange-600">{{ $revenuePercentages['loan_penalties'] }}%</span>
                        </div>
                        <h4 class="mb-1 text-xl font-bold text-gray-900">
                            {{ number_format($revenueBreakdown['loan_penalties'], 0, ',', ' ') }}
                        </h4>
                        <p class="text-xs text-gray-500">FCFA</p>
                    </div>

                    <!-- Frais de Compte -->
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-gray-700">Frais Activation</span>
                            <span class="text-xs font-medium text-green-600">{{ $revenuePercentages['account_fees'] }}%</span>
                        </div>
                        <h4 class="mb-1 text-xl font-bold text-gray-900">
                            {{ number_format($revenueBreakdown['account_fees'], 0, ',', ' ') }}
                        </h4>
                        <p class="text-xs text-gray-500">FCFA</p>
                    </div>

                    <!-- Frais de Transaction -->
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-gray-700">Frais Transaction</span>
                            <span class="text-xs font-medium text-purple-600">{{ $revenuePercentages['transaction_fees'] }}%</span>
                        </div>
                        <h4 class="mb-1 text-xl font-bold text-gray-900">
                            {{ number_format($revenueBreakdown['transaction_fees'], 0, ',', ' ') }}
                        </h4>
                        <p class="text-xs text-gray-500">FCFA</p>
                    </div>

                    <!-- Frais de Retrait -->
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-gray-700">Frais Retrait</span>
                            <span class="text-xs font-medium text-yellow-600">{{ $revenuePercentages['withdrawal_fees'] }}%</span>
                        </div>
                        <h4 class="mb-1 text-xl font-bold text-gray-900">
                            {{ number_format($revenueBreakdown['withdrawal_fees'], 0, ',', ' ') }}
                        </h4>
                        <p class="text-xs text-gray-500">FCFA</p>
                    </div>

                    <!-- Frais de Transfert -->
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-gray-700">Frais Transfert</span>
                            <span class="text-xs font-medium text-pink-600">{{ $revenuePercentages['transfer_fees'] }}%</span>
                        </div>
                        <h4 class="mb-1 text-xl font-bold text-gray-900">
                            {{ number_format($revenueBreakdown['transfer_fees'], 0, ',', ' ') }}
                        </h4>
                        <p class="text-xs text-gray-500">FCFA</p>
                    </div>

                    <!-- Frais Mensuels Épargne -->
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-gray-700">Frais Mensuels</span>
                            <span class="text-xs font-medium text-indigo-600">{{ $revenuePercentages['monthly_fees'] }}%</span>
                        </div>
                        <h4 class="mb-1 text-xl font-bold text-gray-900">
                            {{ number_format($revenueBreakdown['monthly_fees'], 0, ',', ' ') }}
                        </h4>
                        <p class="text-xs text-gray-500">FCFA</p>
                    </div>

                    <!-- Revenus Tontines -->
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-gray-700">Tontines</span>
                            <span class="text-xs font-medium text-cyan-600">{{ $revenuePercentages['tontine_revenue'] }}%</span>
                        </div>
                        <h4 class="mb-1 text-xl font-bold text-gray-900">
                            {{ number_format($revenueBreakdown['tontine_revenue'], 0, ',', ' ') }}
                        </h4>
                        <p class="text-xs text-gray-500">FCFA</p>
                    </div>
                </div>

                <!-- Total -->
                <div class="p-4 mt-4 border-2 border-blue-200 rounded-lg bg-blue-50">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-sm font-semibold text-blue-900">Total des Revenus</span>
                            <p class="text-xs text-blue-700">Sur la période sélectionnée</p>
                        </div>
                        <div class="text-right">
                            <h3 class="text-2xl font-bold text-blue-900">
                                {{ number_format($totalRevenue, 0, ',', ' ') }}
                            </h3>
                            <p class="text-xs text-blue-700">FCFA</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analyse des Risques -->
    @if(isset($riskAnalysis))
    <div class="mb-6">
        <div class="bg-white border border-gray-200 shadow-sm rounded-xl">
            <div class="px-6 py-4 border-b border-gray-200">
                <h5 class="text-lg font-bold text-gray-900">⚠️ Analyse des Risques</h5>
                <p class="text-sm text-gray-500">Indicateurs de risque et qualité du portefeuille</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
                    <!-- Concentration des Prêts -->
                    <div class="p-4 text-center border-2 border-yellow-200 rounded-lg bg-yellow-50">
                        <div class="mb-2">
                            <i class="text-2xl text-yellow-600 fas fa-layer-group"></i>
                        </div>
                        <p class="mb-1 text-xs font-medium text-yellow-700">Concentration</p>
                        <h4 class="text-2xl font-bold text-yellow-900">{{ $riskAnalysis['loan_concentration'] }}%</h4>
                        <p class="text-xs text-yellow-600">Top 10 prêts</p>
                    </div>

                    <!-- Qualité Portefeuille -->
                    <div class="p-4 text-center border-2 border-green-200 rounded-lg bg-green-50">
                        <div class="mb-2">
                            <i class="text-2xl text-green-600 fas fa-shield-alt"></i>
                        </div>
                        <p class="mb-1 text-xs font-medium text-green-700">Qualité</p>
                        <h4 class="text-2xl font-bold text-green-900">{{ $riskAnalysis['portfolio_quality'] }}%</h4>
                        <p class="text-xs text-green-600">Performing</p>
                    </div>

                    <!-- Ratio de Liquidité -->
                    <div class="p-4 text-center border-2 border-blue-200 rounded-lg bg-blue-50">
                        <div class="mb-2">
                            <i class="text-2xl text-blue-600 fas fa-tint"></i>
                        </div>
                        <p class="mb-1 text-xs font-medium text-blue-700">Liquidité</p>
                        <h4 class="text-2xl font-bold text-blue-900">{{ $riskAnalysis['liquidity_ratio'] }}%</h4>
                        <p class="text-xs text-blue-600">Ratio</p>
                    </div>

                    <!-- NPL Ratio -->
                    <div class="p-4 text-center border-2 border-red-200 rounded-lg bg-red-50">
                        <div class="mb-2">
                            <i class="text-2xl text-red-600 fas fa-exclamation-triangle"></i>
                        </div>
                        <p class="mb-1 text-xs font-medium text-red-700">NPL Ratio</p>
                        <h4 class="text-2xl font-bold text-red-900">{{ $riskAnalysis['npl_ratio'] }}%</h4>
                        <p class="text-xs text-red-600">Non-performing</p>
                    </div>

                    <!-- Exposition Risque Élevé -->
                    <div class="p-4 text-center border-2 border-orange-200 rounded-lg bg-orange-50">
                        <div class="mb-2">
                            <i class="text-2xl text-orange-600 fas fa-fire"></i>
                        </div>
                        <p class="mb-1 text-xs font-medium text-orange-700">Risque Élevé</p>
                        <h4 class="text-2xl font-bold text-orange-900">
                            {{ $riskAnalysis['risk_exposure']['high'] + $riskAnalysis['risk_exposure']['very_high'] }}%
                        </h4>
                        <p class="text-xs text-orange-600">Exposition</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Flux de Trésorerie -->
    @if(isset($cashFlow))
    <div class="mb-6">
        <div class="bg-white border border-gray-200 shadow-sm rounded-xl">
            <div class="px-6 py-4 border-b border-gray-200">
                <h5 class="text-lg font-bold text-gray-900">💸 Flux de Trésorerie</h5>
                <p class="text-sm text-gray-500">Entrées et sorties sur la période</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Entrées -->
                    <div class="p-5 border-2 border-green-200 rounded-xl bg-green-50">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-3 bg-green-600 rounded-lg">
                                <i class="text-xl text-white fas fa-arrow-down"></i>
                            </div>
                            <div>
                                <h6 class="text-sm font-bold text-green-900">Entrées</h6>
                                <p class="text-xs text-green-700">Cash Inflows</p>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-green-700">Dépôts</span>
                                <span class="font-semibold text-green-900">{{ number_format($cashFlow['inflows']['deposits'] / 1000000, 2) }}M</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-green-700">Remboursements</span>
                                <span class="font-semibold text-green-900">{{ number_format($cashFlow['inflows']['loan_repayments'] / 1000000, 2) }}M</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-green-700">Frais</span>
                                <span class="font-semibold text-green-900">{{ number_format($cashFlow['inflows']['fees'] / 1000000, 2) }}M</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-green-700">Tontines</span>
                                <span class="font-semibold text-green-900">{{ number_format($cashFlow['inflows']['tontine_contributions'] / 1000000, 2) }}M</span>
                            </div>
                        </div>
                        <div class="pt-3 mt-3 border-t-2 border-green-300">
                            <div class="flex justify-between">
                                <span class="text-sm font-bold text-green-900">Total</span>
                                <span class="text-lg font-bold text-green-900">{{ number_format($cashFlow['total_inflows'] / 1000000, 2) }}M</span>
                            </div>
                        </div>
                    </div>

                    <!-- Sorties -->
                    <div class="p-5 border-2 border-red-200 rounded-xl bg-red-50">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-3 bg-red-600 rounded-lg">
                                <i class="text-xl text-white fas fa-arrow-up"></i>
                            </div>
                            <div>
                                <h6 class="text-sm font-bold text-red-900">Sorties</h6>
                                <p class="text-xs text-red-700">Cash Outflows</p>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-red-700">Retraits</span>
                                <span class="font-semibold text-red-900">{{ number_format($cashFlow['outflows']['withdrawals'] / 1000000, 2) }}M</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-red-700">Décaissements</span>
                                <span class="font-semibold text-red-900">{{ number_format($cashFlow['outflows']['loan_disbursements'] / 1000000, 2) }}M</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-red-700">Paiements Tontines</span>
                                <span class="font-semibold text-red-900">{{ number_format($cashFlow['outflows']['tontine_payouts'] / 1000000, 2) }}M</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-red-700">Coûts Opérationnels</span>
                                <span class="font-semibold text-red-900">{{ number_format($cashFlow['outflows']['operational_costs'] / 1000000, 2) }}M</span>
                            </div>
                        </div>
                        <div class="pt-3 mt-3 border-t-2 border-red-300">
                            <div class="flex justify-between">
                                <span class="text-sm font-bold text-red-900">Total</span>
                                <span class="text-lg font-bold text-red-900">{{ number_format($cashFlow['total_outflows'] / 1000000, 2) }}M</span>
                            </div>
                        </div>
                    </div>

                    <!-- Flux Net -->
                    <div class="p-5 border-2 rounded-xl {{ $cashFlow['net_cash_flow'] >= 0 ? 'border-blue-200 bg-blue-50' : 'border-orange-200 bg-orange-50' }}">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-3 rounded-lg {{ $cashFlow['net_cash_flow'] >= 0 ? 'bg-blue-600' : 'bg-orange-600' }}">
                                <i class="text-xl text-white fas fa-balance-scale"></i>
                            </div>
                            <div>
                                <h6 class="text-sm font-bold {{ $cashFlow['net_cash_flow'] >= 0 ? 'text-blue-900' : 'text-orange-900' }}">Flux Net</h6>
                                <p class="text-xs {{ $cashFlow['net_cash_flow'] >= 0 ? 'text-blue-700' : 'text-orange-700' }}">Net Cash Flow</p>
                            </div>
                        </div>
                        <div class="py-6 text-center">
                            <h2 class="text-4xl font-bold {{ $cashFlow['net_cash_flow'] >= 0 ? 'text-blue-900' : 'text-orange-900' }}">
                                {{ number_format($cashFlow['net_cash_flow'] / 1000000, 2) }}M
                            </h2>
                            <p class="mt-2 text-sm {{ $cashFlow['net_cash_flow'] >= 0 ? 'text-blue-700' : 'text-orange-700' }}">FCFA</p>
                        </div>
                        <div class="pt-3 mt-3 border-t-2 {{ $cashFlow['net_cash_flow'] >= 0 ? 'border-blue-300' : 'border-orange-300' }}">
                            <div class="text-xs text-center {{ $cashFlow['net_cash_flow'] >= 0 ? 'text-blue-700' : 'text-orange-700' }}">
                                {{ $cashFlow['net_cash_flow'] >= 0 ? 'Position positive' : 'Position déficitaire' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Configuration commune pour les graphiques
const commonOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            labels: {
                font: {
                    family: "'Inter', sans-serif",
                    size: 12
                },
                padding: 15,
                usePointStyle: true
            }
        },
        tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            padding: 12,
            titleFont: {
                size: 13,
                weight: 'bold'
            },
            bodyFont: {
                size: 12
            },
            cornerRadius: 8
        }
    }
};

// Graphique répartition des revenus (Doughnut)
const revenueSourceCtx = document.getElementById('revenueSourceChart').getContext('2d');
new Chart(revenueSourceCtx, {
    type: 'doughnut',
    data: {
        labels: [
            @foreach($revenueBySource as $source)
                '{{ $source["label"] }}',
            @endforeach
        ],
        datasets: [{
            data: [
                @foreach($revenueBySource as $source)
                    {{ $source['amount'] }},
                @endforeach
            ],
            backgroundColor: [
                '#3b82f6', // blue
                '#10b981', // green
                '#fbbf24', // yellow
                '#8b5cf6'  // purple
            ],
            borderWidth: 2,
            borderColor: '#ffffff',
            hoverOffset: 15
        }]
    },
    options: {
        ...commonOptions,
        cutout: '65%',
        plugins: {
            ...commonOptions.plugins,
            legend: {
                position: 'bottom',
                labels: {
                    ...commonOptions.plugins.legend.labels,
                    padding: 20
                }
            },
            tooltip: {
                ...commonOptions.plugins.tooltip,
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return `${label}: ${new Intl.NumberFormat('fr-FR').format(value)} FCFA (${percentage}%)`;
                    }
                }
            }
        }
    }
});

// Graphique évolution temporelle (Line)
const timelineCtx = document.getElementById('revenueTimelineChart').getContext('2d');
const gradient = timelineCtx.createLinearGradient(0, 0, 0, 250);
gradient.addColorStop(0, 'rgba(59, 130, 246, 0.3)');
gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

new Chart(timelineCtx, {
    type: 'line',
    data: {
        labels: [
            @foreach($revenueTimeline as $day)
                '{{ \Carbon\Carbon::parse($day["date"])->format("d/m") }}',
            @endforeach
        ],
        datasets: [{
            label: 'Revenus (FCFA)',
            data: [
                @foreach($revenueTimeline as $day)
                    {{ $day['revenue'] ?? 0 }},
                @endforeach
            ],
            borderColor: '#3b82f6',
            backgroundColor: gradient,
            fill: true,
            tension: 0.4,
            borderWidth: 3,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: '#3b82f6',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointHoverBackgroundColor: '#1e40af',
            pointHoverBorderColor: '#ffffff'
        }]
    },
    options: {
        ...commonOptions,
        interaction: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            ...commonOptions.plugins,
            legend: {
                display: false
            },
            tooltip: {
                ...commonOptions.plugins.tooltip,
                callbacks: {
                    label: function(context) {
                        return `Revenus: ${new Intl.NumberFormat('fr-FR').format(context.parsed.y)} FCFA`;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                    drawBorder: false
                },
                ticks: {
                    font: {
                        size: 11
                    },
                    color: '#6b7280',
                    callback: function(value) {
                        if (value >= 1000000) {
                            return (value / 1000000).toFixed(1) + 'M';
                        }
                        return new Intl.NumberFormat('fr-FR').format(value);
                    }
                }
            },
            x: {
                grid: {
                    display: false,
                    drawBorder: false
                },
                ticks: {
                    font: {
                        size: 11
                    },
                    color: '#6b7280',
                    maxRotation: 0,
                    autoSkip: true,
                    maxTicksLimit: 15
                }
            }
        }
    }
});

// Animation au chargement
document.addEventListener('DOMContentLoaded', function() {
    // Animer les chiffres
    const animateValue = (element, start, end, duration) => {
        const range = end - start;
        const increment = range / (duration / 16);
        let current = start;

        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                current = end;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current).toLocaleString('fr-FR');
        }, 16);
    };

    // Appliquer l'animation aux KPIs principaux
    document.querySelectorAll('[data-animate-value]').forEach(el => {
        const finalValue = parseInt(el.getAttribute('data-animate-value'));
        animateValue(el, 0, finalValue, 1000);
    });
});
</script>
@endpush

@endsection
