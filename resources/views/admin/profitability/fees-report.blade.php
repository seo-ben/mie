@extends('layouts.app_admin')

@section('title', 'Rapport des Frais')

@section('content')
<div class="py-4 container-fluid">

    <!-- En-tête -->
    <div class="mb-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="mb-1 text-2xl font-bold text-gray-900">💳 Rapport des Frais</h2>
                <p class="text-sm text-gray-500">
                    Période: {{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }}
                </p>
            </div>

            <div class="flex gap-2">
                <form method="GET" action="{{ route('admin.profitability.fees-report') }}" class="flex gap-2">
                    <select name="period" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="this.form.submit()">
                        <option value="7days" {{ $period == '7days' ? 'selected' : '' }}>7 jours</option>
                        <option value="30days" {{ $period == '30days' ? 'selected' : '' }}>30 jours</option>
                        <option value="90days" {{ $period == '90days' ? 'selected' : '' }}>90 jours</option>
                        <option value="year" {{ $period == 'year' ? 'selected' : '' }}>1 an</option>
                    </select>
                </form>

                <a href="{{ route('admin.profitability.index') }}" class="flex items-center gap-2 px-4 py-2 text-gray-700 transition border border-gray-300 rounded-lg hover:bg-gray-50">
                    <i class="fas fa-arrow-left"></i>
                    <span>Retour</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Total des Frais -->
    <div class="mb-6">
        <div class="p-8 text-center text-white rounded-lg shadow-lg bg-gradient-to-r from-blue-600 to-blue-700">
            <h6 class="mb-2 text-sm text-blue-100">Total des Frais Collectés</h6>
            <h1 class="text-5xl font-bold">
                {{ number_format($totalFees ?? 0, 0, ',', ' ') }} 
                <small class="text-2xl font-normal">FCFA</small>
            </h1>
        </div>
    </div>

    <!-- Détails par Type de Frais -->
    <div class="grid grid-cols-1 gap-4 mb-6 lg:grid-cols-2">

        <!-- Frais d'Activation de Compte -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h5 class="text-lg font-semibold text-gray-900">
                    <i class="text-green-600 fas fa-user-check"></i> Frais d'Ouverture de Compte
                </h5>
            </div>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="mb-1 text-sm text-gray-500">Montant Total</p>
                        <h3 class="text-2xl font-bold text-green-600">
                            {{ number_format($feesData['account_activation']?? 0, 0, ',', ' ') }} FCFA
                        </h3>
                    </div>
                   
                </div>

                @if(isset($feesData['account_activation']['by_type']) && is_array($feesData['account_activation']['by_type']))
                <hr class="my-4 border-gray-200">
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Comptes Épargne</span>
                        <strong class="text-gray-900">{{ number_format($feesData['account_activation']['by_type']['savings'] ?? 0, 0, ',', ' ') }} FCFA</strong>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Comptes Tontine</span>
                        <strong class="text-gray-900">{{ number_format($feesData['account_activation']['by_type']['tontine'] ?? 0, 0, ',', ' ') }} FCFA</strong>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Frais de Transaction -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h5 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-exchange-alt text-cyan-600"></i> Frais de Transaction
                </h5>
            </div>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="mb-1 text-sm text-gray-500">Montant Total</p>
                        <h3 class="text-2xl font-bold text-cyan-600">
                            {{ number_format($feesData['transaction_fees']['total'] ?? 0, 0, ',', ' ') }} FCFA
                        </h3>
                    </div>
                    <div class="text-right">
                        <p class="mb-1 text-sm text-gray-500">Transactions</p>
                        <h4 class="text-xl font-semibold text-gray-900">{{ $feesData['transaction_fees']['count'] ?? 0 }}</h4>
                    </div>
                </div>

                <hr class="my-4 border-gray-200">
                <div class="text-sm text-gray-500">
                    Moyenne par transaction:
                    <strong class="text-gray-900">
                        @php
                            $count = $feesData['transaction_fees']['count'] ?? 0;
                            $total = $feesData['transaction_fees']['total'] ?? 0;
                            $average = $count > 0 ? $total / $count : 0;
                        @endphp
                        {{ number_format($average, 0, ',', ' ') }} FCFA
                    </strong>
                </div>
            </div>
        </div>

        <!-- Frais de Retrait -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h5 class="text-lg font-semibold text-gray-900">
                    <i class="text-yellow-600 fas fa-hand-holding-usd"></i> Frais de Retrait
                </h5>
            </div>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="mb-1 text-sm text-gray-500">Montant Total</p>
                        <h3 class="text-2xl font-bold text-yellow-600">
                            {{ number_format($feesData['withdrawal_fees']['total'] ?? 0, 0, ',', ' ') }} FCFA
                        </h3>
                    </div>
                    <div class="text-right">
                        <p class="mb-1 text-sm text-gray-500">Retraits</p>
                        <h4 class="text-xl font-semibold text-gray-900">{{ $feesData['withdrawal_fees']['count'] ?? 0 }}</h4>
                    </div>
                </div>

                @if(isset($feesData['withdrawal_fees']['by_method']) && is_array($feesData['withdrawal_fees']['by_method']))
                <hr class="my-4 border-gray-200">
                <div class="space-y-2 text-sm">
                    @foreach($feesData['withdrawal_fees']['by_method'] as $method => $amount)
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 capitalize">{{ str_replace('_', ' ', $method) }}</span>
                        <strong class="text-gray-900">{{ number_format($amount ?? 0, 0, ',', ' ') }} FCFA</strong>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        <!-- Frais de Transfert -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h5 class="text-lg font-semibold text-gray-900">
                    <i class="text-blue-600 fas fa-arrows-alt-h"></i> Frais de Transfert
                </h5>
            </div>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="mb-1 text-sm text-gray-500">Montant Total</p>
                        <h3 class="text-2xl font-bold text-blue-600">
                            {{ number_format($feesData['transfer_fees']['total'] ?? 0, 0, ',', ' ') }} FCFA
                        </h3>
                    </div>
                    <div class="text-right">
                        <p class="mb-1 text-sm text-gray-500">Transferts</p>
                        <h4 class="text-xl font-semibold text-gray-900">{{ $feesData['transfer_fees']['count'] ?? 0 }}</h4>
                    </div>
                </div>

                <hr class="my-4 border-gray-200">
                <div class="text-sm text-gray-500">
                    Taux moyen: <strong class="text-gray-900">0.5%</strong> du montant transféré
                </div>
            </div>
        </div>

        <!-- Frais Mensuels d'Épargne -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm lg:col-span-2">
            <div class="px-6 py-4 border-b border-gray-200">
                <h5 class="text-lg font-semibold text-gray-900">
                    <i class="text-gray-600 fas fa-calendar-alt"></i> Frais Mensuels sur Comptes d'Épargne
                </h5>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div>
                        <p class="mb-1 text-sm text-gray-500">Montant Total</p>
                        <h3 class="text-2xl font-bold text-gray-700">
                            {{ number_format($feesData['monthly_savings_fees']['total'] ?? 0, 0, ',', ' ') }} FCFA
                        </h3>
                    </div>
                    <div>
                        <p class="mb-1 text-sm text-gray-500">Comptes Actifs</p>
                        <h4 class="text-xl font-semibold text-gray-900">{{ $feesData['monthly_savings_fees']['active_accounts'] ?? 0 }}</h4>
                    </div>
                    <div>
                        <p class="mb-1 text-sm text-gray-500">Frais Moyen/Compte</p>
                        <h4 class="text-xl font-semibold text-gray-900">
                            @php
                                $accounts = $feesData['monthly_savings_fees']['active_accounts'] ?? 0;
                                $total = $feesData['monthly_savings_fees']['total'] ?? 0;
                                $avgFee = $accounts > 0 ? $total / $accounts : 0;
                            @endphp
                            {{ number_format($avgFee, 0, ',', ' ') }} FCFA
                        </h4>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Graphique Répartition -->
    <div class="grid grid-cols-1 gap-4 mb-6 lg:grid-cols-3">
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm lg:col-span-2">
            <div class="px-6 py-4 border-b border-gray-200">
                <h5 class="text-lg font-semibold text-gray-900">📊 Répartition des Frais</h5>
            </div>
            <div class="p-6">
                <canvas id="feesBreakdownChart" class="w-full" style="height: 300px;"></canvas>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h5 class="text-lg font-semibold text-gray-900">📈 Statistiques</h5>
            </div>
            <div class="p-6">
                @php
                    $allFees = [
                        'account_activation' => $feesData['account_activation']['total'] ?? 0,
                        'transaction_fees' => $feesData['transaction_fees']['total'] ?? 0,
                        'withdrawal_fees' => $feesData['withdrawal_fees']['total'] ?? 0,
                        'transfer_fees' => $feesData['transfer_fees']['total'] ?? 0,
                        'monthly_savings_fees' => $feesData['monthly_savings_fees']['total'] ?? 0
                    ];
                    $maxFee = max($allFees);
                    $maxFeeType = array_search($maxFee, $allFees);
                    
                    $feeLabels = [
                        'account_activation' => 'Ouverture de Comptes',
                        'transaction_fees' => 'Frais de Transaction',
                        'withdrawal_fees' => 'Frais de Retrait',
                        'transfer_fees' => 'Frais de Transfert',
                        'monthly_savings_fees' => 'Frais Mensuels'
                    ];
                @endphp

                <div class="mb-4">
                    <p class="mb-1 text-xs text-gray-500">Source la Plus Rentable</p>
                    <h6 class="mb-1 text-base font-semibold text-gray-900">
                        {{ $feeLabels[$maxFeeType] ?? 'N/A' }}
                    </h6>
                    <p class="font-semibold text-green-600">{{ number_format($maxFee, 0, ',', ' ') }} FCFA</p>
                </div>

                <hr class="my-4 border-gray-200">

                <div class="mb-4">
                    <p class="mb-1 text-xs text-gray-500">Contribution au Revenu Total</p>
                    <h6 class="mb-1 text-base font-semibold text-gray-900">
                        @php
                            $totalRevenue = ($totalFees ?? 0) + 1000000;
                            $contribution = $totalRevenue > 0 ? (($totalFees ?? 0) / $totalRevenue) * 100 : 0;
                        @endphp
                        {{ number_format($contribution, 1) }}%
                    </h6>
                    <small class="text-xs text-gray-500">des revenus globaux</small>
                </div>

                <hr class="my-4 border-gray-200">

                <div>
                    <p class="mb-1 text-xs text-gray-500">Frais Moyen/Transaction</p>
                    <h6 class="text-base font-semibold text-gray-900">
                        @php
                            $txCount = $feesData['transaction_fees']['count'] ?? 0;
                            $avgTxFee = $txCount > 0 ? ($totalFees ?? 0) / $txCount : 0;
                        @endphp
                        {{ number_format($avgTxFee, 0, ',', ' ') }} FCFA
                    </h6>
                </div>
            </div>
        </div>
    </div>

    <!-- Évolution des Frais dans le Temps -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h5 class="text-lg font-semibold text-gray-900">📅 Évolution des Frais</h5>
        </div>
        <div class="p-6">
            <canvas id="feesTimelineChart" class="w-full" style="height: 250px;"></canvas>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Graphique répartition des frais
const feesCtx = document.getElementById('feesBreakdownChart').getContext('2d');
new Chart(feesCtx, {
    type: 'bar',
    data: {
        labels: ['Ouverture Compte', 'Transactions', 'Retraits', 'Transferts', 'Frais Mensuels'],
        datasets: [{
            label: 'Montant (FCFA)',
            data: [
                {{ $feesData['account_activation']['total'] ?? 0 }},
                {{ $feesData['transaction_fees']['total'] ?? 0 }},
                {{ $feesData['withdrawal_fees']['total'] ?? 0 }},
                {{ $feesData['transfer_fees']['total'] ?? 0 }},
                {{ $feesData['monthly_savings_fees']['total'] ?? 0 }}
            ],
            backgroundColor: [
                '#10b981',
                '#06b6d4',
                '#eab308',
                '#3b82f6',
                '#6b7280'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return new Intl.NumberFormat('fr-FR').format(value) + ' FCFA';
                    }
                }
            }
        }
    }
});

// Timeline
const timelineCtx = document.getElementById('feesTimelineChart').getContext('2d');
new Chart(timelineCtx, {
    type: 'line',
    data: {
        labels: ['Semaine 1', 'Semaine 2', 'Semaine 3', 'Semaine 4'],
        datasets: [{
            label: 'Frais Collectés',
            data: [
                {{ ($totalFees ?? 0) * 0.2 }},
                {{ ($totalFees ?? 0) * 0.25 }},
                {{ ($totalFees ?? 0) * 0.28 }},
                {{ ($totalFees ?? 0) * 0.27 }}
            ],
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return new Intl.NumberFormat('fr-FR').format(value) + ' FCFA';
                    }
                }
            }
        }
    }
});
</script>
@endpush

@endsection