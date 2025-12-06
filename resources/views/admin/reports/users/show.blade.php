@extends('layouts.app_admin')

@section('title', 'Rapport - ' . $user->full_name)

@section('content')
<div class="space-y-6">
    <!-- Header avec retour et actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.reports.users.index') }}"
               class="w-10 h-10 bg-gray-100 hover:bg-gray-200 rounded-lg flex items-center justify-center transition">
                <i class="fas fa-arrow-left text-gray-600"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Rapport d'Activité</h2>
                <p class="text-gray-600 text-sm">{{ $user->full_name }} - {{ ucfirst(str_replace('_', ' ', $user->role)) }}</p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.reports.users.export', $user->id) }}?start_date={{ $startDate->format('Y-m-d') }}&end_date={{ $endDate->format('Y-m-d') }}"
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition">
                <i class="fas fa-download"></i>
                <span>Exporter</span>
            </a>
            <button onclick="window.print()"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition">
                <i class="fas fa-print"></i>
                <span>Imprimer</span>
            </button>
        </div>
    </div>

    <!-- Filtre de période -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <form method="GET" action="{{ route('admin.reports.users.show', $user->id) }}"
              class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-2">Date début</label>
                <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-2">Date fin</label>
                <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-filter mr-2"></i>Appliquer
            </button>
        </form>
        <p class="text-sm text-gray-500 mt-2">
            <i class="fas fa-calendar-alt mr-1"></i>
            Période: {{ $startDate->format('d/m/Y') }} au {{ $endDate->format('d/m/Y') }}
            ({{ $startDate->diffInDays($endDate) }} jours)
        </p>
    </div>

    <!-- Informations de l'utilisateur -->
    <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl p-6 text-white shadow-lg">
        <div class="flex flex-wrap items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-3xl font-bold">
                    {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                </div>
                <div>
                    <h3 class="text-2xl font-bold">{{ $user->full_name }}</h3>
                    <div class="flex flex-wrap items-center gap-3 mt-2 text-sm">
                        <span class="bg-white/20 px-3 py-1 rounded-full">
                            <i class="fas fa-envelope mr-1"></i>{{ $user->email }}
                        </span>
                        @if($user->phone)
                        <span class="bg-white/20 px-3 py-1 rounded-full">
                            <i class="fas fa-phone mr-1"></i>{{ $user->phone }}
                        </span>
                        @endif
                        <span class="bg-white/20 px-3 py-1 rounded-full">
                            <i class="fas fa-user-tag mr-1"></i>{{ ucfirst(str_replace('_', ' ', $user->role)) }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="text-right">
                @if($user->agency)
                <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4">
                    <p class="text-sm opacity-80">Agence</p>
                    <p class="text-xl font-bold">{{ $user->agency->name }}</p>
                    <p class="text-sm mt-1">{{ $user->agency->city }} - {{ $user->agency->code }}</p>
                </div>
                @else
                <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4">
                    <p class="text-sm opacity-80">Aucune agence</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Statistiques principales -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Clients -->
        <div class="bg-white rounded-xl p-6 shadow-sm border-l-4 border-blue-500">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
                <span class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded-full">Clients</span>
            </div>
            <p class="text-3xl font-bold text-gray-800">{{ number_format($clientStats['total']) }}</p>
            <p class="text-sm text-gray-600 mt-1">Total clients</p>
            <div class="mt-4 pt-4 border-t border-gray-100 space-y-1 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">KYC Approuvé:</span>
                    <span class="font-semibold text-green-600">{{ $clientStats['kyc_approved'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">En attente:</span>
                    <span class="font-semibold text-orange-600">{{ $clientStats['kyc_pending'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Créés (période):</span>
                    <span class="font-semibold text-blue-600">{{ $clientStats['created_period'] }}</span>
                </div>
            </div>
        </div>

        <!-- Comptes -->
        <div class="bg-white rounded-xl p-6 shadow-sm border-l-4 border-green-500">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-wallet text-green-600 text-xl"></i>
                </div>
                <span class="text-xs bg-green-50 text-green-700 px-2 py-1 rounded-full">Comptes</span>
            </div>
            <p class="text-3xl font-bold text-gray-800">{{ number_format($accountStats['total']) }}</p>
            <p class="text-sm text-gray-600 mt-1">Total comptes</p>
            <div class="mt-4 pt-4 border-t border-gray-100 space-y-1 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Actifs:</span>
                    <span class="font-semibold text-green-600">{{ $accountStats['active'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Épargne:</span>
                    <span class="font-semibold text-blue-600">{{ $accountStats['savings_count'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Tontine:</span>
                    <span class="font-semibold text-purple-600">{{ $accountStats['tontine_count'] }}</span>
                </div>
            </div>
        </div>

        <!-- Transactions -->
        <div class="bg-white rounded-xl p-6 shadow-sm border-l-4 border-purple-500">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-exchange-alt text-purple-600 text-xl"></i>
                </div>
                <span class="text-xs bg-purple-50 text-purple-700 px-2 py-1 rounded-full">Transactions</span>
            </div>
            <p class="text-3xl font-bold text-gray-800">{{ number_format($transactionStats['completed_count']) }}</p>
            <p class="text-sm text-gray-600 mt-1">Complétées</p>
            <div class="mt-4 pt-4 border-t border-gray-100 space-y-1 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Dépôts:</span>
                    <span class="font-semibold text-green-600">{{ $transactionStats['deposits_count'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Retraits:</span>
                    <span class="font-semibold text-red-600">{{ $transactionStats['withdrawals_count'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">En attente:</span>
                    <span class="font-semibold text-orange-600">{{ $transactionStats['pending_count'] }}</span>
                </div>
            </div>
        </div>

        <!-- Montants -->
        <div class="bg-white rounded-xl p-6 shadow-sm border-l-4 border-orange-500">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-coins text-orange-600 text-xl"></i>
                </div>
                <span class="text-xs bg-orange-50 text-orange-700 px-2 py-1 rounded-full">Montants</span>
            </div>
            <p class="text-2xl font-bold text-gray-800">{{ number_format($transactionStats['total_amount'], 0, ',', ' ') }}</p>
            <p class="text-sm text-gray-600 mt-1">FCFA traités</p>
            <div class="mt-4 pt-4 border-t border-gray-100 space-y-1 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Solde total:</span>
                    <span class="font-semibold text-blue-600">{{ number_format($accountStats['total_balance'], 0, ',', ' ') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Moy/transaction:</span>
                    <span class="font-semibold text-gray-800">{{ number_format($transactionStats['avg_transaction_amount'], 0, ',', ' ') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Frais collectés:</span>
                    <span class="font-semibold text-green-600">{{ number_format($transactionStats['total_fees'], 0, ',', ' ') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphique de performance quotidienne -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-chart-line mr-2 text-blue-600"></i>
            Performance Quotidienne
        </h3>
        <div class="h-80">
            <canvas id="dailyPerformanceChart"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Répartition par type de compte -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-chart-pie mr-2 text-purple-600"></i>
                Répartition par Type de Compte
            </h3>
            <div class="h-64">
                <canvas id="accountTypeChart"></canvas>
            </div>
            <div class="mt-4 space-y-2">
                @foreach($accountTypeDistribution as $type)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-4 h-4 rounded {{ $type->account_type === 'savings' ? 'bg-blue-500' : 'bg-purple-500' }}"></div>
                        <span class="font-medium text-gray-800">{{ ucfirst($type->account_type) }}</span>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-gray-800">{{ $type->count }} comptes</p>
                        <p class="text-sm text-gray-600">{{ number_format($type->total_balance, 0, ',', ' ') }} FCFA</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Répartition par méthode de paiement -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-credit-card mr-2 text-green-600"></i>
                Méthodes de Paiement
            </h3>
            <div class="h-64">
                <canvas id="paymentMethodChart"></canvas>
            </div>
            <div class="mt-4 space-y-2">
                @foreach($paymentMethodDistribution as $method)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-{{ $method->payment_method === 'cash' ? 'money-bill-wave' : ($method->payment_method === 'mobile_money' ? 'mobile-alt' : 'university') }} text-gray-600"></i>
                        <span class="font-medium text-gray-800">{{ ucfirst(str_replace('_', ' ', $method->payment_method)) }}</span>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-gray-800">{{ $method->count }} trans.</p>
                        <p class="text-sm text-gray-600">{{ number_format($method->total_amount, 0, ',', ' ') }} FCFA</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Top Clients -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-star mr-2 text-yellow-500"></i>
            Top 10 Clients (par activité)
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Rang</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Client</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Comptes</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Transactions</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Solde Total</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($topClients as $index => $client)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <span class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm
                                {{ $index === 0 ? 'bg-yellow-100 text-yellow-700' : '' }}
                                {{ $index === 1 ? 'bg-gray-200 text-gray-700' : '' }}
                                {{ $index === 2 ? 'bg-orange-100 text-orange-700' : '' }}
                                {{ $index > 2 ? 'bg-gray-100 text-gray-600' : '' }}">
                                {{ $index + 1 }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-medium text-gray-800">{{ $client->first_name }} {{ $client->last_name }}</p>
                                <p class="text-xs text-gray-500">{{ $client->client_number }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-sm font-medium">
                                {{ $client->active_accounts_count }}/{{ $client->accounts_count }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-semibold text-gray-800">{{ number_format($client->transactions_count) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-bold text-green-600">{{ number_format($client->total_balance, 0, ',', ' ') }} FCFA</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-medium rounded
                                {{ $client->kyc_status === 'approved' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $client->kyc_status === 'pending' ? 'bg-orange-100 text-orange-700' : '' }}
                                {{ $client->kyc_status === 'rejected' ? 'bg-red-100 text-red-700' : '' }}">
                                {{ ucfirst($client->kyc_status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Activités récentes -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-history mr-2 text-gray-600"></i>
            20 Dernières Activités
        </h3>
        <div class="space-y-3">
            @forelse($recentActivities as $activity)
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center
                        {{ $activity->transaction_type === 'deposit' ? 'bg-green-100' : '' }}
                        {{ $activity->transaction_type === 'withdrawal' ? 'bg-red-100' : '' }}
                        {{ $activity->transaction_type === 'transfer' ? 'bg-blue-100' : '' }}">
                        <i class="fas fa-{{ $activity->transaction_type === 'deposit' ? 'arrow-down' : ($activity->transaction_type === 'withdrawal' ? 'arrow-up' : 'exchange-alt') }}
                            {{ $activity->transaction_type === 'deposit' ? 'text-green-600' : '' }}
                            {{ $activity->transaction_type === 'withdrawal' ? 'text-red-600' : '' }}
                            {{ $activity->transaction_type === 'transfer' ? 'text-blue-600' : '' }}"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">{{ $activity->account->client->first_name }} {{ $activity->account->client->last_name }}</p>
                        <p class="text-sm text-gray-600">{{ $activity->description }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $activity->transaction_date->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-bold text-gray-800">{{ number_format($activity->amount, 0, ',', ' ') }} FCFA</p>
                    <span class="text-xs px-2 py-1 rounded
                        {{ $activity->status === 'completed' ? 'bg-green-100 text-green-700' : '' }}
                        {{ $activity->status === 'pending' ? 'bg-orange-100 text-orange-700' : '' }}
                        {{ $activity->status === 'failed' ? 'bg-red-100 text-red-700' : '' }}">
                        {{ ucfirst($activity->status) }}
                    </span>
                </div>
            </div>
            @empty
            <p class="text-center text-gray-400 py-8">Aucune activité récente</p>
            @endforelse
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Graphique de performance quotidienne
const dailyPerformanceCtx = document.getElementById('dailyPerformanceChart').getContext('2d');
new Chart(dailyPerformanceCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($dailyPerformance->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'))) !!},
        datasets: [{
            label: 'Transactions',
            data: {!! json_encode($dailyPerformance->pluck('transactions_count')) !!},
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true,
            yAxisID: 'y'
        }, {
            label: 'Montant (FCFA)',
            data: {!! json_encode($dailyPerformance->pluck('total_amount')) !!},
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            tension: 0.4,
            fill: true,
            yAxisID: 'y1'
        }]
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
                position: 'top',
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Nombre de transactions'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Montant (FCFA)'
                },
                grid: {
                    drawOnChartArea: false,
                }
            }
        }
    }
});

// Graphique par type de compte
const accountTypeCtx = document.getElementById('accountTypeChart').getContext('2d');
new Chart(accountTypeCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($accountTypeDistribution->pluck('account_type')->map(fn($t) => ucfirst($t))) !!},
        datasets: [{
            data: {!! json_encode($accountTypeDistribution->pluck('count')) !!},
            backgroundColor: ['rgb(59, 130, 246)', 'rgb(168, 85, 247)', 'rgb(34, 197, 94)'],
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
            }
        }
    }
});

// Graphique par méthode de paiement
const paymentMethodCtx = document.getElementById('paymentMethodChart').getContext('2d');
new Chart(paymentMethodCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($paymentMethodDistribution->pluck('payment_method')->map(fn($m) => ucfirst(str_replace('_', ' ', $m)))) !!},
        datasets: [{
            data: {!! json_encode($paymentMethodDistribution->pluck('count')) !!},
            backgroundColor: ['rgb(34, 197, 94)', 'rgb(59, 130, 246)', 'rgb(249, 115, 22)'],
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
            }
        }
    }
});
</script>

<style>
@media print {
    .no-print {
        display: none !important;
    }
}
</style>
@endsection
